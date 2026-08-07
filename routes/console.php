<?php

use App\Jobs\Billing\DisableCustomerJob;
use App\Jobs\Billing\GenerateInvoiceJob;
use App\Jobs\Billing\UpdateOverdueInvoiceJob;
use App\Jobs\InvoiceReminderJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('mikrotik:sync')->everyFiveMinutes();
Schedule::command('logs:cleanup')->daily();

Schedule::job(new GenerateInvoiceJob)->monthlyOn(1, '00:00');
Schedule::job(new UpdateOverdueInvoiceJob)->dailyAt('00:00');
Schedule::job(new DisableCustomerJob)->dailyAt('00:00');
Schedule::job(new InvoiceReminderJob)->dailyAt('09:00');
