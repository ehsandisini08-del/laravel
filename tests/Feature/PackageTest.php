<?php

use App\Models\Area;
use App\Models\Package;
use App\Models\PppProfile;
use App\Models\Router;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('package can be created with factory', function () {
    $router = Router::factory()->create();
    $profile = PppProfile::factory()->create(['router_id' => $router->id]);

    $package = Package::factory()->create([
        'router_id' => $router->id,
        'ppp_profile_id' => $profile->id,
    ]);

    expect($package->name)->not->toBeEmpty()
        ->and($package->price)->toBeNumeric();
});

test('package can have many areas', function () {
    $router = Router::factory()->create();
    $profile = PppProfile::factory()->create(['router_id' => $router->id]);
    $areas = Area::factory()->count(3)->create();

    $package = Package::factory()->create([
        'router_id' => $router->id,
        'ppp_profile_id' => $profile->id,
    ]);
    $package->areas()->attach($areas->pluck('id'));

    expect($package->areas)->toHaveCount(3);
});

test('package index page is accessible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('packages.index'));

    $response->assertStatus(200);
});

test('package create page is accessible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('packages.create'));

    $response->assertStatus(200);
});

test('package can be stored via controller', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $router = Router::factory()->create();
    $profile = PppProfile::factory()->create(['router_id' => $router->id]);
    $area = Area::factory()->create();

    $response = $this->post(route('packages.store'), [
        'name' => 'Home 20 Mbps',
        'price' => 150000,
        'router_id' => $router->id,
        'ppp_profile_id' => $profile->id,
        'areas' => [$area->id],
    ]);

    $response->assertRedirect(route('packages.index'));
    $response->assertSessionHas('success');

    expect(Package::where('name', 'Home 20 Mbps')->exists())->toBeTrue();
});

test('package show page is accessible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $router = Router::factory()->create();
    $profile = PppProfile::factory()->create(['router_id' => $router->id]);
    $package = Package::factory()->create([
        'router_id' => $router->id,
        'ppp_profile_id' => $profile->id,
    ]);

    $response = $this->get(route('packages.show', $package));

    $response->assertStatus(200)
        ->assertSee($package->name);
});

test('package can be updated via controller', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $router = Router::factory()->create();
    $profile = PppProfile::factory()->create(['router_id' => $router->id]);
    $area = Area::factory()->create();
    $package = Package::factory()->create([
        'router_id' => $router->id,
        'ppp_profile_id' => $profile->id,
    ]);

    $response = $this->put(route('packages.update', $package), [
        'name' => 'Updated Package',
        'price' => 200000,
        'router_id' => $router->id,
        'ppp_profile_id' => $profile->id,
        'areas' => [$area->id],
    ]);

    $response->assertRedirect(route('packages.index'));
    $response->assertSessionHas('success');

    expect($package->fresh()->name)->toBe('Updated Package');
});

test('package can be deleted via controller', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $router = Router::factory()->create();
    $profile = PppProfile::factory()->create(['router_id' => $router->id]);
    $package = Package::factory()->create([
        'router_id' => $router->id,
        'ppp_profile_id' => $profile->id,
    ]);

    $response = $this->delete(route('packages.destroy', $package));

    $response->assertRedirect(route('packages.index'));
    $response->assertSessionHas('success');

    expect(Package::find($package->id))->toBeNull();
});

test('profiles by router endpoint returns json', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $router = Router::factory()->create();
    $profile = PppProfile::factory()->create([
        'router_id' => $router->id,
        'synced_at' => now(),
    ]);

    $response = $this->get(route('packages.profiles-by-router', $router));

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => $profile->id, 'name' => $profile->name]);
});

test('package store requires areas', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $router = Router::factory()->create();
    $profile = PppProfile::factory()->create(['router_id' => $router->id]);

    $response = $this->post(route('packages.store'), [
        'name' => 'Test',
        'price' => 100000,
        'router_id' => $router->id,
        'ppp_profile_id' => $profile->id,
        'areas' => [],
    ]);

    $response->assertSessionHasErrors('areas');
});
