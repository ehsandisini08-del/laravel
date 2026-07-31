<?php

namespace App\Services\Mikrotik;

use App\Models\Router;
use Exception;
use Illuminate\Support\Facades\Log;
use RouterOS\Client;
use RouterOS\Config;
use RouterOS\Query;

class MikrotikService
{
    protected ?Client $client = null;

    protected Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function connect(?Router $router = null): Client
    {
        $router = $router ?? $this->router;

        if ($this->client !== null) {
            Log::info('Reusing existing client', [
                'router_id' => $router->id,
                'router_name' => $router->name,
                'client_hash' => spl_object_hash($this->client),
            ]);

            return $this->client;
        }

        try {
            Log::info('Creating new RouterOS client', [
                'router_id' => $router->id,
                'router_name' => $router->name,
                'host' => $router->host,
                'port' => $router->api_port,
                'username' => $router->username,
                'ssl' => $router->api_ssl,
            ]);

            $config = (new Config)
                ->set('host', $router->host)
                ->set('port', $router->api_port)
                ->set('user', $router->username)
                ->set('pass', $router->decrypted_password)
                ->set('timeout', 30)
                ->set('socket_timeout', 120)
                ->set('socket_blocking', true)
                ->set('throw_timeout_exception', false)
                ->set('attempts', 1);

            if ($router->api_ssl) {
                $config->set('ssl', true);
            }

            $this->client = new Client($config);

            Log::info('RouterOS client created and connected', [
                'router_id' => $router->id,
                'router_name' => $router->name,
                'host' => $router->host,
                'client_hash' => spl_object_hash($this->client),
            ]);

            return $this->client;
        } catch (Exception $e) {
            $this->client = null;

            Log::error('Router connection failed', [
                'router_id' => $router->id,
                'router_name' => $router->name,
                'host' => $router->host,
                'port' => $router->api_port,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function disconnect(): void
    {
        if ($this->client !== null) {
            Log::info('Disconnecting client', [
                'router_id' => $this->router->id,
                'client_hash' => spl_object_hash($this->client),
            ]);

            $this->client = null;
        }
    }

    public function getClient(): Client
    {
        return $this->connect();
    }

    public function testConnection(): array
    {
        try {
            Log::info('Testing router connection', [
                'router_id' => $this->router->id,
                'router_name' => $this->router->name,
                'host' => $this->router->host,
                'port' => $this->router->api_port,
                'ssl' => $this->router->api_ssl,
            ]);

            $client = $this->connect();
            $clientHash = spl_object_hash($client);

            Log::info('Test connection - Client info', [
                'router_id' => $this->router->id,
                'client_hash' => $clientHash,
            ]);

            $query = new Query('/system/resource/print');
            $resourceResponse = $client->query($query)->read();

            $query = new Query('/system/identity/print');
            $identityResponse = $client->query($query)->read();

            $systemResource = $this->parseSystemResource($resourceResponse);
            $systemIdentity = $this->parseSystemIdentity($identityResponse);

            Log::info('Test connection successful', [
                'router_id' => $this->router->id,
                'identity' => $systemIdentity['identity'] ?? null,
            ]);

            return [
                'success' => true,
                'message' => 'Successfully connected to router',
                'data' => array_merge($systemResource, $systemIdentity),
            ];
        } catch (Exception $e) {
            $this->disconnect();

            Log::error('Test connection failed', [
                'router_id' => $this->router->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $this->getErrorMessage($e),
                'data' => null,
            ];
        }
    }

    protected function parseSystemResource(array $response): array
    {
        if (empty($response)) {
            return [];
        }

        $resource = $response[0];

        return [
            'platform' => $resource['platform'] ?? null,
            'board_name' => $resource['board-name'] ?? null,
            'version' => $resource['version'] ?? null,
            'architecture' => $resource['architecture-name'] ?? null,
            'cpu' => $resource['cpu'] ?? null,
            'cpu_count' => $resource['cpu-count'] ?? null,
            'cpu_frequency' => $resource['cpu-frequency'] ?? null,
            'cpu_load' => $resource['cpu-load'] ?? null,
            'uptime' => $resource['uptime'] ?? null,
            'total_memory' => $resource['total-memory'] ?? null,
            'free_memory' => $resource['free-memory'] ?? null,
            'total_hdd_space' => $resource['total-hdd-space'] ?? null,
            'free_hdd_space' => $resource['free-hdd-space'] ?? null,
            'build_time' => $resource['build-time'] ?? null,
            'factory_software' => $resource['factory-software'] ?? null,
        ];
    }

    protected function parseSystemIdentity(array $response): array
    {
        if (empty($response)) {
            return [];
        }

        return [
            'identity' => $response[0]['name'] ?? null,
        ];
    }

    public function syncRouterInformation(): bool
    {
        try {
            $client = $this->connect();
            $clientHash = spl_object_hash($client);

            Log::info('Sync router information - Client info', [
                'router_id' => $this->router->id,
                'client_hash' => $clientHash,
            ]);

            $query = new Query('/system/resource/print');
            $resourceResponse = $client->query($query)->read();

            $query = new Query('/system/identity/print');
            $identityResponse = $client->query($query)->read();

            $systemResource = $this->parseSystemResource($resourceResponse);
            $systemIdentity = $this->parseSystemIdentity($identityResponse);

            $this->router->update([
                'identity' => $systemIdentity['identity'] ?? $this->router->identity,
                'routeros_version' => $systemResource['version'] ?? $this->router->routeros_version,
                'board_name' => $systemResource['board_name'] ?? $this->router->board_name,
                'architecture' => $systemResource['architecture'] ?? $this->router->architecture,
                'cpu' => $systemResource['cpu'] ?? $this->router->cpu,
                'total_memory' => $systemResource['total_memory'] ?? $this->router->total_memory,
                'free_memory' => $systemResource['free_memory'] ?? $this->router->free_memory,
                'uptime' => $systemResource['uptime'] ?? $this->router->uptime,
                'status' => 'online',
                'last_seen_at' => now(),
            ]);

            Log::info('Router information synced', [
                'router_id' => $this->router->id,
                'router_name' => $this->router->name,
            ]);

            return true;
        } catch (Exception $e) {
            $this->disconnect();
            $this->router->markAsOffline();

            Log::error('Failed to sync router information', [
                'router_id' => $this->router->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function getErrorMessage(Exception $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'Connection refused')) {
            return 'Connection refused. Please check if API service is enabled on the router.';
        }

        if (str_contains($message, 'Authentication failed') || str_contains($message, 'cannot log in')) {
            return 'Authentication failed. Please check username and password.';
        }

        if (str_contains($message, 'Connection timed out') || str_contains($message, 'timeout')) {
            return 'Connection timeout. Please check host/IP address and network connectivity.';
        }

        if (str_contains($message, 'SSL')) {
            return 'SSL connection error. Please verify SSL settings.';
        }

        if (str_contains($message, 'Host not found') || str_contains($message, 'could not resolve')) {
            return 'Host not found. Please check the IP address or hostname.';
        }

        return 'Connection failed: '.$message;
    }
}
