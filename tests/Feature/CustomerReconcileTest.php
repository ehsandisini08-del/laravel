<?php

use App\Enums\CustomerStatus;
use App\Enums\ServiceStatus;
use App\Models\Area;
use App\Models\Customer;
use App\Models\Package;
use App\Models\PppProfile;
use App\Models\PppSecret;
use App\Models\Router;
use App\Models\User;
use App\Services\CustomerService;
use App\Services\Mikrotik\PPPSecretService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->router = Router::factory()->create(['status' => 'online']);
    $this->area = Area::factory()->create();
    $this->profile = PppProfile::factory()->forRouter($this->router)->create(['name' => 'profile-10m']);
    $this->package = Package::factory()->create([
        'router_id' => $this->router->id,
        'ppp_profile_id' => $this->profile->id,
    ]);

    $this->service = app(CustomerService::class);
});

test('reconcile updates existing linked secret fields on the router', function () {
    $customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'service_status' => ServiceStatus::Active,
        'status' => CustomerStatus::Active->value,
        'ppp_username' => 'reconcile_user',
        'ppp_password' => 'newpass',
    ]);

    $secret = PppSecret::factory()->create([
        'router_id' => $this->router->id,
        'name' => 'reconcile_user',
        'password' => 'oldpass',
        'profile' => 'old-profile',
        'comment' => 'old comment',
        'disabled' => false,
    ]);
    $customer->update(['ppp_secret_id' => $secret->id]);

    $mock = Mockery::mock(PPPSecretService::class);
    $mock->shouldReceive('updateSecret')
        ->once()
        ->with($secret->mikrotik_id, [
            'profile' => 'profile-10m',
            'comment' => $customer->name,
            'password' => 'newpass',
        ])
        ->andReturn(['success' => true, 'message' => 'ok']);
    app()->bind(PPPSecretService::class, fn () => $mock);

    $result = $this->service->reconcileSecretsForRouter($this->router);

    expect($result['updated'])->toBe(1)
        ->and($result['created'])->toBe(0)
        ->and($result['failed'])->toBe(0);

    $secret->refresh();

    expect($secret->profile)->toBe('profile-10m')
        ->and($secret->comment)->toBe($customer->name)
        ->and($secret->password)->toBe('newpass');
});

test('reconcile disables secret for an isolated customer', function () {
    $customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'service_status' => ServiceStatus::Isolated,
        'status' => CustomerStatus::Active->value,
        'ppp_username' => 'isolated_user',
        'ppp_password' => 'pass123',
    ]);

    $secret = PppSecret::factory()->create([
        'router_id' => $this->router->id,
        'name' => 'isolated_user',
        'disabled' => false,
    ]);
    $customer->update(['ppp_secret_id' => $secret->id]);

    $mock = Mockery::mock(PPPSecretService::class);
    $mock->shouldReceive('updateSecret')->once()->andReturn(['success' => true, 'message' => 'ok']);
    $mock->shouldReceive('disableSecret')->once()->with($secret->mikrotik_id)->andReturn(['success' => true, 'message' => 'ok']);
    app()->bind(PPPSecretService::class, fn () => $mock);

    $result = $this->service->reconcileSecretsForRouter($this->router);

    expect($result['updated'])->toBe(1)
        ->and($secret->fresh()->disabled)->toBeTrue();
});

test('reconcile creates a secret for a customer without one', function () {
    $customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'service_status' => ServiceStatus::Active,
        'status' => CustomerStatus::Active->value,
        'ppp_username' => 'new_user',
        'ppp_password' => 'pass123',
    ]);

    $mock = Mockery::mock(PPPSecretService::class);
    $mock->shouldReceive('createSecret')
        ->once()
        ->with(Mockery::on(fn ($data) => $data['name'] === 'new_user'
            && $data['password'] === 'pass123'
            && $data['profile'] === 'profile-10m'
            && $data['comment'] === $customer->name))
        ->andReturn(['success' => true, 'message' => 'ok', 'data' => ['.id' => '*5A']]);
    app()->bind(PPPSecretService::class, fn () => $mock);

    $result = $this->service->reconcileSecretsForRouter($this->router);

    expect($result['created'])->toBe(1)
        ->and($result['updated'])->toBe(0)
        ->and($result['failed'])->toBe(0)
        ->and($customer->fresh()->ppp_secret_id)->not->toBeNull()
        ->and(PppSecret::where('name', 'new_user')->exists())->toBeTrue();
});

