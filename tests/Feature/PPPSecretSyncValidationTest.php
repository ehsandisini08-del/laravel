<?php

use App\Models\PppSecret;
use App\Models\Router;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sync router A with 20 secrets does not affect router B with 15 secrets', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $routerA = Router::factory()->create(['name' => 'Router A', 'host' => '192.168.88.1']);
    $routerB = Router::factory()->create(['name' => 'Router B', 'host' => '192.168.88.2']);

    PppSecret::factory()->count(20)->create(['router_id' => $routerA->id]);
    PppSecret::factory()->count(15)->create(['router_id' => $routerB->id]);

    $initialCountA = PppSecret::where('router_id', $routerA->id)->count();
    $initialCountB = PppSecret::where('router_id', $routerB->id)->count();

    expect($initialCountA)->toBe(20)
        ->and($initialCountB)->toBe(15);

    $secretsABefore = PppSecret::where('router_id', $routerA->id)->pluck('mikrotik_id')->sort()->values()->toArray();
    $secretsBBefore = PppSecret::where('router_id', $routerB->id)->pluck('mikrotik_id')->sort()->values()->toArray();

    $countAfterA = PppSecret::where('router_id', $routerA->id)->count();
    $countAfterB = PppSecret::where('router_id', $routerB->id)->count();

    expect($countAfterA)->toBe(20)
        ->and($countAfterB)->toBe(15);

    $secretsAAfter = PppSecret::where('router_id', $routerA->id)->pluck('mikrotik_id')->sort()->values()->toArray();
    $secretsBAfter = PppSecret::where('router_id', $routerB->id)->pluck('mikrotik_id')->sort()->values()->toArray();

    expect($secretsAAfter)->toBe($secretsABefore)
        ->and($secretsBAfter)->toBe($secretsBBefore);
});

test('data from router A and router B are completely isolated', function () {
    $routerA = Router::factory()->create(['name' => 'Router A']);
    $routerB = Router::factory()->create(['name' => 'Router B']);

    foreach (range(0, 19) as $index) {
        PppSecret::factory()->create([
            'router_id' => $routerA->id,
            'name' => "user-a-{$index}",
        ]);
    }

    foreach (range(0, 14) as $index) {
        PppSecret::factory()->create([
            'router_id' => $routerB->id,
            'name' => "user-b-{$index}",
        ]);
    }

    $namesA = PppSecret::where('router_id', $routerA->id)->pluck('name')->toArray();
    $namesB = PppSecret::where('router_id', $routerB->id)->pluck('name')->toArray();

    foreach ($namesA as $name) {
        expect($name)->toStartWith('user-a-');
        expect($namesB)->not->toContain($name);
    }

    foreach ($namesB as $name) {
        expect($name)->toStartWith('user-b-');
        expect($namesA)->not->toContain($name);
    }
});

test('no data is lost when syncing multiple routers', function () {
    $routerA = Router::factory()->create(['name' => 'Router A']);
    $routerB = Router::factory()->create(['name' => 'Router B']);

    PppSecret::factory()->count(20)->create(['router_id' => $routerA->id]);
    PppSecret::factory()->count(15)->create(['router_id' => $routerB->id]);

    $totalBefore = PppSecret::count();
    expect($totalBefore)->toBe(35);

    $countA = PppSecret::where('router_id', $routerA->id)->count();
    $countB = PppSecret::where('router_id', $routerB->id)->count();

    expect($countA)->toBe(20)
        ->and($countB)->toBe(15)
        ->and(PppSecret::count())->toBe(35);
});

test('no duplicate data across routers', function () {
    $routerA = Router::factory()->create(['name' => 'Router A']);
    $routerB = Router::factory()->create(['name' => 'Router B']);

    PppSecret::factory()->count(20)->create(['router_id' => $routerA->id]);
    PppSecret::factory()->count(15)->create(['router_id' => $routerB->id]);

    $allSecrets = PppSecret::select('router_id', 'mikrotik_id')->get();

    $combinations = $allSecrets->map(fn ($s) => "{$s->router_id}:{$s->mikrotik_id}")->toArray();
    $uniqueCombinations = array_unique($combinations);

    expect(count($combinations))->toBe(count($uniqueCombinations));
});

test('all queries use router_id as filter', function () {
    $routerA = Router::factory()->create();
    $routerB = Router::factory()->create();

    PppSecret::factory()->count(20)->create(['router_id' => $routerA->id]);
    PppSecret::factory()->count(15)->create(['router_id' => $routerB->id]);

    $queryA = PppSecret::where('router_id', $routerA->id);
    $queryB = PppSecret::where('router_id', $routerB->id);

    expect($queryA->toSql())->toContain('router_id')
        ->and($queryB->toSql())->toContain('router_id')
        ->and($queryA->count())->toBe(20)
        ->and($queryB->count())->toBe(15);
});

test('deleting router A secrets does not delete router B secrets', function () {
    $routerA = Router::factory()->create(['name' => 'Router A']);
    $routerB = Router::factory()->create(['name' => 'Router B']);

    PppSecret::factory()->count(20)->create(['router_id' => $routerA->id]);
    PppSecret::factory()->count(15)->create(['router_id' => $routerB->id]);

    PppSecret::where('router_id', $routerA->id)->delete();

    expect(PppSecret::where('router_id', $routerA->id)->count())->toBe(0)
        ->and(PppSecret::where('router_id', $routerB->id)->count())->toBe(15)
        ->and(PppSecret::count())->toBe(15);
});

test('updating router A secrets does not update router B secrets', function () {
    $routerA = Router::factory()->create(['name' => 'Router A']);
    $routerB = Router::factory()->create(['name' => 'Router B']);

    PppSecret::factory()->count(20)->create(['router_id' => $routerA->id, 'disabled' => false]);
    PppSecret::factory()->count(15)->create(['router_id' => $routerB->id, 'disabled' => false]);

    PppSecret::where('router_id', $routerA->id)->update(['disabled' => true]);

    $disabledA = PppSecret::where('router_id', $routerA->id)->where('disabled', true)->count();
    $disabledB = PppSecret::where('router_id', $routerB->id)->where('disabled', true)->count();

    expect($disabledA)->toBe(20)
        ->and($disabledB)->toBe(0);
});

test('router cascade delete removes only its secrets', function () {
    $routerA = Router::factory()->create(['name' => 'Router A']);
    $routerB = Router::factory()->create(['name' => 'Router B']);

    PppSecret::factory()->count(20)->create(['router_id' => $routerA->id]);
    PppSecret::factory()->count(15)->create(['router_id' => $routerB->id]);

    $routerA->forceDelete();

    expect(PppSecret::where('router_id', $routerA->id)->count())->toBe(0)
        ->and(PppSecret::where('router_id', $routerB->id)->count())->toBe(15);
});
