<?php

namespace App\Jobs\WhatsApp;

use App\Models\WaDevice;
use App\Models\WaMessage;
use App\Models\WaTemplate;
use App\Services\WhatsApp\BaileysGatewayService;
use App\Services\WhatsApp\WhatsAppGatewayService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(
        protected int $deviceId,
        protected string $phone,
        protected ?string $message = null,
        protected ?int $templateId = null,
        protected array $variables = [],
        protected ?int $customerId = null,
    ) {}

    public function handle(WhatsAppGatewayService $service, BaileysGatewayService $baileysGateway): void
    {
        $device = WaDevice::find($this->deviceId);

        if (! $device) {
            Log::error('SendMessageJob: Device not found', ['device_id' => $this->deviceId]);

            return;
        }

        $state = $baileysGateway->getStatus($device->session_name);
        $stateData = $state['data']['status'] ?? null;
        $isActuallyConnected = $state['success'] && $stateData === 'connected';

        if (! $isActuallyConnected) {
            Log::warning('SendMessageJob: Device not actually connected on Baileys Gateway', [
                'device_id' => $this->deviceId,
                'db_status' => $device->status,
                'gateway_status' => $stateData,
            ]);

            if ($device->isConnected()) {
                $device->update(['status' => 'disconnected']);
            }

            if ($this->attempts() >= $this->tries) {
                WaMessage::create([
                    'device_id' => $this->deviceId,
                    'customer_id' => $this->customerId,
                    'phone' => $this->phone,
                    'type' => 'text',
                    'direction' => 'outgoing',
                    'message' => $this->message ?? '[Template]',
                    'status' => 'failed',
                ]);
            }

            $this->release(30);

            return;
        }

        try {
            if ($this->templateId) {
                $template = WaTemplate::find($this->templateId);

                if ($template) {
                    $service->sendTemplate($device, $this->phone, $template, $this->variables, $this->customerId);
                } else {
                    $service->sendMessage($device, $this->phone, $this->message ?? '', 'text', $this->customerId);
                }
            } else {
                $service->sendMessage($device, $this->phone, $this->message ?? '', 'text', $this->customerId);
            }
        } catch (Exception $e) {
            Log::error('SendMessageJob failed', [
                'device_id' => $this->deviceId,
                'phone' => $this->phone,
                'error' => $e->getMessage(),
            ]);

            if ($this->attempts() >= $this->tries) {
                WaMessage::create([
                    'device_id' => $this->deviceId,
                    'customer_id' => $this->customerId,
                    'phone' => $this->phone,
                    'type' => 'text',
                    'direction' => 'outgoing',
                    'message' => $this->message ?? '[Template]',
                    'status' => 'failed',
                ]);
            }

            throw $e;
        }
    }
}
