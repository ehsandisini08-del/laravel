<?php

use App\Models\Setting;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function validSettingsPayload(array $overrides = []): array
{
    $service = app(SettingService::class);
    $payload = [];

    foreach ($service->sections() as $section) {
        foreach ($section['fields'] as $key => $field) {
            $payload[$key] = $field['type'] === 'boolean'
                ? ($field['default'] ? '1' : '0')
                : (string) $field['default'];
        }
    }

    return array_merge($payload, $overrides);
}

test('settings index page is accessible and shows saved values', function () {
    $user = User::factory()->developer()->create();
    $this->actingAs($user);

    Setting::set('app_name', 'NetWave ISP', 'general');

    $response = $this->get(route('settings.index'));

    $response->assertStatus(200)
        ->assertSee('Settings')
        ->assertSee('NetWave ISP');
});

test('settings can be updated via controller', function () {
    $user = User::factory()->developer()->create();
    $this->actingAs($user);

    $response = $this->post(route('settings.update'), validSettingsPayload([
        'app_name' => 'NetWave ISP',
        'company_name' => 'PT NetWave Indonesia',
        'invoice_prefix' => 'INV',
        'auto_isolate_enabled' => '0',
        'log_retention_days' => '60',
    ]));

    $response->assertRedirect(route('settings.index'));
    $response->assertSessionHas('success');

    expect(Setting::get('app_name'))->toBe('NetWave ISP')
        ->and(Setting::get('company_name'))->toBe('PT NetWave Indonesia')
        ->and(Setting::get('auto_isolate_enabled'))->toBe('0')
        ->and(Setting::get('log_retention_days'))->toBe('60');
});

test('settings boolean fields are stored as normalized value', function () {
    $user = User::factory()->developer()->create();
    $this->actingAs($user);

    $this->post(route('settings.update'), validSettingsPayload([
        'auto_isolate_enabled' => '1',
        'payment_sandbox' => '0',
        'maintenance_mode' => '1',
    ]));

    expect(Setting::get('auto_isolate_enabled'))->toBe('1')
        ->and(Setting::get('payment_sandbox'))->toBe('0')
        ->and(Setting::get('maintenance_mode'))->toBe('1');
});

test('settings update validates invalid input', function () {
    $user = User::factory()->developer()->create();
    $this->actingAs($user);

    $response = $this->post(route('settings.update'), validSettingsPayload([
        'app_name' => '',
        'default_due_day' => '40',
        'company_email' => 'not-an-email',
        'log_retention_days' => '500',
    ]));

    $response->assertSessionHasErrors(['app_name', 'default_due_day', 'company_email', 'log_retention_days']);
});

test('setting helper get returns default when key missing', function () {
    expect(Setting::get('tidak_ada', 'fallback'))->toBe('fallback');
});

test('setting helper set and allSettings work', function () {
    Setting::set('app_name', 'NetWave', 'general');
    Setting::set('company_name', 'PT NetWave', 'company');

    $all = Setting::allSettings();

    expect($all['app_name'])->toBe('NetWave')
        ->and($all['company_name'])->toBe('PT NetWave');
});

test('setting helper byGroup filters by group', function () {
    Setting::set('app_name', 'NetWave', 'general');
    Setting::set('company_name', 'PT NetWave', 'company');

    $general = Setting::byGroup('general');

    expect($general)->toHaveKey('app_name')
        ->and($general)->not->toHaveKey('company_name');
});

test('setting service exposes defaults for all sections', function () {
    $service = app(SettingService::class);

    $defaults = $service->defaults();

    expect($defaults)->toHaveKey('app_name')
        ->and($defaults)->toHaveKey('company_name')
        ->and($defaults)->toHaveKey('invoice_prefix')
        ->and($defaults)->toHaveKey('mail_host')
        ->and($defaults)->toHaveKey('payment_provider')
        ->and($defaults)->toHaveKey('genieacs_nbi_url')
        ->and($defaults)->toHaveKey('genieacs_username')
        ->and($defaults)->toHaveKey('genieacs_password')
        ->and($defaults)->toHaveKey('genieacs_online_threshold_minutes')
        ->and($defaults)->toHaveKey('log_retention_days');
});

test('settings payment page shows webhook url and provider scoped fields', function () {
    $user = User::factory()->developer()->create();
    $this->actingAs($user);

    Setting::set('payment_provider', 'midtrans', 'payment');

    $response = $this->get(route('settings.index'));

    $response->assertStatus(200)
        ->assertSee('/webhooks/payment/midtrans')
        ->assertSee('/webhooks/payment/xendit')
        ->assertSee('/webhooks/payment/tripay')
        ->assertSee('payment_midtrans_server_key')
        ->assertSee("x-show=\"paymentProvider === 'midtrans'\"", false)
        ->assertSee("x-show=\"paymentProvider === 'xendit'\"", false)
        ->assertSee("x-show=\"paymentProvider === 'tripay'\"", false);
});

