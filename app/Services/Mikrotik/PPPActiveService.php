<?php

namespace App\Services\Mikrotik;

use App\Models\Router;
use Exception;
use Illuminate\Support\Facades\Log;
use RouterOS\Query;

class PPPActiveService
{
    protected MikrotikService $mikrotikService;

    protected Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
        $this->mikrotikService = new MikrotikService($router);
    }

    public function getActiveConnections(): array
    {
        try {
            Log::info('Getting PPP active connections', [
                'router_id' => $this->router->id,
                'router_name' => $this->router->name,
            ]);

            $client = $this->mikrotikService->getClient();

            $query = new Query('/ppp/active/print');
            $response = $client->query($query)->read();

            $count = count($response);

            Log::info('Retrieved PPP active connections', [
                'router_id' => $this->router->id,
                'count' => $count,
            ]);

            return $this->normalize($response);
        } catch (Exception $e) {
            Log::error('Failed to get PPP active connections', [
                'router_id' => $this->router->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function disconnectUser(string $userId): array
    {
        try {
            $client = $this->mikrotikService->getClient();

            $query = (new Query('/ppp/active/remove'))
                ->equal('.id', $userId);

            $client->query($query)->read();

            Log::info('PPP active user disconnected', [
                'router_id' => $this->router->id,
                'user_id' => $userId,
            ]);

            return [
                'success' => true,
                'message' => 'User disconnected successfully.',
            ];
        } catch (Exception $e) {
            Log::error('Failed to disconnect PPP active user', [
                'router_id' => $this->router->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            $message = $e->getMessage();
            if (str_contains($message, 'no such item')) {
                return [
                    'success' => false,
                    'message' => 'Active session not found. User may already be disconnected.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to disconnect user: '.$e->getMessage(),
            ];
        }
    }

    public function bulkDisconnect(array $userIds): array
    {
        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($userIds as $userId) {
            $result = $this->disconnectUser($userId);
            if ($result['success']) {
                $success++;
            } else {
                $failed++;
                $errors[] = $result['message'];
            }
        }

        Log::info('Bulk disconnect completed', [
            'router_id' => $this->router->id,
            'success' => $success,
            'failed' => $failed,
        ]);

        return [
            'success' => $success > 0 || $failed === 0,
            'message' => "{$success} user(s) disconnected successfully.".($failed > 0 ? " {$failed} failed." : ''),
            'success_count' => $success,
            'failed_count' => $failed,
            'errors' => $errors,
        ];
    }

    public function getStatistics(): array
    {
        try {
            $connections = $this->getActiveConnections();

            $totalBytesIn = 0;
            $totalBytesOut = 0;
            $services = [];
            $profiles = [];

            foreach ($connections as $conn) {
                $totalBytesIn += (int) ($conn['bytes_in'] ?? 0);
                $totalBytesOut += (int) ($conn['bytes_out'] ?? 0);

                $service = $conn['service'] ?? 'unknown';
                $services[$service] = ($services[$service] ?? 0) + 1;

                $profile = $conn['profile'] ?? 'unknown';
                $profiles[$profile] = ($profiles[$profile] ?? 0) + 1;
            }

            return [
                'total_active' => count($connections),
                'total_bytes_in' => $totalBytesIn,
                'total_bytes_out' => $totalBytesOut,
                'services' => $services,
                'profiles' => $profiles,
            ];
        } catch (Exception $e) {
            return [
                'total_active' => 0,
                'total_bytes_in' => 0,
                'total_bytes_out' => 0,
                'services' => [],
                'profiles' => [],
            ];
        }
    }

    protected function normalize(array $response): array
    {
        return array_map(function ($item) {
            return [
                'id' => $item['.id'] ?? null,
                'name' => $item['name'] ?? null,
                'service' => $item['service'] ?? null,
                'profile' => $item['profile'] ?? null,
                'caller_id' => $item['caller-id'] ?? null,
                'address' => $item['address'] ?? null,
                'uptime' => $item['uptime'] ?? null,
                'session_time' => $item['session-time'] ?? null,
                'connected_since' => $item['connected-since'] ?? null,
                'interface' => $item['interface'] ?? null,
                'encoding' => $item['encoding'] ?? null,
                'radius' => $item['radius'] ?? null,
                'bytes_in' => $item['bytes-in'] ?? null,
                'bytes_out' => $item['bytes-out'] ?? null,
                'packets_in' => $item['packets-in'] ?? null,
                'packets_out' => $item['packets-out'] ?? null,
                'comment' => $item['comment'] ?? null,
                'session_id' => $item['session-id'] ?? null,
            ];
        }, $response);
    }

    public function formatUptime(?string $uptime): string
    {
        if (! $uptime) {
            return '-';
        }

        // Parse RouterOS uptime format like "1d2h3m4s" or "2h30m"
        $parts = [];
        if (preg_match('/(\d+)d/', $uptime, $m)) {
            $parts[] = $m[1].'d';
        }
        if (preg_match('/(\d+)h/', $uptime, $m)) {
            $parts[] = $m[1].'h';
        }
        if (preg_match('/(\d+)m(?![a-z])/', $uptime, $m)) {
            $parts[] = $m[1].'m';
        }
        if (preg_match('/(\d+)s/', $uptime, $m)) {
            $parts[] = $m[1].'s';
        }

        return ! empty($parts) ? implode(' ', $parts) : $uptime;
    }
}
