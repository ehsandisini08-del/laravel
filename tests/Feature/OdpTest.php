<?php

use App\Models\Odc;
use App\Models\Odp;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('odp can be created with factory', function () {
    $odc = Odc::factory()->create();
    $odp = Odp::factory()->create([
        'odc_id' => $odc->id,
        'kode_odp' => 'ODP-001',
        'nama_odp' => 'ODP Kebon Jeruk',
    ]);

    expect($odp->kode_odp)->toBe('ODP-001')
        ->and($odp->nama_odp)->toBe('ODP Kebon Jeruk')
        ->and($odp->odc->is($odc))->toBeTrue()
        ->and($odp->latitude)->not->toBeNull();
});

test('odp can be created without coordinates', function () {
    $odp = Odp::factory()->withoutCoordinates()->create();

    expect($odp->latitude)->toBeNull()
        ->and($odp->longitude)->toBeNull();
});

test('odp kode must be unique', function () {
    Odp::factory()->create(['kode_odp' => 'ODP-001']);

    expect(fn () => Odp::factory()->create(['kode_odp' => 'ODP-001']))
        ->toThrow(QueryException::class);
});

test('odp index redirects to infrastruktur page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('odps.index'))
        ->assertRedirect(route('infrastruktur.index'));
});

test('odp create page is accessible and lists odcs', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $odc = Odc::factory()->create(['kode_odc' => 'ODC-100']);

    $this->get(route('odps.create'))
        ->assertStatus(200)
        ->assertSee('Add ODP')
        ->assertSee($odc->kode_odc);
});

test('odp can be stored', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $odc = Odc::factory()->create();

    $this->post(route('odps.store'), [
        'odc_id' => $odc->id,
        'kode_odp' => 'ODP-100',
        'nama_odp' => 'ODP Cengkareng',
        'latitude' => -6.1475,
        'longitude' => 106.8025,
    ])->assertRedirect(route('infrastruktur.index'));

    $this->assertDatabaseHas('odps', [
        'odc_id' => $odc->id,
        'kode_odp' => 'ODP-100',
        'nama_odp' => 'ODP Cengkareng',
    ]);
});

test('odp store requires odc, kode and nama', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('odps.store'), [])
        ->assertSessionHasErrors(['odc_id', 'kode_odp', 'nama_odp']);
});

test('odp store rejects invalid odc', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('odps.store'), [
        'odc_id' => 999999,
        'kode_odp' => 'ODP-100',
        'nama_odp' => 'ODP Test',
    ])->assertSessionHasErrors(['odc_id']);
});

test('odp store rejects duplicate kode', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Odp::factory()->create(['kode_odp' => 'ODP-001']);

    $this->post(route('odps.store'), [
        'odc_id' => Odc::factory()->create()->id,
        'kode_odp' => 'ODP-001',
        'nama_odp' => 'ODP Lain',
    ])->assertSessionHasErrors(['kode_odp']);
});

test('odp edit page is accessible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $odp = Odp::factory()->create();

    $this->get(route('odps.edit', $odp))
        ->assertStatus(200)
        ->assertSee('Edit ODP')
        ->assertSee($odp->kode_odp);
});

test('odp can be updated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $odp = Odp::factory()->create();
    $newOdc = Odc::factory()->create();

    $this->put(route('odps.update', $odp), [
        'odc_id' => $newOdc->id,
        'kode_odp' => $odp->kode_odp,
        'nama_odp' => 'ODP Diperbarui',
        'latitude' => -6.2000,
        'longitude' => 106.8167,
    ])->assertRedirect(route('infrastruktur.index'));

    expect($odp->fresh()->nama_odp)->toBe('ODP Diperbarui')
        ->and($odp->fresh()->odc_id)->toBe($newOdc->id);
});

test('odp can be deleted', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $odp = Odp::factory()->create();

    $this->delete(route('odps.destroy', $odp))
        ->assertRedirect(route('infrastruktur.index'));

    $this->assertDatabaseMissing('odps', ['id' => $odp->id]);
});

test('odps are deleted when its odc is deleted', function () {
    $odp = Odp::factory()->create();

    $odp->odc->delete();

    $this->assertDatabaseMissing('odps', ['id' => $odp->id]);
});

test('infrastruktur index shows odp list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $odc = Odc::factory()->create(['kode_odc' => 'ODC-555']);
    $odp = Odp::factory()->create([
        'odc_id' => $odc->id,
        'kode_odp' => 'ODP-777',
        'nama_odp' => 'ODP Kebayoran',
    ]);

    $this->get(route('infrastruktur.index'))
        ->assertStatus(200)
        ->assertSee('ODP-777')
        ->assertSee($odp->nama_odp)
        ->assertSee('ODC-555');
});
