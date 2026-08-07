<?php

use App\Enums\InvoiceStatus;
use App\Models\Area;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Router;

beforeEach(function () {
    $this->router = Router::factory()->create();
    $this->area = Area::factory()->create();
    $this->package = Package::factory()->create(['router_id' => $this->router->id]);

    $this->customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);
});

function makePortalInvoice(Customer $customer, Package $package, array $overrides = []): Invoice
{
    return Invoice::factory()->create(array_merge([
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'router_id' => $customer->router_id,
    ], $overrides));
}

test('paid invoice shows success page for the owning customer', function () {
    $invoice = makePortalInvoice($this->customer, $this->package, ['status' => InvoiceStatus::Paid]);

    $this->actingAs($this->customer, 'customer');

    $this->get(route('portal.payment.success', $invoice))
        ->assertStatus(200)
        ->assertSee($invoice->invoice_number)
        ->assertSee('Pembayaran Berhasil');
});

test('unpaid invoice shows pending confirmation on success page', function () {
    $invoice = makePortalInvoice($this->customer, $this->package, ['status' => InvoiceStatus::Unpaid]);

    $this->actingAs($this->customer, 'customer');

    $this->get(route('portal.payment.success', $invoice))
        ->assertStatus(200)
        ->assertSee('Menunggu Konfirmasi');
});

test('success page disallows other customers', function () {
    $owner = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $invoice = makePortalInvoice($owner, $this->package);

    $intruder = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $this->actingAs($intruder, 'customer');

    $this->get(route('portal.payment.success', $invoice))->assertForbidden();
});

test('success page resolves invoice from query reference', function () {
    $invoice = makePortalInvoice($this->customer, $this->package, ['status' => InvoiceStatus::Paid]);

    $this->actingAs($this->customer, 'customer');

    $this->get('/portal/payment/success?order_id='.$invoice->invoice_number)
        ->assertStatus(200)
        ->assertSee($invoice->invoice_number);
});

test('success page handles unknown reference gracefully', function () {
    $this->actingAs($this->customer, 'customer');

    $this->get('/portal/payment/success')
        ->assertStatus(200)
        ->assertSee('Status Pembayaran');
});
