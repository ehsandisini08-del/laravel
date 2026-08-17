<?php

use App\Enums\CustomerStatus;
use App\Enums\ServiceStatus;
use App\Models\Area;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\PppSecret;
use App\Models\Router;
use App\Models\User;
use App\Services\Mikrotik\PPPSecretService;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->router = Router::factory()->create();
    $this->area = Area::factory()->create();
    $this->package = Package::factory()->create([
        'router_id' => $this->router->id,
    ]);

    Cache::put('ppp-active-connections:'.$this->router->id, [], 30);
});

test('customer list page can be rendered', function () {
    $response = $this->get(route('customers.index'));

    $response->assertStatus(200);
});

test('customer list shows search on top with filter button and removes header title', function () {
    $response = $this->get(route('customers.index'));

    $response
        ->assertDontSee('Manage ISP Customer Data')
        ->assertSee('Filter')
        ->assertSee('Terapkan')
        ->assertSee('Reset')
        ->assertSee('All Routers')
        ->assertSee('All Areas')
        ->assertSee('All Status')
        ->assertSee('order-1 lg:order-2', false);
});

test('customer list shows Online connection badge with uptime when ppp active on router', function () {
    Customer::factory()->create([
        'router_id' => $this->router->id,
        'ppp_username' => 'active_user',
    ]);

    Cache::put('ppp-active-connections:'.$this->router->id, [
        ['name' => 'active_user', 'uptime' => '1d2h3m4s', 'session_time' => '1d2h3m4s'],
    ], 30);

    $this->get(route('customers.index'))
        ->assertOk()
        ->assertSee('Online')
        ->assertSee('1d 2h 3m 4s');
});

test('customer list shows Offline connection badge when ppp not active', function () {
    Customer::factory()->create([
        'router_id' => $this->router->id,
        'ppp_username' => 'idle_user',
    ]);

    $this->get(route('customers.index'))
        ->assertOk()
        ->assertSee('Offline');
});

test('customer list shows Isolir status badge when customer service is isolated', function () {
    $customer = Customer::factory()->create([
        'router_id' => $this->router->id,
        'ppp_username' => 'isolir_user',
        'service_status' => ServiceStatus::Isolated,
    ]);

    expect($customer->status_badge)->toBe('Isolir')
        ->and($customer->status_color)->toBe('danger');

    $this->get(route('customers.index'))
        ->assertOk()
        ->assertSee('Isolir');
});

test('customer create page can be rendered', function () {
    $response = $this->get(route('customers.create'));

    $response->assertStatus(200);
});

test('customer can be created', function () {
    $response = $this->post(route('customers.store'), [
        'name' => 'John Doe',
        'address' => 'Jl. Example No. 123',
        'phone' => '08123456789',
        'latitude' => '-6.2088',
        'longitude' => '106.8456',
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'ppp_username' => 'john_ppp',
        'ppp_password' => 'secret123',
        'installation_date' => now()->format('Y-m-d'),
        'due_day' => 15,
    ]);

    $response->assertRedirect(route('customers.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('customers', [
        'name' => 'John Doe',
        'phone' => '08123456789',
        'ppp_username' => 'john_ppp',
    ]);
});

test('customer creation with invalid data returns validation errors', function () {
    $response = $this->post(route('customers.store'), [
        'name' => '',
        'phone' => '',
    ]);

    $response->assertSessionHasErrors(['name', 'phone', 'address', 'latitude', 'longitude', 'area_id', 'router_id', 'package_id', 'ppp_username', 'ppp_password', 'installation_date', 'due_day']);
});

