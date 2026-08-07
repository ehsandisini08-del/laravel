<?php

use App\Enums\InvoiceStatus;
use App\Jobs\InvoiceReminderJob;
use App\Jobs\SendReminderMessageJob;
use App\Models\Area;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\Package;
use App\Models\Router;
use App\Models\WaDevice;
use App\Services\WhatsApp\BaileysGatewayService;
use App\Services\WhatsApp\WhatsAppGatewayService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->router = Router::factory()->create();
    $this->area = Area::factory()->create();
    $this->package = Package::factory()->create(['router_id' => $this->router->id]);

    $this->customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    Carbon::setTestNow(Carbon::create(2026, 8, 10, 9, 0));
});

afterEach(function () {
    Carbon::setTestNow();
});

function makeDueInvoice(Customer $customer, Package $package, int $daysFromNow): Invoice
{
    return Invoice::factory()->create([
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'router_id' => $customer->router_id,
        'status' => InvoiceStatus::Unpaid,
        'due_date' => now()->addDays($daysFromNow),
    ]);
}

test('invoice reminder job dispatches staggered jobs for invoices near due date', function () {
    Queue::fake();

    WaDevice::factory()->connected()->create();
    makeDueInvoice($this->customer, $this->package, 7);
    makeDueInvoice($this->customer, $this->package, 3);

    (new InvoiceReminderJob)->handle();

    Queue::assertPushed(SendReminderMessageJob::class, 2);

    $delayed = Queue::pushed(SendReminderMessageJob::class)
        ->filter(fn ($job) => $job->delay !== null && $job->delay->isFuture());

    expect($delayed)->toHaveCount(2)
        ->and(InvoiceReminder::count())->toBe(2);
});

test('invoice reminder job does not duplicate reminders', function () {
    Queue::fake();

    WaDevice::factory()->connected()->create();
    makeDueInvoice($this->customer, $this->package, 3);

    (new InvoiceReminderJob)->handle();
    (new InvoiceReminderJob)->handle();

    Queue::assertPushed(SendReminderMessageJob::class, 1);
    expect(InvoiceReminder::count())->toBe(1);
});

test('invoice reminder job skips when no whatsapp device is connected', function () {
    Queue::fake();

    makeDueInvoice($this->customer, $this->package, 3);

    (new InvoiceReminderJob)->handle();

    Queue::assertNothingPushed();
    expect(InvoiceReminder::count())->toBe(0);
});

test('invoice reminder job ignores invoices with other due dates', function () {
    Queue::fake();

    WaDevice::factory()->connected()->create();
    makeDueInvoice($this->customer, $this->package, 5);

    (new InvoiceReminderJob)->handle();

    Queue::assertNothingPushed();
});

test('send reminder job sends whatsapp message and marks reminder as sent', function () {
    $device = WaDevice::factory()->connected()->create();
    $invoice = makeDueInvoice($this->customer, $this->package, 3);

    InvoiceReminder::create([
        'invoice_id' => $invoice->id,
        'days_before' => 3,
        'status' => InvoiceReminder::STATUS_QUEUED,
    ]);

    $this->mock(BaileysGatewayService::class, function ($mock) {
        $mock->shouldReceive('sendText')
            ->once()
            ->andReturn(['success' => true, 'data' => ['message_id' => 'abc123']]);
    });

    (new SendReminderMessageJob($invoice->id, 3))->handle(app(WhatsAppGatewayService::class));

    $reminder = InvoiceReminder::first();

    expect($reminder->status)->toBe(InvoiceReminder::STATUS_SENT)
        ->and($reminder->sent_at)->not->toBeNull();

    $this->assertDatabaseHas('wa_messages', [
        'customer_id' => $this->customer->id,
        'device_id' => $device->id,
        'status' => 'sent',
        'type' => 'reminder',
    ]);
});

test('send reminder job does not send when invoice already paid', function () {
    $invoice = Invoice::factory()->create([
        'customer_id' => $this->customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->customer->router_id,
        'status' => InvoiceStatus::Paid,
    ]);

    InvoiceReminder::create([
        'invoice_id' => $invoice->id,
        'days_before' => 3,
        'status' => InvoiceReminder::STATUS_QUEUED,
    ]);

    (new SendReminderMessageJob($invoice->id, 3))->handle(app(WhatsAppGatewayService::class));

    expect(InvoiceReminder::first()->status)->toBe(InvoiceReminder::STATUS_SENT);
    $this->assertDatabaseCount('wa_messages', 0);
});
