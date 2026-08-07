<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\Setting;
use App\Models\WaDevice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class InvoiceReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    protected int $maxDaily = 100;

    public function handle(): void
    {
        $days = $this->reminderDays();

        if ($days === []) {
            return;
        }

        if (! WaDevice::where('status', 'connected')->exists()) {
            Log::info('Invoice reminder skipped: tidak ada perangkat WhatsApp terhubung');

            return;
        }

        $today = today();

        $invoices = Invoice::with('customer')
            ->whereIn('status', ['unpaid', 'overdue'])
            ->whereNotNull('due_date')
            ->get();

        $dispatched = 0;
        $delay = 0;

        foreach ($invoices as $invoice) {
            foreach ($days as $n) {
                if ($dispatched >= $this->maxDaily) {
                    Log::warning('Invoice reminder mencapai batas harian', ['max_daily' => $this->maxDaily]);
                    break 2;
                }

                if (! $invoice->due_date?->isSameDay($today->copy()->addDays($n))) {
                    continue;
                }

                if (InvoiceReminder::where('invoice_id', $invoice->id)->where('days_before', $n)->exists()) {
                    continue;
                }

                InvoiceReminder::create([
                    'invoice_id' => $invoice->id,
                    'days_before' => $n,
                    'status' => InvoiceReminder::STATUS_QUEUED,
                ]);

                // Jeda acak antar pesan agar terlihat natural, bukan kirim serentak.
                $delay += random_int(20, 90);

                SendReminderMessageJob::dispatch($invoice->id, $n)
                    ->delay(now()->addSeconds($delay));

                $dispatched++;
            }
        }

        Log::info('Invoice reminder job selesai', [
            'dispatched' => $dispatched,
            'days' => $days,
        ]);
    }

    protected function reminderDays(): array
    {
        $raw = (string) Setting::get('reminder_days_before_due', '7,3,1');

        $days = array_values(array_unique(array_filter(
            array_map('intval', explode(',', $raw)),
            fn ($d) => $d > 0,
        )));

        sort($days);

        return $days;
    }
}
