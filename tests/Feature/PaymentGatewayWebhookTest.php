<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\ServiceStatus;
use App\Models\Area;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Payment;
use App\Models\PppSecret;
use App\Models\Router;
use App\Models\Setting;
use App\Services\Mikrotik\PPPSecretService as MikrotikPPPSecretService;
use App\Services\PaymentGateway\PaymentGatewayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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
        'invoice_number' => 'INV-202608-000001',
        'customer_id' => $this->customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->router->id,
        'amount' => 200000,
        'status' => InvoiceStatus::Unpaid,
    ]);

    Setting::set('payment_sandbox', '1', 'payment');
});

test('midtrans webhook with valid signature marks invoice as paid', function () {
    Setting::set('payment_midtrans_server_key', 'server-key-123', 'payment');

    $response = $this->post('/webhooks/payment/midtrans', [
        'order_id' => 'INV-202608-000001',
        'status_code' => '200',
        'transaction_status' => 'settlement',
        'gross_amount' => '200000',
        'signature_key' => hash('sha512', 'INV-202608-000001'.'200'.'200000'.'server-key-123'),
        'payment_type' => 'bank_transfer',
        'transaction_id' => 'mtx-000001',
        'va_numbers' => [['bank' => 'bca', 'va_number' => '12345']],
    ]);

    $response->assertStatus(200);
    expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::Paid);

    $payment = Payment::where('invoice_id', $this->invoice->id)->first();

    expect($payment)->not->toBeNull()
        ->and($payment->payment_method)->toBe(PaymentMethod::VaBca)
        ->and($payment->gateway_provider)->toBe('midtrans')
        ->and($payment->reference)->toBe('mtx-000001')
        ->and($payment->paid_by_user_id)->toBeNull();
});

test('midtrans webhook with invalid signature returns 403', function () {
    Setting::set('payment_midtrans_server_key', 'server-key-123', 'payment');

    $response = $this->post('/webhooks/payment/midtrans', [
        'order_id' => 'INV-202608-000001',
        'status_code' => '200',
        'gross_amount' => '200000',
        'signature_key' => 'invalid-signature',
    ]);

    $response->assertStatus(403);
    expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::Unpaid);
});

test('midtrans webhook with amount mismatch returns 400', function () {
    Setting::set('payment_midtrans_server_key', 'server-key-123', 'payment');

    $response = $this->post('/webhooks/payment/midtrans', [
        'order_id' => 'INV-202608-000001',
        'status_code' => '200',
        'transaction_status' => 'settlement',
        'gross_amount' => '999999',
        'signature_key' => hash('sha512', 'INV-202608-000001'.'200'.'999999'.'server-key-123'),
    ]);

    $response->assertStatus(400);
    expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::Unpaid);
});

test('midtrans webhook reactivates isolated customer automatically', function () {
    Setting::set('payment_midtrans_server_key', 'server-key-123', 'payment');

    $this->customer->update(['service_status' => ServiceStatus::Isolated]);
    $this->pppSecret->update(['disabled' => true]);

    $mock = Mockery::mock(MikrotikPPPSecretService::class);
    $mock->shouldReceive('enableSecret')->once()->andReturn(['success' => true, 'message' => 'ok']);
    app()->bind(MikrotikPPPSecretService::class, fn () => $mock);

    $response = $this->post('/webhooks/payment/midtrans', [
        'order_id' => 'INV-202608-000001',
        'status_code' => '200',
        'transaction_status' => 'settlement',
        'gross_amount' => '200000',
        'signature_key' => hash('sha512', 'INV-202608-000001'.'200'.'200000'.'server-key-123'),
        'payment_type' => 'qris',
        'transaction_id' => 'mtx-000002',
    ]);

    $response->assertStatus(200);
    expect($this->customer->fresh()->service_status)->toBe(ServiceStatus::Active)
        ->and($this->pppSecret->fresh()->disabled)->toBeFalse();
});

test('midtrans webhook with pending status does not mark invoice as paid', function () {
    Setting::set('payment_midtrans_server_key', 'server-key-123', 'payment');

    $response = $this->post('/webhooks/payment/midtrans', [
        'order_id' => 'INV-202608-000001',
        'status_code' => '201',
        'transaction_status' => 'pending',
        'gross_amount' => '200000',
        'signature_key' => hash('sha512', 'INV-202608-000001'.'201'.'200000'.'server-key-123'),
        'payment_type' => 'qris',
        'transaction_id' => 'mtx-000004',
    ]);

    $response->assertStatus(200);
    expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::Unpaid);

    expect(Payment::where('invoice_id', $this->invoice->id)->count())->toBe(0);
});

test('midtrans webhook with denied capture does not mark invoice as paid', function () {
    Setting::set('payment_midtrans_server_key', 'server-key-123', 'payment');

    $response = $this->post('/webhooks/payment/midtrans', [
        'order_id' => 'INV-202608-000001',
        'status_code' => '200',
        'transaction_status' => 'capture',
        'fraud_status' => 'deny',
        'gross_amount' => '200000',
        'signature_key' => hash('sha512', 'INV-202608-000001'.'200'.'200000'.'server-key-123'),
        'payment_type' => 'credit_card',
        'transaction_id' => 'mtx-000005',
    ]);

    $response->assertStatus(200);
    expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::Unpaid);

    expect(Payment::where('invoice_id', $this->invoice->id)->count())->toBe(0);
});

