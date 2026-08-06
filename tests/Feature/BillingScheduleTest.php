<?php

use App\Jobs\Billing\GenerateInvoiceJob;
use Illuminate\Support\Facades\Schedule;

test('invoice generation is scheduled on the first day of each month at midnight', function () {
    $event = collect(Schedule::events())
        ->first(fn ($event) => $event->description === GenerateInvoiceJob::class);

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 0 1 * *');
});
