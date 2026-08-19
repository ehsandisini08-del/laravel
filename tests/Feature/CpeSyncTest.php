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
        '_deviceId' => [
            '_Manufacturer' => 'Huawei Technologies Co., Ltd',
            '_OUI' => '485754',
            '_ProductClass' => 'HG8145V5',
            '_SerialNumber' => 'SN123456',
        ],
        'InternetGatewayDevice' => [
            'DeviceInfo' => [
                'SerialNumber' => ['_value' => 'SN123456', '_type' => 'xsd:string'],
                'Manufacturer' => ['_value' => 'Huawei', '_type' => 'xsd:string'],
                'ModelName' => ['_value' => 'HG8145V5', '_type' => 'xsd:string'],
                'HardwareVersion' => ['_value' => 'HW1.0', '_type' => 'xsd:string'],
                'SoftwareVersion' => ['_value' => 'V5.20', '_type' => 'xsd:string'],
                'UpTime' => ['_value' => '86400', '_type' => 'xsd:unsignedInt'],
            ],
            'WANDevice' => [
                '1' => [
                    'WANConnectionDevice' => [
                        '1' => [
                            'WANIPConnection' => [
                                '1' => [
                                    'Username' => ['_value' => 'user01', '_type' => 'xsd:string'],
                                    'ExternalIPAddress' => ['_value' => '10.0.0.5', '_type' => 'xsd:string'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'LANDevice' => [
                '1' => [
                    'WLANConfiguration' => [
                        '1' => [
                            'SSID' => ['_value' => 'NET-INDIGO', '_type' => 'xsd:string'],
                        ],
                    ],
                ],
            ],
            'X_HW' => [
                'OpticalSignalLevel' => ['_value' => '-18.5 dBm', '_type' => 'xsd:string'],
            ],
        ],
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
        ->and($cpe->uptime)->toBe(86400)
        ->and($cpe->ssid)->toBe('NET-INDIGO')
        ->and($cpe->wifi_config_path)->toBe('InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.');
});

test('pushWifiConfig enqueues setParameterValues task on the ACS', function () {
    Http::fake(['*genieacs.test*' => Http::response(['status' => 200])]);

    $cpe = Cpe::factory()->create([
        'genieacs_id' => 'GW-PUSH',
        'wifi_config_path' => 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.',
    ]);

    $result = app(CpeSyncService::class)->pushWifiConfig($cpe, 'NET-BARU', 'Pass12345');

    expect($result['success'])->toBeTrue();

    Http::assertSent(function ($request) {
        if ($request->url() !== 'http://genieacs.test:7557/devices/GW-PUSH/tasks?connection_request=true') {
            return false;
        }

        $body = $request->data();

        return $body === [
            'name' => 'setParameterValues',
            'parameterValues' => [
                ['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID', 'NET-BARU'],
                ['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.PreSharedKey', 'Pass12345'],
            ],
        ];
    });
});

test('pushWifiConfig returns error without wifi_config_path', function () {
    $cpe = Cpe::factory()->create(['wifi_config_path' => null]);

    $result = app(CpeSyncService::class)->pushWifiConfig($cpe, 'NET-BARU', 'Pass12345');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('tidak terdeteksi');

    Http::assertNothingSent();
});

test('pushWifiConfig succeeds without sending when no values provided', function () {
    $cpe = Cpe::factory()->create([
        'wifi_config_path' => 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.',
    ]);

    $result = app(CpeSyncService::class)->pushWifiConfig($cpe, null, '');

    expect($result['success'])->toBeTrue();

    Http::assertNothingSent();
});

test('pushWifiConfig reports the acs error when the task fails', function () {
    Http::fake(['*genieacs.test*' => Http::response('', 500)]);

    $cpe = Cpe::factory()->create([
        'genieacs_id' => 'GW-FAIL',
        'wifi_config_path' => 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.',
    ]);

    $result = app(CpeSyncService::class)->pushWifiConfig($cpe, 'NET-X', 'X123');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('500');
});

test('sync fills blank ssid but preserves manually edited ssid', function () {
    Http::fake(['*genieacs.test*' => Http::response([
        devicePayload(),
        devicePayload(['_id' => 'GW-002']),
    ])]);

    Cpe::factory()->create([
        'genieacs_id' => 'GW-002',
        'ssid' => 'EDITED-SSID',
        'customer_id' => null,
    ]);

    app(CpeSyncService::class)->sync();

    expect(Cpe::where('genieacs_id', 'GW-001')->first()->ssid)->toBe('NET-INDIGO')
        ->and(Cpe::where('genieacs_id', 'GW-002')->first()->ssid)->toBe('EDITED-SSID');
});

test('sync extracts ssid from TR-181 WiFi tree', function () {
    Http::fake(['*genieacs.test*' => Http::response([
        devicePayload([
            'InternetGatewayDevice' => [
                'DeviceInfo' => devicePayload()['InternetGatewayDevice']['DeviceInfo'],
                'Device' => [
                    'WiFi' => [
                        'SSID' => ['_value' => 'TR181-NET', '_type' => 'xsd:string'],
                    ],
                ],
            ],
        ]),
    ])]);

    app(CpeSyncService::class)->sync();

    expect(Cpe::first()->ssid)->toBe('TR181-NET');
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
        devicePayload(['_id' => 'GW-001', 'InternetGatewayDevice' => [
            'DeviceInfo' => ['ModelName' => ['_value' => 'HG8145V6', '_type' => 'xsd:string']],
        ]]),
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
    expect($signals)->toHaveKey('InternetGatewayDevice.X_HW.OpticalSignalLevel')
        ->and($signals['InternetGatewayDevice.X_HW.OpticalSignalLevel']['value'])->toBe('-18.5 dBm');
});

test('sync extracts VirtualParameters RXPower', function () {
    Http::fake(['*genieacs.test*' => Http::response([
        devicePayload([
            'InternetGatewayDevice' => [
                'VirtualParameters' => [
                    'RXPower' => ['_value' => '-21.3', '_type' => 'xsd:string'],
                    'TXPower' => ['_value' => '2.1', '_type' => 'xsd:string'],
                    'OLTRXPower' => ['_value' => '-17.8', '_type' => 'xsd:string'],
                    'ExpectedThroughput' => ['_value' => '100000', '_type' => 'xsd:unsignedInt'],
                ],
            ],
        ]),
    ])]);

    app(CpeSyncService::class)->sync();

    $signals = Cpe::first()->signal_parameters;
    expect($signals)->toHaveKey('InternetGatewayDevice.VirtualParameters.RXPower')
        ->and($signals['InternetGatewayDevice.VirtualParameters.RXPower']['value'])->toBe('-21.3')
        ->and($signals['InternetGatewayDevice.VirtualParameters.TXPower']['value'])->toBe('2.1')
        ->and($signals['InternetGatewayDevice.VirtualParameters.OLTRXPower']['value'])->toBe('-17.8')
        ->and($signals)->not->toHaveKey('InternetGatewayDevice.VirtualParameters.ExpectedThroughput');
});

test('sync uses deviceId fallback when DeviceInfo params are missing', function () {
    Http::fake(['*genieacs.test*' => Http::response([
        devicePayload([
            '_id' => 'GW-FRESH',
            'InternetGatewayDevice' => [
                'WANDevice' => [
                    '1' => ['WANConnectionDevice' => ['1' => ['WANIPConnection' => ['1' => [
                        'Username' => ['_value' => 'user02', '_type' => 'xsd:string'],
                    ]]]]],
                ],
            ],
        ]),
    ])]);

    app(CpeSyncService::class)->sync();

    $cpe = Cpe::first();
    expect($cpe->serial_number)->toBe('SN123456')
        ->and($cpe->manufacturer)->toBe('Huawei Technologies Co., Ltd')
        ->and($cpe->model_name)->toBe('HG8145V5')
        ->and($cpe->ppp_username)->toBe('user02')
        ->and($cpe->model_number)->toBeNull();
});

test('sync extracts pppoe username from WANPPPConnection path', function () {
    Http::fake(['*genieacs.test*' => Http::response([
        devicePayload([
            '_id' => 'GW-PPP',
            'InternetGatewayDevice' => [
                'WANDevice' => [
                    '1' => ['WANConnectionDevice' => ['1' => ['WANPPPConnection' => ['1' => [
                        'Username' => ['_value' => 'ppp-user', '_type' => 'xsd:string'],
                    ]]]]],
                ],
            ],
        ]),
    ])]);

    app(CpeSyncService::class)->sync();

    expect(Cpe::first()->ppp_username)->toBe('ppp-user');
});

test('sync treats epoch seconds lastInform as recent and marks online', function () {
    Http::fake(['*genieacs.test*' => Http::response([
        devicePayload(['_lastInform' => now()->timestamp]),
    ])]);

    app(CpeSyncService::class)->sync();

    expect(Cpe::first()->status)->toBe(Cpe::STATUS_ONLINE);
});

test('sync treats iso date string lastInform as recent and marks online', function () {
    Http::fake(['*genieacs.test*' => Http::response([
        devicePayload(['_lastInform' => now()->toIso8601String()]),
    ])]);

    app(CpeSyncService::class)->sync();

    expect(Cpe::first()->status)->toBe(Cpe::STATUS_ONLINE);
});

test('rx power accessor prefers VirtualParameters over vendor parameter', function () {
    $cpe = Cpe::factory()->create([
        'signal_parameters' => [
            'InternetGatewayDevice.WANDevice.1.X_CT-COM_EponInterfaceConfig.RXPower' => [
                'label' => 'WANDevice.1.X_CT-COM_EponInterfaceConfig.RXPower',
                'value' => '-5.1',
            ],
            'InternetGatewayDevice.VirtualParameters.RXPower' => [
                'label' => 'VirtualParameters.RXPower',
                'value' => '-21.3',
            ],
        ],
    ]);

    expect($cpe->rx_power)->toBe('-21.3');
});

test('rx power accessor falls back to vendor parameter when virtual is missing', function () {
    $cpe = Cpe::factory()->create([
        'signal_parameters' => [
            'InternetGatewayDevice.WANDevice.1.X_CT-COM_EponInterfaceConfig.RXPower' => [
                'label' => 'WANDevice.1.X_CT-COM_EponInterfaceConfig.RXPower',
                'value' => '-5.1',
            ],
        ],
    ]);

    expect($cpe->rx_power)->toBe('-5.1');
});

test('rx power accessor returns null when no rx power parameter exists', function () {
    $cpe = Cpe::factory()->create([
        'signal_parameters' => [
            'InternetGatewayDevice.VirtualParameters.TXPower' => [
                'label' => 'VirtualParameters.TXPower',
                'value' => '2.1',
            ],
        ],
    ]);

    expect($cpe->rx_power)->toBeNull();
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
    Http::fake(['*genieacs.test*' => Http::response([devicePayload(['_id' => 'GW-NEW'])])]);

    $result = app(CpeSyncService::class)->refreshDevice('GW-NEW');

    expect($result['success'])->toBeTrue()
        ->and($result['cpe']->genieacs_id)->toBe('GW-NEW')
        ->and($result['cpe']->ppp_username)->toBe('user01');
});

test('refreshDevice queries the devices collection by _id instead of GET /devices/{id}', function () {
    Http::fake(['*genieacs.test*' => Http::response([devicePayload(['_id' => 'GW-NEW'])])]);

    $result = app(CpeSyncService::class)->refreshDevice('GW-NEW');

    expect($result['success'])->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/devices')
            && str_contains($request->url(), 'query=')
            && str_contains($request->url(), '%22_id%22')
            && str_contains($request->url(), 'GW-NEW');
    });
});

test('refreshDevice reports missing device', function () {
    Http::fake(['*genieacs.test*' => Http::response([])]);

    $result = app(CpeSyncService::class)->refreshDevice('GW-NOPE');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toBe('Device tidak ditemukan di GenieACS.');
});
