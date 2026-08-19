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
            'signal_parameters' => $this->extractSignalParameters($params),
            'tags' => $device['_tags'] ?? [],
            'synced_at' => now(),
        ];
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
