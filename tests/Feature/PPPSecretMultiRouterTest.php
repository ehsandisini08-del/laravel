<?php

use App\Models\PppSecret;
use App\Models\Router;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ppp secrets are isolated per router', function () {
    $routerA = Router::factory()->create(['name' => 'Router A']);
    $routerB = Router::factory()->create(['name' => 'Router B']);

    PppSecret::factory()->count(20)->create(['router_id' => $routerA->id]);
    PppSecret::factory()->count(15)->create(['router_id' => $routerB->id]);

    $secretsA = PppSecret::where('router_id', $routerA->id)->count();
    $secretsB = PppSecret::where('router_id', $routerB->id)->count();

    expect($secretsA)->toBe(20)
        ->and($secretsB)->toBe(15)
        ->and(PppSecret::count())->toBe(35);
});

test('ppp secrets query is scoped to selected router', function () {
    $routerA = Router::factory()->create(['name' => 'Router A']);
    $routerB = Router::factory()->create(['name' => 'Router B']);

    $secretA1 = PppSecret::factory()->create(['router_id' => $routerA->id, 'name' => 'user-a1']);
    $secretA2 = PppSecret::factory()->create(['router_id' => $routerA->id, 'name' => 'user-a2']);
    $secretB1 = PppSecret::factory()->create(['router_id' => $routerB->id, 'name' => 'user-b1']);

    $resultsA = PppSecret::where('router_id', $routerA->id)->pluck('name')->toArray();
    $resultsB = PppSecret::where('router_id', $routerB->id)->pluck('name')->toArray();

    expect($resultsA)->toContain('user-a1', 'user-a2')
        ->and($resultsA)->not->toContain('user-b1')
        ->and($resultsB)->toContain('user-b1')
        ->and($resultsB)->not->toContain('user-a1', 'user-a2');
});

test('unique constraint allows same username on different routers', function () {
    $routerA = Router::factory()->create(['name' => 'Router A']);
    $routerB = Router::factory()->create(['name' => 'Router B']);

    PppSecret::factory()->create([
        'router_id' => $routerA->id,
        'name' => 'john_doe',
        'mikrotik_id' => '*1',
    ]);

    PppSecret::factory()->create([
        'router_id' => $routerB->id,
        'name' => 'john_doe',
        'mikrotik_id' => '*2',
    ]);

    expect(PppSecret::where('name', 'john_doe')->count())->toBe(2);
});

test('deleting secrets from one router does not affect another router', function () {
    $routerA = Router::factory()->create(['name' => 'Router A']);
    $routerB = Router::factory()->create(['name' => 'Router B']);

    PppSecret::factory()->count(10)->create(['router_id' => $routerA->id]);
    PppSecret::factory()->count(5)->create(['router_id' => $routerB->id]);

    PppSecret::where('router_id', $routerA->id)->delete();

    expect(PppSecret::where('router_id', $routerA->id)->count())->toBe(0)
        ->and(PppSecret::where('router_id', $routerB->id)->count())->toBe(5);
});

test('forRouter scope filters secrets correctly', function () {
    $routerA = Router::factory()->create();
    $routerB = Router::factory()->create();

    PppSecret::factory()->count(3)->create(['router_id' => $routerA->id]);
    PppSecret::factory()->count(7)->create(['router_id' => $routerB->id]);

    $scopedA = PppSecret::forRouter($routerA->id)->count();
    $scopedB = PppSecret::forRouter($routerB->id)->count();

    expect($scopedA)->toBe(3)
        ->and($scopedB)->toBe(7);
});

test('router relationship returns correct secrets', function () {
    $routerA = Router::factory()->create();
    $routerB = Router::factory()->create();

    PppSecret::factory()->count(4)->create(['router_id' => $routerA->id]);
    PppSecret::factory()->count(2)->create(['router_id' => $routerB->id]);

    expect($routerA->pppSecrets()->count())->toBe(4)
        ->and($routerB->pppSecrets()->count())->toBe(2);
});
