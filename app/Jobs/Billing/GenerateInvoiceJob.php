<?php

namespace App\Jobs\Billing;

use App\Services\Billing\InvoiceService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(
        protected ?int $month = null,
        protected ?int $year = null,
    ) {}

    public function handle(InvoiceService $invoiceService): void
    {
        $now = Carbon::now();
        $month = $this->month ?? $now->month;
        $year = $this->year ?? $now->year;

        Log::info('GenerateInvoiceJob started', ['month' => $month, 'year' => $year]);

        $result = $invoiceService->generateAllForMonth($month, $year);

        Log::info('GenerateInvoiceJob completed', $result);
    }
}
