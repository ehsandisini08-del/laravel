<?php

use App\Models\Router;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ppp active page is accessible without router', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('ppp-active.index'));

    $response->assertStatus(200);
});

test('ppp active page loads with router selected', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $router = Router::factory()->create(['enabled' => true]);

    $response = $this->get(route('ppp-active.index', ['router_id' => $router->id]));

    $response->assertStatus(200);
});

test('ppp active fetch requires router id', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('ppp-active.fetch'));

    expect($response->status())->toBe(400);
});

test('ppp active fetch returns json with invalid router', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('ppp-active.fetch', ['router_id' => 99999]));

    expect($response->status())->toBe(500);
});

test('ppp active disconnect requires router_id and user_id', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('ppp-active.disconnect'), [], ['Accept' => 'application/json']);

    expect($response->status())->toBe(400);
});

test('ppp active bulk disconnect requires router_id and user_ids', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('ppp-active.bulk-disconnect'), [], ['Accept' => 'application/json']);

    expect($response->status())->toBe(400);
});

test('ppp active page has router selector', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Router::factory()->count(3)->create(['enabled' => true]);

    $response = $this->get(route('ppp-active.index'));

    $response->assertSee('Select Router');
});