test('customer can be viewed', function () {
    $customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $unpaid = Invoice::factory()->unpaid()->create([
        'customer_id' => $customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->router->id,
        'billing_month' => 8,
        'billing_year' => 2026,
        'amount' => 150000,
    ]);

    $paid = Invoice::factory()->paid()->create([
        'customer_id' => $customer->id,
        'package_id' => $this->package->id,
        'router_id' => $this->router->id,
        'billing_month' => 7,
        'billing_year' => 2026,
        'amount' => 150000,
    ]);

    $response = $this->get(route('customers.show', $customer));

    $response->assertStatus(200);
    $response->assertSee($customer->name);
    $response->assertSee('Detail');
    $response->assertSee('Tagihan');
    $response->assertSee('Wifi');
    $response->assertSee($unpaid->billing_period);
    $response->assertSee($paid->billing_period);
    $response->assertSee('150.000');
});

test('customer can be updated', function () {
    $customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $response = $this->put(route('customers.update', $customer), [
        'name' => 'Updated Name',
        'address' => $customer->address,
        'phone' => $customer->phone,
        'latitude' => $customer->latitude,
        'longitude' => $customer->longitude,
        'area_id' => $customer->area_id,
        'router_id' => $customer->router_id,
        'package_id' => $customer->package_id,
        'ppp_username' => $customer->ppp_username,
        'installation_date' => $customer->installation_date->format('Y-m-d'),
        'due_day' => $customer->due_day,
        'status' => CustomerStatus::Suspended->value,
    ]);

    $response->assertRedirect(route('customers.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'name' => 'Updated Name',
        'status' => CustomerStatus::Suspended->value,
    ]);
});

test('customer can be deleted', function () {
    $customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $response = $this->delete(route('customers.destroy', $customer));

    $response->assertRedirect(route('customers.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
});

test('bulk delete shows select checkboxes only for superadmin and developer', function () {
    Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $this->get(route('customers.index'))
        ->assertDontSee('Hapus Terpilih')
        ->assertDontSee('x-model="selected"', false);

    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin);

    $this->get(route('customers.index'))
        ->assertSee('Hapus Terpilih')
        ->assertSee('x-model="selected"', false);
});

test('bulk delete removes selected customers for superadmin', function () {
    $first = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $second = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $this->delete(route('customers.destroy-many'), ['ids' => [$first->id, $second->id]])
        ->assertForbidden();

    $this->assertDatabaseHas('customers', ['id' => $first->id]);
    $this->assertDatabaseHas('customers', ['id' => $second->id]);

    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin);

    $response = $this->delete(route('customers.destroy-many'), ['ids' => [$first->id, $second->id]]);
    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('customers', ['id' => $first->id]);
    $this->assertDatabaseMissing('customers', ['id' => $second->id]);
});

test('customer creation logs activity', function () {
    $this->post(route('customers.store'), [
        'name' => 'Activity Test',
        'address' => 'Jl. Test',
        'phone' => '08999999999',
        'latitude' => '-6.2088',
        'longitude' => '106.8456',
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'ppp_username' => 'activity_test_ppp',
        'ppp_password' => 'secret123',
        'installation_date' => now()->format('Y-m-d'),
        'due_day' => 10,
    ]);

    $log = Activity::query()
        ->where('event', 'Created')
        ->where('description', 'like', '%Activity Test%')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->properties->get('module'))->toBe('Customer');
});

test('customer update logs activity', function () {
    $customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $this->put(route('customers.update', $customer), [
        'name' => 'Updated For Log',
        'address' => $customer->address,
        'phone' => $customer->phone,
        'latitude' => $customer->latitude,
        'longitude' => $customer->longitude,
        'area_id' => $customer->area_id,
        'router_id' => $customer->router_id,
        'package_id' => $customer->package_id,
        'ppp_username' => $customer->ppp_username,
        'installation_date' => $customer->installation_date->format('Y-m-d'),
        'due_day' => $customer->due_day,
    ]);

    $log = Activity::query()
        ->where('event', 'Updated')
        ->where('description', 'like', '%Updated For Log%')
        ->first();

    expect($log)->not->toBeNull();
});

test('customer delete logs activity', function () {
    $customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);
    $name = $customer->name;

    $this->delete(route('customers.destroy', $customer));

    $log = Activity::query()
        ->where('event', 'Deleted')
        ->where('description', 'like', "%{$name}%")
        ->first();

    expect($log)->not->toBeNull();
});

