<?php

use App\Models\Area;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Router;
use App\Models\User;
use App\Services\KtpOcrService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

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

test('nik is required when creating a customer', function () {
    $response = $this->post(route('customers.store'), [
        'name' => 'Tanpa NIK',
        'address' => 'Jl. Tanpa NIK',
        'phone' => '081200001111',
        'latitude' => '-6.2088',
        'longitude' => '106.8456',
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'ppp_username' => 'tanpa_nik_ppp',
        'ppp_password' => 'secret123',
        'installation_date' => now()->format('Y-m-d'),
        'due_day' => 10,
    ]);

    $response->assertSessionHasErrors('nik');
    $this->assertDatabaseMissing('customers', ['ppp_username' => 'tanpa_nik_ppp']);
});

test('nik must be exactly 16 digits', function (string $nik) {
    $response = $this->post(route('customers.store'), [
        'name' => 'NIK Invalid',
        'address' => 'Jl. NIK Invalid',
        'phone' => '081200002222',
        'nik' => $nik,
        'latitude' => '-6.2088',
        'longitude' => '106.8456',
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'ppp_username' => 'nik_invalid_ppp',
        'ppp_password' => 'secret123',
        'installation_date' => now()->format('Y-m-d'),
        'due_day' => 10,
    ]);

    $response->assertSessionHasErrors('nik');
})->with([
    'terlalu pendek' => '12345',
    'terlalu panjang' => '327301010101000199',
    'mengandung huruf' => '32730101010100ab',
]);

test('customer can be created with valid nik', function () {
    $response = $this->post(route('customers.store'), [
        'name' => 'Dengan NIK',
        'address' => 'Jl. Dengan NIK',
        'phone' => '081200003333',
        'nik' => '3273010101010042',
        'latitude' => '-6.2088',
        'longitude' => '106.8456',
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'ppp_username' => 'dengan_nik_ppp',
        'ppp_password' => 'secret123',
        'installation_date' => now()->format('Y-m-d'),
        'due_day' => 10,
    ]);

    $response->assertRedirect(route('customers.index'));

    $this->assertDatabaseHas('customers', [
        'ppp_username' => 'dengan_nik_ppp',
        'nik' => '3273010101010042',
    ]);
});

test('ktp photo is stored when creating a customer', function () {
    Storage::fake('public');

    $response = $this->post(route('customers.store'), [
        'name' => 'Dengan Foto KTP',
        'address' => 'Jl. Foto KTP',
        'phone' => '081200004444',
        'nik' => '3273010101010043',
        'ktp_photo' => UploadedFile::fake()->image('ktp.jpg', 800, 500),
        'latitude' => '-6.2088',
        'longitude' => '106.8456',
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'ppp_username' => 'foto_ktp_ppp',
        'ppp_password' => 'secret123',
        'installation_date' => now()->format('Y-m-d'),
        'due_day' => 10,
    ]);

    $response->assertRedirect(route('customers.index'));

    $customer = Customer::where('ppp_username', 'foto_ktp_ppp')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->ktp_photo_path)->not->toBeNull();

    Storage::disk('public')->assertExists($customer->ktp_photo_path);
});

test('ktp photo must be an image', function () {
    $response = $this->post(route('customers.store'), [
        'name' => 'File Bukan Gambar',
        'address' => 'Jl. File',
        'phone' => '081200005555',
        'nik' => '3273010101010044',
        'ktp_photo' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        'latitude' => '-6.2088',
        'longitude' => '106.8456',
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'ppp_username' => 'file_bukan_gambar_ppp',
        'ppp_password' => 'secret123',
        'installation_date' => now()->format('Y-m-d'),
        'due_day' => 10,
    ]);

    $response->assertSessionHasErrors('ktp_photo');
});

test('ocr endpoint returns detected nik from ktp photo', function () {
    Storage::fake('local');

    $this->mock(KtpOcrService::class, function ($mock) {
        $mock->shouldReceive('extractNik')->once()->andReturn('3273010101010099');
    });

    $response = $this->post(route('customers.ocr-ktp'), [
        'ktp_photo' => UploadedFile::fake()->image('ktp.jpg', 800, 500),
    ]);

    $response->assertOk()->assertJson([
        'success' => true,
        'nik' => '3273010101010099',
    ]);
});

test('ocr endpoint returns failure when nik cannot be detected', function () {
    Storage::fake('local');

    $this->mock(KtpOcrService::class, function ($mock) {
        $mock->shouldReceive('extractNik')->once()->andReturn(null);
    });

    $response = $this->post(route('customers.ocr-ktp'), [
        'ktp_photo' => UploadedFile::fake()->image('ktp.jpg', 800, 500),
    ]);

    $response->assertOk()->assertJson([
        'success' => false,
        'nik' => null,
    ]);
});

test('ocr endpoint requires a valid image', function () {
    $response = $this->post(route('customers.ocr-ktp'), [
        'ktp_photo' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
    ]);

    $response->assertSessionHasErrors('ktp_photo');
});

test('customer detail page displays nik and ktp photo', function () {
    Storage::fake('public');

    $path = UploadedFile::fake()->image('ktp.jpg')->store('ktp/1', 'public');

    $customer = Customer::factory()->create([
        'nik' => '3273010101010055',
        'ktp_photo_path' => $path,
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $response = $this->get(route('customers.show', $customer));

    $response->assertOk()
        ->assertSee('3273010101010055')
        ->assertSee('NIK');
});
