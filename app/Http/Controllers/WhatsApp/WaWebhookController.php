<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WaDevice;
use App\Models\WaMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WaWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $payload = $request->all();

        $webhookSecret = config('services.baileys_gateway.webhook_secret');
        $incomingSecret = $request->header('X-Webhook-Secret');

        if ($webhookSecret && $incomingSecret !== $webhookSecret) {
            Log::warning('WhatsApp webhook: invalid secret');

            return response()->json(['status' => 'error', 'message' => 'Invalid webhook secret'], 403);
        }

        Log::info('WhatsApp webhook received', ['payload' => $payload]);

        $event = $payload['event'] ?? null;
        $sessionName = $payload['session'] ?? null;

        if (! $sessionName) {
            return response()->json(['status' => 'error', 'message' => 'Missing session name'], 400);
        }

        $device = WaDevice::where('session_name', $sessionName)->first();

        if (! $device) {
            return response()->json(['status' => 'error', 'message' => 'Device not found'], 404);
        }

        match ($event) {
            'connected' => $this->handleConnected($device, $payload),
            'disconnected' => $this->handleDisconnected($device, $payload),
            'qr_updated' => $this->handleQrUpdated($device, $payload),
            'message_received' => $this->handleMessageReceived($device, $payload),
            'message_sent' => $this->handleMessageSent($device, $payload),
            default => Log::warning('Unknown webhook event', ['event' => $event, 'payload' => $payload]),
        };

        return response()->json(['status' => 'ok']);
    }

    protected function handleConnected(WaDevice $device, array $payload): void
    {
        $data = $payload['data'] ?? [];

        $device->update([
            'status' => 'connected',
            'phone_number' => $data['phone_number'] ?? $device->phone_number,
            'profile_name' => $data['profile_name'] ?? $device->profile_name,
            'connected_at' => now(),
            'disconnected_at' => null,
            'last_seen' => now(),
        ]);
    }

    protected function handleDisconnected(WaDevice $device, array $payload): void
    {
        $data = $payload['data'] ?? [];
        $reason = $data['reason'] ?? 'connection_lost';

        $status = $reason === 'logged_out' ? 'logged_out' : 'disconnected';

        $updateData = [
            'status' => $status,
            'disconnected_at' => now(),
            'last_seen' => now(),
        ];

        if ($reason === 'logged_out') {
            $updateData['phone_number'] = null;
            $updateData['profile_name'] = null;
        }

        $device->update($updateData);
    }

    protected function handleQrUpdated(WaDevice $device, array $payload): void
    {
        $device->update([
            'status' => 'qr_waiting',
            'last_seen' => now(),
        ]);
    }

    protected function handleMessageReceived(WaDevice $device, array $payload): void
    {
        $data = $payload['data'] ?? [];
        $message = $data['message'] ?? [];
        $key = $message['key'] ?? [];
        $remoteJid = $key['remoteJid'] ?? '';
        $phone = preg_replace('/@.*$/', '', $remoteJid);

        if ($key['fromMe'] ?? false) {
            return;
        }

        $msgContent = $message['message'] ?? null;
        $messageContent = '';

        if (is_array($msgContent)) {
            $messageContent = $msgContent['conversation']
                ?? $msgContent['extendedTextMessage']['text']
                ?? json_encode($msgContent);
        } elseif (is_string($msgContent)) {
            $messageContent = $msgContent;
        }

        WaMessage::create([
            'device_id' => $device->id,
            'phone' => $phone,
            'type' => 'text',
            'direction' => 'incoming',
            'message' => $messageContent,
            'status' => 'received',
            'baileys_message_id' => $key['id'] ?? null,
        ]);
    }

    protected function handleMessageSent(WaDevice $device, array $payload): void
    {
        $data = $payload['data'] ?? [];
        $baileysMessageId = $data['message_id'] ?? null;

        if (! $baileysMessageId) {
            return;
        }

        $message = WaMessage::where('baileys_message_id', $baileysMessageId)->first();

        if (! $message) {
            return;
        }

        $message->update([
            'status' => $data['status'] ?? 'sent',
            'sent_at' => now(),
        ]);
    }
}
