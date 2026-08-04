<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ServiceStatus;
use App\Models\Area;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\IsolationLog;
use App\Models\Package;
use App\Models\Payment;
use App\Models\PppSecret;
use App\Models\Router;
use App\Models\User;
use App\Services\Billing\PaymentService;
use App\Services\Mikrotik\PPPSecretService as MikrotikPPPSecretService;

beforeEach(function () {
    $this->router = Router::factory()->create(['status' => 'online']);
    $this->area = Area::factory()->create();
    $this->package = Package::factory()->create([
        'router_id' => $this->router->id,
    ]);

    $this->customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'service_status' => ServiceStatus::Active,
    ]);

    $this->pppSecret = PppSecret::factory()->create([
        'router_id' => $this->router->id,
        'mikrotik_id' => '*2D',
        'disabled' => false,
    ]);

    $this->customer->update(['ppp_secret_id' => $this->pppSecret->id]);

    $this->invoice = Invoice::factory()->create([
        'customer_id' => $this->customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->router->id,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $this->service = app(PaymentService::class);
});

test('marks unpaid invoice as paid', function () {
    $result = $this->service->markAsPaid($this->invoice);

    expect($result['success'])->toBeTrue();
    expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
    expect($this->invoice->fresh()->paid_at)->not->toBeNull();

    $this->assertDatabaseHas('billing_logs', [
        'invoice_id' => $this->invoice->id,
        'action' => 'invoice_paid',
    ]);
});

test('marks unpaid invoice as paid and records a cash payment', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $result = $this->service->markAsPaid($this->invoice, [
        'method' => 'cash',
        'paid_by' => $user,
    ]);

    expect($result['success'])->toBeTrue();
    expect($this->invoice->fresh()->payment_method)->toBe(PaymentMethod::Cash);

    $payment = Payment::where('invoice_id', $this->invoice->id)->first();

    expect($payment)->not->toBeNull()
        ->and($payment->payment_method)->toBe(PaymentMethod::Cash)
        ->and($payment->gateway_provider)->toBe('manual')
        ->and($payment->status)->toBe(PaymentStatus::Success)
        ->and($payment->paid_by_user_id)->toBe($user->id);
});

test('does not allow double payment', function () {
    $this->service->markAsPaid($this->invoice);

    $result = $this->service->markAsPaid($this->invoice->fresh());

    expect($result['success'])->toBeFalse();
});

test('reactivates overdue customer with enabled ppp secret without touching MikroTik', function () {
    $this->customer->update(['service_status' => ServiceStatus::Overdue]);

    $result = $this->service->markAsPaid($this->invoice);

    expect($result['success'])->toBeTrue();
    expect($result['reactivated'])->toBeTrue();
    expect($this->customer->fresh()->service_status)->toBe(ServiceStatus::Active);
    expect($this->pppSecret->fresh()->disabled)->toBeFalse();
    expect(IsolationLog::where('invoice_id', $this->invoice->id)->count())->toBe(0);

    $this->assertDatabaseHas('billing_logs', [
        'invoice_id' => $this->invoice->id,
        'action' => 'customer_reactivated',
    ]);
});

test('re-enables disabled ppp secret on MikroTik when isolated customer pays', function () {
    $this->customer->update(['service_status' => ServiceStatus::Isolated]);
    $this->pppSecret->update(['disabled' => true]);

    $mock = Mockery::mock(MikrotikPPPSecretService::class);
    $mock->shouldReceive('enableSecret')
        ->once()
        ->with('*2D')
        ->andReturn(['success' => true, 'message' => 'ok']);
    app()->bind(MikrotikPPPSecretService::class, fn () => $mock);

    $result = $this->service->markAsPaid($this->invoice);

    expect($result['success'])->toBeTrue();
    expect($result['reactivated'])->toBeTrue();
    expect($this->customer->fresh()->service_status)->toBe(ServiceStatus::Active);
    expect($this->pppSecret->fresh()->disabled)->toBeFalse();

    $this->assertDatabaseHas('isolation_logs', [
        'invoice_id' => $this->invoice->id,
        'action' => 'enabled',
        'status' => 'success',
    ]);
});

test('keeps invoice paid but customer isolated when MikroTik re-enable fails', function () {
    $this->customer->update(['service_status' => ServiceStatus::Isolated]);
    $this->pppSecret->update(['disabled' => true]);

    $mock = Mockery::mock(MikrotikPPPSecretService::class);
    $mock->shouldReceive('enableSecret')
        ->once()
        ->andReturn(['success' => false, 'message' => 'connection refused']);
    app()->bind(MikrotikPPPSecretService::class, fn () => $mock);

    $result = $this->service->markAsPaid($this->invoice);

    expect($result['success'])->toBeTrue();
    expect($result['reactivated'])->toBeFalse();
    expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
    expect($this->customer->fresh()->service_status)->toBe(ServiceStatus::Isolated);
    expect($this->pppSecret->fresh()->disabled)->toBeTrue();

    $this->assertDatabaseHas('isolation_logs', [
        'invoice_id' => $this->invoice->id,
        'action' => 'enabled',
        'status' => 'failed',
    ]);
});

test('keeps invoice paid but customer isolated when router is offline', function () {
    $this->customer->update(['service_status' => ServiceStatus::Isolated]);
    $this->pppSecret->update(['disabled' => true]);
    $this->router->update(['status' => 'offline']);

    $result = $this->service->markAsPaid($this->invoice);

    expect($result['success'])->toBeTrue();
    expect($result['reactivated'])->toBeFalse();
    expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
    expect($this->customer->fresh()->service_status)->toBe(ServiceStatus::Isolated);

    $this->assertDatabaseHas('isolation_logs', [
        'invoice_id' => $this->invoice->id,
        'action' => 'enabled',
        'status' => 'failed',
    ]);
});
