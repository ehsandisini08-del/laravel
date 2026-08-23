<?php

use App\Enums\InvoiceStatus;
use App\Models\Area;
use App\Models\Cpe;
use App\Models\Customer;
use App\Models\FiberLine;
use App\Models\Invoice;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Package;
use App\Models\Router;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('teknisi can see all customers from all areas with restricted billing view', function () {
    $router = Router::factory()->create();
    $area1 = Area::factory()->create(['name' => 'Area Timur']);
    $area2 = Area::factory()->create(['name' => 'Area Barat']);
    $package = Package::factory()->create(['router_id' => $router->id]);

    $customer1 = Customer::factory()->create([
        'area_id' => $area1->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
        'name' => 'Pelanggan Timur',
    ]);
    $customer2 = Customer::factory()->create([
        'area_id' => $area2->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
        'name' => 'Pelanggan Barat',
    ]);

    $invoice = Invoice::factory()->create([
        'customer_id' => $customer1->id,
        'package_id' => $package->id,
        'router_id' => $router->id,
        'status' => InvoiceStatus::Unpaid,
        'invoice_number' => 'INV-TEST-001',
    ]);

    $user = teknisiUser();
    $this->actingAs($user);

    $this->get(route('customers.index'))
        ->assertOk()
        ->assertSee('Pelanggan Timur')
        ->assertSee('Pelanggan Barat')
        ->assertSee('Add Customer')
        ->assertDontSee('Sync ke MikroTik')
        ->assertDontSee('Import');

    $this->get(route('customers.show', $customer1))
        ->assertOk()
        ->assertSee('Pelanggan Timur')
        ->assertDontSee('>Edit<', false)
        ->assertDontSee('>Delete<', false)
        ->assertDontSee('Kirim Login via WhatsApp')
        ->assertDontSee('Tagihan Aktif')
        ->assertDontSee('Lihat Invoice Belum Bayar')
        ->assertSee('Riwayat Tagihan')
        ->assertSee('INV-TEST-001')
        ->assertDontSee(route('billing.invoices.show', $invoice));

    $this->get(route('customers.show', $customer2))
        ->assertOk()
        ->assertSee('Pelanggan Barat');
});

test('teknisi can add customer and access dropdown helpers', function () {
    $router = Router::factory()->create();
    $area = Area::factory()->create(['name' => 'Area Pusat']);
    $package = Package::factory()->create(['router_id' => $router->id]);
    $package->areas()->attach($area->id);

    $user = teknisiUser();
    $this->actingAs($user);

    $this->get(route('customers.create'))
        ->assertOk()
        ->assertSee('Add Customer')
        ->assertSee('Area Pusat');

    $this->get(route('customers.packages-by-router', $router->id))
        ->assertOk()
        ->assertJsonFragment(['id' => $package->id]);

    $this->get(route('customers.areas-by-package', $package->id))
        ->assertOk()
        ->assertJsonFragment(['id' => $area->id]);

    $response = $this->post(route('customers.store'), [
        'name' => 'Pelanggan Baru Teknisi',
        'address' => 'Jl. Teknisi No. 12',
        'phone' => '081234567890',
        'latitude' => -6.200000,
        'longitude' => 106.816666,
        'area_id' => $area->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
        'ppp_username' => 'teknisi_cust_1',
        'ppp_password' => 'secret123',
        'installation_date' => now()->toDateString(),
        'due_day' => 10,
    ]);

    $response->assertRedirect(route('customers.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('customers', [
        'name' => 'Pelanggan Baru Teknisi',
        'ppp_username' => 'teknisi_cust_1',
    ]);
});

test('teknisi cannot edit, update, delete, import, or reconcile customers', function () {
    $router = Router::factory()->create();
    $area = Area::factory()->create();
    $package = Package::factory()->create(['router_id' => $router->id]);
    $customer = Customer::factory()->create([
        'area_id' => $area->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
    ]);

    $user = teknisiUser();
    $this->actingAs($user);

    $this->get(route('customers.edit', $customer))->assertForbidden();
    $this->put(route('customers.update', $customer), [
        'name' => 'Ganti Nama',
        'address' => 'Jl. Baru',
        'phone' => '081299998888',
        'latitude' => -6.2,
        'longitude' => 106.8,
        'area_id' => $area->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
        'ppp_username' => 'test_user_upd',
        'installation_date' => now()->toDateString(),
        'due_day' => 10,
    ])->assertForbidden();

    $this->delete(route('customers.destroy', $customer))->assertForbidden();
    $this->delete(route('customers.destroy-many'), ['ids' => [$customer->id]])->assertForbidden();

    $this->get(route('customers.import.form'))->assertForbidden();
    $this->get(route('customers.import.template'))->assertForbidden();
    $this->post(route('customers.import'))->assertForbidden();
    $this->post(route('customers.reconcile'))->assertForbidden();
    $this->post(route('customers.portal-password.send', $customer))->assertForbidden();
});