test('customer list can be filtered by status', function () {
    Customer::factory()->active()->count(3)->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);
    Customer::factory()->suspended()->count(2)->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $response = $this->get(route('customers.index', ['status' => 'Active']));

    $response->assertStatus(200);
});

test('customer list can be searched', function () {
    Customer::factory()->create([
        'name' => 'UniqueSearchName',
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);
    Customer::factory()->count(5)->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $response = $this->get(route('customers.index', ['search' => 'UniqueSearchName']));

    $response->assertStatus(200);
    $response->assertSee('UniqueSearchName');
});

test('customer search via ajax returns filtered results fragment', function () {
    Customer::factory()->create([
        'name' => 'AjaxMatchCustomer',
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);
    Customer::factory()->count(3)->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    $response = $this->getJson(route('customers.index', ['search' => 'AjaxMatchCustomer']));

    $response->assertOk()
        ->assertSee('AjaxMatchCustomer')
        ->assertDontSee('No Customers')
        ->assertDontSee('All Areas');
});

test('duplicate phone is rejected', function () {
    Customer::factory()->create(['phone' => '08111111111']);

    $response = $this->post(route('customers.store'), [
        'name' => 'Duplicate Phone',
        'address' => 'Jl. Test',
        'phone' => '08111111111',
        'latitude' => '-6.2088',
        'longitude' => '106.8456',
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'ppp_username' => 'unique_ppp_1',
        'ppp_password' => 'secret123',
        'installation_date' => now()->format('Y-m-d'),
        'due_day' => 5,
    ]);

    $response->assertSessionHasErrors('phone');
});

test('duplicate ppp username is rejected', function () {
    Customer::factory()->create(['ppp_username' => 'duplicate_ppp']);

    $response = $this->post(route('customers.store'), [
        'name' => 'Duplicate PPP',
        'address' => 'Jl. Test',
        'phone' => '08222222222',
        'latitude' => '-6.2088',
        'longitude' => '106.8456',
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'ppp_username' => 'duplicate_ppp',
        'ppp_password' => 'secret123',
        'installation_date' => now()->format('Y-m-d'),
        'due_day' => 5,
    ]);

    $response->assertSessionHasErrors('ppp_username');
});

test('customer code is auto generated', function () {
    $response = $this->post(route('customers.store'), [
        'name' => 'Auto Code',
        'address' => 'Jl. Auto',
        'phone' => '08333333333',
        'latitude' => '-6.2088',
        'longitude' => '106.8456',
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'ppp_username' => 'auto_code_ppp',
        'ppp_password' => 'secret123',
        'installation_date' => now()->format('Y-m-d'),
        'due_day' => 20,
    ]);

    $response->assertRedirect(route('customers.index'));

    $customer = Customer::where('ppp_username', 'auto_code_ppp')->first();
    expect($customer->customer_code)->toMatch('/^\d{6}$/');
});

test('packages by router endpoint returns packages', function () {
    $package2 = Package::factory()->create([
        'router_id' => $this->router->id,
    ]);

    $response = $this->get(route('customers.packages-by-router', $this->router->id));

    $response->assertStatus(200);
    $response->assertJsonCount(2);
});

test('areas by package endpoint returns areas', function () {
    $this->package->areas()->attach($this->area->id);

    $response = $this->get(route('customers.areas-by-package', $this->package->id));

    $response->assertStatus(200);
});

test('customer creation without ppp secret flag does not create ppp_secret record', function () {
    $this->post(route('customers.store'), [
        'name' => 'No PPP Secret',
        'address' => 'Jl. Test',
        'phone' => '08444444444',
        'latitude' => '-6.2088',
        'longitude' => '106.8456',
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'ppp_username' => 'noppp_secret_user',
        'ppp_password' => 'secret123',
        'installation_date' => now()->format('Y-m-d'),
        'due_day' => 5,
        'create_ppp_secret' => false,
    ]);

    $customer = Customer::where('ppp_username', 'noppp_secret_user')->first();
    expect($customer)->not->toBeNull();
    expect($customer->ppp_secret_id)->toBeNull();
});

test('customer update with ppp secret and offline router rolls back changes', function () {
    $originalRouter = Router::factory()->create(['status' => 'online', 'enabled' => true]);
    $originalPackage = Package::factory()->create(['router_id' => $originalRouter->id]);
    $pppSecret = PppSecret::factory()->create([
        'router_id' => $originalRouter->id,
        'name' => 'test_ppp_user_update',
    ]);

    $customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $originalRouter->id,
        'package_id' => $originalPackage->id,
        'ppp_secret_id' => $pppSecret->id,
        'ppp_username' => 'test_ppp_user_update',
        'name' => 'Original Name',
    ]);

    $offlineRouter = Router::factory()->create(['status' => 'offline', 'enabled' => true]);
    $offlinePackage = Package::factory()->create(['router_id' => $offlineRouter->id]);

    $this->put(route('customers.update', $customer), [
        'name' => 'Changed Name',
        'address' => $customer->address,
        'phone' => $customer->phone,
        'latitude' => $customer->latitude,
        'longitude' => $customer->longitude,
        'area_id' => $this->area->id,
        'router_id' => $offlineRouter->id,
        'package_id' => $offlinePackage->id,
        'ppp_username' => $customer->ppp_username,
        'ppp_password' => 'newpassword123',
        'installation_date' => $customer->installation_date->format('Y-m-d'),
        'due_day' => $customer->due_day,
        'status' => 'Active',
    ]);

    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'name' => 'Original Name',
        'router_id' => $originalRouter->id,
    ]);
});

