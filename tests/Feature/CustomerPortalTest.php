<?php

use App\Enums\InvoiceStatus;
use App\Models\Area;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Router;
use App\Models\Setting;
use App\Models\User;
use App\Models\WaDevice;
use App\Models\WaMessage;
use App\Services\CustomerService;
use App\Services\WhatsApp\BaileysGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->router = Router::factory()->create();
    $this->area = Area::factory()->create();
    $this->package = Package::factory()->create(['router_id' => $this->router->id]);
});

test('customer code generator produces six digits', function () {
    $code = app(CustomerService::class)->generateCustomerCode();

    expect($code)->toMatch('/^\d{6}$/');
});

test('generated customer codes are random, not sequential', function () {
    $service = app(CustomerService::class);
    $codes = [];

    for ($i = 0; $i < 20; $i++) {
        $codes[] = $service->generateCustomerCode();
    }

    expect($codes)->toHaveCount(20)
        ->and(collect($codes)->unique()->count())->toBe(20)
        ->and($codes)->each->toMatch('/^\d{6}$/');
});

test('customer codes stay unique when creating many customers', function () {
    $service = app(CustomerService::class);
    $codes = [];

    for ($i = 0; $i < 100; $i++) {
        $customer = Customer::factory()->create([
            'customer_code' => $service->generateCustomerCode(),
            'area_id' => $this->area->id,
            'router_id' => $this->router->id,
            'package_id' => $this->package->id,
            'ppp_username' => 'bulkuser'.$i,
            'phone' => '08'.$i,
        ]);
        $codes[] = $customer->customer_code;
    }

    expect(collect($codes)->unique()->count())->toBe(100);

    $newCode = $service->generateCustomerCode();

    expect($codes)->not->toContain($newCode);
});

test('customer creation auto generates three digit portal password', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $customer = app(CustomerService::class)->create([
        'name' => 'Portal User',
        'address' => 'Jl. Test 1',
        'phone' => '081234567890',
        'latitude' => -6.2,
        'longitude' => 106.8,
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'ppp_username' => 'portaluser1',
        'ppp_password' => 'secret123',
        'installation_date' => now()->format('Y-m-d'),
        'due_day' => 10,
        'portal_enabled' => true,
    ]);

    expect($customer->generated_portal_password)->toMatch('/^\d{3}$/')
        ->and($customer->portal_enabled)->toBeTrue()
        ->and(Hash::check($customer->generated_portal_password, $customer->portal_password))->toBeTrue();
});

test('customer can login to portal with code and password', function () {
    $customer = Customer::factory()->withPortal('123')->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $response = $this->post(route('portal.login'), [
        'customer_code' => $customer->customer_code,
        'password' => '123',
    ]);

    $response->assertRedirect(route('portal.dashboard'));
    $this->assertAuthenticatedAs($customer, 'customer');
});

test('customer login fails with wrong password', function () {
    $customer = Customer::factory()->withPortal('123')->create();

    $response = $this->post(route('portal.login'), [
        'customer_code' => $customer->customer_code,
        'password' => '999',
    ]);

    $response->assertSessionHasErrors('customer_code');
    $this->assertGuest('customer');
});

test('customer with portal disabled cannot login', function () {
    $customer = Customer::factory()->withoutPortal()->create();

    $response = $this->post(route('portal.login'), [
        'customer_code' => $customer->customer_code,
        'password' => '123',
    ]);

    $response->assertSessionHasErrors('customer_code');
    $this->assertGuest('customer');
});

test('portal login page is accessible', function () {
    $this->get(route('portal.login'))->assertStatus(200);
});

test('portal dashboard shows customer detail and active bill', function () {
    $customer = Customer::factory()->withPortal('123')->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $invoice = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->router->id,
        'status' => InvoiceStatus::Unpaid,
        'amount' => 150000,
    ]);

    $this->actingAs($customer, 'customer');

    $response = $this->get(route('portal.dashboard'));

    $response->assertStatus(200)
        ->assertSee($customer->name)
        ->assertSee($customer->customer_code)
        ->assertSee($customer->ppp_username)
        ->assertSee('Rp');
});

test('portal invoices history shows invoices and detail page', function () {
    $customer = Customer::factory()->withPortal('123')->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $invoice = Invoice::factory()->create([
        'invoice_number' => 'INV-202608-000100',
        'customer_id' => $customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->router->id,
        'status' => InvoiceStatus::Paid,
    ]);

    $this->actingAs($customer, 'customer');

    $this->get(route('portal.invoices.index'))
        ->assertStatus(200)
        ->assertSee('INV-202608-000100');

    $this->get(route('portal.invoices.show', $invoice))
        ->assertStatus(200)
        ->assertSee($invoice->invoice_number);
});

test('portal invoice detail is restricted to invoice owner', function () {
    $customer = Customer::factory()->withPortal('123')->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $other = Customer::factory()->create();
    $otherInvoice = Invoice::factory()->create([
        'customer_id' => $other->id,
    ]);

    $this->actingAs($customer, 'customer');

    $this->get(route('portal.invoices.show', $otherInvoice))->assertForbidden();
});

test('admin user is redirected from portal pages', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin);

    $response = $this->get(route('portal.dashboard'));

    $response->assertRedirect(route('portal.login'));
});

test('customer cannot access admin dashboard', function () {
    $customer = Customer::factory()->withPortal('123')->create();

    $this->actingAs($customer, 'customer');

    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('portal.dashboard'));
});