test('xendit webhook with valid callback token marks invoice as paid', function () {
    Setting::set('payment_xendit_verification_token', 'xendit-token-123', 'payment');

    $response = $this->postJson('/webhooks/payment/xendit', [
        'id' => 'xdt-000001',
        'external_id' => 'INV-202608-000001',
        'status' => 'PAID',
        'payment_channel' => 'BCA',
        'payment_method' => 'VIRTUAL_ACCOUNT',
    ], ['X-Callback-Token' => 'xendit-token-123']);

    $response->assertStatus(200);
    expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::Paid);

    $payment = Payment::where('invoice_id', $this->invoice->id)->first();

    expect($payment)->not->toBeNull()
        ->and($payment->payment_method)->toBe(PaymentMethod::VaBca)
        ->and($payment->gateway_provider)->toBe('xendit')
        ->and($payment->reference)->toBe('xdt-000001');
});

test('xendit webhook with invalid callback token returns 403', function () {
    Setting::set('payment_xendit_verification_token', 'xendit-token-123', 'payment');

    $response = $this->postJson('/webhooks/payment/xendit', [
        'id' => 'xdt-000001',
        'external_id' => 'INV-202608-000001',
        'status' => 'PAID',
    ], ['X-Callback-Token' => 'wrong-token']);

    $response->assertStatus(403);
    expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::Unpaid);
});

test('xendit webhook without configured verification token returns 403', function () {
    $response = $this->postJson('/webhooks/payment/xendit', [
        'id' => 'xdt-000001',
        'external_id' => 'INV-202608-000001',
        'status' => 'PAID',
    ], ['X-Callback-Token' => 'xendit-token-123']);

    $response->assertStatus(403);
    expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::Unpaid);
});

test('tripay webhook with valid signature marks invoice as paid', function () {
    Setting::set('payment_tripay_private_key', 'tripay-private-key', 'payment');

    $payload = [
        'merchant_ref' => 'INV-202608-000001',
        'amount' => 200000,
        'status' => 'PAID',
        'payment_method' => 'BCA_VA',
        'reference' => 'trp-000001',
    ];

    $body = json_encode($payload);

    $response = $this->call('POST', '/webhooks/payment/tripay', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X-Callback-Signature' => hash_hmac('sha256', $body, 'tripay-private-key'),
    ], $body);

    $response->assertStatus(200);
    expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::Paid);

    $payment = Payment::where('invoice_id', $this->invoice->id)->first();

    expect($payment)->not->toBeNull()
        ->and($payment->payment_method)->toBe(PaymentMethod::VaBca)
        ->and($payment->gateway_provider)->toBe('tripay')
        ->and($payment->reference)->toBe('trp-000001');
});

test('tripay webhook with invalid signature returns 403', function () {
    Setting::set('payment_tripay_private_key', 'tripay-private-key', 'payment');

    $body = json_encode([
        'merchant_ref' => 'INV-202608-000001',
        'status' => 'PAID',
    ]);

    $response = $this->call('POST', '/webhooks/payment/tripay', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X-Callback-Signature' => 'invalid',
    ], $body);

    $response->assertStatus(403);
    expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::Unpaid);
});

test('duplicate webhook does not create duplicate payment', function () {
    Setting::set('payment_midtrans_server_key', 'server-key-123', 'payment');

    $payload = [
        'order_id' => 'INV-202608-000001',
        'status_code' => '200',
        'transaction_status' => 'settlement',
        'gross_amount' => '200000',
        'signature_key' => hash('sha512', 'INV-202608-000001'.'200'.'200000'.'server-key-123'),
        'payment_type' => 'qris',
        'transaction_id' => 'mtx-000003',
    ];

    $this->post('/webhooks/payment/midtrans', $payload)->assertStatus(200);
    $this->post('/webhooks/payment/midtrans', $payload)->assertStatus(200);

    expect(Payment::where('invoice_id', $this->invoice->id)->count())->toBe(1);
});

test('payment method mapping works for common gateway methods', function () {
    $manager = app(PaymentGatewayManager::class);

    $midtrans = $manager->driver('midtrans');
    $xendit = $manager->driver('xendit');
    $tripay = $manager->driver('tripay');

    expect($midtrans->mapMethod('bank_transfer:bri'))->toBe(PaymentMethod::VaBri)
        ->and($midtrans->mapMethod('qris'))->toBe(PaymentMethod::Qris)
        ->and($xendit->mapMethod('ewallet'))->toBe(PaymentMethod::Ewallet)
        ->and($tripay->mapMethod('QRIS'))->toBe(PaymentMethod::Qris)
        ->and($tripay->mapMethod('BNI_VA'))->toBe(PaymentMethod::VaBni);
});
