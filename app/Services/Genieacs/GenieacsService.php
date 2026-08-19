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
        return $this->request('get', '/devices', [
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
        $result = $this->request('get', '/devices', [
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
     * @return array{success: bool, data: array, error: ?string}
     */
    protected function request(string $method, string $endpoint, array $query = []): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'data' => [],
                'error' => 'URL NBI GenieACS belum dikonfigurasi.',
            ];
        }

        $url = $this->baseUrl.$endpoint;

        try {
            $http = Http::timeout($this->timeout)
                ->connectTimeout(min($this->timeout, 5))
                ->withBasicAuth($this->username, $this->password)
                ->acceptJson();

            $response = $method === 'get'
                ? $http->get($url, $query)
                : $http->post($url, $query);

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
