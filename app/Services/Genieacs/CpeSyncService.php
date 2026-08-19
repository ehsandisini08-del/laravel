<?php

namespace App\Services\Genieacs;

use App\Models\Cpe;
use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CpeSyncService
{
    protected const PAGE_LIMIT = 1000;

    public function __construct(
        protected GenieacsService $genieacs,
    ) {}

    /**
     * Pull all devices from GenieACS and mirror them into the local cpes table.
     *
     * @return array{success: bool, total: int, matched: int, error: ?string}
     */
    public function sync(): array
    {
        if (! $this->genieacs->isConfigured()) {
            return [
                'success' => false,
                'total' => 0,
                'matched' => 0,
                'error' => 'URL NBI GenieACS belum dikonfigurasi.',
            ];
        }

        $skip = 0;
        $synced = 0;
        $matched = 0;
        $seenIds = [];

        do {
            $result = $this->genieacs->getDevices(self::PAGE_LIMIT, $skip);

            if (! $result['success']) {
                return [
                    'success' => false,
                    'total' => $synced,
                    'matched' => $matched,
                    'error' => $result['error'],
                ];
            }

            $devices = $result['data'];

            if ($devices === []) {
                break;
            }

            $synced += count($devices);
            $matched += $this->processBatch($devices, $seenIds);
            $skip += self::PAGE_LIMIT;
        } while (count($devices) >= self::PAGE_LIMIT);

        $this->markMissingDevicesOffline($seenIds);

        return [
            'success' => true,
            'total' => $synced,
            'matched' => $matched,
            'error' => null,
        ];
    }

    /**
     * Fetch a single device from the ACS and persist it locally.
     *
     * @return array{success: bool, cpe: ?Cpe, error: ?string}
     */
    public function refreshDevice(string $deviceId): array
    {
        $result = $this->genieacs->getDevice($deviceId);

        if (! $result['success']) {
            return [
                'success' => false,
                'cpe' => null,
                'error' => $result['error'],
            ];
        }

        $cpe = $this->persistDevice($result['data']);

        return [
            'success' => true,
            'cpe' => $cpe,
            'error' => null,
        ];
    }

    /**
     * Persist a batch of GenieACS devices into the local cpes table.
     *
     * @param  array<int, array>  $devices
     * @param  array<int, string>  $seenIds
     */
    protected function processBatch(array $devices, array &$seenIds): int
    {
        $deviceParams = [];
        $usernames = [];

        foreach ($devices as $device) {
            $params = $this->collectParameters($device);
            $deviceParams[(string) ($device['_id'] ?? '')] = $params;

            $username = $this->extractPppoeUsername($params);

            if ($username !== null) {
                $usernames[] = $username;
            }
        }

        /** @var Collection<string, Customer> $customersByUsername */
        $customersByUsername = Customer::query()
            ->whereIn('ppp_username', array_values(array_unique($usernames)))
            ->get()
            ->keyBy('ppp_username');

        $matched = 0;

        foreach ($devices as $device) {
            $deviceId = $device['_id'] ?? null;

            if ($deviceId === null) {
                continue;
            }

            $seenIds[] = $deviceId;

            $params = $deviceParams[(string) $deviceId] ?? $this->collectParameters($device);
            $username = $this->extractPppoeUsername($params);
            $customer = $username !== null ? ($customersByUsername[$username] ?? null) : null;

            $cpe = Cpe::query()->updateOrCreate(
                ['genieacs_id' => (string) $deviceId],
                $this->buildDeviceData($device, $params, $customer)
            );

            $this->fillSsidIfBlank($cpe, $params);

            if ($customer !== null) {
                $matched++;
            }
        }

        return $matched;
    }

    /**
     * Persist a single GenieACS device document locally.
     *
     * @param  array<int|string, mixed>  $device
     */
    protected function persistDevice(array $device): ?Cpe
    {
        $deviceId = $device['_id'] ?? null;

        if ($deviceId === null) {
            return null;
        }

        $params = $this->collectParameters($device);
        $username = $this->extractPppoeUsername($params);
        $customer = $username !== null
            ? Customer::query()->where('ppp_username', $username)->first()
            : null;

        return Cpe::query()->updateOrCreate(
            ['genieacs_id' => (string) $deviceId],
            $this->buildDeviceData($device, $params, $customer)
        );
    }

    /**
     * Populate the local SSID from the device when it is still blank and keep
     * the WLAN configuration path in sync with the device tree.
     * Manually edited SSIDs are never overwritten by the sync.
     *
     * @param  array<string, string>  $params
     */
    protected function fillSsidIfBlank(?Cpe $cpe, array $params): void
    {
        if ($cpe === null) {
            return;
        }

        $data = [];

        if ($cpe->ssid === null || $cpe->ssid === '') {
            $ssid = $this->extractSsid($params);

            if ($ssid !== null) {
                $data['ssid'] = $ssid;
            }
        }

        $wlanPath = $this->extractWlanPath($params);

        if ($wlanPath !== null) {
            $data['wifi_config_path'] = $wlanPath;
        }

        if ($data !== []) {
            $cpe->update($data);
        }
    }

    /**
     * Push SSID/password changes to the physical device via a
     * setParameterValues task on the ACS.
     *
     * @return array{success: bool, error: ?string}
     */
    public function pushWifiConfig(Cpe $cpe, ?string $ssid, ?string $wifiPassword): array
    {
        if ($cpe->wifi_config_path === null) {
            return ['success' => false, 'error' => 'Parameter WiFi tidak terdeteksi di perangkat.'];
        }

        $parameterValues = [];

        if ($ssid !== null && $ssid !== '') {
            $parameterValues[] = [$cpe->wifi_config_path.'SSID', $ssid];
        }

        if ($wifiPassword !== null && $wifiPassword !== '') {
            $parameterValues[] = [$cpe->wifi_config_path.'PreSharedKey.1.PreSharedKey', $wifiPassword];
        }

        if ($parameterValues === []) {
            return ['success' => true, 'error' => null];
        }

        $result = $this->genieacs->enqueueTask($cpe->genieacs_id, [
            'name' => 'setParameterValues',
            'parameterValues' => $parameterValues,
        ]);

        return [
            'success' => $result['success'],
            'error' => $result['error'],
        ];
    }

    /**
     * Extract the WiFi SSID from WLAN/WiFi configuration parameters.
     *
     * @param  array<string, string>  $params
     */
    protected function extractSsid(array $params): ?string
    {
        foreach ($params as $path => $value) {
            if (str_starts_with($path, '_')) {
                continue;
            }

            $normalized = strtolower($path);

            if ((str_contains($normalized, 'wlan') || str_contains($normalized, 'wifi'))
                && str_ends_with($normalized, '.ssid')) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Extract the WLAN configuration object path (directory of the SSID
     * parameter, e.g. InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.)
     * used for pushing setParameterValues tasks to the device.
     *
     * @param  array<string, string>  $params
     */
    protected function extractWlanPath(array $params): ?string
    {
        foreach ($params as $path => $value) {
            if (str_starts_with($path, '_')) {
                continue;
            }

            $normalized = strtolower($path);

            if ((str_contains($normalized, 'wlan') || str_contains($normalized, 'wifi'))
                && str_ends_with($normalized, '.ssid')) {
                $offset = strrpos($path, '.');

                return $offset !== false ? substr($path, 0, $offset + 1) : null;
            }
        }

        return null;
    }

    /**
     * Map a GenieACS device document into local Cpe columns.
     *
     * @param  array<int|string, mixed>  $device
     * @param  array<string, string>  $params
     * @return array<string, mixed>
     */
    protected function buildDeviceData(array $device, array $params, ?Customer $customer): array
    {
        $username = $this->extractPppoeUsername($params);
        $uptime = $this->deviceInfoValue($params, 'UpTime');

        return [
            'customer_id' => $customer?->id,
            'ppp_username' => $username,
            'serial_number' => $this->deviceInfoValue($params, 'SerialNumber') ?? $this->param($params, '_deviceId._SerialNumber'),
            'manufacturer' => $this->deviceInfoValue($params, 'Manufacturer') ?? $this->param($params, '_deviceId._Manufacturer'),
            'model_name' => $this->deviceInfoValue($params, 'ModelName') ?? $this->param($params, '_deviceId._ProductClass'),
            'model_number' => $this->deviceInfoValue($params, 'ModelNumber'),
            'hardware_version' => $this->deviceInfoValue($params, 'HardwareVersion'),
            'software_version' => $this->deviceInfoValue($params, 'SoftwareVersion'),
            'ip_address' => $this->firstMatchingParam($params, '/ExternalIPAddress$/'),
            'mac_address' => $this->deviceInfoValue($params, 'MACAddress') ?? $this->firstMatchingParam($params, '/\.MACAddress$/'),
            'status' => $this->determineStatus($device),
            'last_inform_at' => $this->lastInformAt($device),
            'uptime' => $uptime !== null ? (int) $uptime : null,
            'wifi_clients' => $this->extractWifiClientCount($params),
            'wifi_devices' => $this->extractWifiDevices($params),
            'signal_parameters' => $this->extractSignalParameters($params),
            'tags' => $device['_tags'] ?? [],
            'synced_at' => now(),
        ];
    }

    /**
     * Count the WiFi clients currently associated with the device.
     * The AssociatedDeviceNumberOfEntries parameter is preferred when
     * reported; otherwise unique AssociatedDevice instances are counted.
     *
     * @param  array<string, string>  $params
     */
    protected function extractWifiClientCount(array $params): ?int
    {
        foreach ($params as $path => $value) {
            if (str_contains(strtolower($path), 'associateddevicenumberofentries')) {
                return (int) $value;
            }
        }

        $instances = [];

        foreach ($params as $path => $value) {
            if (preg_match('/(.+)AssociatedDevice\.(\d+)\./', $path, $matches) === 1) {
                $instances[$matches[1].'.'.$matches[2]] = true;
            }
        }

        return $instances !== [] ? count($instances) : null;
    }

    /**
     * Extract the list of WiFi clients currently associated with the device.
     * Each entry holds the MAC address, IP address, hostname (when reported)
     * and a vendor guess derived from the MAC OUI.
     *
     * @param  array<string, string>  $params
     * @return array<int, array{mac_address: ?string, ip_address: ?string, hostname: ?string, vendor: ?string}>|null
     */
    protected function extractWifiDevices(array $params): ?array
    {
        $devices = [];

        foreach ($params as $path => $value) {
            if (preg_match('/(.+)AssociatedDevice\.(\d+)\.([^.]+)$/', $path, $matches) !== 1) {
                continue;
            }

            $instance = $matches[1].'.'.$matches[2];
            $leaf = strtolower($matches[3]);

            $devices[$instance] ??= ['mac_address' => null, 'ip_address' => null, 'hostname' => null];

            if ($leaf === 'macaddress') {
                $devices[$instance]['mac_address'] = $value;
            } elseif ($leaf === 'ipaddress') {
                $devices[$instance]['ip_address'] = $value;
            } elseif ($leaf === 'hostname' || $leaf === 'host') {
                $devices[$instance]['hostname'] = $value;
            }
        }

        if ($devices === []) {
            return null;
        }

        return array_values(array_map(
            fn (array $device) => [
                'mac_address' => $device['mac_address'],
                'ip_address' => $device['ip_address'],
                'hostname' => $device['hostname'],
                'vendor' => $this->vendorFromMac($device['mac_address']),
            ],
            $devices
        ));
    }

    /**
     * Guess the device vendor from the first six hex digits (OUI) of a MAC
     * address. Returns null when the OUI is unknown.
     */
    protected function vendorFromMac(?string $mac): ?string
    {
        if ($mac === null) {
            return null;
        }

        $oui = strtoupper(substr(preg_replace('/[^0-9A-Fa-f]/', '', $mac) ?? '', 0, 6));

        $vendors = [
            '3CD16E' => 'Xiaomi',
            '50D2B5' => 'Xiaomi',
            '64A53D' => 'Xiaomi',
            '78E3B5' => 'Xiaomi',
            '8CA1B1' => 'Xiaomi',
            '984B4A' => 'Xiaomi',
            'A0B4A5' => 'Xiaomi',
            'B4E3F9' => 'Xiaomi',
            'F02F74' => 'Xiaomi',
            '001AC4' => 'Samsung',
            '0021D8' => 'Samsung',
            '0050B2' => 'Samsung',
            '008CF7' => 'Samsung',
            '0433A2' => 'Samsung',
            '0CF0B4' => 'Samsung',
            '14B9DC' => 'Samsung',
            '1C38C7' => 'Samsung',
            '28C7CE' => 'Samsung',
            '2C8BF5' => 'Samsung',
            '38E8DF' => 'Samsung',
            '3C1C25' => 'Samsung',
            '44EAE8' => 'Samsung',
            '48A2C6' => 'Samsung',
            '5CDAD4' => 'Samsung',
            '78A3E4' => 'Samsung',
            '88D9F9' => 'Samsung',
            '8CCDE8' => 'Samsung',
            '94F65A' => 'Samsung',
            'A8182E' => 'Samsung',
            'AC5F3E' => 'Samsung',
            'B076A7' => 'Samsung',
            'C42C03' => 'Samsung',
            'D048B0' => 'Samsung',
            'D8C79A' => 'Samsung',
            'EC0BD0' => 'Samsung',
            'F4CE46' => 'Samsung',
            'F8A45F' => 'Samsung',
            '0019E3' => 'Apple',
            '0026BB' => 'Apple',
            '0026C7' => 'Apple',
            '003EE1' => 'Apple',
            '0050C9' => 'Apple',
            '00F43C' => 'Apple',
            '04D3B0' => 'Apple',
            '04E536' => 'Apple',
            '087B1A' => 'Apple',
            '08E84F' => 'Apple',
            '0C2D30' => 'Apple',
            '0C4C42' => 'Apple',
            '0C74C2' => 'Apple',
            '109ACD' => 'Apple',
            '10A5D0' => 'Apple',
            '10DDD8' => 'Apple',
            '148FC5' => 'Apple',
            '18AF61' => 'Apple',
            '1C9E46' => 'Apple',
            '1CC0DE' => 'Apple',
            '20AB37' => 'Apple',
            '246AAB' => 'Apple',
            '24A05C' => 'Apple',
            '24E3B5' => 'Apple',
            '28E02C' => 'Apple',
            '2C8F8A' => 'Apple',
            '2CBE08' => 'Apple',
            '2CF0A2' => 'Apple',
            '30D9E9' => 'Apple',
            '3400A3' => 'Apple',
            '34C0F9' => 'Apple',
            '380F4A' => 'Apple',
            '383F10' => 'Apple',
            '3C0754' => 'Apple',
            '3CD0F8' => 'Apple',
            '40CB17' => 'Apple',
            '445E4F' => 'Apple',
            '44AB5E' => 'Apple',
            '48A5E7' => 'Apple',
            '48C093' => 'Apple',
            '4CEB42' => 'Apple',
            '505CBF' => 'Apple',
            '506B4B' => 'Apple',
            '506F9A' => 'Apple',
            '5483D4' => 'Apple',
            '54E43A' => 'Apple',
            '58B0D4' => 'Apple',
            '58E0BA' => 'Apple',
            '5C2DD9' => 'Apple',
            '5C95AE' => 'Apple',
            '5CF938' => 'Apple',
            '60C7AE' => 'Apple',
            '644C36' => 'Apple',
            '64A222' => 'Apple',
            '64B9E8' => 'Apple',
            '68A828' => 'Apple',
            '68850A' => 'Apple',
            '6C709F' => 'Apple',
            '6C96CF' => 'Apple',
            '705E46' => 'Apple',
            '70B3D5' => 'Apple',
            '74078A' => 'Apple',
            '74E1B6' => 'Apple',
            '78C6BB' => 'Apple',
            '78414E' => 'Apple',
            '7C1125' => 'Apple',
            '7CD9FE' => 'Apple',
            '802CA8' => 'Apple',
            '80BE05' => 'Apple',
            '840BD9' => 'Apple',
            '848506' => 'Apple',
            '84B154' => 'Apple',
            '88C8D9' => 'Apple',
            '88D392' => 'Apple',
            '8C85A1' => 'Apple',
            '8C8CD6' => 'Apple',
            '90B21F' => 'Apple',
            '9094E4' => 'Apple',
            '94F68B' => 'Apple',
            '982ECB' => 'Apple',
            '98E7F5' => 'Apple',
            '9CE33F' => 'Apple',
            'A02599' => 'Apple',
            'A06CAC' => 'Apple',
            'A4D578' => 'Apple',
            'A8B11D' => 'Apple',
            'AC83F0' => 'Apple',
            'ACD3A9' => 'Apple',
            'B09C05' => 'Apple',
            'B0E235' => 'Apple',
            'B4A95A' => 'Apple',
            'B8B7D7' => 'Apple',
            'B8D1F1' => 'Apple',
            'BC16F5' => 'Apple',
            'BC52B7' => 'Apple',
            'C09F05' => 'Apple',
            'C0D662' => 'Apple',
            'C45006' => 'Apple',
            'C87888' => 'Apple',
            'C898BD' => 'Apple',
            'C8BCC8' => 'Apple',
            'C8E7D8' => 'Apple',
            'CC44B0' => 'Apple',
            'D0A60C' => 'Apple',
            'D0965A' => 'Apple',
            'D0E140' => 'Apple',
            'D41732' => 'Apple',
            'D8A25E' => 'Apple',
            'DC2B2A' => 'Apple',
            'DC2C6E' => 'Apple',
            'DC4523' => 'Apple',
            'E05B6B' => 'Apple',
            'E0B0F8' => 'Apple',
            'E455EA' => 'Apple',
            'E8A3AC' => 'Apple',
            'EC5A86' => 'Apple',
            'EC85FB' => 'Apple',
            'ECADB8' => 'Apple',
            'F05851' => 'Apple',
            'F0D5BF' => 'Apple',
            'F45C89' => 'Apple',
            'F4882B' => 'Apple',
            'F4A52A' => 'Apple',
            'F4D1B6' => 'Apple',
            'F80F41' => 'Apple',
            'F8C48A' => 'Apple',
            'FCF1CD' => 'Apple',
            '00247C' => 'TP-Link',
            '0050F1' => 'TP-Link',
            '00AF1C' => 'TP-Link',
            '109F41' => 'TP-Link',
            '14E6E4' => 'TP-Link',
            '18A6F7' => 'TP-Link',
            '1C3E84' => 'TP-Link',
            '20DCE6' => 'TP-Link',
            '28D07B' => 'TP-Link',
            '2C3996' => 'TP-Link',
            '2C54CF' => 'TP-Link',
            '30249A' => 'TP-Link',
            '30B49E' => 'TP-Link',
            '34B571' => 'TP-Link',
            '38AABB' => 'TP-Link',
            '40D29C' => 'TP-Link',
            '44C96F' => 'TP-Link',
            '48176F' => 'TP-Link',
            '4C09D4' => 'TP-Link',
            '508ACE' => 'TP-Link',
            '54C62F' => 'TP-Link',
            '587A62' => 'TP-Link',
            '5C4CA9' => 'TP-Link',
            '64F987' => 'TP-Link',
            '68B6FC' => 'TP-Link',
            '6CA906' => 'TP-Link',
            '70818C' => 'TP-Link',
            '748C1C' => 'TP-Link',
            '78DBA8' => 'TP-Link',
            '7CA023' => 'TP-Link',
            '84292A' => 'TP-Link',
            '88699C' => 'TP-Link',
            '8C1905' => 'TP-Link',
            '902B34' => 'TP-Link',
            '94DAFB' => 'TP-Link',
            '986ABB' => 'TP-Link',
            '9C3BA1' => 'TP-Link',
            'A0BD1D' => 'TP-Link',
            'A43D9A' => 'TP-Link',
            'A8F941' => 'TP-Link',
            'AC175B' => 'TP-Link',
            'AC84C6' => 'TP-Link',
            'B080A4' => 'TP-Link',
            'B0C554' => 'TP-Link',
            'B4B676' => 'TP-Link',
            'B8A96A' => 'TP-Link',
            'BCF685' => 'TP-Link',
            'C0FFD4' => 'TP-Link',
            'C44EAC' => 'TP-Link',
            'C89C1D' => 'TP-Link',
            'CC3205' => 'TP-Link',
            'D06EA5' => 'TP-Link',
            'D4A02A' => 'TP-Link',
            'D83B43' => 'TP-Link',
            'DC1EA3' => 'TP-Link',
            'DCFA5E' => 'TP-Link',
            'E0B4E8' => 'TP-Link',
            'E8E3B1' => 'TP-Link',
            'EC3F05' => 'TP-Link',
            'F0CDA8' => 'TP-Link',
            'F45386' => 'TP-Link',
            'F8D29C' => 'TP-Link',
            'FC75E4' => 'TP-Link',
            '28E347' => 'Huawei',
            '48B8B7' => 'Huawei',
            '8CBEBE' => 'Huawei',
            'D4EEE8' => 'Huawei',
            '0013A2' => 'OPPO',
            '0846E1' => 'OPPO',
            '188E4F' => 'OPPO',
            '1C6B87' => 'OPPO',
            '20736F' => 'OPPO',
            '241F2C' => 'OPPO',
            '28CE52' => 'OPPO',
            '2C53FA' => 'OPPO',
            '30B4F8' => 'OPPO',
            '3CC74C' => 'OPPO',
            '40786D' => 'OPPO',
            '44BFE3' => 'OPPO',
            '48AB18' => 'OPPO',
            '4C05DB' => 'OPPO',
            '50871E' => 'OPPO',
            '58C802' => 'OPPO',
            '5C2B16' => 'OPPO',
            '644AA9' => 'OPPO',
            '68E3B4' => 'OPPO',
            '6C422B' => 'OPPO',
            '70B50C' => 'OPPO',
            '74A245' => 'OPPO',
            '78FA3C' => 'OPPO',
            '7C0BD4' => 'OPPO',
            '80396C' => 'OPPO',
            '84DBAC' => 'OPPO',
            '88A25E' => 'OPPO',
            '8C79F5' => 'OPPO',
            '907FA0' => 'OPPO',
            '94E948' => 'OPPO',
            '9819C4' => 'OPPO',
            '9C79EC' => 'OPPO',
            'A03E2C' => 'OPPO',
            'A4E7B6' => 'OPPO',
            'A863DF' => 'OPPO',
            'AC9E17' => 'OPPO',
            'B06CBF' => 'OPPO',
            'B4783E' => 'OPPO',
            'BC6C21' => 'OPPO',
            'C0B65A' => 'OPPO',
            'C4E790' => 'OPPO',
            'C88650' => 'OPPO',
            'CCFF2A' => 'OPPO',
            'D03110' => 'OPPO',
            'D47F35' => 'OPPO',
            'D8E1DE' => 'OPPO',
            'E40B61' => 'OPPO',
            'E89A8F' => 'OPPO',
            'EC9520' => 'OPPO',
            'F42F8C' => 'OPPO',
            'F87394' => 'OPPO',
            'FCF96B' => 'OPPO',
            '001E58' => 'Vivo',
            '049A76' => 'Vivo',
            '083E0C' => 'Vivo',
            '0C7664' => 'Vivo',
            '1071F9' => 'Vivo',
            '180860' => 'Vivo',
            '1C3C17' => 'Vivo',
            '20A7C1' => 'Vivo',
            '242A58' => 'Vivo',
            '2CDFC0' => 'Vivo',
            '307164' => 'Vivo',
            '38BDA5' => 'Vivo',
            '3CF011' => 'Vivo',
            '448F5A' => 'Vivo',
            '4818A1' => 'Vivo',
            '4C1D52' => 'Vivo',
            '50B03C' => 'Vivo',
            '54BD79' => 'Vivo',
            '5C2221' => 'Vivo',
            '60CBFB' => 'Vivo',
            '64A3CB' => 'Vivo',
            '684BCF' => 'Vivo',
            '6C3C8B' => 'Vivo',
            '70F167' => 'Vivo',
            '74A0A6' => 'Vivo',
            '78D752' => 'Vivo',
            '7C38F8' => 'Vivo',
            '80E31A' => 'Vivo',
            '849FB5' => 'Vivo',
            '8C3D4A' => 'Vivo',
            '903DFB' => 'Vivo',
            '9471AC' => 'Vivo',
            '980284' => 'Vivo',
            '9CA617' => 'Vivo',
            'A0477C' => 'Vivo',
            'A86BC1' => 'Vivo',
            'ACDB48' => 'Vivo',
            'B09829' => 'Vivo',
            'B4EEB4' => 'Vivo',
            'B8D9CE' => 'Vivo',
            'C0288D' => 'Vivo',
            'C45EEF' => 'Vivo',
            'CC43E3' => 'Vivo',
            'D01018' => 'Vivo',
            'D4910D' => 'Vivo',
            'DC4A3E' => 'Vivo',
            'E08D8C' => 'Vivo',
            'E4A844' => 'Vivo',
            'E8D2A2' => 'Vivo',
            'EC3F3B' => 'Vivo',
            'F023B9' => 'Vivo',
            'F4692B' => 'Vivo',
            'FC8F90' => 'Vivo',
            'FCD4F2' => 'Vivo',
            '1C9E00' => 'Asus',
            '2891E0' => 'Asus',
            '2C560A' => 'Asus',
            '3054A9' => 'Asus',
            '3C5282' => 'Asus',
            '446D57' => 'Asus',
            '48C9B5' => 'Asus',
            '4C0289' => 'Asus',
            '50C7BF' => 'Asus',
            '548998' => 'Asus',
            '5882A5' => 'Asus',
            '60672C' => 'Asus',
            '6CAC60' => 'Asus',
            '74831D' => 'Asus',
            '7CE9D3' => 'Asus',
            '8C604F' => 'Asus',
            '904CE5' => 'Asus',
            '94D793' => 'Asus',
            '9C5C8D' => 'Asus',
            'A0E5E9' => 'Asus',
            'AC54EC' => 'Asus',
            'B068B6' => 'Asus',
            'B8CEF6' => 'Asus',
            'BC3FA4' => 'Asus',
            'C01242' => 'Asus',
            'C8CBB8' => 'Asus',
            'D0F411' => 'Asus',
            'D43D7E' => 'Asus',
            'DC530C' => 'Asus',
            'E03F49' => 'Asus',
            'E87A3D' => 'Asus',
            'EC8CA2' => 'Asus',
            'F03D29' => 'Asus',
            'F47D1B' => 'Asus',
            'F83D4E' => 'Asus',
            'FC9AFA' => 'Asus',
            '001B21' => 'Dell',
            '001E4F' => 'Dell',
            '001F29' => 'Dell',
            '0021CC' => 'Dell',
            '0023AE' => 'Dell',
            '00B049' => 'Dell',
            '0026B9' => 'HP',
            '00C0B7' => 'HP',
            '00672B' => 'HP',
            '00231D' => 'Lenovo',
            '003C96' => 'Lenovo',
            '0425C5' => 'Lenovo',
            '0C2C54' => 'Lenovo',
            '1824E0' => 'Lenovo',
            '28D244' => 'Lenovo',
            '2CBE97' => 'Lenovo',
            '3440B5' => 'Lenovo',
            '3C462F' => 'Lenovo',
            '4C4B68' => 'Lenovo',
            '54B620' => 'Lenovo',
            '6045BD' => 'Lenovo',
            '6CC217' => 'Lenovo',
            '782BCB' => 'Lenovo',
            '8843E1' => 'Lenovo',
            '9067F3' => 'Lenovo',
            '98BE94' => 'Lenovo',
            'A87989' => 'Lenovo',
            'C0D944' => 'Lenovo',
            'C825E2' => 'Lenovo',
            'D89E3F' => 'Lenovo',
            'E46F13' => 'Lenovo',
            'ECF4BB' => 'Lenovo',
            'F02FA7' => 'Lenovo',
            'F4B7E2' => 'Lenovo',
            '000FD4' => 'Intel',
            '0013E8' => 'Intel',
            '001B21' => 'Dell',
            '001DE1' => 'Intel',
            '0022FB' => 'Intel',
            '0024D7' => 'Intel',
            '080028' => 'Realtek',
            '001BD7' => 'Realtek',
            '00249A' => 'Realtek',
            '00E04C' => 'Realtek',
            '52E5C9' => 'Realtek',
        ];

        return $vendors[$oui] ?? null;
    }

    /**
     * Mark devices that no longer exist on the ACS as offline.
     *
     * @param  array<int, string>  $seenIds
     */
    protected function markMissingDevicesOffline(array $seenIds): void
    {
        foreach (array_chunk($seenIds, 500) as $chunk) {
            Cpe::query()
                ->whereNotIn('genieacs_id', $chunk)
                ->update(['status' => Cpe::STATUS_OFFLINE]);
        }

        if ($seenIds === []) {
            Cpe::query()->update(['status' => Cpe::STATUS_OFFLINE]);
        }
    }

    /**
     * Flatten the nested GenieACS device document into a map of
     * dotted parameter paths to their values.
     *
     * @param  array<int|string, mixed>  $device
     * @return array<string, string>
     */
    protected function collectParameters(array $device): array
    {
        $params = [];

        $this->walkParameterTree($device, '', $params);

        return $params;
    }

    /**
     * Recursively walk the TR-069 parameter tree, unwrapping "_value" objects.
     *
     * @param  array<int|string, mixed>  $node
     * @param  array<string, string>  $params
     */
    protected function walkParameterTree(array $node, string $path, array &$params): void
    {
        foreach ($node as $key => $value) {
            if (! is_string($key) && ! is_int($key)) {
                continue;
            }

            $current = $path === '' ? (string) $key : "{$path}.{$key}";

            if (is_array($value)) {
                if (array_key_exists('_value', $value)) {
                    $unwrapped = $value['_value'];

                    if ($unwrapped !== null && $unwrapped !== '' && $unwrapped !== false) {
                        $params[$current] = is_scalar($unwrapped) ? (string) $unwrapped : (string) json_encode($unwrapped);
                    }

                    continue;
                }

                $this->walkParameterTree($value, $current, $params);

                continue;
            }

            if ($value !== null && $value !== '') {
                $params[$current] = (string) $value;
            }
        }
    }

    /**
     * Read a parameter from the flattened parameter map.
     *
     * @param  array<string, string>  $params
     */
    protected function param(array $params, string $key): ?string
    {
        return $params[$key] ?? null;
    }

    /**
     * Read a DeviceInfo parameter, supporting both TR-098 and TR-181 roots.
     *
     * @param  array<string, string>  $params
     */
    protected function deviceInfoValue(array $params, string $suffix): ?string
    {
        foreach (["InternetGatewayDevice.DeviceInfo.{$suffix}", "Device.DeviceInfo.{$suffix}"] as $path) {
            if (isset($params[$path])) {
                return $params[$path];
            }
        }

        return null;
    }

    /**
     * Find the first parameter whose path matches the given regex.
     *
     * @param  array<string, string>  $params
     */
    protected function firstMatchingParam(array $params, string $pattern): ?string
    {
        foreach ($params as $path => $value) {
            if (preg_match($pattern, $path) === 1) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Locate the PPPoE username configured on the device WAN connection.
     * Matches both WANIPConnection and WANPPPConnection instances (with or
     * without vendor namespaces) since PPPoE may be reported under either.
     *
     * @param  array<string, string>  $params
     */
    protected function extractPppoeUsername(array $params): ?string
    {
        foreach ($params as $path => $value) {
            if (preg_match('/WANIPConnection\.\d+\.Username$/', $path) === 1
                || preg_match('/WANPPPConnection\.\d+\.Username$/', $path) === 1) {
                return $value;
            }
        }

        return null;
    }

    protected function determineStatus(array $device): string
    {
        $lastInform = $this->lastInformAt($device);

        if ($lastInform === null) {
            return Cpe::STATUS_UNKNOWN;
        }

        $thresholdMinutes = (int) Setting::get('genieacs_online_threshold_minutes', 15);

        return $lastInform->gte(now()->subMinutes($thresholdMinutes))
            ? Cpe::STATUS_ONLINE
            : Cpe::STATUS_OFFLINE;
    }

    /**
     * Parse the _lastInform timestamp, supporting epoch milliseconds,
     * epoch seconds, and ISO-8601 strings across GenieACS versions.
     */
    protected function lastInformAt(array $device): ?Carbon
    {
        $lastInform = $device['_lastInform'] ?? null;

        if ($lastInform === null || $lastInform === '' || (int) $lastInform === 0) {
            return null;
        }

        if (is_numeric($lastInform)) {
            $timestamp = (int) $lastInform;

            return $timestamp < 10_000_000_000
                ? Carbon::createFromTimestamp($timestamp)
                : Carbon::createFromTimestampMs($timestamp);
        }

        try {
            return Carbon::parse((string) $lastInform);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Collect optical/signal parameters into a structured snapshot.
     * VirtualParameters paths (e.g. RXPower) are included when their name
     * relates to signal/power monitoring.
     *
     * @param  array<string, string>  $params
     * @return array<string, array{label: string, value: string}>
     */
    protected function extractSignalParameters(array $params): array
    {
        $signals = [];

        foreach ($params as $path => $value) {
            if (str_starts_with($path, '_')) {
                continue;
            }

            $normalized = strtolower($path);
            $lastSegment = strtolower((string) strrchr($path, '.'));

            $isSignal = str_contains($normalized, 'optical')
                || str_contains($normalized, 'signal')
                || str_contains($normalized, 'rxpower')
                || str_contains($normalized, 'txpower')
                || str_contains($normalized, 'rx_power')
                || str_contains($normalized, 'tx_power')
                || str_contains($normalized, 'rssi')
                || str_contains($normalized, 'ont_rx')
                || str_contains($normalized, 'ont_tx');

            if (! $isSignal && ! str_contains($normalized, 'virtualparameters')) {
                continue;
            }

            $isVirtualSignal = str_contains($lastSegment, 'rx')
                || str_contains($lastSegment, 'tx')
                || str_contains($lastSegment, 'power')
                || str_contains($lastSegment, 'optical')
                || str_contains($lastSegment, 'signal');

            if (! $isSignal && ! $isVirtualSignal) {
                continue;
            }

            $signals[$path] = [
                'label' => preg_replace('/^(InternetGatewayDevice|Device)\./', '', $path) ?? $path,
                'value' => $value,
            ];
        }

        return $signals;
    }
}