test('setting service exposes payment webhook urls', function () {
    $service = app(SettingService::class);

    $webhooks = $service->paymentWebhooks();

    expect($webhooks)->toHaveKey('midtrans')
        ->and($webhooks['midtrans'])->toContain('/webhooks/payment/midtrans')
        ->and($webhooks['xendit'])->toContain('/webhooks/payment/xendit')
        ->and($webhooks['tripay'])->toContain('/webhooks/payment/tripay');
});

test('password settings persist and stay prefilled after save and refresh', function () {
    $user = User::factory()->developer()->create();
    $this->actingAs($user);

    $this->post(route('settings.update'), validSettingsPayload([
        'payment_midtrans_server_key' => 'SK-123',
        'payment_tripay_private_key' => 'PRIV-456',
    ]));

    expect(Setting::get('payment_midtrans_server_key'))->toBe('SK-123')
        ->and(Setting::get('payment_tripay_private_key'))->toBe('PRIV-456');

    $response = $this->get(route('settings.index'));

    $response->assertStatus(200)
        ->assertSee('value="SK-123"', false)
        ->assertSee('value="PRIV-456"', false);
});

test('company logo can be uploaded and stored', function () {
    Storage::fake('public');

    $user = User::factory()->developer()->create();
    $this->actingAs($user);

    $file = UploadedFile::fake()->image('custom_logo.png', 200, 200);

    $response = $this->post(route('settings.update'), validSettingsPayload([
        'company_logo' => $file,
    ]));

    $response->assertRedirect(route('settings.index'));
    $response->assertSessionHas('success');

    $storedLogo = Setting::get('company_logo');
    expect($storedLogo)->not->toBeEmpty();

    Storage::disk('public')->assertExists($storedLogo);
});

test('company logo can be removed', function () {
    Storage::fake('public');

    $user = User::factory()->developer()->create();
    $this->actingAs($user);

    $file = UploadedFile::fake()->image('custom_logo.png');
    $path = $file->store('company', 'public');
    Setting::set('company_logo', $path, 'company');

    expect(Storage::disk('public')->exists($path))->toBeTrue();

    $response = $this->post(route('settings.update'), validSettingsPayload([
        'remove_company_logo' => '1',
    ]));

    $response->assertRedirect(route('settings.index'));
    $response->assertSessionHas('success');

    expect(Setting::get('company_logo'))->toBe('')
        ->and(Storage::disk('public')->exists($path))->toBeFalse();
});

test('updating other settings preserves existing company logo', function () {
    Storage::fake('public');

    $user = User::factory()->developer()->create();
    $this->actingAs($user);

    $file = UploadedFile::fake()->image('existing_logo.png');
    $path = $file->store('company', 'public');
    Setting::set('company_logo', $path, 'company');

    $response = $this->post(route('settings.update'), validSettingsPayload([
        'company_name' => 'PT Maju Terus',
        'company_logo' => '',
    ]));

    $response->assertRedirect(route('settings.index'));

    expect(Setting::get('company_logo'))->toBe($path)
        ->and(Setting::get('company_name'))->toBe('PT Maju Terus')
        ->and(Storage::disk('public')->exists($path))->toBeTrue();
});

test('company logo validates file format and size', function () {
    Storage::fake('public');

    $user = User::factory()->developer()->create();
    $this->actingAs($user);

    // Invalid file type (PDF instead of image)
    $invalidFile = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

    $response = $this->post(route('settings.update'), validSettingsPayload([
        'company_logo' => $invalidFile,
    ]));

    $response->assertSessionHasErrors(['company_logo']);

    // Oversized file (> 2MB)
    $oversizedFile = UploadedFile::fake()->image('big_logo.png')->size(3000);

    $response2 = $this->post(route('settings.update'), validSettingsPayload([
        'company_logo' => $oversizedFile,
    ]));

    $response2->assertSessionHasErrors(['company_logo']);
});

test('settings index page renders company logo input and preview when logo exists', function () {
    Storage::fake('public');

    $user = User::factory()->developer()->create();
    $this->actingAs($user);

    $file = UploadedFile::fake()->image('test_logo.png');
    $path = $file->store('company', 'public');
    Setting::set('company_logo', $path, 'company');

    $response = $this->get(route('settings.index'));

    $response->assertStatus(200)
        ->assertSee('enctype="multipart/form-data"', false)
        ->assertSee('name="company_logo"', false)
        ->assertSee('name="remove_company_logo"', false)
        ->assertSee($path);
});
