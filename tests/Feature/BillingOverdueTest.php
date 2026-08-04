<?php

use App\Enums\CustomerStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ServiceStatus;
use App\Models\Area;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Router;
use App\Services\Billing\InvoiceService;

beforeEach(function () {
    $this->router = Router::factory()->create();
    $this->area = Area::factory()->create();
    $this->package = Package::factory()->create([
        'router_id' => $this->router->id,
        'price' => 150000,
    ]);

    $this->customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'service_status' => ServiceStatus::Active,
        'status' => CustomerStatus::Active->value,
    ]);

    $this->service = app(InvoiceService::class);
});

test('markOverdue marks past due unpaid invoices as overdue and updates customer status', function () {
    $invoice = Invoice::factory()->create([
        'customer_id' => $this->customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->router->id,
        'status' => InvoiceStatus::Unpaid,
        'due_date' => now()->subDays(2),
    ]);

    $count = $this->service->markOverdue();

    expect($count)->toBe(1);
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Overdue);
    expect($this->customer->fresh()->service_status)->toBe(ServiceStatus::Overdue);
});

test('markOverdue does not touch paid invoices', function () {
    $invoice = Invoice::factory()->create([
        'customer_id' => $this->customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->router->id,
        'status' => InvoiceStatus::Paid,
        'due_date' => now()->subDays(2),
    ]);

    $count = $this->service->markOverdue();

    expect($count)->toBe(0);
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
    expect($this->customer->fresh()->service_status)->toBe(ServiceStatus::Active);
});

test('markOverdue does not change isolated customer service status', function () {
    $this->customer->update(['service_status' => ServiceStatus::Isolated]);

    $invoice = Invoice::factory()->create([
        'customer_id' => $this->customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->router->id,
        'status' => InvoiceStatus::Unpaid,
        'due_date' => now()->subDays(2),
    ]);

    $count = $this->service->markOverdue();

    expect($count)->toBe(1);
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Overdue);
    expect($this->customer->fresh()->service_status)->toBe(ServiceStatus::Isolated);
});

test('generateAllForMonth creates invoice for active customers', function () {
    $result = $this->service->generateAllForMonth(8, 2026);

    expect($result['generated'])->toBe(1);

    $this->assertDatabaseHas('invoices', [
        'customer_id' => $this->customer->id,
        'billing_month' => 8,
        'billing_year' => 2026,
        'status' => InvoiceStatus::Unpaid->value,
    ]);
});
