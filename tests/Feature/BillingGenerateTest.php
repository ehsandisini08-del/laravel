<?php

use App\Models\Area;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Router;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('generate invoices creates invoices for active customers synchronously', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $router = Router::factory()->create();
    $area = Area::factory()->create();
    $package = Package::factory()->create(['router_id' => $router->id, 'price' => 150000]);

    $customer = Customer::factory()->create([
        'area_id' => $area->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
        'status' => 'Active',
        'due_day' => 10,
    ]);

    $response = $this->post(route('billing.generate'), [], ['HTTP_REFERER' => route('billing.invoices.index')]);

    $response->assertRedirect(route('billing.invoices.index'));
    $response->assertSessionHas('success');

    $nextMonth = now()->addMonth();

    $invoice = Invoice::where('customer_id', $customer->id)
        ->where('billing_month', $nextMonth->month)
        ->where('billing_year', $nextMonth->year)
        ->first();

    expect($invoice)->not->toBeNull()
        ->and((float) $invoice->amount)->toBe(150000.0)
        ->and($invoice->status->value)->toBe('unpaid');
});

test('generate invoices skips non active customers', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $router = Router::factory()->create();
    $area = Area::factory()->create();
    $package = Package::factory()->create(['router_id' => $router->id]);

    Customer::factory()->create([
        'area_id' => $area->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
        'status' => 'Terminated',
        'due_day' => 10,
    ]);

    $this->post(route('billing.generate'));

    expect(Invoice::count())->toBe(0);
});