test('customer delete without ppp secret succeeds', function () {
    $customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'ppp_secret_id' => null,
    ]);

    $response = $this->delete(route('customers.destroy', $customer));

    $response->assertRedirect(route('customers.index'));
    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
});

test('customer creation with ppp secret flag and offline router returns error', function () {
    $router = Router::factory()->create(['status' => 'offline', 'enabled' => true]);
    $package = Package::factory()->create(['router_id' => $router->id]);

    $response = $this->post(route('customers.store'), [
        'name' => 'Offline Router Test',
        'address' => 'Jl. Offline',
        'phone' => '08555555555',
        'latitude' => '-6.2088',
        'longitude' => '106.8456',
        'area_id' => $this->area->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
        'ppp_username' => 'offline_router_user',
        'ppp_password' => 'secret123',
        'installation_date' => now()->format('Y-m-d'),
        'due_day' => 10,
        'create_ppp_secret' => true,
    ]);

    $response->assertSessionHas('error');
    $this->assertDatabaseMissing('customers', ['ppp_username' => 'offline_router_user']);
});

test('customer creation links to existing synced ppp secret without touching router', function () {
    $router = Router::factory()->create(['status' => 'offline']);
    $package = Package::factory()->create(['router_id' => $router->id]);

    $existingSecret = PppSecret::factory()->create([
        'router_id' => $router->id,
        'name' => 'existing_ppp_user',
        'password' => 'realsecret',
    ]);

    $response = $this->post(route('customers.store'), [
        'name' => 'Link User',
        'address' => 'Jl. Link',
        'phone' => '08777777777',
        'latitude' => '-6.2088',
        'longitude' => '106.8456',
        'area_id' => $this->area->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
        'ppp_username' => 'existing_ppp_user',
        'ppp_password' => 'typedpass',
        'installation_date' => now()->format('Y-m-d'),
        'due_day' => 10,
        'create_ppp_secret' => true,
    ]);

    $response->assertSessionHas('success');

    $customer = Customer::where('ppp_username', 'existing_ppp_user')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->ppp_secret_id)->toBe($existingSecret->id)
        ->and(PppSecret::where('router_id', $router->id)->count())->toBe(1);
});

