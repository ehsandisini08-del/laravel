<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\Setting;
use App\Models\WaDevice;
use App\Services\WhatsApp\WhatsAppGatewayService;
use App\Support\Currency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendReminderMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(
        public int $invoiceId,
        public int $daysBefore,
    ) {}

    public function handle(WhatsAppGatewayService $whatsApp): void
    {
        $invoice = Invoice::with('customer')->find($this->invoiceId);

        if (! $invoice || ! $invoice->customer) {
            return;
        }

        $reminder = InvoiceReminder::where('invoice_id', $this->invoiceId)
            ->where('days_before', $this->daysBefore)
            ->first();

        if (! $reminder || $reminder->status === InvoiceReminder::STATUS_SENT) {
            return;
        }

        if ($invoice->isPaid()) {
            $this->markSent($reminder);

            return;
        }

        $device = WaDevice::where('status', 'connected')->latest('id')->first();

        if (! $device) {
            Log::warning('Reminder tidak terkirim: tidak ada perangkat terhubung', [
                'invoice_id' => $this->invoiceId,
            ]);

            $reminder->delete();

            return;
        }

        $phone = $this->normalizePhone($invoice->customer->phone);

        if (! $phone) {
            Log::warning('Reminder tidak terkirim: nomor tidak valid', [
                'invoice_id' => $this->invoiceId,
            ]);

            $reminder->delete();

            return;
        }

        $message = $this->buildMessage($invoice);

        $waMessage = $whatsApp->sendMessage($device, $phone, $message, 'reminder', $invoice->customer_id);

        if ($waMessage->status === 'failed') {
            Log::warning('Reminder gagal dikirim via WhatsApp', [
                'invoice_id' => $this->invoiceId,
                'device_id' => $device->id,
            ]);

            $reminder->delete();

            return;
        }

        $this->markSent($reminder);
    }

    protected function buildMessage(Invoice $invoice): string
    {
        $company = Setting::get('company_name') ?: config('app.name');

        $parts = [
            "Halo {$invoice->customer->name},",
            '',
            "Tagihan internet Anda untuk periode {$invoice->billing_period} sebesar ".Currency::format((float) $invoice->amount).' akan jatuh tempo pada '.$invoice->due_date?->format('d M Y').'.',
            'Mohon lakukan pembayaran sebelum jatuh tempo. Terima kasih.',
            '',
            $company,
        ];

        return implode("\n", $parts);
    }

    protected function normalizePhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $phone = preg_replace('/\D/', '', $phone);

        if (strlen($phone) < 9) {
            return null;
        }

        if (str_starts_with($phone, '0')) {
            return '62'.substr($phone, 1);
        }

        return str_starts_with($phone, '62') ? $phone : '62'.$phone;
    }

    protected function markSent(InvoiceReminder $reminder): void
    {
        $reminder->update([
            'status' => InvoiceReminder::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }
}
