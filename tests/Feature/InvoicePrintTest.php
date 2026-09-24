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

test('cetak invoice page shows print button without invoice list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $invoice = makePrintInvoice();

    $this->get(route('billing.cetak-invoice'))
        ->assertOk()
        ->assertSee('Cetak Invoice')
        ->assertDontSee($invoice->invoice_number);
});

test('cetak invoice preview returns matching invoices for customer and month range', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $router = Router::factory()->create();
    $area = Area::factory()->create();
    $package = Package::factory()->create(['router_id' => $router->id]);
    $customer = Customer::factory()->create([
        'area_id' => $area->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
        'name' => 'Pelanggan Rentang',
    ]);

    foreach ([1, 2, 3, 4, 5] as $month) {
        Invoice::factory()->create([
            'invoice_number' => 'INV-'.sprintf('%04d%02d', now()->year, $month).'-000101',
            'customer_id' => $customer->id,
            'package_id' => $package->id,
            'router_id' => $router->id,
            'billing_month' => $month,
            'billing_year' => now()->year,
        ]);
    }

    $response = $this->get(route('billing.cetak-invoice.preview', [
        'customer_id' => $customer->id,
        'from_month' => 1,
        'from_year' => now()->year,
        'to_month' => 5,
        'to_year' => now()->year,
    ]));

    $response->assertOk();
    expect($response->json('count'))->toBe(5);
    expect(count($response->json('invoices')))->toBe(5);
});

test('cetak invoice pdf downloads a single pdf for customer month range', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $router = Router::factory()->create();
    $area = Area::factory()->create();
    $package = Package::factory()->create(['router_id' => $router->id]);
    $customer = Customer::factory()->create([
        'area_id' => $area->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
        'name' => 'Pelanggan PDF',
    ]);

    foreach ([1, 2] as $month) {
        Invoice::factory()->create([
            'invoice_number' => 'INV-'.sprintf('%04d%02d', now()->year, $month).'-000102',
            'customer_id' => $customer->id,
            'package_id' => $package->id,
            'router_id' => $router->id,
            'billing_month' => $month,
            'billing_year' => now()->year,
            'amount' => 150000,
        ]);
    }

    $response = $this->get(route('billing.cetak-invoice.pdf', [
        'customer_id' => $customer->id,
        'from_month' => 1,
        'from_year' => now()->year,
        'to_month' => 2,
        'to_year' => now()->year,
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
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

    $response = $this->get(route('billing.invoices.print', $first))
        ->assertOk();

    expect($response->getContent())->toContain('INV-202608-000203');

    $this->get(route('billing.invoices.print', $second))
        ->assertOk()
        ->assertSee('INV-202608-000204');
});

test('pdf without matching invoices redirects back with error', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->from(route('billing.cetak-invoice'))
        ->get(route('billing.cetak-invoice.pdf', ['customer_id' => 999999]))
        ->assertRedirect(route('billing.cetak-invoice'))
        ->assertSessionHas('error');
});

test('admin area user only sees assigned customer invoices on cetak invoice', function () {
    $assigned = Area::factory()->create();
    $other = Area::factory()->create();
    $router = Router::factory()->create();
    $package = Package::factory()->create(['router_id' => $router->id]);
    $myCustomer = Customer::factory()->create([
        'area_id' => $assigned->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
        'name' => 'Pelanggan Saya',
    ]);
    $otherCustomer = Customer::factory()->create([
        'area_id' => $other->id,
        'router_id' => $router->id,
        'package_id' => $package->id,
        'name' => 'Pelanggan Lain',
    ]);

    Invoice::factory()->create([
        'invoice_number' => 'INV-202608-000205',
        'customer_id' => $myCustomer->id,
        'package_id' => $package->id,
        'router_id' => $router->id,
        'billing_month' => now()->month,
        'billing_year' => now()->year,
    ]);
    Invoice::factory()->create([
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
        ->assertSee('Cetak Invoice');

    $this->get(route('billing.cetak-invoice.preview', [
        'customer_id' => $otherCustomer->id,
        'from_month' => now()->month,
        'from_year' => now()->year,
        'to_month' => now()->month,
        'to_year' => now()->year,
    ]))->assertOk()
        ->assertJsonPath('count', 0);

    $this->get(route('billing.cetak-invoice.pdf', [
        'customer_id' => $otherCustomer->id,
        'from_month' => now()->month,
        'from_year' => now()->year,
        'to_month' => now()->month,
        'to_year' => now()->year,
    ]))->assertRedirect(route('billing.cetak-invoice'));
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
