<?php

namespace App\Services\WhatsApp;

use App\Models\WaDevice;
use App\Models\WaMessage;
use App\Models\WaTemplate;
use Exception;
use Illuminate\Support\Facades\Log;

class WhatsAppGatewayService
{
    public function __construct(
        protected BaileysGatewayService $baileysGateway,
    ) {}

    public function createDevice(string $deviceName, string $sessionName): WaDevice
    {
        $result = $this->baileysGateway->createDevice($sessionName);

        $qrCode = null;
        $status = 'qr_waiting';

        if ($result['success']) {
            $data = $result['data'] ?? [];
            $qrCode = $data['qr_code'] ?? null;
            $status = $qrCode ? 'qr_waiting' : 'connecting';
        } else {
            Log::warning('Baileys Gateway unreachable, creating device locally', [
                'device_name' => $deviceName,
                'session_name' => $sessionName,
                'error' => $result['error'] ?? null,
            ]);
            $status = 'disconnected';
        }

        return WaDevice::create([
            'device_name' => $deviceName,
            'session_name' => $sessionName,
            'status' => $status,
        ]);
    }

    public function deleteDevice(WaDevice $device): void
    {
        $this->baileysGateway->deleteDevice($device->session_name);

        $device->delete();
    }

    public function generateQr(WaDevice $device): ?string
    {
        $result = $this->baileysGateway->connect($device->session_name);

        if ($result['success'] === false) {
            Log::warning('Baileys Gateway connect failed', [
                'session_name' => $device->session_name,
                'error' => $result['error'] ?? null,
            ]);
        }

        $result = $this->baileysGateway->getQr($device->session_name);

        if (! $result['success']) {
            throw new Exception(
                'Tidak dapat generate QR. Pastikan Baileys Gateway sudah berjalan. '
                .$result['error']
            );
        }

        $data = $result['data'] ?? [];
        $qrCode = $this->extractQrBase64($data);

        if ($qrCode) {
            $device->update([
                'status' => 'qr_waiting',
            ]);
        }

        return $qrCode;
    }

    public function getQr(WaDevice $device): ?string
    {
        $result = $this->baileysGateway->getQr($device->session_name);

        if (! $result['success']) {
            return null;
        }

        $data = $result['data'] ?? [];

        return $this->extractQrBase64($data);
    }

    public function refreshStatus(WaDevice $device): string
    {
        $result = $this->baileysGateway->getStatus($device->session_name);

        if (! $result['success']) {
            return $device->status;
        }

        $data = $result['data'] ?? [];
        $newStatus = $data['status'] ?? $device->status;
        $now = now();

        $statusMap = [
            'connected' => 'connected',
            'connecting' => 'connecting',
            'qr_waiting' => 'qr_waiting',
            'disconnected' => 'disconnected',
            'logged_out' => 'logged_out',
        ];

        $mappedStatus = $statusMap[$newStatus] ?? $newStatus;

        $updateData = ['status' => $mappedStatus];

        if ($mappedStatus === 'connected') {
            $updateData['connected_at'] = $now;
            $updateData['disconnected_at'] = null;
            $updateData['phone_number'] = $data['phone_number'] ?? $device->phone_number;
            $updateData['profile_name'] = $data['profile_name'] ?? $device->profile_name;
        } elseif ($mappedStatus === 'disconnected' && $device->isConnected()) {
            $updateData['disconnected_at'] = $now;
        }

        $updateData['last_seen'] = $now;

        $device->update($updateData);

        return $mappedStatus;
    }

    public function disconnect(WaDevice $device): void
    {
        $this->baileysGateway->disconnect($device->session_name);

        $device->update([
            'status' => 'disconnected',
            'disconnected_at' => now(),
            'last_seen' => now(),
        ]);
    }

    public function logout(WaDevice $device): void
    {
        $this->baileysGateway->logout($device->session_name);

        $device->update([
            'status' => 'logged_out',
            'phone_number' => null,
            'profile_name' => null,
            'profile_picture' => null,
            'connected_at' => null,
            'disconnected_at' => now(),
            'last_seen' => now(),
        ]);
    }

    public function sendMessage(WaDevice $device, string $phone, string $message, ?string $type = 'text', ?int $customerId = null): WaMessage
    {
        $result = $this->baileysGateway->sendText($device->session_name, $phone, $message);

        $status = $result['success'] ? 'sent' : 'failed';
        $baileysMessageId = $result['data']['message_id'] ?? null;

        $waMessage = WaMessage::create([
            'device_id' => $device->id,
            'customer_id' => $customerId,
            'phone' => $phone,
            'type' => $type,
            'direction' => 'outgoing',
            'message' => $message,
            'status' => $status,
            'baileys_message_id' => $baileysMessageId,
            'sent_at' => $result['success'] ? now() : null,
        ]);

        if (! $result['success']) {
            Log::error('Failed to send WhatsApp message', [
                'device_id' => $device->id,
                'phone' => $phone,
                'error' => $result['error'] ?? 'Unknown',
            ]);
        }

        return $waMessage;
    }

    public function sendTemplate(WaDevice $device, string $phone, WaTemplate $template, array $variables = [], ?int $customerId = null): WaMessage
    {
        $message = $this->parseTemplate($template->content, $variables);

        return $this->sendMessage($device, $phone, $message, 'template', $customerId);
    }

    public function parseTemplate(string $content, array $variables = []): string
    {
        $replacements = [
            '{{customer_name}}' => $variables['customer_name'] ?? '',
            '{{phone}}' => $variables['phone'] ?? '',
            '{{package}}' => $variables['package'] ?? '',
            '{{price}}' => $variables['price'] ?? '',
            '{{due_date}}' => $variables['due_date'] ?? '',
            '{{invoice_number}}' => $variables['invoice_number'] ?? '',
            '{{company}}' => $variables['company'] ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    public function syncDevices(): int
    {
        $devices = WaDevice::all();
        $synced = 0;

        foreach ($devices as $device) {
            try {
                $this->refreshStatus($device);
                $synced++;
            } catch (Exception $e) {
                Log::error('Failed to sync device', [
                    'device_id' => $device->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $synced;
    }

    public function getDashboardStats(): array
    {
        return [
            'connected' => WaDevice::where('status', 'connected')->count(),
            'disconnected' => WaDevice::where('status', 'disconnected')->count(),
            'qr_waiting' => WaDevice::where('status', 'qr_waiting')->count(),
            'logged_out' => WaDevice::where('status', 'logged_out')->count(),
            'total_messages_today' => WaMessage::whereDate('created_at', today())->count(),
            'total_sent' => WaMessage::where('direction', 'outgoing')->count(),
            'total_failed' => WaMessage::where('status', 'failed')->count(),
            'total_delivered' => WaMessage::where('status', 'delivered')->count(),
        ];
    }

    public function checkGatewayHealth(): bool
    {
        $result = $this->baileysGateway->health();

        return $result['success'] ?? false;
    }

    protected function extractQrBase64(array $data): ?string
    {
        $base64 = $data['qr_code'] ?? null;

        if (! is_string($base64)) {
            return null;
        }

        if (str_starts_with($base64, 'data:')) {
            return substr($base64, strpos($base64, ',') + 1);
        }

        return $base64;
    }
}
