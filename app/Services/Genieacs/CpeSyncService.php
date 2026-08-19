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
        $usernames = [];

        foreach ($devices as $device) {
            $username = $this->extractPppoeUsername($device);

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

            $username = $this->extractPppoeUsername($device);
            $customer = $username !== null ? ($customersByUsername[$username] ?? null) : null;

            Cpe::query()->updateOrCreate(
                ['genieacs_id' => (string) $deviceId],
                $this->buildDeviceData($device, $customer)
            );

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

        $username = $this->extractPppoeUsername($device);
        $customer = $username !== null
            ? Customer::query()->where('ppp_username', $username)->first()
            : null;

        return Cpe::query()->updateOrCreate(
            ['genieacs_id' => (string) $deviceId],
            $this->buildDeviceData($device, $customer)
        );
    }

    /**
     * Map a GenieACS device document into local Cpe columns.
     *
     * @param  array<int|string, mixed>  $device
     * @return array<string, mixed>
     */
    protected function buildDeviceData(array $device, ?Customer $customer): array
    {
        $username = $this->extractPppoeUsername($device);

        return [
            'customer_id' => $customer?->id,
            'ppp_username' => $username,
            'serial_number' => $this->param($device, 'InternetGatewayDevice.DeviceInfo.SerialNumber'),
            'manufacturer' => $this->param($device, 'InternetGatewayDevice.DeviceInfo.Manufacturer'),
            'model_name' => $this->param($device, 'InternetGatewayDevice.DeviceInfo.ModelName'),
            'model_number' => $this->param($device, 'InternetGatewayDevice.DeviceInfo.ModelNumber'),
            'hardware_version' => $this->param($device, 'InternetGatewayDevice.DeviceInfo.HardwareVersion'),
            'software_version' => $this->param($device, 'InternetGatewayDevice.DeviceInfo.SoftwareVersion'),
            'ip_address' => $this->firstMatchingParam($device, '/ExternalIPAddress$/'),
            'mac_address' => $this->param($device, 'InternetGatewayDevice.DeviceInfo.MACAddress'),
            'status' => $this->determineStatus($device),
            'last_inform_at' => $this->lastInformAt($device),
            'uptime' => $this->intParam($device, 'InternetGatewayDevice.DeviceInfo.UpTime'),
            'signal_parameters' => $this->extractSignalParameters($device),
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
     * Read a flat TR-069 parameter from the GenieACS device document.
     */
    protected function param(array $device, string $key): ?string
    {
        $value = $device[$key] ?? null;

        return $this->unwrap($value);
    }

    /**
     * Find the first parameter whose path matches the given regex.
     */
    protected function firstMatchingParam(array $device, string $pattern): ?string
    {
        foreach ($device as $key => $value) {
            if (! str_starts_with((string) $key, 'InternetGatewayDevice')) {
                continue;
            }

            if (preg_match($pattern, (string) $key) !== 1) {
                continue;
            }

            $unwrapped = $this->unwrap($value);

            if ($unwrapped !== null) {
                return $unwrapped;
            }
        }

        return null;
    }

    /**
     * Unwrap a GenieACS parameter value which may be a plain scalar
     * or an object with a "_value" key.
     */
    protected function unwrap(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $value['_value'] ?? null;
        }

        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        return (string) $value;
    }

    /**
     * Locate the PPPoE username configured on the device WAN connection.
     */
    protected function extractPppoeUsername(array $device): ?string
    {
        foreach ($device as $key => $value) {
            if (preg_match('/WANConnectionDevice\.\d+\.WANIPConnection\.\d+\.Username$/', (string) $key) !== 1) {
                continue;
            }

            $username = $this->unwrap($value);

            if ($username !== null) {
                return $username;
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

    protected function lastInformAt(array $device): ?Carbon
    {
        $lastInform = $device['_lastInform'] ?? null;

        if ($lastInform === null || (int) $lastInform === 0) {
            return null;
        }

        return Carbon::createFromTimestampMs((int) $lastInform);
    }

    protected function intParam(array $device, string $key): ?int
    {
        $value = $this->param($device, $key);

        return $value !== null ? (int) $value : null;
    }

    /**
     * Collect vendor-specific optical/signal parameters into a structured snapshot.
     *
     * @return array<string, array{label: string, value: string}>
     */
    protected function extractSignalParameters(array $device): array
    {
        $signals = [];

        foreach ($device as $key => $value) {
            $normalized = strtolower((string) $key);

            if (! str_contains($normalized, 'optical') && ! str_contains($normalized, 'signal_level') && ! str_contains($normalized, 'rxpower') && ! str_contains($normalized, 'txpower')) {
                continue;
            }

            $unwrapped = $this->unwrap($value);

            if ($unwrapped === null) {
                continue;
            }

            $signals[(string) $key] = [
                'label' => preg_replace('/^InternetGatewayDevice\./', '', (string) $key) ?? (string) $key,
                'value' => $unwrapped,
            ];
        }

        return $signals;
    }
}