test('teknisi has full access to FTTH modules', function () {
    $user = teknisiUser();
    $this->actingAs($user);

    // Map
    $this->get(route('ftth.map'))->assertOk();

    // ODC CRUD
    $odc = Odc::create([
        'kode' => 'ODC-TEST-01',
        'nama' => 'ODC Test 1',
        'kapasitas' => 144,
        'status' => 'ACTIVE',
    ]);
    $this->get(route('ftth.odc.index'))->assertOk()->assertSee('ODC-TEST-01');
    $this->get(route('ftth.odc.create'))->assertOk();
    $this->get(route('ftth.odc.show', $odc))->assertOk();
    $this->get(route('ftth.odc.edit', $odc))->assertOk();

    // ODP CRUD
    $odp = Odp::create([
        'odc_id' => $odc->id,
        'kode' => 'ODP-TEST-01',
        'nama' => 'ODP Test 1',
        'kapasitas' => 8,
        'status' => 'ACTIVE',
    ]);
    $this->get(route('ftth.odp.index'))->assertOk()->assertSee('ODP-TEST-01');
    $this->get(route('ftth.odp.create'))->assertOk();
    $this->get(route('ftth.odp.show', $odp))->assertOk();
    $this->get(route('ftth.odp.edit', $odp))->assertOk();

    // Fiber CRUD
    $fiber = FiberLine::create([
        'nama' => 'Jalur Fiber Test',
        'warna' => '#ff0000',
        'status' => 'ACTIVE',
    ]);
    $this->get(route('ftth.fiber.index'))->assertOk();
    $this->get(route('ftth.fiber.create'))->assertOk();
    $this->get(route('ftth.fiber.show', $fiber))->assertOk();
    $this->get(route('ftth.fiber.edit', $fiber))->assertOk();

    // FTTH APIs
    $this->get(route('ftth.api.stats'))->assertOk();
    $this->get(route('ftth.api.odcs'))->assertOk();
    $this->get(route('ftth.api.odps'))->assertOk();
    $this->get(route('ftth.api.customers'))->assertOk();
    $this->get(route('ftth.api.fibers'))->assertOk();
    $this->get(route('ftth.api.search', ['q' => 'TEST']))->assertOk();
});

test('teknisi can access and manage CPE devices', function () {
    $cpe = Cpe::create([
        'genieacs_id' => '000000-TEST-123456',
        'serial_number' => 'SN123456789',
        'manufacturer' => 'ZTE',
        'model_name' => 'F670L',
        'status' => 'online',
        'ssid' => 'WiFi-Teknisi',
        'wifi_password' => 'password123',
    ]);

    $user = teknisiUser();
    $this->actingAs($user);

    $this->get(route('cpes.index'))
        ->assertOk()
        ->assertSee('SN123456789')
        ->assertSee('F670L');

    $this->get(route('cpes.show', $cpe))
        ->assertOk()
        ->assertSee('SN123456789');

    $this->put(route('cpes.update', $cpe), [
        'ssid' => 'WiFi-Teknisi-New',
        'wifi_password' => 'newpassword123',
    ])->assertRedirect();

    expect($cpe->fresh()->ssid)->toBe('WiFi-Teknisi-New');
});

test('teknisi cannot access billing routes', function () {
    $router = Router::factory()->create();
    $area = Area::factory()->create();
    $package = Package::factory()->create(['router_id' => $router->id]);
    $customer = Customer::factory()->create([
        'area_id' => $area->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
    ]);
    $invoice = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'router_id' => $router->id,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $user = teknisiUser();
    $this->actingAs($user);

    $this->get(route('billing.dashboard'))->assertForbidden();
    $this->post(route('billing.generate'))->assertForbidden();
    $this->get(route('billing.invoices.index'))->assertForbidden();
    $this->get(route('billing.cetak-invoice'))->assertForbidden();
    $this->get(route('billing.cetak-invoice.print'))->assertForbidden();
    $this->get(route('billing.invoices.show', $invoice))->assertForbidden();
    $this->get(route('billing.invoices.print', $invoice))->assertForbidden();
    $this->post(route('billing.invoices.pay', $invoice))->assertForbidden();
    $this->delete(route('billing.invoices.destroy', $invoice))->assertForbidden();
    $this->delete(route('billing.invoices.destroy-many'), ['ids' => [$invoice->id]])->assertForbidden();
});

