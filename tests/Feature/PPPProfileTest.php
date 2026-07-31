<?php

use App\Models\PppProfile;
use App\Models\Router;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ppp profiles can be created with factory', function () {
    $router = Router::factory()->create();

    $profile = PppProfile::factory()->create([
        'router_id' => $router->id,
        'name' => 'test-profile',
    ]);

    expect($profile->name)->toBe('test-profile')
        ->and($profile->router_id)->toBe($router->id);
});

test('ppp profiles are isolated per router', function () {
    $routerA = Router::factory()->create(['name' => 'Router A']);
    $routerB = Router::factory()->create(['name' => 'Router B']);

    PppProfile::factory()->count(5)->create(['router_id' => $routerA->id]);
    PppProfile::factory()->count(3)->create(['router_id' => $routerB->id]);

    expect(PppProfile::where('router_id', $routerA->id)->count())->toBe(5)
        ->and(PppProfile::where('router_id', $routerB->id)->count())->toBe(3)
        ->and(PppProfile::count())->toBe(8);
});

test('ppp profiles query scoped to selected router', function () {
    $routerA = Router::factory()->create();
    $routerB = Router::factory()->create();

    PppProfile::factory()->create(['router_id' => $routerA->id, 'name' => 'profile-a']);
    PppProfile::factory()->create(['router_id' => $routerB->id, 'name' => 'profile-b']);

    $resultsA = PppProfile::where('router_id', $routerA->id)->pluck('name')->toArray();
    $resultsB = PppProfile::where('router_id', $routerB->id)->pluck('name')->toArray();

    expect($resultsA)->toContain('profile-a')
        ->and($resultsA)->not->toContain('profile-b')
        ->and($resultsB)->toContain('profile-b')
        ->and($resultsB)->not->toContain('profile-a');
});

test('unique constraint allows same profile name on different routers', function () {
    $routerA = Router::factory()->create();
    $routerB = Router::factory()->create();

    PppProfile::factory()->create([
        'router_id' => $routerA->id,
        'name' => 'default',
        'mikrotik_id' => '*1',
    ]);

    PppProfile::factory()->create([
        'router_id' => $routerB->id,
        'name' => 'default',
        'mikrotik_id' => '*2',
    ]);

    expect(PppProfile::where('name', 'default')->count())->toBe(2);
});

test('forRouter scope filters correctly', function () {
    $routerA = Router::factory()->create();
    $routerB = Router::factory()->create();

    PppProfile::factory()->count(4)->create(['router_id' => $routerA->id]);
    PppProfile::factory()->count(6)->create(['router_id' => $routerB->id]);

    expect(PppProfile::forRouter($routerA->id)->count())->toBe(4)
        ->and(PppProfile::forRouter($routerB->id)->count())->toBe(6);
});

test('router relationship returns correct profiles', function () {
    $routerA = Router::factory()->create();
    $routerB = Router::factory()->create();

    PppProfile::factory()->count(3)->create(['router_id' => $routerA->id]);
    PppProfile::factory()->count(2)->create(['router_id' => $routerB->id]);

    expect($routerA->pppProfiles()->count())->toBe(3)
        ->and($routerB->pppProfiles()->count())->toBe(2);
});

test('ppp profile page is accessible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('ppp-profiles.index'));

    $response->assertStatus(200);
});

test('ppp profile show page is accessible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $profile = PppProfile::factory()->create();

    $response = $this->get(route('ppp-profiles.show', $profile));

    $response->assertStatus(200);
});

test('deleting profiles from one router does not affect another', function () {
    $routerA = Router::factory()->create();
    $routerB = Router::factory()->create();

    PppProfile::factory()->count(5)->create(['router_id' => $routerA->id]);
    PppProfile::factory()->count(3)->create(['router_id' => $routerB->id]);

    PppProfile::where('router_id', $routerA->id)->delete();

    expect(PppProfile::where('router_id', $routerA->id)->count())->toBe(0)
        ->and(PppProfile::where('router_id', $routerB->id)->count())->toBe(3);
});
