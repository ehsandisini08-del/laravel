<?php

use App\Enums\InvoiceStatus;
use App\Enums\ServiceStatus;
use App\Models\Area;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\PppSecret;
use App\Models\Router;
use App\Services\Billing\AutoIsolationService;
use App\Services\Mikrotik\PPPSecretService as MikrotikPPPSecretService;
use Carbon\Carbon;

beforeEach(function () {
    $this->router = Router::factory()->create(['status' => 'online']);
    $this->area = Area::factory()->create();
    $this->package = Package::factory()->create(['router_id' => $this->router->id]);

    $this->customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'service_status' => ServiceStatus::Active,
        'status' => 'active',
    ]);

    $this->pppSecret = PppSecret::factory()->create([
        'router_id' => $this->router->id,
        'mikrotik_id' => '*2D',
        'disabled' => false,
    ]);

    $this->customer->update(['ppp_secret_id' => $this->pppSecret->id]);

    $this->service = app(AutoIsolationService::class);
});

it('isolates an active customer past their isolation day within the billing month', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 20, 12));

    Invoice::factory()->create([
        'customer_id' => $this->customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->router->id,
        'billing_month' => 8,
        'billing_year' => 2026,
        'isolation_day' => 15,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $mock = Mockery::mock(MikrotikPPPSecretService::class);
    $mock->shouldReceive('disableSecret')
        ->once()
        ->with('*2D')
        ->andReturn(['success' => true, 'message' => 'ok']);
    app()->bind(MikrotikPPPSecretService::class, fn () => $mock);

    $result = $this->service->disableExpiredCustomers();

    expect($result['disabled'])->toBe(1)
        ->and($result['failed'])->toBe(0)
        ->and($this->customer->fresh()->service_status)->toBe(ServiceStatus::Isolated)
        ->and($this->pppSecret->fresh()->disabled)->toBeTrue();

    $this->assertDatabaseHas('isolation_logs', [
        'customer_id' => $this->customer->id,
        'action' => 'disabled',
        'status' => 'success',
    ]);

    Carbon::setTestNow();
});

it('does not isolate a customer before their isolation day', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 10, 12));

    Invoice::factory()->create([
        'customer_id' => $this->customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->router->id,
        'billing_month' => 8,
        'billing_year' => 2026,
        'isolation_day' => 15,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $result = $this->service->disableExpiredCustomers();

    expect($result['disabled'])->toBe(0)
        ->and($this->customer->fresh()->service_status)->toBe(ServiceStatus::Active);

    Carbon::setTestNow();
});

it('does not revive isolation for an unpaid invoice from a previous billing month', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 20, 12));

    Invoice::factory()->create([
        'customer_id' => $this->customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->router->id,
        'billing_month' => 7,
        'billing_year' => 2026,
        'isolation_day' => 15,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $result = $this->service->disableExpiredCustomers();

    expect($result['disabled'])->toBe(0)
        ->and($this->customer->fresh()->service_status)->toBe(ServiceStatus::Active);

    Carbon::setTestNow();
});

it('skips customers without an isolation day', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 20, 12));

    Invoice::factory()->create([
        'customer_id' => $this->customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->router->id,
        'billing_month' => 8,
        'billing_year' => 2026,
        'isolation_day' => null,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $result = $this->service->disableExpiredCustomers();

    expect($result['disabled'])->toBe(0)
        ->and($this->customer->fresh()->service_status)->toBe(ServiceStatus::Active);

    Carbon::setTestNow();
});
