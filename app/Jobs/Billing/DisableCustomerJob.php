<?php

namespace App\Jobs\Billing;

use App\Services\Billing\AutoIsolationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DisableCustomerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function handle(AutoIsolationService $isolationService): void
    {
        Log::info('DisableCustomerJob started');

        $result = $isolationService->disableExpiredCustomers();

        Log::info('DisableCustomerJob completed', $result);
    }
}
