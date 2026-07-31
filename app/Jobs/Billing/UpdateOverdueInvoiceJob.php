<?php

namespace App\Jobs\Billing;

use App\Services\Billing\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateOverdueInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(InvoiceService $invoiceService): void
    {
        Log::info('UpdateOverdueInvoiceJob started');

        $count = $invoiceService->markOverdue();

        Log::info('UpdateOverdueInvoiceJob completed', ['marked' => $count]);
    }
}
