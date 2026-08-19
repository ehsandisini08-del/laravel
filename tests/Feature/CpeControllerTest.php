<?php

use App\Models\Cpe;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setting::set('genieacs_nbi_url', 'http://genieacs.test:7557', 'genieacs');
    Setting::set('genieacs_username', 'admin', 'genieacs');
    Setting::set('genieacs_password', 'secret', 'genieacs');
    Setting::set('genieacs_timeout', '15', 'genieacs');
    Setting::set('genieacs_online_threshold_minutes', '15', 'genieacs');
});

function cpeControllerDevicePayload(array $overrides = []): array
{
    return array_merge([
        '_id' => 'GW-100',
        '_lastInform' => now()->timestamp * 1000,
        '_deviceId' => [
            '_Manufacturer' => 'ZTE',
            '_ProductClass' => 'F660',
            '_SerialNumber' => 'SN778899',
        ],
        'InternetGatewayDevice' => [
            'DeviceInfo' => [
                'SerialNumber' => ['_value' => 'SN778899', '_type' => 'xsd:string'],
                'Manufacturer' => ['_value' => 'ZTE', '_type' => 'xsd:string'],
                'ModelName' => ['_value' => 'F660', '_type' => 'xsd:string'],
            ],
            'WANDevice' => [
                '1' => [
                    'WANConnectionDevice' => [
                        '1' => [
                            'WANIPConnection' => [
                                '1' => [
                                    'Username' => ['_value' => 'cpe-user', '_type' => 'xsd:string'],
                                    'ExternalIPAddress' => ['_value' => '10.1.1.1', '_type' => 'xsd:string'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ], $overrides);
}

test('cpes index page is accessible for superadmin', function () {
    $user = User::factory()->superadmin()->create();
    $this->actingAs($user);

    $customer = Customer::factory()->create();
    Cpe::factory()->create([
        'customer_id' => $customer->id,
        'model_name' => 'F660',
        'signal_parameters' => [
            'InternetGatewayDevice.VirtualParameters.RXPower' => [
                'label' => 'VirtualParameters.RXPower',
                'value' => '-21.3',
            ],
        ],
    ]);

    $response = $this->get(route('cpes.index'));

    $response->assertStatus(200)
        ->assertSee('CPE Devices')
        ->assertSee('F660')
        ->assertSee($customer->name)
        ->assertSee('-21.3');
});

test('cpes index page is forbidden for admin area', function () {
    $user = User::factory()->adminArea()->create();
    $this->actingAs($user);

    $this->get(route('cpes.index'))->assertStatus(403);
});

test('cpes index filters by status and search', function () {
    $user = User::factory()->superadmin()->create();
    $this->actingAs($user);

    Cpe::factory()->online()->create(['serial_number' => 'SERIES-ONLINE']);
    Cpe::factory()->offline()->create(['serial_number' => 'SERIES-OFFLINE']);

    $response = $this->get(route('cpes.index', ['status' => 'offline']));

    $response->assertStatus(200)
        ->assertSee('SERIES-OFFLINE')
        ->assertDontSee('SERIES-ONLINE');

    $response = $this->get(route('cpes.index', ['search' => 'SERIES-ONLINE']));

    $response->assertStatus(200)
        ->assertSee('SERIES-ONLINE')
        ->assertDontSee('SERIES-OFFLINE');
});

test('cpes index warns when genieacs not configured', function () {
    Setting::set('genieacs_nbi_url', '', 'genieacs');

    $user = User::factory()->superadmin()->create();
    $this->actingAs($user);

    $this->get(route('cpes.index'))
        ->assertStatus(200)
        ->assertSee('GenieACS belum dikonfigurasi');
});

test('cpe detail page shows device information and linked customer', function () {
    $user = User::factory()->superadmin()->create();
    $this->actingAs($user);

    $customer = Customer::factory()->create(['ppp_username' => 'cpe-user']);
    $cpe = Cpe::factory()->create([
        'genieacs_id' => 'GW-100',
        'customer_id' => $customer->id,
        'ppp_username' => 'cpe-user',
        'serial_number' => 'SN778899',
        'signal_parameters' => [
            'InternetGatewayDevice.X_HW_OpticalSignalLevel' => [
                'label' => 'X_HW_OpticalSignalLevel',
                'value' => '-18.5 dBm',
            ],
            'InternetGatewayDevice.VirtualParameters.RXPower' => [
                'label' => 'VirtualParameters.RXPower',
                'value' => '-21.3',
            ],
        ],
    ]);

    $response = $this->get(route('cpes.show', $cpe));

    $response->assertStatus(200)
        ->assertSee('SN778899')
        ->assertSee('cpe-user')
        ->assertSee($customer->name)
        ->assertSee('X_HW_OpticalSignalLevel')
        ->assertSee('-18.5 dBm')
        ->assertSee('RX Power')
        ->assertSee('-21.3')
        ->assertSee('Refresh dari ACS');
});

test('cpe detail page is forbidden for admin area', function () {
    $user = User::factory()->adminArea()->create();
    $this->actingAs($user);

    $cpe = Cpe::factory()->create();

    $this->get(route('cpes.show', $cpe))->assertStatus(403);
});

test('sync endpoint synchronizes devices and returns json', function () {
    Http::fake(['*genieacs.test*' => Http::response([cpeControllerDevicePayload()])]);

    $user = User::factory()->superadmin()->create();
    $this->actingAs($user);

    $response = $this->postJson(route('cpes.sync'));

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonPath('total', 1);

    expect(Cpe::count())->toBe(1);
});

test('sync endpoint returns error json when acs unreachable', function () {
    Http::fake(['*genieacs.test*' => Http::response([], 500)]);

    $user = User::factory()->superadmin()->create();
    $this->actingAs($user);

    $this->postJson(route('cpes.sync'))
        ->assertStatus(500)
        ->assertJsonPath('success', false);
});

test('refresh endpoint fetches live device data', function () {
    Http::fake(['*genieacs.test*' => Http::response([cpeControllerDevicePayload()])]);

    $user = User::factory()->superadmin()->create();
    $this->actingAs($user);

    $cpe = Cpe::factory()->create(['genieacs_id' => 'GW-100']);

    $response = $this->postJson(route('cpes.refresh', $cpe));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('cpe.genieacs_id', 'GW-100')
        ->assertJsonPath('cpe.ppp_username', 'cpe-user');
});
