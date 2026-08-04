<?php

use App\Models\Area;
use App\Models\Customer;
use App\Models\Package;
use App\Models\PppSecret;
use App\Models\Router;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

function makeCustomerXlsx(array $rows): string
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->fromArray([
        'Nama', 'Alamat', 'No HP', 'Area', 'Router', 'Paket',
        'PPP Username', 'PPP Password', 'Tanggal Pasang', 'Jatuh Tempo',
        'Hari Isolir', 'Status', 'Catatan',
    ], null, 'A1');

    foreach ($rows as $i => $row) {
        $sheet->fromArray($row, null, 'A'.($i + 2));
    }

    $writer = new Xlsx($spreadsheet);
    ob_start();
    $writer->save('php://output');

    return ob_get_clean();
}

function uploadCustomerXlsx(string $content): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'import').'.xlsx';
    file_put_contents($path, $content);

    return new UploadedFile(
        $path,
        'customers.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true,
    );
}

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $this->area = Area::factory()->create(['name' => 'Area Import', 'code' => 'IMP']);
    $this->router = Router::factory()->create(['name' => 'Router Import', 'status' => 'offline']);
    $this->package = Package::factory()->create(['name' => 'Paket Import', 'router_id' => $this->router->id]);
});

test('customer import template can be downloaded', function () {
    $response = $this->get(route('customers.import.template'));

    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('customers can be imported from excel and linked to existing ppp secret', function () {
    PppSecret::factory()->create([
        'router_id' => $this->router->id,
        'name' => 'imp_ppp_user',
        'password' => 'realsecret',
    ]);

    $content = makeCustomerXlsx([[
        'John Import',
        'Jl. Import No. 1',
        '08111111111',
        'Area Import',
        'Router Import',
        'Paket Import',
        'imp_ppp_user',
        'typedpass',
        date('Y-m-d'),
        10,
        '',
        'Active',
        '',
    ]]);

    $response = $this->post(route('customers.import'), [
        'file' => uploadCustomerXlsx($content),
        'link_ppp_secret' => '1',
    ]);

    $response->assertRedirect(route('customers.import.form'));
    $response->assertSessionHas('success');

    $customer = Customer::where('ppp_username', 'imp_ppp_user')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->name)->toBe('John Import')
        ->and($customer->ppp_secret_id)->not->toBeNull()
        ->and($customer->customer_code)->toMatch('/^\d{6}$/');
});

test('customer import reports per-row errors', function () {
    PppSecret::factory()->create([
        'router_id' => $this->router->id,
        'name' => 'valid_ppp',
        'password' => 'realsecret',
    ]);

    $content = makeCustomerXlsx([
        [
            'Valid User',
            'Jl. Valid',
            '08222222222',
            'Area Import',
            'Router Import',
            'Paket Import',
            'valid_ppp',
            'pass123',
            date('Y-m-d'),
            10,
            '',
            'Active',
            '',
        ],
        [
            'Invalid User',
            'Jl. Invalid',
            '08333333333',
            'Area Tidak Ada',
            'Router Import',
            'Paket Import',
            'invalid_ppp',
            'pass123',
            date('Y-m-d'),
            10,
            '',
            'Active',
            '',
        ],
    ]);

    $response = $this->post(route('customers.import'), [
        'file' => uploadCustomerXlsx($content),
    ]);

    $response->assertRedirect(route('customers.import.form'));
    $response->assertSessionHas('warning');

    $result = session('import_result');

    expect($result['success'])->toBe(1)
        ->and($result['errors'])->toHaveCount(1)
        ->and($result['errors'][0]['row'])->toBe(3)
        ->and($result['errors'][0]['message'])->toContain('Area Tidak Ada');

    expect(Customer::where('ppp_username', 'valid_ppp')->exists())->toBeTrue()
        ->and(Customer::where('ppp_username', 'invalid_ppp')->exists())->toBeFalse();
});

test('customer import rejects file missing required columns', function () {
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray(['Nama', 'Alamat'], null, 'A1');
    $writer = new Xlsx($spreadsheet);
    ob_start();
    $writer->save('php://output');
    $content = ob_get_clean();

    $response = $this->post(route('customers.import'), [
        'file' => uploadCustomerXlsx($content),
    ]);

    $response->assertSessionHas('error');
});

test('customer import requires a file', function () {
    $response = $this->post(route('customers.import'), []);

    $response->assertSessionHasErrors('file');
});
