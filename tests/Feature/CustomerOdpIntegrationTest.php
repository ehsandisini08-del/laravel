<?php

use App\Models\Area;
use App\Models\Cpe;
use App\Models\Customer;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Package;
use App\Models\Router;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->router = Router::factory()->create();
    $this->area = Area::factory()->create();
    $this->package = Package::factory()->create([
        'router_id' => $this->router->id,
    ]);

    $this->odc = Odc::create([
        'kode' => 'ODC-TEST-01',
        'nama' => 'ODC Test Utama',
        'kapasitas' => 144,
        'status' => 'ACTIVE',
        'latitude' => -6.2088,
        'longitude' => 106.8456,
    ]);

    $this->odp = Odp::create([
        'odc_id' => $this->odc->id,
        'kode' => 'ODP-TEST-01',
        'nama' => 'ODP Test Cluster A',
        'kapasitas' => 16,
        'port_terpakai' => 0,
        'status' => 'ACTIVE',
        'latitude' => -6.2090,
        'longitude' => 106.8460,
    ]);
});

test('customer create page displays odp select options', function () {
    $response = $this->get(route('customers.create'));

    $response->assertOk()
        ->assertSee('ODP-TEST-01')
        ->assertSee('FTTH & Jaringan ODP');
});

test('customer can be created with odp_id and port_odp and updates odp port_terpakai', function () {
    $response = $this->post(route('customers.store'), [
        'name' => 'John Doe FTTH',
        'address' => 'Jl. Merdeka No. 10',
        'phone' => '081234567890',
        'latitude' => -6.2091,
        'longitude' => 106.8462,
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'ppp_username' => 'johndoe_ftth',
        'ppp_password' => 'secret123',
        'installation_date' => '2026-08-01',
        'due_day' => 10,
        'odp_id' => $this->odp->id,
        'port_odp' => 1,
        'create_ppp_secret' => 0,
    ]);

    $response->assertRedirect(route('customers.index'));

    $customer = Customer::where('ppp_username', 'johndoe_ftth')->first();
    expect($customer)->not->toBeNull();
    expect($customer->odp_id)->toBe($this->odp->id);
    expect($customer->port_odp)->toBe(1);

    // ODP port_terpakai should be synchronized
    expect($this->odp->fresh()->port_terpakai)->toBe(1);
    expect($this->odp->fresh()->port_available)->toBe(15);
});

test('customer show page displays connected odp and port', function () {
    $customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'odp_id' => $this->odp->id,
        'port_odp' => 3,
    ]);

    $response = $this->get(route('customers.show', $customer));

    $response->assertOk()
        ->assertSee('ODP-TEST-01')
        ->assertSee('Port 3');
});

test('customer edit page displays odp options with selected odp', function () {
    $customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'odp_id' => $this->odp->id,
        'port_odp' => 2,
    ]);

    $response = $this->get(route('customers.edit', $customer));

    $response->assertOk()
        ->assertSee('ODP-TEST-01')
        ->assertSee('FTTH & Jaringan ODP');
});

test('customer can update odp assignment and syncs both old and new odp port_terpakai', function () {
    $odp2 = Odp::create([
        'odc_id' => $this->odc->id,
        'kode' => 'ODP-TEST-02',
        'nama' => 'ODP Test Cluster B',
        'kapasitas' => 8,
        'port_terpakai' => 0,
        'status' => 'ACTIVE',
        'latitude' => -6.2100,
        'longitude' => 106.8470,
    ]);

    $customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'odp_id' => $this->odp->id,
        'port_odp' => 1,
    ]);
    $this->odp->update(['port_terpakai' => 1]);

    $response = $this->put(route('customers.update', $customer), [
        'name' => $customer->name,
        'address' => $customer->address,
        'phone' => $customer->phone,
        'latitude' => $customer->latitude,
        'longitude' => $customer->longitude,
        'area_id' => $customer->area_id,
        'router_id' => $customer->router_id,
        'package_id' => $customer->package_id,
        'ppp_username' => $customer->ppp_username,
        'installation_date' => $customer->installation_date->format('Y-m-d'),
        'due_day' => $customer->due_day,
        'odp_id' => $odp2->id,
        'port_odp' => 4,
    ]);

    $response->assertRedirect(route('customers.index'));

    $customer->refresh();
    expect($customer->odp_id)->toBe($odp2->id);
    expect($customer->port_odp)->toBe(4);

    expect($this->odp->fresh()->port_terpakai)->toBe(0);
    expect($odp2->fresh()->port_terpakai)->toBe(1);
});

test('ftth map page renders with google hybrid tile layer and fullscreen toggle', function () {
    $response = $this->get(route('ftth.map'));

    $response->assertOk()
        ->assertSee('FTTH Monitoring')
        ->assertSee('Perluas Peta (Layar Penuh)')
        ->assertSee('Google Satelit + Label (Hybrid)')
        ->assertSee('toggleFullscreen');
});

test('ftth customers api returns is_online, cpe, and rx_power', function () {
    $customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'odp_id' => $this->odp->id,
        'port_odp' => 1,
        'ppp_username' => 'user_test_ftth',
        'latitude' => -6.2095,
        'longitude' => 106.8465,
    ]);

    Cpe::create([
        'genieacs_id' => 'cpe-test-01',
        'customer_id' => $customer->id,
        'ppp_username' => 'user_test_ftth',
        'serial_number' => 'ZTEG12345678',
        'model_name' => 'F609',
        'status' => 'online',
        'signal_parameters' => [
            'VirtualParameters.RXPower' => [
                'value' => '-19.45 dBm',
            ],
        ],
    ]);

    $response = $this->get(route('ftth.api.customers'));

    $response->assertOk()
        ->assertJsonFragment([
            'id' => $customer->id,
            'customer_code' => $customer->customer_code,
            'rx_power' => '-19.45 dBm',
        ]);
});

test('ftth search api returns customer search results with rx info', function () {
    $customer = Customer::factory()->create([
        'name' => 'Budi FTTH Search',
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'odp_id' => $this->odp->id,
        'port_odp' => 1,
        'ppp_username' => 'budi_ftth',
        'latitude' => -6.2095,
        'longitude' => 106.8465,
    ]);

    Cpe::create([
        'genieacs_id' => 'cpe-test-02',
        'customer_id' => $customer->id,
        'ppp_username' => 'budi_ftth',
        'serial_number' => 'ZTEG99887766',
        'model_name' => 'F670L',
        'status' => 'online',
        'signal_parameters' => [
            'VirtualParameters.RXPower' => [
                'value' => '-21.30 dBm',
            ],
        ],
    ]);

    $response = $this->get(route('ftth.api.search', ['q' => 'Budi']));

    $response->assertOk()
        ->assertJsonFragment([
            'id' => $customer->id,
            'type' => 'customer',
        ]);
});

test('ftth stats api returns accurate counters', function () {
    $response = $this->get(route('ftth.api.stats'));

    $response->assertOk()
        ->assertJsonStructure([
            'total_odc',
            'total_odp',
            'total_customers',
            'customers_online',
            'customers_offline',
            'customers_gangguan',
            'customers_isolir',
            'customers_nonaktif',
        ]);
});
