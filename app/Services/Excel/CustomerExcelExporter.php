<?php

namespace App\Services\Excel;

use App\Models\Area;
use App\Models\Package;
use App\Models\Router;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerExcelExporter
{
    protected const HEADERS = [
        'Nama',
        'Alamat',
        'No HP',
        'Area',
        'Router',
        'Paket',
        'PPP Username',
        'PPP Password',
        'Tanggal Pasang',
        'Jatuh Tempo',
        'Hari Isolir',
        'Status',
        'Catatan',
    ];

    public const STATUS_OPTIONS = ['Active', 'Isolated', 'Suspended', 'Terminated'];

    public function createTemplate(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');
        $sheet->fromArray(self::HEADERS, null, 'A1');

        $this->styleHeader($sheet);

        $areas = Area::orderBy('name')->pluck('name')->values()->all();
        $routers = Router::orderBy('name')->pluck('name')->values()->all();
        $packages = Package::orderBy('name')->pluck('name')->values()->all();

        $example = [
            'Contoh Pelanggan',
            'Jl. Contoh No. 1',
            '081234567890',
            $areas[0] ?? 'AreaA',
            $routers[0] ?? 'RouterA',
            $packages[0] ?? 'PaketA',
            'contoh_ppp',
            'password123',
            date('Y-m-d'),
            '10',
            '',
            'Active',
            '',
        ];

        $sheet->fromArray($example, null, 'A2');
        $sheet->getStyle('A2:M2')->getFont()->getColor()->setARGB('FF9CA3AF');

        $this->addDaftarSheet($spreadsheet, $areas, $routers, $packages);
        $this->addValidations($sheet);

        $spreadsheet->getActiveSheet()->setTitle('Template');

        return $spreadsheet;
    }

    public function fileName(): string
    {
        return 'template-import-customer.xlsx';
    }

    protected function styleHeader(Worksheet $sheet): void
    {
        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF2563EB'],
            ],
        ]);
        $sheet->getColumnDimension('A')->setWidth(22);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(16);
        $sheet->getColumnDimension('I')->setWidth(16);
        $sheet->getColumnDimension('J')->setWidth(14);
        $sheet->getColumnDimension('K')->setWidth(14);
        $sheet->getColumnDimension('L')->setWidth(14);
        $sheet->getColumnDimension('M')->setWidth(30);
    }

    protected function addDaftarSheet(Spreadsheet $spreadsheet, array $areas, array $routers, array $packages): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Daftar');

        $sheet->setCellValue('A1', 'Area');
        $sheet->setCellValue('B1', 'Router');
        $sheet->setCellValue('C1', 'Paket');
        $sheet->setCellValue('D1', 'Status');

        foreach ($areas as $i => $area) {
            $sheet->setCellValueExplicit('A'.($i + 2), $area, DataType::TYPE_STRING);
        }

        foreach ($routers as $i => $router) {
            $sheet->setCellValueExplicit('B'.($i + 2), $router, DataType::TYPE_STRING);
        }

        foreach ($packages as $i => $package) {
            $sheet->setCellValueExplicit('C'.($i + 2), $package, DataType::TYPE_STRING);
        }

        foreach (self::STATUS_OPTIONS as $i => $status) {
            $sheet->setCellValue('D'.($i + 2), $status);
        }
    }

    protected function addValidations(Worksheet $sheet): void
    {
        $this->addListValidation($sheet, 'D2:D1000', "='Daftar'!\$A\$2:\$A\$1000");
        $this->addListValidation($sheet, 'E2:E1000', "='Daftar'!\$B\$2:\$B\$1000");
        $this->addListValidation($sheet, 'F2:F1000', "='Daftar'!\$C\$2:\$C\$1000");
        $this->addListValidation($sheet, 'L2:L1000', "='Daftar'!\$D\$2:\$D\$1000");
    }

    protected function addListValidation(Worksheet $sheet, string $range, string $formula): void
    {
        $validation = new DataValidation;
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setFormula1($formula);
        $validation->setAllowBlank(true);

        $sheet->setDataValidation($range, $validation);
    }
}
