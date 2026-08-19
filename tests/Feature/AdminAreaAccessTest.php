<?php

use App\Enums\InvoiceStatus;
use App\Models\Area;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\PppProfile;
use App\Models\Router;
use App\Models\User;
use App\Models\WaDevice;
use App\Models\WaMessage;
use App\Services\WhatsApp\WhatsAppGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adminAreaUser(array $areaIds = []): User
{
    $user = User::factory()->adminArea()->create();
    $user->areas()->attach($areaIds);

    return $user;
}

test('admin area user cannot access network and administration menus', function () {
    $user = adminAreaUser();
    $this->actingAs($user);

    $this->get(route('routers.index'))->assertForbidden();
    $this->get(route('ppp-secrets.index'))->assertForbidden();
    $this->get(route('ppp-profiles.index'))->assertForbidden();
    $this->get(route('ppp-active.index'))->assertForbidden();
    $this->get(route('logs.index'))->assertForbidden();
    $this->get(route('backup.index'))->assertForbidden();
    $this->get(route('whatsapp.menu'))->assertForbidden();
    $this->get(route('users.index'))->assertForbidden();
});

test('admin area dashboard hides router nav, import, activity, quick actions and system status', function () {
    $user = adminAreaUser();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('billing.invoices.index'))
        ->assertDontSee(route('routers.index'))
        ->assertDontSee(route('customers.import.form'))
        ->assertDontSee('Aktivitas Terbaru')
        ->assertDontSee('Aksi Cepat')
        ->assertDontSee('Status Sistem')
        ->assertDontSee('System Status');
});

test('superadmin dashboard still shows router nav, import, activity, quick actions and system status', function () {
    $user = User::factory()->superadmin()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('routers.index'))
        ->assertSee(route('customers.import.form'))
        ->assertSee('Aktivitas Terbaru')
        ->assertSee('Aksi Cepat')
        ->assertSee('Status Sistem')
        ->assertSee('System Status');
});

test('admin area user sees only assigned areas and cannot manage them', function () {
    $assigned = Area::factory()->create(['name' => 'Area Utara']);
    $other = Area::factory()->create(['name' => 'Area Selatan']);
    $user = adminAreaUser([$assigned->id]);
    $this->actingAs($user);

    $this->get(route('areas.index'))
        ->assertOk()
        ->assertSee('Area Utara')
        ->assertDontSee('Area Selatan')
        ->assertDontSee('Tambah Area');

    $this->get(route('areas.show', $assigned))->assertOk();
    $this->get(route('areas.show', $other))->assertForbidden();

    $this->get(route('areas.create'))->assertForbidden();
    $this->post(route('areas.store'), ['code' => 'BRU', 'name' => 'Area Baru'])->assertForbidden();
    $this->put(route('areas.update', $assigned), ['code' => $assigned->code, 'name' => 'Area Ganti'])->assertForbidden();
    $this->delete(route('areas.destroy', $assigned))->assertForbidden();
});

test('admin area user sees only packages of assigned areas and cannot manage them', function () {
    $router = Router::factory()->create();
    $profile = PppProfile::factory()->forRouter($router)->create();
    $assigned = Area::factory()->create();
    $other = Area::factory()->create();
    $package = Package::factory()->create(['router_id' => $router->id, 'name' => 'Paket 10M']);
    $package->areas()->attach($assigned->id);
    $otherPackage = Package::factory()->create(['router_id' => $router->id, 'name' => 'Paket 20M']);
    $otherPackage->areas()->attach($other->id);

    $user = adminAreaUser([$assigned->id]);
    $this->actingAs($user);

    $this->get(route('packages.index'))
        ->assertOk()
        ->assertSee('Paket 10M')
        ->assertDontSee('Paket 20M')
        ->assertDontSee('Tambah Paket');

    $this->get(route('packages.show', $package))->assertOk();
    $this->get(route('packages.show', $otherPackage))->assertForbidden();

    $this->get(route('packages.create'))->assertForbidden();
    $this->post(route('packages.store'), [
        'name' => 'Paket Baru',
        'price' => 150000,
        'router_id' => $router->id,
        'ppp_profile_id' => $profile->id,
        'areas' => [$assigned->id],
    ])->assertForbidden();
    $this->put(route('packages.update', $package), [
        'name' => 'Paket Ganti',
        'price' => 150000,
        'router_id' => $router->id,
        'ppp_profile_id' => $profile->id,
        'areas' => [$assigned->id],
    ])->assertForbidden();
    $this->delete(route('packages.destroy', $package))->assertForbidden();
});

