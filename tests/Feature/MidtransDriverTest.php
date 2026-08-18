<?php

use App\Models\Area;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Router;
use App\Models\Setting;
use App\Services\PaymentGateway\PaymentGatewayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->router = Router::factory()->create();
    $this->area = Area::factory()->create();
    $this->package = Package::factory()->create(['router_id' => $this->router->id]);

    $this->customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $this->invoice = Invoice::factory()->create([
        'invoice_number' => 'INV-202608-000777',
        'customer_id' => $this->customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->router->id,
        'amount' => 150000,
    ]);

    Setting::set('payment_provider', 'midtrans', 'payment');
    Setting::set('payment_sandbox', '1', 'payment');
    Setting::set('payment_midtrans_server_key', 'server-key-123', 'payment');
});

test('midtrans create payment succeeds and returns token and redirect url', function () {
    Http::fake([
        'app.sandbox.midtrans.com/*' => Http::response([
            'token' => 'snap-token-123',
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v3/redirection/snap-token-123',
            'transaction_id' => 'tx-123',
        ], 201),
    ]);

    $result = app(PaymentGatewayManager::class)->driver('midtrans')->createPayment($this->invoice);

    expect($result['success'])->toBeTrue()
        ->and($result['token'])->toBe('snap-token-123')
        ->and($result['redirect_url'])->toContain('snap-token-123');

    Http::assertSent(function ($request) {
        $payload = $request->data();

        return $payload['transaction_details']['order_id'] === 'INV-202608-000777'
            && $payload['transaction_details']['gross_amount'] === 150000
            && $payload['item_details'][0]['quantity'] === 1
            && $payload['item_details'][0]['price'] === 150000;
    });
});

test('midtrans create payment failure surfaces the gateway error', function () {
    Http::fake([
        'app.sandbox.midtrans.com/*' => Http::response([
            'error_messages' => ['Access denied due to unauthorized transaction, please check client or server key'],
        ], 401),
    ]);

    $result = app(PaymentGatewayManager::class)->driver('midtrans')->createPayment($this->invoice);

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('HTTP 401')
        ->and($result['message'])->toContain('Access denied');
});

test('midtrans create payment falls back to invoice item when invoice has no items', function () {
    $this->invoice->items()->delete();

    Http::fake([
        'app.sandbox.midtrans.com/*' => Http::response([
            'token' => 'snap-token-123',
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v3/redirection/snap-token-123',
        ], 201),
    ]);

    $result = app(PaymentGatewayManager::class)->driver('midtrans')->createPayment($this->invoice);

    expect($result['success'])->toBeTrue();

    Http::assertSent(function ($request) {
        $payload = $request->data();

        return $payload['item_details'] === [[
            'id' => 'invoice',
            'price' => 150000,
            'quantity' => 1,
            'name' => 'Invoice INV-202608-000777',
        ]];
    });
});

test('portal pay with midtrans error shows the gateway error to customer', function () {
    $this->actingAs($this->customer, 'customer');

    Http::fake([
        'app.sandbox.midtrans.com/*' => Http::response([
            'error_messages' => ['Access denied due to unauthorized transaction, please check client or server key'],
        ], 401),
    ]);

    $response = $this->post(route('portal.invoices.pay', $this->invoice));

    $response->assertRedirect(route('portal.invoices.show', $this->invoice));
    $response->assertSessionHas('error', function (string $error) {
        return str_contains($error, 'Access denied');
    });
});