test('reconcile aggregates across routers and excludes terminated customers', function () {
    $routeUser = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'service_status' => ServiceStatus::Active,
        'status' => CustomerStatus::Active->value,
        'ppp_username' => 'agg_user_a',
        'ppp_password' => 'pass123',
    ]);

    $secretA = PppSecret::factory()->create([
        'router_id' => $this->router->id,
        'name' => 'agg_user_a',
    ]);
    $routeUser->update(['ppp_secret_id' => $secretA->id]);

    Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'service_status' => ServiceStatus::Active,
        'status' => CustomerStatus::Terminated->value,
        'ppp_username' => 'agg_terminated',
    ]);

    $routerB = Router::factory()->create(['status' => 'online']);
    $profileB = PppProfile::factory()->forRouter($routerB)->create(['name' => 'profile-b']);
    $packageB = Package::factory()->create(['router_id' => $routerB->id, 'ppp_profile_id' => $profileB->id]);
    $areaB = Area::factory()->create();
    Customer::factory()->create([
        'area_id' => $areaB->id,
        'router_id' => $routerB->id,
        'package_id' => $packageB->id,
        'service_status' => ServiceStatus::Active,
        'status' => CustomerStatus::Active->value,
        'ppp_username' => 'agg_user_b',
        'ppp_password' => 'pass456',
    ]);

    $mock = Mockery::mock(PPPSecretService::class);
    $mock->shouldReceive('updateSecret')
        ->once()
        ->with($secretA->mikrotik_id, Mockery::any())
        ->andReturn(['success' => true, 'message' => 'ok']);
    $mock->shouldReceive('createSecret')
        ->once()
        ->andReturn(['success' => true, 'message' => 'ok', 'data' => ['.id' => '*6B']]);
    app()->bind(PPPSecretService::class, fn () => $mock);

    $result = $this->service->reconcileSecrets();

    expect($result['total']['updated'])->toBe(1)
        ->and($result['total']['created'])->toBe(1)
        ->and($result['total']['skipped'])->toBe(0)
        ->and($result['total']['failed'])->toBe(0)
        ->and(count($result['routers']))->toBe(2);
});

test('reconcile reports failure for a linked secret when the router is offline', function () {
    $this->router->update(['status' => 'offline']);

    $customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'service_status' => ServiceStatus::Active,
        'status' => CustomerStatus::Active->value,
        'ppp_username' => 'offline_user',
        'ppp_password' => 'pass123',
    ]);

    $secret = PppSecret::factory()->create([
        'router_id' => $this->router->id,
        'name' => 'offline_user',
    ]);
    $customer->update(['ppp_secret_id' => $secret->id]);

    $result = $this->service->reconcileSecretsForRouter($this->router);

    expect($result['failed'])->toBe(1);
});

test('reconcile controller returns a success summary for a router', function () {
    $customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'service_status' => ServiceStatus::Active,
        'status' => CustomerStatus::Active->value,
        'ppp_username' => 'route_user',
        'ppp_password' => 'pass123',
    ]);

    $secret = PppSecret::factory()->create([
        'router_id' => $this->router->id,
        'name' => 'route_user',
    ]);
    $customer->update(['ppp_secret_id' => $secret->id]);

    $mock = Mockery::mock(PPPSecretService::class);
    $mock->shouldReceive('updateSecret')->once()->andReturn(['success' => true, 'message' => 'ok']);
    app()->bind(PPPSecretService::class, fn () => $mock);

    $response = $this->post(route('customers.reconcile'), ['router_id' => $this->router->id]);

    $response->assertOk()
        ->assertJson(['success' => true])
        ->assertJsonPath('data.total.updated', 1)
        ->assertJsonPath('data.total.failed', 0);
});
