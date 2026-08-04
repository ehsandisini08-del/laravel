<?php

use App\Models\Area;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Router;
use App\Models\Setting;
use App\Models\User;
use App\Services\Billing\AutoIsolationService;
use App\Services\Billing\InvoiceService;
use App\Services\CustomerService;
use App\Support\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $this->router = Router::factory()->create();
    $this->area = Area::factory()->create();
    $this->package = Package::factory()->create(['router_id' => $this->router->id]);
});

test('invoice prefix setting is used for invoice numbers', function () {
    Setting::set('invoice_prefix', 'BILL', 'billing');

    $customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'status' => 'Active',
        'due_day' => 10,
    ]);

    $nextMonth = now()->addMonth();
    $invoice = app(InvoiceService::class)->generateForCustomer($customer, $nextMonth->month, $nextMonth->year);

    expect($invoice)->not->toBeNull()
        ->and($invoice->invoice_number)->toStartWith('BILL-');
});

test('auto isolate disabled setting prevents isolation', function () {
    Setting::set('auto_isolate_enabled', '0', 'billing');

    $customer = Customer::factory()->create([
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
    ]);

    Invoice::factory()->create([
        'customer_id' => $customer->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'status' => 'unpaid',
        'isolation_day' => now()->day,
    ]);

    $result = app(AutoIsolationService::class)->disableExpiredCustomers();

    expect($result['disabled'])->toBe(0)
        ->and($customer->fresh()->service_status->value)->toBe('active');
});

test('default due day and isolation day settings are applied on customer create', function () {
    Setting::set('default_due_day', '20', 'billing');
    Setting::set('default_isolation_day', '25', 'billing');

    $customer = app(CustomerService::class)->create([
        'name' => 'Default Days',
        'address' => 'Jl. Test',
        'phone' => '08888888888',
        'latitude' => -6.2,
        'longitude' => 106.8,
        'area_id' => $this->area->id,
        'router_id' => $this->router->id,
        'package_id' => $this->package->id,
        'ppp_username' => 'default_days',
        'ppp_password' => 'secret',
        'installation_date' => now()->format('Y-m-d'),
        'portal_enabled' => false,
    ]);

    expect($customer->due_day)->toBe(20)
        ->and($customer->isolation_day)->toBe(25);
});

test('log retention days setting is used by logs cleanup command', function () {
    Setting::set('log_retention_days', '5', 'system');

    Activity::create(['description' => 'old log', 'created_at' => now()->subDays(10)]);
    Activity::create(['description' => 'recent log', 'created_at' => now()->subHours(2)]);

    $this->artisan('logs:cleanup')->assertSuccessful();

    $this->assertDatabaseMissing('activity_log', ['description' => 'old log']);
    $this->assertDatabaseHas('activity_log', ['description' => 'recent log']);
});

test('maintenance mode blocks guests and portal but keeps admin login reachable', function () {
    Setting::set('maintenance_mode', '1', 'system');

    Auth::logout();

    $this->get(route('portal.login'))->assertStatus(200);

    $this->get(route('login'))->assertStatus(200);

    $this->get(route('portal.dashboard'))->assertStatus(503);

    $admin = User::factory()->developer()->create();
    $this->actingAs($admin);

    $this->get(route('dashboard'))->assertStatus(200);
});

test('currency helper uses currency symbol setting', function () {
    Setting::set('currency_symbol', 'Rp', 'billing');

    expect(Currency::format(150000))->toBe('Rp 150.000');
});

test('settings page is restricted to developer role', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin);

    $this->get(route('settings.index'))->assertForbidden();

    $developer = User::factory()->developer()->create();
    $this->actingAs($developer);

    $this->get(route('settings.index'))->assertStatus(200);
});

test('maintenance mode blocks non developer admins but allows developer', function () {
    Setting::set('maintenance_mode', '1', 'system');

    $admin = User::factory()->create();
    $this->actingAs($admin);

    $this->get(route('dashboard'))->assertStatus(503);

    Auth::logout();

    $developer = User::factory()->developer()->create();
    $this->actingAs($developer);

    $this->get(route('dashboard'))->assertStatus(200);
});

test('developer can submit login during maintenance mode', function () {
    Setting::set('maintenance_mode', '1', 'system');

    Auth::logout();

    $developer = User::factory()->developer()->create([
        'password' => 'secret123',
    ]);

    $this->post('/login', [
        'email' => $developer->email,
        'password' => 'secret123',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated('web');
});
