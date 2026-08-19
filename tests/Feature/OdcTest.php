<?php

use App\Models\Odc;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('odc can be created with factory', function () {
    $odc = Odc::factory()->create([
        'kode_odc' => 'ODC-001',
        'nama_odc' => 'ODC Kebon Jeruk',
    ]);

    expect($odc->kode_odc)->toBe('ODC-001')
        ->and($odc->nama_odc)->toBe('ODC Kebon Jeruk')
        ->and($odc->latitude)->not->toBeNull();
});

test('odc can be created without coordinates', function () {
    $odc = Odc::factory()->withoutCoordinates()->create();

    expect($odc->latitude)->toBeNull()
        ->and($odc->longitude)->toBeNull();
});

test('odc kode must be unique', function () {
    Odc::factory()->create(['kode_odc' => 'ODC-001']);

    expect(fn () => Odc::factory()->create(['kode_odc' => 'ODC-001']))
        ->toThrow(QueryException::class);
});

test('odc index redirects to infrastruktur page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('odcs.index'))
        ->assertRedirect(route('infrastruktur.index'));
});

test('odc create page is accessible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('odcs.create'))
        ->assertStatus(200)
        ->assertSee('Add ODC');
});

test('odc can be stored', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('odcs.store'), [
        'kode_odc' => 'ODC-100',
        'nama_odc' => 'ODC Cengkareng',
        'latitude' => -6.1475,
        'longitude' => 106.8025,
    ])->assertRedirect(route('infrastruktur.index'));

    $this->assertDatabaseHas('odcs', [
        'kode_odc' => 'ODC-100',
        'nama_odc' => 'ODC Cengkareng',
    ]);
});

test('odc store requires kode and nama', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('odcs.store'), [])
        ->assertSessionHasErrors(['kode_odc', 'nama_odc']);
});

test('odc store rejects duplicate kode', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Odc::factory()->create(['kode_odc' => 'ODC-001']);

    $this->post(route('odcs.store'), [
        'kode_odc' => 'ODC-001',
        'nama_odc' => 'ODC Lain',
    ])->assertSessionHasErrors(['kode_odc']);
});

test('odc edit page is accessible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $odc = Odc::factory()->create();

    $this->get(route('odcs.edit', $odc))
        ->assertStatus(200)
        ->assertSee('Edit ODC')
        ->assertSee($odc->kode_odc);
});

test('odc can be updated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $odc = Odc::factory()->create();

    $this->put(route('odcs.update', $odc), [
        'kode_odc' => $odc->kode_odc,
        'nama_odc' => 'ODC Diperbarui',
        'latitude' => -6.2000,
        'longitude' => 106.8167,
    ])->assertRedirect(route('infrastruktur.index'));

    expect($odc->fresh()->nama_odc)->toBe('ODC Diperbarui');
});

test('odc can be deleted', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $odc = Odc::factory()->create();

    $this->delete(route('odcs.destroy', $odc))
        ->assertRedirect(route('infrastruktur.index'));

    $this->assertDatabaseMissing('odcs', ['id' => $odc->id]);
});

test('infrastruktur index shows odc list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $odc = Odc::factory()->create(['kode_odc' => 'ODC-777']);

    $this->get(route('infrastruktur.index'))
        ->assertStatus(200)
        ->assertSee('ODC-777')
        ->assertSee($odc->nama_odc);
});
