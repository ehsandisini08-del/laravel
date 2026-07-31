<?php

namespace App\Services\Mikrotik;

use App\Models\PppProfile;
use App\Models\Router;
use Exception;
use Illuminate\Support\Facades\Log;
use RouterOS\Query;

class PPPProfileService
{
    protected MikrotikService $mikrotikService;

    protected Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
        $this->mikrotikService = new MikrotikService($router);
    }

    public function getAllProfiles(): array
    {
        $maxRetries = 2;
        $attempt = 0;

        while ($attempt <= $maxRetries) {
            try {
                $attempt++;

                Log::info('Getting PPP profiles - Attempt', [
                    'router_id' => $this->router->id,
                    'attempt' => $attempt,
                ]);

                $client = $this->mikrotikService->getClient();
                $clientHash = spl_object_hash($client);

                Log::info('Executing /ppp/profile/print', [
                    'router_id' => $this->router->id,
                    'client_hash' => $clientHash,
                ]);

                $query = new Query('/ppp/profile/print');
                $response = $client->query($query)->read();

                $responseCount = count($response);

                Log::info('Retrieved PPP profiles from RouterOS API', [
                    'router_id' => $this->router->id,
                    'count' => $responseCount,
                    'attempt' => $attempt,
                ]);

                if ($responseCount === 0) {
                    Log::warning('No PPP profiles found on router', [
                        'router_id' => $this->router->id,
                        'raw_response_full' => json_encode($response, JSON_PRETTY_PRINT),
                    ]);
                }

                return $response;
            } catch (Exception $e) {
                $isTimeout = str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'timeout');

                Log::error('Failed to get PPP profiles', [
                    'router_id' => $this->router->id,
                    'error' => $e->getMessage(),
                    'is_timeout' => $isTimeout,
                    'attempt' => $attempt,
                    'trace' => $e->getTraceAsString(),
                ]);

                if ($attempt > $maxRetries) {
                    Log::error('Max retries reached, giving up', [
                        'router_id' => $this->router->id,
                        'total_attempts' => $attempt,
                    ]);

                    return [];
                }

                if ($isTimeout) {
                    $this->mikrotikService->disconnect();
                    sleep(3);

                    continue;
                }

                return [];
            }
        }

        return [];
    }

    public function createProfile(array $data): array
    {
        try {
            $client = $this->mikrotikService->getClient();

            $query = (new Query('/ppp/profile/add'))
                ->equal('name', $data['name']);

            if (! empty($data['local_address'])) {
                $query->equal('local-address', $data['local_address']);
            }

            if (! empty($data['remote_address'])) {
                $query->equal('remote-address', $data['remote_address']);
            }

            if (! empty($data['dns_server'])) {
                $query->equal('dns-server', $data['dns_server']);
            }

            if (! empty($data['rate_limit'])) {
                $query->equal('rate-limit', $data['rate_limit']);
            }

            if (! empty($data['parent_queue'])) {
                $query->equal('parent-queue', $data['parent_queue']);
            }

            if (! empty($data['only_one'])) {
                $query->equal('only-one', $data['only_one']);
            }

            if (! empty($data['change_tcp_mss'])) {
                $query->equal('change-tcp-mss', $data['change_tcp_mss']);
            }

            if (! empty($data['use_compression'])) {
                $query->equal('use-compression', $data['use_compression']);
            }

            if (! empty($data['use_encryption'])) {
                $query->equal('use-encryption', $data['use_encryption']);
            }

            if (! empty($data['use_ipv6'])) {
                $query->equal('use-ipv6', $data['use_ipv6']);
            }

            if (! empty($data['bridge'])) {
                $query->equal('bridge', $data['bridge']);
            }

            if (! empty($data['bridge_path_cost'])) {
                $query->equal('bridge-path-cost', $data['bridge_path_cost']);
            }

            if (! empty($data['bridge_horizon'])) {
                $query->equal('bridge-horizon', $data['bridge_horizon']);
            }

            if (! empty($data['comment'])) {
                $query->equal('comment', $data['comment']);
            }

            $response = $client->query($query)->read();

            Log::info('PPP profile created on router', [
                'router_id' => $this->router->id,
                'profile_name' => $data['name'],
            ]);

            return [
                'success' => true,
                'message' => 'PPP Profile created successfully',
                'data' => $response,
            ];
        } catch (Exception $e) {
            Log::error('Failed to create PPP profile', [
                'router_id' => $this->router->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $this->getErrorMessage($e),
            ];
        }
    }

    public function updateProfile(string $mikrotikId, array $data): array
    {
        try {
            $client = $this->mikrotikService->getClient();

            $query = (new Query('/ppp/profile/set'))
                ->equal('.id', $mikrotikId);

            if (isset($data['local_address'])) {
                $query->equal('local-address', $data['local_address']);
            }

            if (isset($data['remote_address'])) {
                $query->equal('remote-address', $data['remote_address']);
            }

            if (isset($data['dns_server'])) {
                $query->equal('dns-server', $data['dns_server']);
            }

            if (isset($data['rate_limit'])) {
                $query->equal('rate-limit', $data['rate_limit']);
            }

            if (isset($data['parent_queue'])) {
                $query->equal('parent-queue', $data['parent_queue']);
            }

            if (isset($data['only_one'])) {
                $query->equal('only-one', $data['only_one']);
            }

            if (isset($data['change_tcp_mss'])) {
                $query->equal('change-tcp-mss', $data['change_tcp_mss']);
            }

            if (isset($data['use_compression'])) {
                $query->equal('use-compression', $data['use_compression']);
            }

            if (isset($data['use_encryption'])) {
                $query->equal('use-encryption', $data['use_encryption']);
            }

            if (isset($data['use_ipv6'])) {
                $query->equal('use-ipv6', $data['use_ipv6']);
            }

            if (isset($data['bridge'])) {
                $query->equal('bridge', $data['bridge']);
            }

            if (isset($data['bridge_path_cost'])) {
                $query->equal('bridge-path-cost', $data['bridge_path_cost']);
            }

            if (isset($data['bridge_horizon'])) {
                $query->equal('bridge-horizon', $data['bridge_horizon']);
            }

            if (isset($data['comment'])) {
                $query->equal('comment', $data['comment']);
            }

            $client->query($query)->read();

            Log::info('PPP profile updated on router', [
                'router_id' => $this->router->id,
                'mikrotik_id' => $mikrotikId,
            ]);

            return [
                'success' => true,
                'message' => 'PPP Profile updated successfully',
            ];
        } catch (Exception $e) {
            Log::error('Failed to update PPP profile', [
                'router_id' => $this->router->id,
                'mikrotik_id' => $mikrotikId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $this->getErrorMessage($e),
            ];
        }
    }

    public function deleteProfile(string $mikrotikId): array
    {
        try {
            $client = $this->mikrotikService->getClient();

            $query = (new Query('/ppp/profile/remove'))
                ->equal('.id', $mikrotikId);

            $client->query($query)->read();

            Log::info('PPP profile deleted from router', [
                'router_id' => $this->router->id,
                'mikrotik_id' => $mikrotikId,
            ]);

            return [
                'success' => true,
                'message' => 'PPP Profile deleted successfully',
            ];
        } catch (Exception $e) {
            Log::error('Failed to delete PPP profile', [
                'router_id' => $this->router->id,
                'mikrotik_id' => $mikrotikId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $this->getErrorMessage($e),
            ];
        }
    }

    public function syncProfiles(): int
    {
        try {
            Log::info('Starting PPP profiles sync', [
                'router_id' => $this->router->id,
                'router_name' => $this->router->name,
            ]);

            $profiles = $this->getAllProfiles();

            Log::info('PPP profiles fetched from API', [
                'router_id' => $this->router->id,
                'total_received' => count($profiles),
            ]);

            if (empty($profiles)) {
                Log::warning('No profiles to sync - empty response from API', [
                    'router_id' => $this->router->id,
                ]);

                return 0;
            }

            $synced = 0;
            $inserted = 0;
            $updated = 0;
            $remoteIds = [];

            foreach ($profiles as $profile) {
                $mikrotikId = $profile['.id'] ?? null;

                if (! $mikrotikId) {
                    continue;
                }

                $remoteIds[] = $mikrotikId;

                $existing = PppProfile::where('router_id', $this->router->id)
                    ->where('mikrotik_id', $mikrotikId)
                    ->first();

                $wasNew = ! $existing;

                PppProfile::updateOrCreate(
                    [
                        'router_id' => $this->router->id,
                        'mikrotik_id' => $mikrotikId,
                    ],
                    [
                        'name' => $profile['name'] ?? null,
                        'local_address' => $profile['local-address'] ?? null,
                        'remote_address' => $profile['remote-address'] ?? null,
                        'dns_server' => $profile['dns-server'] ?? null,
                        'rate_limit' => $profile['rate-limit'] ?? null,
                        'parent_queue' => $profile['parent-queue'] ?? null,
                        'only_one' => isset($profile['only-one']) && $profile['only-one'] === 'true',
                        'change_tcp_mss' => isset($profile['change-tcp-mss']) && $profile['change-tcp-mss'] === 'true',
                        'use_compression' => isset($profile['use-compression']) && $profile['use-compression'] === 'true',
                        'use_encryption' => isset($profile['use-encryption']) && $profile['use-encryption'] === 'true',
                        'use_ipv6' => isset($profile['use-ipv6']) && $profile['use-ipv6'] === 'true',
                        'bridge' => $profile['bridge'] ?? null,
                        'bridge_path_cost' => $profile['bridge-path-cost'] ?? null,
                        'bridge_horizon' => $profile['bridge-horizon'] ?? null,
                        'comment' => $profile['comment'] ?? null,
                        'synced_at' => now(),
                    ]
                );

                if ($wasNew) {
                    $inserted++;
                } else {
                    $updated++;
                }

                $synced++;
            }

            PppProfile::where('router_id', $this->router->id)
                ->whereNotIn('mikrotik_id', $remoteIds)
                ->delete();

            $totalInDb = PppProfile::where('router_id', $this->router->id)->count();

            Log::info('PPP profiles sync completed', [
                'router_id' => $this->router->id,
                'total_from_api' => count($profiles),
                'total_synced' => $synced,
                'inserted' => $inserted,
                'updated' => $updated,
                'total_in_database' => $totalInDb,
            ]);

            return $synced;
        } catch (Exception $e) {
            Log::error('Failed to sync PPP profiles', [
                'router_id' => $this->router->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 0;
        }
    }

    protected function getErrorMessage(Exception $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'already have') || str_contains($message, 'already exists')) {
            return 'PPP Profile with this name already exists.';
        }

        if (str_contains($message, 'no such item')) {
            return 'PPP Profile not found on router.';
        }

        if (str_contains($message, 'invalid value')) {
            return 'Invalid value provided.';
        }

        if (str_contains($message, 'in use')) {
            return 'PPP Profile is in use by one or more PPP Secrets and cannot be deleted.';
        }

        return 'Operation failed: '.$message;
    }
}
