<?php

namespace App\Services\Mikrotik;

use App\Models\PppSecret;
use App\Models\Router;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use RouterOS\Query;

class PPPSecretService
{
    protected MikrotikService $mikrotikService;

    protected Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
        $this->mikrotikService = new MikrotikService($router);
    }

    public function getAllSecrets(): array
    {
        $maxRetries = 2;
        $attempt = 0;

        while ($attempt <= $maxRetries) {
            try {
                $attempt++;

                Log::info('Getting PPP secrets - Attempt', [
                    'router_id' => $this->router->id,
                    'router_name' => $this->router->name,
                    'host' => $this->router->host,
                    'identity' => $this->router->identity,
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                ]);

                $client = $this->mikrotikService->getClient();
                $clientHash = spl_object_hash($client);

                Log::info('Client created for PPP secrets query', [
                    'router_id' => $this->router->id,
                    'router_name' => $this->router->name,
                    'client_hash' => $clientHash,
                ]);

                $query = new Query('/ppp/secret/print');

                Log::info('Executing query /ppp/secret/print', [
                    'router_id' => $this->router->id,
                    'router_name' => $this->router->name,
                    'client_hash' => $clientHash,
                ]);

                $response = $client->query($query)->read();

                $responseCount = count($response);

                Log::info('Retrieved PPP secrets from RouterOS API', [
                    'router_id' => $this->router->id,
                    'router_name' => $this->router->name,
                    'host' => $this->router->host,
                    'identity' => $this->router->identity,
                    'count' => $responseCount,
                    'attempt' => $attempt,
                    'client_hash' => $clientHash,
                    'first_item' => $responseCount > 0 ? ($response[0]['name'] ?? 'N/A') : 'N/A',
                    'last_item' => $responseCount > 0 ? ($response[$responseCount - 1]['name'] ?? 'N/A') : 'N/A',
                ]);

                if ($responseCount === 0) {
                    Log::warning('No PPP secrets found on router', [
                        'router_id' => $this->router->id,
                        'router_name' => $this->router->name,
                        'raw_response_full' => json_encode($response, JSON_PRETTY_PRINT),
                    ]);
                }

                return $response;
            } catch (Exception $e) {
                $isTimeout = str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'timeout');

                Log::error('Failed to get PPP secrets', [
                    'router_id' => $this->router->id,
                    'router_name' => $this->router->name,
                    'host' => $this->router->host,
                    'error' => $e->getMessage(),
                    'is_timeout' => $isTimeout,
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                    'trace' => $e->getTraceAsString(),
                ]);

                if ($attempt > $maxRetries) {
                    Log::error('Max retries reached, giving up', [
                        'router_id' => $this->router->id,
                        'router_name' => $this->router->name,
                        'total_attempts' => $attempt,
                    ]);

                    return [];
                }

                if ($isTimeout) {
                    Log::warning('Timeout detected, disconnecting and retrying...', [
                        'router_id' => $this->router->id,
                        'next_attempt' => $attempt + 1,
                    ]);

                    $this->mikrotikService->disconnect();

                    sleep(3);

                    continue;
                }

                return [];
            }
        }

        return [];
    }

    public function createSecret(array $data): array
    {
        try {
            $client = $this->mikrotikService->getClient();

            $query = (new Query('/ppp/secret/add'))
                ->equal('name', $data['name'])
                ->equal('password', $data['password']);

            if (! empty($data['service'])) {
                $query->equal('service', $data['service']);
            }

            if (! empty($data['profile'])) {
                $query->equal('profile', $data['profile']);
            }

            if (! empty($data['local_address'])) {
                $query->equal('local-address', $data['local_address']);
            }

            if (! empty($data['remote_address'])) {
                $query->equal('remote-address', $data['remote_address']);
            }

            if (! empty($data['caller_id'])) {
                $query->equal('caller-id', $data['caller_id']);
            }

            if (! empty($data['comment'])) {
                $query->equal('comment', $data['comment']);
            }

            $response = $client->query($query)->read();

            Log::info('PPP secret created on router', [
                'router_id' => $this->router->id,
                'secret_name' => $data['name'],
            ]);

            return [
                'success' => true,
                'message' => 'PPP Secret created successfully',
                'data' => $response,
            ];
        } catch (Exception $e) {
            Log::error('Failed to create PPP secret', [
                'router_id' => $this->router->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $this->getErrorMessage($e),
            ];
        }
    }

    public function findSecret(string $mikrotikId): ?array
    {
        try {
            $client = $this->mikrotikService->getClient();

            $query = (new Query('/ppp/secret/print'))
                ->equal('.id', $mikrotikId);

            $response = $client->query($query)->read();

            if (empty($response)) {
                return null;
            }

            return $this->extractFirstResult($response);
        } catch (Exception $e) {
            Log::warning('Failed to find PPP secret on router', [
                'router_id' => $this->router->id,
                'router_name' => $this->router->name,
                'mikrotik_id' => $mikrotikId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function findSecretByName(string $name): ?array
    {
        try {
            $client = $this->mikrotikService->getClient();

            $query = (new Query('/ppp/secret/print'))
                ->equal('name', $name);

            $response = $client->query($query)->read();

            if (empty($response)) {
                return null;
            }

            return $this->extractFirstResult($response);
        } catch (Exception $e) {
            Log::warning('Failed to find PPP secret by name on router', [
                'router_id' => $this->router->id,
                'router_name' => $this->router->name,
                'name' => $name,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function extractFirstResult(array $response): ?array
    {
        if (isset($response[0]) && is_array($response[0])) {
            return $response[0];
        }

        if (isset($response['.id']) || isset($response['name'])) {
            return $response;
        }

        $values = array_values($response);

        if (isset($values[0]) && is_array($values[0])) {
            return $values[0];
        }

        return null;
    }

    public function updateSecret(string $mikrotikId, array $data): array
    {
        try {
            $client = $this->mikrotikService->getClient();

            $query = (new Query('/ppp/secret/set'))
                ->equal('.id', $mikrotikId);

            if (! empty($data['password'])) {
                $query->equal('password', $data['password']);
            }

            if (! empty($data['name'])) {
                $query->equal('name', $data['name']);
            }

            if (isset($data['profile'])) {
                $query->equal('profile', $data['profile']);
            }

            if (isset($data['disabled'])) {
                $query->equal('disabled', $data['disabled']);
            }

            if (isset($data['local_address'])) {
                $query->equal('local-address', $data['local_address']);
            }

            if (isset($data['remote_address'])) {
                $query->equal('remote-address', $data['remote_address']);
            }

            if (isset($data['caller_id'])) {
                $query->equal('caller-id', $data['caller_id']);
            }

            if (isset($data['comment'])) {
                $query->equal('comment', $data['comment']);
            }

            $client->query($query)->read();

            $maxRetries = 3;
            $updated = null;

            for ($i = 0; $i < $maxRetries; $i++) {
                usleep(300000);
                $updated = $this->findSecret($mikrotikId);

                if ($updated !== null) {
                    break;
                }

                Log::warning('PPP secret not found after update, retrying verification', [
                    'router_id' => $this->router->id,
                    'mikrotik_id' => $mikrotikId,
                    'retry' => $i + 1,
                ]);
            }

            if ($updated === null) {
                Log::warning('PPP secret verification failed after retries, assuming update succeeded', [
                    'router_id' => $this->router->id,
                    'mikrotik_id' => $mikrotikId,
                ]);
            } elseif (! empty($data['name']) && ($updated['name'] ?? null) !== $data['name']) {
                Log::warning('PPP secret name verification failed after update, using retry', [
                    'router_id' => $this->router->id,
                    'mikrotik_id' => $mikrotikId,
                    'expected_name' => $data['name'],
                    'actual_name' => $updated['name'] ?? null,
                ]);

                usleep(500000);
                $retryUpdated = $this->findSecret($mikrotikId);

                if ($retryUpdated !== null && ($retryUpdated['name'] ?? null) !== $data['name']) {
                    Log::error('PPP secret name verification still failed after retry', [
                        'router_id' => $this->router->id,
                        'mikrotik_id' => $mikrotikId,
                        'expected_name' => $data['name'],
                        'actual_name' => $retryUpdated['name'] ?? null,
                    ]);
                }
            }

            Log::info('PPP secret updated on router', [
                'router_id' => $this->router->id,
                'router_name' => $this->router->name,
                'mikrotik_id' => $mikrotikId,
            ]);

            return [
                'success' => true,
                'message' => 'PPP Secret updated successfully',
            ];
        } catch (Exception $e) {
            Log::error('Failed to update PPP secret', [
                'router_id' => $this->router->id,
                'router_name' => $this->router->name,
                'mikrotik_id' => $mikrotikId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $this->getErrorMessage($e),
            ];
        }
    }

    public function deleteSecret(string $mikrotikId): array
    {
        try {
            $client = $this->mikrotikService->getClient();

            $query = (new Query('/ppp/secret/remove'))
                ->equal('.id', $mikrotikId);

            $client->query($query)->read();

            $maxRetries = 3;
            $deleted = null;

            for ($i = 0; $i < $maxRetries; $i++) {
                usleep(300000);
                $deleted = $this->findSecret($mikrotikId);

                if ($deleted === null) {
                    break;
                }

                Log::warning('PPP secret still found after deletion, retrying verification', [
                    'router_id' => $this->router->id,
                    'router_name' => $this->router->name,
                    'mikrotik_id' => $mikrotikId,
                    'retry' => $i + 1,
                ]);
            }

            if ($deleted !== null) {
                Log::warning('PPP secret verification failed after retries, assuming deletion succeeded', [
                    'router_id' => $this->router->id,
                    'router_name' => $this->router->name,
                    'mikrotik_id' => $mikrotikId,
                ]);
            }

            Log::info('PPP secret deleted from router', [
                'router_id' => $this->router->id,
                'router_name' => $this->router->name,
                'mikrotik_id' => $mikrotikId,
            ]);

            return [
                'success' => true,
                'message' => 'PPP Secret deleted successfully',
            ];
        } catch (Exception $e) {
            Log::error('Failed to delete PPP secret', [
                'router_id' => $this->router->id,
                'router_name' => $this->router->name,
                'mikrotik_id' => $mikrotikId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $this->getErrorMessage($e),
            ];
        }
    }

    public function enableSecret(string $mikrotikId): array
    {
        try {
            $client = $this->mikrotikService->getClient();

            $query = (new Query('/ppp/secret/enable'))
                ->equal('.id', $mikrotikId);

            $client->query($query)->read();

            Log::info('PPP secret enabled on router', [
                'router_id' => $this->router->id,
                'mikrotik_id' => $mikrotikId,
            ]);

            return [
                'success' => true,
                'message' => 'PPP Secret enabled successfully',
            ];
        } catch (Exception $e) {
            Log::error('Failed to enable PPP secret', [
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

    public function disableSecret(string $mikrotikId): array
    {
        try {
            $client = $this->mikrotikService->getClient();

            $query = (new Query('/ppp/secret/disable'))
                ->equal('.id', $mikrotikId);

            $client->query($query)->read();

            Log::info('PPP secret disabled on router', [
                'router_id' => $this->router->id,
                'mikrotik_id' => $mikrotikId,
            ]);

            return [
                'success' => true,
                'message' => 'PPP Secret disabled successfully',
            ];
        } catch (Exception $e) {
            Log::error('Failed to disable PPP secret', [
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

    public function getProfiles(): array
    {
        try {
            $client = $this->mikrotikService->getClient();

            $query = new Query('/ppp/profile/print');
            $response = $client->query($query)->read();

            return collect($response)->pluck('name')->toArray();
        } catch (Exception $e) {
            Log::error('Failed to get PPP profiles', [
                'router_id' => $this->router->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function syncSecrets(): int
    {
        try {
            Log::info('Starting PPP secrets sync', [
                'router_id' => $this->router->id,
                'router_name' => $this->router->name,
                'host' => $this->router->host,
                'identity' => $this->router->identity,
            ]);

            $secrets = $this->getAllSecrets();

            Log::info('PPP secrets fetched from API', [
                'router_id' => $this->router->id,
                'router_name' => $this->router->name,
                'total_received' => count($secrets),
            ]);

            if (empty($secrets)) {
                Log::warning('No secrets to sync - empty response from API', [
                    'router_id' => $this->router->id,
                    'router_name' => $this->router->name,
                ]);

                return 0;
            }

            $synced = 0;
            $inserted = 0;
            $updated = 0;

            foreach ($secrets as $secret) {
                $mikrotikId = $secret['.id'] ?? null;

                if (! $mikrotikId) {
                    Log::warning('Secret without mikrotik_id - skipping', [
                        'router_id' => $this->router->id,
                        'secret_name' => $secret['name'] ?? 'unknown',
                    ]);

                    continue;
                }

                $existing = PppSecret::where('router_id', $this->router->id)
                    ->where('mikrotik_id', $mikrotikId)
                    ->first();

                $wasNew = ! $existing;

                PppSecret::updateOrCreate(
                    [
                        'router_id' => $this->router->id,
                        'mikrotik_id' => $mikrotikId,
                    ],
                    [
                        'name' => $secret['name'] ?? null,
                        'password' => $secret['password'] ?? '',
                        'service' => $secret['service'] ?? null,
                        'profile' => $secret['profile'] ?? null,
                        'local_address' => $secret['local-address'] ?? null,
                        'remote_address' => $secret['remote-address'] ?? null,
                        'caller_id' => $secret['caller-id'] ?? null,
                        'disabled' => isset($secret['disabled']) && $secret['disabled'] === 'true',
                        'comment' => $secret['comment'] ?? null,
                        'last_logged_out' => isset($secret['last-logged-out']) && $secret['last-logged-out'] !== ''
                            ? rescue(fn () => Carbon::createFromFormat('M/d/Y H:i:s', ucfirst($secret['last-logged-out'])), null, false)
                            : null,
                    ]
                );

                if ($wasNew) {
                    $inserted++;
                } else {
                    $updated++;
                }

                $synced++;
            }

            $totalInDb = PppSecret::where('router_id', $this->router->id)->count();

            Log::info('PPP secrets sync completed', [
                'router_id' => $this->router->id,
                'router_name' => $this->router->name,
                'total_from_api' => count($secrets),
                'total_synced' => $synced,
                'inserted' => $inserted,
                'updated' => $updated,
                'total_in_database' => $totalInDb,
            ]);

            return $synced;
        } catch (Exception $e) {
            Log::error('Failed to sync PPP secrets', [
                'router_id' => $this->router->id,
                'router_name' => $this->router->name,
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
            return 'PPP Secret with this name already exists.';
        }

        if (str_contains($message, 'no such item')) {
            return 'PPP Secret not found on router.';
        }

        if (str_contains($message, 'invalid value')) {
            return 'Invalid value provided.';
        }

        return 'Operation failed: '.$message;
    }
}
