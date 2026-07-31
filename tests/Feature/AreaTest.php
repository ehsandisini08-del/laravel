<?php

use App\Models\Area;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('area can be created with factory', function () {
    $area = Area::factory()->create(['code' => 'JKT', 'name' => 'Jakarta']);

    expect($area->code)->toBe('JKT')
        ->and($area->name)->toBe('Jakarta')
        ->and($area->is_active)->toBeTrue();
});

test('area code and name must be unique', function () {
    Area::factory()->create(['code' => 'JKT', 'name' => 'Jakarta']);

    expect(fn () => Area::factory()->create(['code' => 'JKT', 'name' => 'Jakarta']))
        ->toThrow(QueryException::class);
});

test('area can be inactive', function () {
    $area = Area::factory()->inactive()->create();

    expect($area->is_active)->toBeFalse();
});

test('area index page is accessible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Area::factory()->count(3)->create();

    $response = $this->get(route('areas.index'));

    $response->assertStatus(200);
});

test('area create page is accessible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('areas.create'));

    $response->assertStatus(200);
});

test('area can be stored via controller', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('areas.store'), [
        'code' => 'JKT',
        'name' => 'Jakarta',
    ]);

    $response->assertRedirect(route('areas.index'));
    $response->assertSessionHas('success');

    expect(Area::where('code', 'JKT')->exists())->toBeTrue();
});

test('area show page is accessible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $area = Area::factory()->create();

    $response = $this->get(route('areas.show', $area));

    $response->assertStatus(200)
        ->assertSee($area->name);
});

test('area can be updated via controller', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $area = Area::factory()->create();

    $response = $this->put(route('areas.update', $area), [
        'code' => 'BGR',
        'name' => 'Bogor',
    ]);

    $response->assertRedirect(route('areas.index'));
    $response->assertSessionHas('success');

    expect($area->fresh()->code)->toBe('BGR')
        ->and($area->fresh()->name)->toBe('Bogor');
});

test('area can be deleted via controller', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $area = Area::factory()->create();

    $response = $this->delete(route('areas.destroy', $area));

    $response->assertRedirect(route('areas.index'));
    $response->assertSessionHas('success');

    expect(Area::find($area->id))->toBeNull();
});

test('area scope active works', function () {
    Area::factory()->create(['is_active' => true]);
    Area::factory()->inactive()->create();

    expect(Area::active()->count())->toBe(1)
        ->and(Area::inactive()->count())->toBe(1);
});

test('area search works', function () {
    Area::factory()->create(['code' => 'JKT', 'name' => 'Jakarta']);
    Area::factory()->create(['code' => 'BGR', 'name' => 'Bogor']);

    $results = Area::where('name', 'like', '%jakar%')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->code)->toBe('JKT');
});
