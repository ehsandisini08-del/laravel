<?php

namespace App\Services\Genieacs;

use App\Models\Setting;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenieacsService
{
    protected string $baseUrl;

    protected string $username;

    protected string $password;

    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) Setting::get('genieacs_nbi_url', ''), '/');
        $this->username = (string) Setting::get('genieacs_username', '');
        $this->password = (string) Setting::get('genieacs_password', '');
        $this->timeout = (int) Setting::get('genieacs_timeout', 15);
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '';
    }

    /**
     * Fetch all devices paginated via the NBI API.
     *
     * @return array{success: bool, data: array, error: ?string}
     */
    public function getDevices(int $limit = 1000, int $skip = 0): array
    {
        return $this->request('get', '/devices', [], [
            'limit' => $limit,
            'skip' => $skip,
        ]);
    }

    /**
     * Fetch a single device by its GenieACS id.
     * The NBI API does not expose GET /devices/{id}; devices are fetched
     * through the collection endpoint with an _id query filter.
     *
     * @return array{success: bool, data: array, error: ?string}
     */
    public function getDevice(string $deviceId): array
    {
        $result = $this->request('get', '/devices', [], [
            'query' => json_encode(['_id' => $deviceId]),
        ]);

        if (! $result['success']) {
            return $result;
        }

        $device = $result['data'][0] ?? null;

        if ($device === null) {
            return [
                'success' => false,
                'data' => [],
                'error' => 'Device tidak ditemukan di GenieACS.',
            ];
        }

        return [
            'success' => true,
            'data' => $device,
            'error' => null,
        ];
    }

    /**
     * Enqueue a task on a device (e.g. setParameterValues) and optionally
     * trigger a connection request so it applies immediately.
     *
     * With connection_request enabled the NBI waits for the CPE session to
     * finish, so a generous request timeout (60s) and matching NBI wait
     * (55s) are used instead of the general client timeout.
     *
     * @param  array<string, mixed>  $task
     * @return array{success: bool, data: array, error: ?string}
     */
    public function enqueueTask(string $deviceId, array $task, bool $connectionRequest = true, int $timeoutMs = 55000): array
    {
        return $this->request('post', '/devices/'.rawurlencode($deviceId).'/tasks', $task, [
            'connection_request' => $connectionRequest ? 'true' : 'false',
            'timeout' => (string) $timeoutMs,
        ], 60000);
    }

    /**
     * @return array{success: bool, data: array, error: ?string}
     */
    protected function request(string $method, string $endpoint, array $data = [], array $query = [], ?int $timeout = null): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'data' => [],
                'error' => 'URL NBI GenieACS belum dikonfigurasi.',
            ];
        }

        $timeout ??= $this->timeout;
        $url = $this->baseUrl.$endpoint;

        try {
            $http = Http::timeout($timeout)
                ->connectTimeout(min($timeout, 5))
                ->withBasicAuth($this->username, $this->password)
                ->acceptJson();

            $response = $method === 'get'
                ? $http->get($url, $query)
                : $http->asJson()->withQueryParameters($query)->post($url, $data);

            if ($response->failed()) {
                Log::error('GenieACS NBI request failed', [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'data' => [],
                    'error' => "GenieACS merespon status {$response->status()}.",
                ];
            }

            return [
                'success' => true,
                'data' => $response->json() ?? [],
                'error' => null,
            ];
        } catch (Exception $e) {
            Log::error('GenieACS NBI connection failed', [
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data' => [],
                'error' => $e->getMessage(),
            ];
        }
    }
}
