<?php

use App\Models\Cpe;
use App\Models\Customer;
use App\Models\Setting;
use App\Services\Genieacs\CpeSyncService;
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

function devicePayload(array $overrides = []): array
{
    return array_merge([
        '_id' => 'GW-001',
        '_lastInform' => now()->timestamp * 1000,
        '_tags' => ['provisioned'],
        'InternetGatewayDevice.DeviceInfo.SerialNumber' => 'SN123456',
        'InternetGatewayDevice.DeviceInfo.Manufacturer' => 'Huawei',
        'InternetGatewayDevice.DeviceInfo.ModelName' => 'HG8145V5',
        'InternetGatewayDevice.DeviceInfo.HardwareVersion' => 'HW1.0',
        'InternetGatewayDevice.DeviceInfo.SoftwareVersion' => 'V5.20',
        'InternetGatewayDevice.DeviceInfo.UpTime' => '86400',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.Username' => 'user01',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.ExternalIPAddress' => '10.0.0.5',
        'InternetGatewayDevice.X_HW_OpticalSignalLevel' => '-18.5 dBm',
    ], $overrides);
}

test('sync stores devices and matches customers by pppoe username', function () {
    Http::fake(['*genieacs.test*' => Http::response([devicePayload()])]);

    $customer = Customer::factory()->create(['ppp_username' => 'user01']);

    $result = app(CpeSyncService::class)->sync();

    expect($result['success'])->toBeTrue()
        ->and($result['total'])->toBe(1)
        ->and($result['matched'])->toBe(1);

    $cpe = Cpe::first();
    expect($cpe)->not->toBeNull()
        ->and($cpe->customer_id)->toBe($customer->id)
        ->and($cpe->ppp_username)->toBe('user01')
        ->and($cpe->genieacs_id)->toBe('GW-001')
        ->and($cpe->serial_number)->toBe('SN123456')
        ->and($cpe->manufacturer)->toBe('Huawei')
        ->and($cpe->model_name)->toBe('HG8145V5')
        ->and($cpe->ip_address)->toBe('10.0.0.5')
        ->and($cpe->status)->toBe(Cpe::STATUS_ONLINE)
        ->and($cpe->uptime)->toBe(86400);
});

test('sync leaves device unlinked when no customer matches', function () {
    Http::fake(['*genieacs.test*' => Http::response([devicePayload()])]);

    $result = app(CpeSyncService::class)->sync();

    expect($result['matched'])->toBe(0);

    $cpe = Cpe::first();
    expect($cpe->customer_id)->toBeNull()
        ->and($cpe->ppp_username)->toBe('user01');
});

test('sync marks device offline when last inform is old', function () {
    Http::fake(['*genieacs.test*' => Http::response([
        devicePayload(['_lastInform' => now()->subHour()->timestamp * 1000]),
    ])]);

    app(CpeSyncService::class)->sync();

    expect(Cpe::first()->status)->toBe(Cpe::STATUS_OFFLINE);
});

test('sync marks device unknown when last inform is missing', function () {
    Http::fake(['*genieacs.test*' => Http::response([devicePayload(['_lastInform' => null])])]);

    app(CpeSyncService::class)->sync();

    expect(Cpe::first()->status)->toBe(Cpe::STATUS_UNKNOWN);
});

test('sync updates existing device instead of duplicating', function () {
    Http::fake(['*genieacs.test*' => Http::response([
        devicePayload(['_id' => 'GW-001', 'InternetGatewayDevice.DeviceInfo.ModelName' => 'HG8145V6']),
    ])]);

    Cpe::factory()->create(['genieacs_id' => 'GW-001', 'model_name' => 'OLD']);

    app(CpeSyncService::class)->sync();

    expect(Cpe::count())->toBe(1)
        ->and(Cpe::first()->model_name)->toBe('HG8145V6');
});

test('sync marks devices missing from acs as offline', function () {
    Http::fake(['*genieacs.test*' => Http::response([devicePayload(['_id' => 'GW-001'])])]);

    $orphan = Cpe::factory()->online()->create(['genieacs_id' => 'GW-ORPHAN']);

    app(CpeSyncService::class)->sync();

    expect($orphan->fresh()->status)->toBe(Cpe::STATUS_OFFLINE);
});

test('sync extracts vendor signal parameters', function () {
    Http::fake(['*genieacs.test*' => Http::response([devicePayload()])]);

    app(CpeSyncService::class)->sync();

    $signals = Cpe::first()->signal_parameters;
    expect($signals)->toHaveKey('InternetGatewayDevice.X_HW_OpticalSignalLevel')
        ->and($signals['InternetGatewayDevice.X_HW_OpticalSignalLevel']['value'])->toBe('-18.5 dBm');
});

test('sync returns error when nbi url not configured', function () {
    Setting::set('genieacs_nbi_url', '', 'genieacs');

    $result = app(CpeSyncService::class)->sync();

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('belum dikonfigurasi');
});

test('sync returns error when nbi request fails', function () {
    Http::fake(['*genieacs.test*' => Http::response([], 500)]);

    $result = app(CpeSyncService::class)->sync();

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('500');
});

test('sync paginates through all devices', function () {
    Http::fake(['*genieacs.test*' => function ($request) {
        parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);
        $skip = (int) ($query['skip'] ?? 0);

        return match ($skip) {
            0 => Http::response(array_map(fn ($id) => devicePayload(['_id' => "GW-{$id}"]), range(1, 1000))),
            1000 => Http::response(array_map(fn ($id) => devicePayload(['_id' => "GW-{$id}"]), range(1001, 1002))),
            default => Http::response([]),
        };
    }]);

    app(CpeSyncService::class)->sync();

    expect(Cpe::count())->toBe(1002);
});

test('refreshDevice persists a single device', function () {
    Http::fake(['*genieacs.test*' => Http::response(devicePayload(['_id' => 'GW-NEW']))]);

    $result = app(CpeSyncService::class)->refreshDevice('GW-NEW');

    expect($result['success'])->toBeTrue()
        ->and($result['cpe']->genieacs_id)->toBe('GW-NEW')
        ->and($result['cpe']->ppp_username)->toBe('user01');
});
