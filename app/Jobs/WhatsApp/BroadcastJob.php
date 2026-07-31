<?php

namespace App\Jobs\WhatsApp;

use App\Models\Customer;
use App\Models\WaDevice;
use App\Models\WaTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        protected int $deviceId,
        protected ?int $templateId,
        protected string $message,
        protected array $filters = [],
    ) {}

    public function handle(): void
    {
        $device = WaDevice::find($this->deviceId);

        if (! $device || ! $device->isConnected()) {
            Log::error('BroadcastJob: Device not found or not connected');

            return;
        }

        $query = Customer::query();

        if (! empty($this->filters['area_id'])) {
            $query->where('area_id', $this->filters['area_id']);
        }

        if (! empty($this->filters['package_id'])) {
            $query->where('package_id', $this->filters['package_id']);
        }

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        $customers = $query->whereNotNull('phone')->get();

        $template = null;
        if ($this->templateId) {
            $template = WaTemplate::find($this->templateId);
        }

        $total = $customers->count();
        $sent = 0;
        $failed = 0;

        foreach ($customers as $customer) {
            $phone = $customer->phone;

            if (! $phone) {
                $failed++;

                continue;
            }

            $variables = [
                'customer_name' => $customer->name,
                'phone' => $customer->phone,
                'package' => $customer->package?->name ?? '',
                'price' => $customer->package?->price_formatted ?? '',
                'due_date' => (string) $customer->due_day,
                'invoice_number' => '',
                'company' => config('app.name'),
            ];

            SendMessageJob::dispatch(
                $this->deviceId,
                $phone,
                $template ? null : $this->message,
                $this->templateId,
                $variables,
                $customer->id,
            );

            $sent++;
        }

        Log::info('BroadcastJob completed', [
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
        ]);
    }
}
