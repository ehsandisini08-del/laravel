<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Area;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Router;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->router = Router::factory()->create();
    $this->area = Area::factory()->create();
    $this->package = Package::factory()->create(['router_id' => $this->router->id]);

    $this->customer = Customer::factory()->withPortal('123')->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $this->invoice = Invoice::factory()->create([
        'invoice_number' => 'INV-202608-000900',
        'customer_id' => $this->customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->router->id,
        'amount' => 150000,
        'status' => InvoiceStatus::Unpaid,
    ]);
});

function configureTripay(): void
{
    Setting::set('payment_provider', 'tripay', 'payment');
    Setting::set('payment_sandbox', '1', 'payment');
    Setting::set('payment_tripay_merchant_code', 'T1234', 'payment');
    Setting::set('payment_tripay_api_key', 'API-KEY', 'payment');
    Setting::set('payment_tripay_private_key', 'PRIVATE-KEY', 'payment');
}

function fakeTripayCreatePayment(): void
{
    Http::fake([
        'tripay.co.id/*' => Http::response([
            'success' => true,
            'data' => [
                'reference' => 'REF-TRIPAY-1',
                'merchant_ref' => 'INV-202608-000900',
                'checkout_url' => 'https://payment.tripay.test/checkout/REF-TRIPAY-1',
                'status' => 'UNPAID',
            ],
        ]),
    ]);
}

test('customer can pay invoice via portal and is redirected to checkout', function () {
    $this->actingAs($this->customer, 'customer');

    configureTripay();
    fakeTripayCreatePayment();

    $response = $this->post(route('portal.invoices.pay', $this->invoice));

    $response->assertRedirect('https://payment.tripay.test/checkout/REF-TRIPAY-1');

    $payment = Payment::where('invoice_id', $this->invoice->id)->first();

    expect($payment)->not->toBeNull()
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->gateway_provider)->toBe('tripay')
        ->and($payment->reference)->toBe('REF-TRIPAY-1')
        ->and($payment->payload['payment_url'])->toBe('https://payment.tripay.test/checkout/REF-TRIPAY-1');
});

test('clicking pay again reuses the same pending payment url without new api call', function () {
    $this->actingAs($this->customer, 'customer');

    configureTripay();
    fakeTripayCreatePayment();

    $this->post(route('portal.invoices.pay', $this->invoice));
    $this->post(route('portal.invoices.pay', $this->invoice));

    Http::assertSentCount(1);

    expect(Payment::where('invoice_id', $this->invoice->id)->count())->toBe(1);
});

test('webhook after portal payment updates pending payment to success without duplicate', function () {
    $this->actingAs($this->customer, 'customer');

    configureTripay();
    fakeTripayCreatePayment();

    $this->post(route('portal.invoices.pay', $this->invoice));

    $payload = [
        'merchant_ref' => 'INV-202608-000900',
        'amount' => 150000,
        'status' => 'PAID',
        'payment_method' => 'QRIS',
        'reference' => 'REF-TRIPAY-1',
    ];

    $body = json_encode($payload);

    $response = $this->call('POST', '/webhooks/payment/tripay', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X-Callback-Signature' => hash_hmac('sha256', $body, 'PRIVATE-KEY'),
    ], $body);

    $response->assertStatus(200);

    expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::Paid);

    $payments = Payment::where('invoice_id', $this->invoice->id)->get();

    expect($payments)->toHaveCount(1)
        ->and($payments->first()->status)->toBe(PaymentStatus::Success);
});

test('paying an already paid invoice is rejected', function () {
    $this->invoice->update(['status' => InvoiceStatus::Paid]);

    $this->actingAs($this->customer, 'customer');
    configureTripay();

    $response = $this->post(route('portal.invoices.pay', $this->invoice));

    $response->assertRedirect(route('portal.invoices.show', $this->invoice));
    $response->assertSessionHas('error');
});

test('paying is rejected when no payment provider is configured', function () {
    $this->actingAs($this->customer, 'customer');

    Setting::set('payment_provider', 'none', 'payment');

    $response = $this->post(route('portal.invoices.pay', $this->invoice));

    $response->assertRedirect(route('portal.invoices.show', $this->invoice));
    $response->assertSessionHas('error');
});

test('customer cannot pay another customers invoice', function () {
    $other = Customer::factory()->withPortal('456')->create();
    $otherInvoice = Invoice::factory()->create([
        'customer_id' => $other->id,
    ]);

    $this->actingAs($this->customer, 'customer');
    configureTripay();

    $this->post(route('portal.invoices.pay', $otherInvoice))->assertForbidden();
});
