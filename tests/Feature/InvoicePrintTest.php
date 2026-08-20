<?php

use App\Enums\InvoiceStatus;
use App\Models\Area;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Router;
use App\Models\Setting;
use App\Models\User;
use App\Support\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makePrintInvoice(array $overrides = []): Invoice
{
    $router = Router::factory()->create();
    $area = Area::factory()->create();
    $package = Package::factory()->create(['router_id' => $router->id]);
    $customer = Customer::factory()->create([
        'area_id' => $area->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
        'name' => 'Pelanggan Cetak',
        'address' => 'Jl. Cetak No. 1',
        'phone' => fake()->unique()->numerify('08##########'),
        'ppp_username' => fake()->unique()->numerify('cetak_#####'),
    ]);

    return Invoice::factory()->create(array_merge([
        'invoice_number' => 'INV-202608-000200',
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'router_id' => $router->id,
        'billing_month' => now()->month,
        'billing_year' => now()->year,
        'amount' => 150000,
        'due_date' => now()->addDays(10),
        'status' => InvoiceStatus::Unpaid,
    ], $overrides));
}

test('cetak invoice page shows invoices with print actions', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $invoice = makePrintInvoice();

    $this->get(route('billing.cetak-invoice'))
        ->assertOk()
        ->assertSee('Cetak Invoice')
        ->assertSee($invoice->invoice_number)
        ->assertSee('Pelanggan Cetak')
        ->assertSee(route('billing.invoices.print', $invoice));
});

test('cetak invoice page filters by status and search', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $unpaid = makePrintInvoice(['invoice_number' => 'INV-202608-000201', 'status' => InvoiceStatus::Unpaid]);
    $paid = makePrintInvoice(['invoice_number' => 'INV-202608-000202', 'status' => InvoiceStatus::Paid]);

    $this->get(route('billing.cetak-invoice', ['status' => 'paid']))
        ->assertOk()
        ->assertSee($paid->invoice_number)
        ->assertDontSee($unpaid->invoice_number);

    $this->get(route('billing.cetak-invoice', ['search' => $unpaid->invoice_number]))
        ->assertOk()
        ->assertSee($unpaid->invoice_number)
        ->assertDontSee($paid->invoice_number);
});

test('single invoice print page renders professional invoice', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Setting::set('company_name', 'PT Contoh Internet', 'company');
    Setting::set('company_address', 'Jl. Kantor No. 5', 'company');
    Setting::set('company_tax_number', '00.000.000.0-000.000', 'company');

    $invoice = makePrintInvoice(['amount' => 250000]);

    $this->get(route('billing.invoices.print', $invoice))
        ->assertOk()
        ->assertSee('INVOICE')
        ->assertSee('INV-202608-000200')
        ->assertSee('PT Contoh Internet')
        ->assertSee('Jl. Kantor No. 5')
        ->assertSee('00.000.000.0-000.000')
        ->assertSee('Pelanggan Cetak')
        ->assertSee('Jl. Cetak No. 1')
        ->assertSee($invoice->customer->phone)
        ->assertSee($invoice->customer->ppp_username)
        ->assertSee('Terbilang')
        ->assertSee('BELUM BAYAR')
        ->assertSee('Hormat Kami')
        ->assertSee('Penerima');
});

test('paid invoice print page shows LUNAS stamp', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $invoice = makePrintInvoice([
        'status' => InvoiceStatus::Paid,
        'paid_at' => now(),
    ]);

    $this->get(route('billing.invoices.print', $invoice))
        ->assertOk()
        ->assertSee('LUNAS');
});

test('bulk print renders one sheet per selected invoice', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $first = makePrintInvoice(['invoice_number' => 'INV-202608-000203']);
    $second = makePrintInvoice(['invoice_number' => 'INV-202608-000204']);

    $response = $this->get(route('billing.cetak-invoice.print', ['ids' => [$first->id, $second->id]]))
        ->assertOk()
        ->assertSee('INV-202608-000203')
        ->assertSee('INV-202608-000204');

    expect(substr_count($response->getContent(), 'class="invoice-sheet"'))->toBe(2);
});

test('bulk print without ids redirects back with error', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->from(route('billing.cetak-invoice'))
        ->get(route('billing.cetak-invoice.print'))
        ->assertRedirect(route('billing.cetak-invoice'))
        ->assertSessionHas('error');
});

test('admin area user sees only assigned invoices on cetak invoice page', function () {
    $assigned = Area::factory()->create();
    $other = Area::factory()->create();
    $router = Router::factory()->create();
    $package = Package::factory()->create(['router_id' => $router->id]);
    $myCustomer = Customer::factory()->create([
        'area_id' => $assigned->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
    ]);
    $otherCustomer = Customer::factory()->create([
        'area_id' => $other->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
    ]);
    $myInvoice = Invoice::factory()->create([
        'invoice_number' => 'INV-202608-000205',
        'customer_id' => $myCustomer->id,
        'package_id' => $package->id,
        'router_id' => $router->id,
        'billing_month' => now()->month,
        'billing_year' => now()->year,
    ]);
    $otherInvoice = Invoice::factory()->create([
        'invoice_number' => 'INV-202608-000206',
        'customer_id' => $otherCustomer->id,
        'package_id' => $package->id,
        'router_id' => $router->id,
        'billing_month' => now()->month,
        'billing_year' => now()->year,
    ]);

    $user = adminAreaUser([$assigned->id]);
    $this->actingAs($user);

    $this->get(route('billing.cetak-invoice'))
        ->assertOk()
        ->assertSee($myInvoice->invoice_number)
        ->assertDontSee($otherInvoice->invoice_number);

    $this->get(route('billing.invoices.print', $myInvoice))->assertOk();
    $this->get(route('billing.invoices.print', $otherInvoice))->assertForbidden();

    $this->get(route('billing.cetak-invoice.print', ['ids' => [$otherInvoice->id]]))->assertForbidden();
});

test('invoice items are rendered on print page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $invoice = makePrintInvoice();
    $invoice->items()->create([
        'description' => 'Paket Internet 10 Mbps',
        'qty' => 1,
        'price' => 150000,
        'subtotal' => 150000,
    ]);

    $this->get(route('billing.invoices.print', $invoice))
        ->assertOk()
        ->assertSee('Paket Internet 10 Mbps')
        ->assertSee('150.000');
});

test('currency terbilang converts amounts to indonesian words', function () {
    expect(Currency::terbilang(0))->toBe('Nol Rupiah')
        ->and(Currency::terbilang(150000))->toBe('Seratus Lima Puluh Ribu Rupiah')
        ->and(Currency::terbilang(2500000))->toBe('Dua Juta Lima Ratus Ribu Rupiah')
        ->and(Currency::terbilang(123456789))->toBe('Seratus Dua Puluh Tiga Juta Empat Ratus Lima Puluh Enam Ribu Tujuh Ratus Delapan Puluh Sembilan Rupiah');
});