test('customer creation links to secret when mikrotik reports it already exists', function () {
    $this->router->update(['status' => 'online']);

    $mock = Mockery::mock(PPPSecretService::class);
    $mock->shouldReceive('createSecret')
        ->once()
        ->andReturn(['success' => false, 'message' => 'PPP Secret with this name already exists.']);
    $mock->shouldReceive('findSecretByName')
        ->once()
        ->with('already_ppp_user')
        ->andReturn(['.id' => '*2A', 'name' => 'already_ppp_user', 'password' => 'realpass', 'service' => 'pppoe', 'profile' => 'default', 'disabled' => 'false']);
    $mock->shouldReceive('updateSecret')
        ->once()
        ->with('*2A', ['comment' => 'Already User'])
        ->andReturn(['success' => true, 'message' => 'ok']);
    app()->bind(PPPSecretService::class, fn () => $mock);

    $response = $this->post(route('customers.store'), [
        'name' => 'Already User',
        'address' => 'Jl. Already',
        'phone' => '08666666666',
        'latitude' => '-6.2088',
        'longitude' => '106.8456',
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'ppp_username' => 'already_ppp_user',
        'ppp_password' => 'typedpass',
        'installation_date' => now()->format('Y-m-d'),
        'due_day' => 10,
        'create_ppp_secret' => true,
    ]);

    $response->assertSessionHas('success');

    $customer = Customer::where('ppp_username', 'already_ppp_user')->first();
    $secret = PppSecret::where('name', 'already_ppp_user')->first();

    expect($customer)->not->toBeNull()
        ->and($secret)->not->toBeNull()
        ->and($customer->ppp_secret_id)->toBe($secret->id)
        ->and($secret->mikrotik_id)->toBe('*2A');
});

test('customer creation updates comment on existing synced ppp secret when router is online', function () {
    $existingSecret = PppSecret::factory()->create([
        'router_id' => $this->router->id,
        'name' => 'sync_comment_user',
        'password' => 'realsecret',
        'comment' => 'old comment',
    ]);

    $mock = Mockery::mock(PPPSecretService::class);
    $mock->shouldReceive('updateSecret')
        ->once()
        ->with($existingSecret->mikrotik_id, ['comment' => 'Comment Sync User'])
        ->andReturn(['success' => true, 'message' => 'ok']);
    app()->bind(PPPSecretService::class, fn () => $mock);

    $response = $this->post(route('customers.store'), [
        'name' => 'Comment Sync User',
        'address' => 'Jl. Sync',
        'phone' => '08555555555',
        'latitude' => '-6.2088',
        'longitude' => '106.8456',
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'ppp_username' => 'sync_comment_user',
        'ppp_password' => 'typedpass',
        'installation_date' => now()->format('Y-m-d'),
        'due_day' => 10,
        'create_ppp_secret' => true,
    ]);

    $response->assertSessionHas('success');

    $customer = Customer::where('ppp_username', 'sync_comment_user')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->ppp_secret_id)->toBe($existingSecret->id)
        ->and($existingSecret->fresh()->comment)->toBe('Comment Sync User');
});

test('customer creation does not change existing ppp secret comment when router is offline', function () {
    $router = Router::factory()->create(['status' => 'offline']);
    $package = Package::factory()->create(['router_id' => $router->id]);

    $existingSecret = PppSecret::factory()->create([
        'router_id' => $router->id,
        'name' => 'offline_comment_user',
        'comment' => 'keep me',
    ]);

    $response = $this->post(route('customers.store'), [
        'name' => 'Offline Comment User',
        'address' => 'Jl. Offline',
        'phone' => '08444444444',
        'latitude' => '-6.2088',
        'longitude' => '106.8456',
        'area_id' => $this->area->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
        'ppp_username' => 'offline_comment_user',
        'ppp_password' => 'typedpass',
        'installation_date' => now()->format('Y-m-d'),
        'due_day' => 10,
        'create_ppp_secret' => true,
    ]);

    $response->assertSessionHas('success');

    $customer = Customer::where('ppp_username', 'offline_comment_user')->first();

    expect($customer)->not->toBeNull()
        ->and($existingSecret->fresh()->comment)->toBe('keep me');
});