test('customer can logout from portal', function () {
    $customer = Customer::factory()->withPortal('123')->create();

    $this->actingAs($customer, 'customer');

    $this->post(route('portal.logout'))->assertRedirect(route('portal.login'));
    $this->assertGuest('customer');
});

test('portal bills page shows unpaid invoices', function () {
    $customer = Customer::factory()->withPortal('123')->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $invoice = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->router->id,
        'status' => InvoiceStatus::Overdue,
        'amount' => 150000,
    ]);

    $this->actingAs($customer, 'customer');

    $this->get(route('portal.bills'))
        ->assertStatus(200)
        ->assertSee($invoice->invoice_number)
        ->assertSee('Telat');
});

test('portal bills page shows empty state when no unpaid invoices', function () {
    $customer = Customer::factory()->withPortal('123')->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $this->actingAs($customer, 'customer');

    $this->get(route('portal.bills'))
        ->assertStatus(200)
        ->assertSee('Tidak Ada Tagihan');
});

test('portal account page shows customer profile', function () {
    $customer = Customer::factory()->withPortal('123')->create([
        'name' => 'Test Customer',
        'address' => 'Jl. Akun 1',
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $this->actingAs($customer, 'customer');

    $this->get(route('portal.account'))
        ->assertStatus(200)
        ->assertSee('Test Customer')
        ->assertSee('Jl. Akun 1')
        ->assertSee($customer->customer_code);
});

test('sending portal login via whatsapp sends message with login info', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    WaDevice::factory()->connected()->create();

    $baileys = Mockery::mock(BaileysGatewayService::class);
    $baileys->shouldReceive('sendText')
        ->once()
        ->andReturn(['success' => true, 'data' => ['message_id' => 'wa-abc']]);
    app()->instance(BaileysGatewayService::class, $baileys);

    $customer = Customer::factory()->withPortal('123')->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'phone' => '081234567890',
    ]);

    $response = $this->post(route('customers.portal-password.send', $customer));

    $response->assertRedirect(route('customers.show', $customer));
    $response->assertSessionHas('success');

    $waMessage = WaMessage::where('customer_id', $customer->id)->first();

    expect($waMessage)->not->toBeNull()
        ->and($waMessage->status)->toBe('sent')
        ->and($waMessage->phone)->toBe('6281234567890')
        ->and($waMessage->message)->toContain($customer->customer_code)
        ->and($waMessage->message)->toContain('Password')
        ->and($waMessage->message)->toContain('Download Aplikasi')
        ->and($waMessage->message)->toContain(url('/portal'));
});

test('sending portal login via whatsapp uses the configured app download url', function () {
    Setting::set('customer_app_url', 'https://billing.labsaid.site/download/billnet-customer.apk');

    $user = User::factory()->create();
    $this->actingAs($user);

    WaDevice::factory()->connected()->create();

    $baileys = Mockery::mock(BaileysGatewayService::class);
    $baileys->shouldReceive('sendText')
        ->once()
        ->andReturn(['success' => true, 'data' => ['message_id' => 'wa-abc']]);
    app()->instance(BaileysGatewayService::class, $baileys);

    $customer = Customer::factory()->withPortal('123')->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'phone' => '081234567890',
    ]);

    $this->post(route('customers.portal-password.send', $customer));

    $waMessage = WaMessage::where('customer_id', $customer->id)->first();

    expect($waMessage->message)
        ->toContain('Download Aplikasi: https://billing.labsaid.site/download/billnet-customer.apk')
        ->not->toContain('URL: '.url('/portal'));
});

test('sending portal login does not change the stored password', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    WaDevice::factory()->connected()->create();

    $baileys = Mockery::mock(BaileysGatewayService::class);
    $baileys->shouldReceive('sendText')->once()->andReturn(['success' => true, 'data' => []]);
    app()->instance(BaileysGatewayService::class, $baileys);

    $customer = Customer::factory()->withPortal('123')->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'phone' => '081234567890',
    ]);

    $before = $customer->fresh()->portal_password;

    $this->post(route('customers.portal-password.send', $customer));

    expect($customer->fresh()->portal_password)->toBe($before);
});

test('send portal login reports error when the gateway fails to send', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    WaDevice::factory()->connected()->create();

    $baileys = Mockery::mock(BaileysGatewayService::class);
    $baileys->shouldReceive('sendText')->once()->andReturn(['success' => false, 'error' => 'timeout']);
    app()->instance(BaileysGatewayService::class, $baileys);

    $customer = Customer::factory()->withPortal('123')->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'phone' => '081234567890',
    ]);

    $response = $this->post(route('customers.portal-password.send', $customer));

    $response->assertRedirect(route('customers.show', $customer));
    $response->assertSessionHas('error');
});

test('send portal password fails when no device is connected', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $customer = Customer::factory()->withPortal('123')->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'phone' => '081234567890',
    ]);

    $response = $this->post(route('customers.portal-password.send', $customer));

    $response->assertRedirect(route('customers.show', $customer));
    $response->assertSessionHas('error');
});

test('ensure portal password creates password once and returns the same value', function () {
    $customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'portal_enabled' => true,
        'portal_password' => null,
        'portal_password_plain' => null,
    ]);

    $service = app(CustomerService::class);

    $first = $service->ensurePortalPassword($customer);
    $second = $service->ensurePortalPassword($customer->fresh());

    expect($first)->toMatch('/^\d{3}$/')
        ->and($second)->toBe($first)
        ->and($customer->fresh()->portal_password_plain)->toBe($first);
});
