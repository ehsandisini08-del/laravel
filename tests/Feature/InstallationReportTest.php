<?php

use App\Models\Area;
use App\Models\Customer;
use App\Models\InstallationReport;
use App\Models\Package;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->router = Router::factory()->create();
    $this->area = Area::factory()->create();
    $this->package = Package::factory()->create([
        'router_id' => $this->router->id,
    ]);

    Cache::put('ppp-active-connections:'.$this->router->id, [], 30);
});

test('installation report is automatically created when customer is created', function () {
    $this->post(route('customers.store'), [
        'name' => 'Test Customer',
        'address' => 'Jl. Test No. 1',
        'phone' => '08123456789',
        'nik' => '3273010101010030',
        'latitude' => '-6.2088',
        'longitude' => '106.8456',
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'ppp_username' => 'test_ppp',
        'ppp_password' => 'secret123',
        'installation_date' => now()->format('Y-m-d'),
        'due_day' => 15,
    ]);

    $customer = Customer::where('ppp_username', 'test_ppp')->first();

    expect($customer)->not->toBeNull();

    $this->assertDatabaseHas('installation_reports', [
        'customer_id' => $customer->id,
        'user_id' => $this->user->id,
    ]);
});

test('installation report has correct relationship with customer', function () {
    $customer = Customer::factory()->create();

    $report = InstallationReport::create([
        'customer_id' => $customer->id,
        'user_id' => $this->user->id,
        'installation_date' => now(),
    ]);

    expect($customer->installationReport->id)->toBe($report->id);
    expect($report->customer->id)->toBe($customer->id);
    expect($report->user->id)->toBe($this->user->id);
});

test('laporan pemasangan page can be rendered', function () {
    $response = $this->get(route('teknisi.laporan-pemasangan'));

    $response->assertSuccessful();
    $response->assertSee('Laporan Pemasangan');
});

test('laporan pemasangan shows installation reports data', function () {
    $customer = Customer::factory()->create([
        'name' => 'Pelanggan Uji Coba',
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    InstallationReport::create([
        'customer_id' => $customer->id,
        'user_id' => $this->user->id,
        'installation_date' => now(),
    ]);

    $response = $this->get(route('teknisi.laporan-pemasangan'));

    $response->assertSuccessful();
    $response->assertSee('Pelanggan Uji Coba');
});

test('laporan pemasangan can filter by search', function () {
    $customer1 = Customer::factory()->create([
        'name' => 'Ahmad Fauzi',
        'area_id' => $this->area->id,
    ]);
    $customer2 = Customer::factory()->create([
        'name' => 'Budi Santoso',
        'area_id' => $this->area->id,
    ]);

    InstallationReport::create([
        'customer_id' => $customer1->id,
        'user_id' => $this->user->id,
        'installation_date' => now(),
    ]);
    InstallationReport::create([
        'customer_id' => $customer2->id,
        'user_id' => $this->user->id,
        'installation_date' => now(),
    ]);

    $response = $this->get(route('teknisi.laporan-pemasangan', ['search' => 'Ahmad']));

    $response->assertSuccessful();
    $response->assertSee('Ahmad Fauzi');
    $response->assertDontSee('Budi Santoso');
});

test('laporan pemasangan can filter by date range', function () {
    $customer = Customer::factory()->create([
        'name' => 'Filter Tanggal',
        'area_id' => $this->area->id,
    ]);

    InstallationReport::create([
        'customer_id' => $customer->id,
        'user_id' => $this->user->id,
        'installation_date' => '2026-01-15',
    ]);

    $response = $this->get(route('teknisi.laporan-pemasangan', [
        'date_from' => '2026-01-01',
        'date_to' => '2026-01-31',
    ]));

    $response->assertSuccessful();
    $response->assertSee('Filter Tanggal');

    $responseOutOfRange = $this->get(route('teknisi.laporan-pemasangan', [
        'date_from' => '2026-06-01',
        'date_to' => '2026-06-30',
    ]));

    $responseOutOfRange->assertSuccessful();
    $responseOutOfRange->assertDontSee('Filter Tanggal');
});
