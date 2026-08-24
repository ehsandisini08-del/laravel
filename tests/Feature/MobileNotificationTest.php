<?php

use App\Enums\InvoiceStatus;
use App\Enums\ServiceStatus;
use App\Models\Area;
use App\Models\Customer;
use App\Models\DeviceToken;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\PppProfile;
use App\Models\PppSecret;
use App\Models\Router;
use App\Models\User;
use App\Notifications\CustomerIsolatedNotification;
use App\Notifications\InvoiceOverdueNotification;
use App\Notifications\NewInvoiceNotification;
use App\Notifications\NewPaymentNotification;
use App\Notifications\PaymentReceivedNotification;
use App\Services\Billing\AutoIsolationService;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\PaymentService;
use App\Services\Mikrotik\PPPActiveService;
use App\Services\Mikrotik\PPPSecretService as MikrotikPPPSecretService;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->router = Router::factory()->create(['status' => 'online']);
    $this->area = Area::factory()->create();
    $this->profile = PppProfile::factory()->forRouter($this->router)->create();
    $this->package = Package::factory()->create([
        'router_id' => $this->router->id,
        'ppp_profile_id' => $this->profile->id,
    ]);

    $this->customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'service_status' => ServiceStatus::Active,
        'status' => 'Active',
    ]);
});

test('payment marks invoice paid and sends push to customer and admins', function () {
    Notification::fake();

    $admin = User::factory()->create();
    DeviceToken::create([
        'user_type' => DeviceToken::TYPE_ADMIN,
        'user_id' => $admin->id,
        'token' => 'fcm-admin-payment',
    ]);

    $invoice = Invoice::factory()->create([
        'customer_id' => $this->customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->router->id,
        'status' => InvoiceStatus::Unpaid,
    ]);

    app(PaymentService::class)->markAsPaid($invoice);

    Notification::assertSentTo($this->customer, PaymentReceivedNotification::class);
    Notification::assertSentTo($admin, NewPaymentNotification::class);
});

test('marking overdue sends push to the customer', function () {
    Notification::fake();

    Invoice::factory()->create([
        'customer_id' => $this->customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->router->id,
        'status' => InvoiceStatus::Unpaid,
        'due_date' => now()->subDays(2),
    ]);

    app(InvoiceService::class)->markOverdue();

    Notification::assertSentTo($this->customer, InvoiceOverdueNotification::class);
});

test('auto isolation sends push to the customer', function () {
    Notification::fake();

    $pppSecret = PppSecret::factory()->create([
        'router_id' => $this->router->id,
        'mikrotik_id' => '*2D',
        'disabled' => false,
    ]);
    $this->customer->update(['ppp_secret_id' => $pppSecret->id]);

    Invoice::factory()->create([
        'customer_id' => $this->customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->router->id,
        'billing_month' => now()->month,
        'billing_year' => now()->year,
        'isolation_day' => 1,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $mock = Mockery::mock(MikrotikPPPSecretService::class);
    $mock->shouldReceive('disableSecret')
        ->once()
        ->with('*2D')
        ->andReturn(['success' => true, 'message' => 'ok']);
    app()->bind(MikrotikPPPSecretService::class, fn () => $mock);

    $activeMock = Mockery::mock(PPPActiveService::class);
    $activeMock->shouldReceive('getActiveConnections')->once()->andReturn([]);
    app()->bind(PPPActiveService::class, fn () => $activeMock);

    app(AutoIsolationService::class)->disableExpiredCustomers();

    Notification::assertSentTo($this->customer, CustomerIsolatedNotification::class);
});

test('push does not break when firebase credentials are missing', function () {
    $this->customer->update(['service_status' => ServiceStatus::Active]);

    $invoice = Invoice::factory()->create([
        'customer_id' => $this->customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->router->id,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $result = app(PaymentService::class)->markAsPaid($invoice);

    expect($result['success'])->toBeTrue()
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
});

test('generating invoice sends push notification to customer', function () {
    Notification::fake();

    $invoice = app(InvoiceService::class)->generateForCustomer($this->customer, 11, 2026);

    expect($invoice)->not->toBeNull();
    Notification::assertSentTo($this->customer, NewInvoiceNotification::class);
});

test('new invoice notification formats fcm message with customer channel and string-only data payload', function () {
    $invoice = Invoice::factory()->create([
        'customer_id' => $this->customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->router->id,
        'billing_month' => 11,
        'billing_year' => 2026,
        'amount' => 150000,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $notification = new NewInvoiceNotification($invoice);
    $fcmMessage = $notification->toFcm($this->customer);

    $data = $fcmMessage->data;
    expect($data)->toBeArray();
    expect($data['type'])->toBe('new_invoice');
    expect($data['invoice_id'])->toBe((string) $invoice->id);
    expect($data['amount'])->toBe((string) $invoice->amount);

    foreach ($data as $key => $value) {
        expect(is_string($key))->toBeTrue();
        expect(is_string($value))->toBeTrue();
    }

    $messageArray = $fcmMessage->toArray();
    expect($messageArray['android']['notification']['channel_id'])->toBe('billnet_customer');
});