test('teknisi cannot access packages and areas routes', function () {
    $router = Router::factory()->create();
    $area = Area::factory()->create();
    $package = Package::factory()->create(['router_id' => $router->id]);

    $user = teknisiUser();
    $this->actingAs($user);

    $this->get(route('packages.index'))->assertForbidden();
    $this->get(route('packages.create'))->assertForbidden();
    $this->post(route('packages.store'), [
        'name' => 'Paket Baru',
        'price' => 100000,
        'router_id' => $router->id,
        'areas' => [$area->id],
    ])->assertForbidden();
    $this->get(route('packages.show', $package))->assertForbidden();
    $this->get(route('packages.edit', $package))->assertForbidden();
    $this->put(route('packages.update', $package), [
        'name' => 'Paket Edit',
        'price' => 120000,
        'router_id' => $router->id,
        'areas' => [$area->id],
    ])->assertForbidden();
    $this->delete(route('packages.destroy', $package))->assertForbidden();
    $this->get(route('packages.profiles-by-router', $router))->assertForbidden();

    $this->get(route('areas.index'))->assertForbidden();
    $this->get(route('areas.create'))->assertForbidden();
    $this->post(route('areas.store'), [
        'code' => 'TEST',
        'name' => 'Area Baru',
    ])->assertForbidden();
    $this->get(route('areas.show', $area))->assertForbidden();
    $this->get(route('areas.edit', $area))->assertForbidden();
    $this->put(route('areas.update', $area), [
        'code' => 'TEST2',
        'name' => 'Area Edit',
    ])->assertForbidden();
    $this->delete(route('areas.destroy', $area))->assertForbidden();
});

test('teknisi cannot access network, gudang, administration, logs, backup, whatsapp, settings', function () {
    $user = teknisiUser();
    $this->actingAs($user);

    // Network (non-CPE)
    $this->get(route('routers.index'))->assertForbidden();
    $this->get(route('ppp-secrets.index'))->assertForbidden();
    $this->get(route('ppp-profiles.index'))->assertForbidden();
    $this->get(route('ppp-active.index'))->assertForbidden();

    // Gudang
    $this->get(route('gudang.stok'))->assertForbidden();
    $this->get(route('gudang.barang-masuk'))->assertForbidden();
    $this->get(route('gudang.barang-keluar'))->assertForbidden();
    $this->get(route('gudang.riwayat'))->assertForbidden();
    $this->get(route('gudang.opname.index'))->assertForbidden();
    $this->get(route('gudang.barang.index'))->assertForbidden();
    $this->get(route('gudang.kategori.index'))->assertForbidden();

    // Administration
    $this->get(route('users.index'))->assertForbidden();
    $this->get(route('unlock-accounts.index'))->assertForbidden();
    $this->get(route('logs.index'))->assertForbidden();
    $this->get(route('backup.index'))->assertForbidden();
    $this->get(route('whatsapp.dashboard'))->assertForbidden();
    $this->get(route('whatsapp.menu'))->assertForbidden();
    $this->get(route('settings.index'))->assertForbidden();
    $this->get(route('update.index'))->assertForbidden();
    $this->get(route('monitoring.jobs'))->assertForbidden();
});

test('forbidden responses render access restricted popup view with informative message', function () {
    $user = teknisiUser();
    $this->actingAs($user);

    $this->get(route('billing.dashboard'))
        ->assertForbidden()
        ->assertSee('Akses Dibatasi')
        ->assertSee('Akun teknisi tidak bisa mengakses halaman ini.')
        ->assertSee('Ke Dashboard')
        ->assertSee('Kembali');
});

test('teknisi dashboard and navigation renders appropriately', function () {
    $user = teknisiUser();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Customers')
        ->assertSee('FTTH')
        ->assertSee(route('cpes.index'))
        ->assertDontSee(route('billing.invoices.index'))
        ->assertDontSee(route('routers.index'))
        ->assertDontSee(route('packages.index'))
        ->assertDontSee(route('areas.index'))
        ->assertDontSee('Aktivitas Terbaru')
        ->assertDontSee('Aksi Cepat')
        ->assertDontSee('Status Sistem')
        ->assertDontSee('System Status');
});
