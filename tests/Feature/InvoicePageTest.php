<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Area;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Router;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('invoice status labels are in indonesian', function () {
    expect(InvoiceStatus::Unpaid->label())->toBe('Belum Bayar')
        ->and(InvoiceStatus::Overdue->label())->toBe('Telat Bayar')
        ->and(InvoiceStatus::Paid->label())->toBe('Sudah Bayar')
        ->and(InvoiceStatus::Cancelled->label())->toBe('Dibatalkan')
        ->and(InvoiceStatus::Draft->label())->toBe('Draf');
});

test('payment method labels are in indonesian', function () {
    expect(PaymentMethod::Cash->label())->toBe('Cash')
        ->and(PaymentMethod::VaBca->label())->toBe('Virtual Account BCA')
        ->and(PaymentMethod::VaBri->label())->toBe('Virtual Account BRI')
        ->and(PaymentMethod::VaMandiri->label())->toBe('Virtual Account Mandiri')
        ->and(PaymentMethod::Qris->label())->toBe('QRIS')
        ->and(PaymentMethod::Ewallet->label())->toBe('E-Wallet');
});

test('payment status labels are in indonesian', function () {
    expect(PaymentStatus::Success->label())->toBe('Berhasil')
        ->and(PaymentStatus::Pending->label())->toBe('Menunggu')
        ->and(PaymentStatus::Failed->label())->toBe('Gagal')
        ->and(PaymentStatus::Expired->label())->toBe('Kedaluwarsa');
});

test('invoice index page shows pay button and payment method column', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $router = Router::factory()->create();
    $area = Area::factory()->create();
    $package = Package::factory()->create(['router_id' => $router->id]);
    $customer = Customer::factory()->create([
        'area_id' => $area->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
    ]);

    $invoice = Invoice::factory()->create([
        'invoice_number' => 'INV-202608-000005',
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'router_id' => $router->id,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $response = $this->get(route('billing.invoices.index'));

    $response->assertStatus(200)
        ->assertSee('Buat Invoice')
        ->assertSee('Bayar')
        ->assertSee('Metode')
        ->assertSee('Belum Bayar')
        ->assertSee('INV-202608-000005');
});

test('manual pay via invoice page marks as paid by current admin', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $router = Router::factory()->create();
    $area = Area::factory()->create();
    $package = Package::factory()->create(['router_id' => $router->id]);
    $customer = Customer::factory()->create([
        'area_id' => $area->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
    ]);

    $invoice = Invoice::factory()->create([
        'invoice_number' => 'INV-202608-000006',
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'router_id' => $router->id,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $response = $this->post(route('billing.invoices.pay', $invoice));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->fresh()->payment_method)->toBe(PaymentMethod::Cash);

    $payment = Payment::where('invoice_id', $invoice->id)->first();

    expect($payment)->not->toBeNull()
        ->and($payment->paid_by_user_id)->toBe($user->id)
        ->and($payment->gateway_provider)->toBe('manual');
});

test('delete button is only visible to superadmin and developer', function () {
    $router = Router::factory()->create();
    $area = Area::factory()->create();
    $package = Package::factory()->create(['router_id' => $router->id]);
    $customer = Customer::factory()->create([
        'area_id' => $area->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
    ]);

    $invoice = Invoice::factory()->create([
        'invoice_number' => 'INV-202608-000007',
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'router_id' => $router->id,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $this->get(route('billing.invoices.show', $invoice))->assertDontSee('Hapus');

    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin);

    $this->get(route('billing.invoices.show', $invoice))->assertSee('Hapus');
});

test('only superadmin and developer can delete invoices', function () {
    $router = Router::factory()->create();
    $area = Area::factory()->create();
    $package = Package::factory()->create(['router_id' => $router->id]);
    $customer = Customer::factory()->create([
        'area_id' => $area->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
    ]);

    $invoice = Invoice::factory()->create([
        'invoice_number' => 'INV-202608-000008',
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'router_id' => $router->id,
        'status' => InvoiceStatus::Unpaid,
    ]);

    DB::table('invoice_reminders')->insert([
        'invoice_id' => $invoice->id,
        'days_before' => 3,
        'status' => 'queued',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $this->delete(route('billing.invoices.destroy', $invoice))->assertForbidden();
    expect(Invoice::find($invoice->id))->not->toBeNull();

    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin);

    $response = $this->delete(route('billing.invoices.destroy', $invoice));
    $response->assertRedirect(route('billing.invoices.index'));
    $response->assertSessionHas('success');

    expect(Invoice::find($invoice->id))->toBeNull()
        ->and(DB::table('invoice_reminders')->where('invoice_id', $invoice->id)->count())->toBe(0);
});

test('bulk delete shows select checkboxes only for superadmin and developer', function () {
    $router = Router::factory()->create();
    $area = Area::factory()->create();
    $package = Package::factory()->create(['router_id' => $router->id]);
    $customer = Customer::factory()->create([
        'area_id' => $area->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
    ]);

    $invoice = Invoice::factory()->create([
        'invoice_number' => 'INV-202608-000009',
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'router_id' => $router->id,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $this->get(route('billing.invoices.index'))
        ->assertDontSee('Hapus Terpilih')
        ->assertDontSee('x-model="selected"', false);

    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin);

    $this->get(route('billing.invoices.index'))
        ->assertSee('Hapus Terpilih')
        ->assertSee('x-model="selected"', false);
});

test('bulk delete removes selected invoices for superadmin', function () {
    $router = Router::factory()->create();
    $area = Area::factory()->create();
    $package = Package::factory()->create(['router_id' => $router->id]);
    $customer = Customer::factory()->create([
        'area_id' => $area->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
    ]);

    $first = Invoice::factory()->create([
        'invoice_number' => 'INV-202608-000010',
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'router_id' => $router->id,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $second = Invoice::factory()->create([
        'invoice_number' => 'INV-202608-000011',
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'router_id' => $router->id,
        'status' => InvoiceStatus::Paid,
    ]);

    DB::table('invoice_reminders')->insert([
        'invoice_id' => $first->id,
        'days_before' => 3,
        'status' => 'queued',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $this->delete(route('billing.invoices.destroy-many'), ['ids' => [$first->id, $second->id]])
        ->assertForbidden();

    expect(Invoice::find($first->id))->not->toBeNull()
        ->and(Invoice::find($second->id))->not->toBeNull();

    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin);

    $response = $this->delete(route('billing.invoices.destroy-many'), ['ids' => [$first->id, $second->id]]);
    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(Invoice::find($first->id))->toBeNull()
        ->and(Invoice::find($second->id))->toBeNull()
        ->and(DB::table('invoice_reminders')->where('invoice_id', $first->id)->count())->toBe(0);
});
