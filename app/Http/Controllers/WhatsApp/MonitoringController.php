<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class MonitoringController extends Controller
{
    protected function getGatewayUrl()
    {
        return config('services.baileys_gateway.base_url', 'http://localhost:3001');
    }

    protected function getApiToken()
    {
        return config('services.baileys_gateway.api_token', '');
    }

    public function index()
    {
        return view('whatsapp.monitoring');
    }

    public function status()
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['Authorization' => 'Bearer '.$this->getApiToken()])
                ->get($this->getGatewayUrl().'/monitoring/status');

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'success' => false,
                'error' => 'Gateway not responding',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function overview()
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => 'Bearer '.$this->getApiToken()])
                ->get($this->getGatewayUrl().'/monitoring/overview');

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'success' => false,
                'error' => 'Gateway not responding',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function statistics($sessionName)
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['Authorization' => 'Bearer '.$this->getApiToken()])
                ->get($this->getGatewayUrl()."/monitoring/statistics/{$sessionName}");

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'success' => false,
                'error' => 'Gateway not responding',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function history($sessionName)
    {
        try {
            $limit = request()->query('limit', 100);

            $response = Http::timeout(5)
                ->withHeaders(['Authorization' => 'Bearer '.$this->getApiToken()])
                ->get($this->getGatewayUrl()."/monitoring/history/{$sessionName}", [
                    'limit' => $limit,
                ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'success' => false,
                'error' => 'Gateway not responding',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function alerts()
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['Authorization' => 'Bearer '.$this->getApiToken()])
                ->get($this->getGatewayUrl().'/monitoring/alerts');

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'success' => false,
                'error' => 'Gateway not responding',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function queue($sessionName = null)
    {
        try {
            $url = $sessionName
                ? $this->getGatewayUrl()."/monitoring/queue/{$sessionName}"
                : $this->getGatewayUrl().'/monitoring/queue';

            $response = Http::timeout(5)
                ->withHeaders(['Authorization' => 'Bearer '.$this->getApiToken()])
                ->get($url);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'success' => false,
                'error' => 'Gateway not responding',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function reconnect($sessionName)
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => 'Bearer '.$this->getApiToken()])
                ->post($this->getGatewayUrl()."/monitoring/reconnect/{$sessionName}");

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Reconnect initiated',
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to reconnect',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function backup($sessionName)
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => 'Bearer '.$this->getApiToken()])
                ->post($this->getGatewayUrl()."/monitoring/backup/{$sessionName}");

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Backup created successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to create backup',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function restore($sessionName)
    {
        try {
            $backupIndex = request()->input('backupIndex', 0);

            $response = Http::timeout(15)
                ->withHeaders(['Authorization' => 'Bearer '.$this->getApiToken()])
                ->post($this->getGatewayUrl()."/monitoring/restore/{$sessionName}", [
                    'backupIndex' => $backupIndex,
                ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Session restored and reconnecting',
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to restore session',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