test('admin area user sees only customers of assigned areas and cannot manage them', function () {
    $router = Router::factory()->create();
    $assigned = Area::factory()->create();
    $other = Area::factory()->create();
    $package = Package::factory()->create(['router_id' => $router->id]);
    $customer = Customer::factory()->create([
        'area_id' => $assigned->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
        'name' => 'Pelanggan Utara',
    ]);
    $otherCustomer = Customer::factory()->create([
        'area_id' => $other->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
        'name' => 'Pelanggan Selatan',
    ]);

    $user = adminAreaUser([$assigned->id]);
    $this->actingAs($user);

    $this->get(route('customers.index'))
        ->assertOk()
        ->assertSee('Pelanggan Utara')
        ->assertDontSee('Pelanggan Selatan')
        ->assertDontSee('Sync')
        ->assertDontSee('Import');

    $this->get(route('customers.show', $customer))->assertOk();
    $this->get(route('customers.show', $otherCustomer))->assertForbidden();

    $this->get(route('customers.create'))->assertForbidden();
    $this->post(route('customers.store'), [
        'name' => 'Pelanggan Baru',
        'address' => 'Jl. Contoh No. 1',
        'phone' => '081234567899',
        'latitude' => -6.2,
        'longitude' => 106.8,
        'area_id' => $assigned->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
        'ppp_username' => 'baru_test',
        'ppp_password' => 'secret123',
        'installation_date' => now()->toDateString(),
        'due_day' => 5,
    ])->assertForbidden();
    $this->put(route('customers.update', $customer), [
        'name' => 'Ganti Nama',
        'address' => 'Jl. Contoh No. 1',
        'phone' => $customer->phone,
        'latitude' => -6.2,
        'longitude' => 106.8,
        'area_id' => $assigned->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
        'ppp_username' => $customer->ppp_username,
        'installation_date' => now()->toDateString(),
        'due_day' => 5,
    ])->assertForbidden();
    $this->delete(route('customers.destroy', $customer))->assertForbidden();
    $this->post(route('customers.import'), [])->assertForbidden();
    $this->get(route('customers.import.form'))->assertForbidden();
    $this->post(route('customers.reconcile'))->assertForbidden();
});

test('admin area user sees only invoices of assigned areas and cannot generate or delete', function () {
    $router = Router::factory()->create();
    $assigned = Area::factory()->create();
    $other = Area::factory()->create();
    $package = Package::factory()->create(['router_id' => $router->id]);
    $customer = Customer::factory()->create([
        'area_id' => $assigned->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
    ]);
    $otherCustomer = Customer::factory()->create([
        'area_id' => $other->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
    ]);
    $invoice = Invoice::factory()->create([
        'invoice_number' => 'INV-202608-000101',
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'router_id' => $router->id,
        'billing_month' => now()->month,
        'billing_year' => now()->year,
        'status' => InvoiceStatus::Unpaid,
    ]);
    $otherInvoice = Invoice::factory()->create([
        'invoice_number' => 'INV-202608-000102',
        'customer_id' => $otherCustomer->id,
        'package_id' => $package->id,
        'router_id' => $router->id,
        'billing_month' => now()->month,
        'billing_year' => now()->year,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $user = adminAreaUser([$assigned->id]);
    $this->actingAs($user);

    $this->get(route('billing.invoices.index'))
        ->assertOk()
        ->assertSee('INV-202608-000101')
        ->assertDontSee('INV-202608-000102')
        ->assertDontSee('Buat Invoice');

    $this->get(route('billing.invoices.show', $invoice))->assertOk();
    $this->get(route('billing.invoices.show', $otherInvoice))->assertForbidden();

    $this->post(route('billing.generate'), ['month' => now()->month, 'year' => now()->year])->assertForbidden();
    $this->delete(route('billing.invoices.destroy', $invoice))->assertForbidden();
    $this->delete(route('billing.invoices.destroy-many'), ['ids' => [$invoice->id]])->assertForbidden();

    expect(Invoice::find($invoice->id))->not->toBeNull();
});

test('admin area user can mark assigned invoice as paid', function () {
    $router = Router::factory()->create();
    $assigned = Area::factory()->create();
    $package = Package::factory()->create(['router_id' => $router->id]);
    $customer = Customer::factory()->create([
        'area_id' => $assigned->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
    ]);
    $invoice = Invoice::factory()->create([
        'invoice_number' => 'INV-202608-000103',
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'router_id' => $router->id,
        'billing_month' => now()->month,
        'billing_year' => now()->year,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $user = adminAreaUser([$assigned->id]);
    $this->actingAs($user);

    $this->post(route('billing.invoices.pay', $invoice))
        ->assertRedirect();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
});

test('admin area user can send portal password via whatsapp for assigned customer', function () {
    $router = Router::factory()->create();
    $assigned = Area::factory()->create();
    $package = Package::factory()->create(['router_id' => $router->id]);
    $customer = Customer::factory()->withPortal('rahasia123')->create([
        'area_id' => $assigned->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
        'phone' => '081234567890',
    ]);
    WaDevice::factory()->connected()->create();

    $waMessage = new WaMessage(['status' => 'sent']);
    $this->mock(WhatsAppGatewayService::class)
        ->shouldReceive('sendMessage')
        ->once()
        ->andReturn($waMessage);

    $user = adminAreaUser([$assigned->id]);
    $this->actingAs($user);

    $this->post(route('customers.portal-password.send', $customer))
        ->assertRedirect(route('customers.show', $customer));
});
