<?php

namespace App\Services\WhatsApp;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BaileysGatewayService
{
    protected string $baseUrl;

    protected ?string $apiToken;

    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.baileys_gateway.base_url', 'http://localhost:3001'), '/');
        $this->apiToken = config('services.baileys_gateway.api_token');
        $this->timeout = config('services.baileys_gateway.timeout', 15);
    }

    public function health(): array
    {
        return $this->get('/health', false);
    }

    public function createDevice(string $sessionName): array
    {
        return $this->post('/devices', ['session_name' => $sessionName]);
    }

    public function deleteDevice(string $sessionName): array
    {
        return $this->delete("/devices/{$sessionName}");
    }

    public function connect(string $sessionName): array
    {
        return $this->post("/devices/{$sessionName}/connect");
    }

    public function disconnect(string $sessionName): array
    {
        return $this->post("/devices/{$sessionName}/disconnect");
    }

    public function logout(string $sessionName): array
    {
        return $this->post("/devices/{$sessionName}/logout");
    }

    public function getStatus(string $sessionName): array
    {
        return $this->get("/devices/{$sessionName}/status");
    }

    public function getQr(string $sessionName): array
    {
        return $this->get("/devices/{$sessionName}/qr");
    }

    public function listDevices(): array
    {
        return $this->get('/devices');
    }

    public function sendText(string $sessionName, string $phone, string $message): array
    {
        return $this->post('/messages/send-text', [
            'session' => $sessionName,
            'phone' => $phone,
            'text' => $message,
        ]);
    }

    public function sendImage(string $sessionName, string $phone, string $imageUrl, ?string $caption = null): array
    {
        return $this->post('/messages/send-image', [
            'session' => $sessionName,
            'phone' => $phone,
            'image_url' => $imageUrl,
            'caption' => $caption ?? '',
        ]);
    }

    public function sendDocument(string $sessionName, string $phone, string $documentUrl, ?string $fileName = null): array
    {
        return $this->post('/messages/send-document', [
            'session' => $sessionName,
            'phone' => $phone,
            'document_url' => $documentUrl,
            'file_name' => $fileName ?? 'document',
        ]);
    }

    protected function request(string $method, string $endpoint, ?array $data = null, ?int $timeout = null, bool $auth = true): array
    {
        $url = $this->baseUrl.$endpoint;

        $headers = [
            'Content-Type' => 'application/json',
        ];

        if ($auth && $this->apiToken) {
            $headers['Authorization'] = 'Bearer '.$this->apiToken;
        }

        try {
            $http = Http::timeout($timeout ?? $this->timeout)
                ->withHeaders($headers);

            $response = match ($method) {
                'get' => $http->get($url),
                'post' => $http->post($url, $data),
                'delete' => $http->delete($url),
                default => $http->get($url),
            };

            $body = $response->json() ?? [];

            if ($response->failed()) {
                $error = $body['error'] ?? $response->body();

                Log::error('Baileys Gateway request failed', [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'error' => $error,
                ]);

                return [
                    'success' => false,
                    'error' => $error,
                ];
            }

            return [
                'success' => true,
                'data' => $body['data'] ?? $body,
            ];
        } catch (Exception $e) {
            Log::error('Baileys Gateway connection failed', [
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function get(string $endpoint, bool $auth = true, ?int $timeout = null): array
    {
        return $this->request('get', $endpoint, null, $timeout, $auth);
    }

    protected function post(string $endpoint, array $data = [], ?int $timeout = null, bool $auth = true): array
    {
        return $this->request('post', $endpoint, $data, $timeout, $auth);
    }

    protected function delete(string $endpoint, ?int $timeout = null, bool $auth = true): array
    {
        return $this->request('delete', $endpoint, null, $timeout, $auth);
    }
}
