<?php

use App\Models\Odc;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
});

test('odc index page is accessible', function () {
    $this->actingAs($this->admin)
        ->get(route('ftth.odc.index'))
        ->assertOk();
});

test('odc create page is accessible', function () {
    $this->actingAs($this->admin)
        ->get(route('ftth.odc.create'))
        ->assertOk();
});

test('odc can be stored', function () {
    $this->actingAs($this->admin)
        ->post(route('ftth.odc.store'), [
            'kode' => 'ODC-TEST-001',
            'nama' => 'ODC Test',
            'kapasitas' => 144,
            'status' => 'ACTIVE',
        ])
        ->assertRedirect(route('ftth.odc.index'));

    $this->assertDatabaseHas('odcs', ['kode' => 'ODC-TEST-001']);
});

test('odc show page displays odc', function () {
    $odc = Odc::create([
        'kode' => 'ODC-SHOW-001',
        'nama' => 'ODC Show Test',
        'kapasitas' => 144,
        'status' => 'ACTIVE',
    ]);

    $this->actingAs($this->admin)
        ->get(route('ftth.odc.show', $odc))
        ->assertOk()
        ->assertSee('ODC-SHOW-001');
});

test('odc can be updated', function () {
    $odc = Odc::create([
        'kode' => 'ODC-UPD-001',
        'nama' => 'ODC Update Test',
        'kapasitas' => 144,
        'status' => 'ACTIVE',
    ]);

    $this->actingAs($this->admin)
        ->put(route('ftth.odc.update', $odc), [
            'kode' => 'ODC-UPD-001',
            'nama' => 'ODC Updated',
            'kapasitas' => 288,
            'status' => 'WARNING',
        ])
        ->assertRedirect(route('ftth.odc.show', $odc));

    $this->assertDatabaseHas('odcs', ['nama' => 'ODC Updated', 'kapasitas' => 288]);
});

test('odc can be deleted', function () {
    $odc = Odc::create([
        'kode' => 'ODC-DEL-001',
        'nama' => 'ODC Delete Test',
        'kapasitas' => 144,
        'status' => 'ACTIVE',
    ]);

    $this->actingAs($this->admin)
        ->delete(route('ftth.odc.destroy', $odc))
        ->assertRedirect(route('ftth.odc.index'));

    $this->assertDatabaseMissing('odcs', ['kode' => 'ODC-DEL-001']);
});

test('ftth api odcs endpoint returns json', function () {
    $this->actingAs($this->admin)
        ->getJson(route('ftth.api.odcs'))
        ->assertOk()
        ->assertJsonIsArray();
});

test('ftth api stats endpoint returns json', function () {
    $this->actingAs($this->admin)
        ->getJson(route('ftth.api.stats'))
        ->assertOk()
        ->assertJsonStructure(['total_odc', 'total_odp', 'total_customers', 'customers_online']);
});
