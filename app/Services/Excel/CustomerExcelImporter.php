<?php

namespace App\Services\Excel;

use App\Enums\CustomerStatus;
use App\Models\Area;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Router;
use App\Services\CustomerService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class CustomerExcelImporter
{
    protected const COLUMN_ALIASES = [
        'nama' => 'name',
        'name' => 'name',
        'alamat' => 'address',
        'address' => 'address',
        'nohp' => 'phone',
        'phone' => 'phone',
        'telepon' => 'phone',
        'area' => 'area',
        'router' => 'router',
        'paket' => 'package',
        'package' => 'package',
        'pppusername' => 'ppp_username',
        'ppppassword' => 'ppp_password',
        'tanggalpasang' => 'installation_date',
        'installationdate' => 'installation_date',
        'jatuhtempo' => 'due_day',
        'dueday' => 'due_day',
        'hariisolir' => 'isolation_day',
        'isolationday' => 'isolation_day',
        'status' => 'status',
        'catatan' => 'notes',
        'notes' => 'notes',
    ];

    protected const REQUIRED_COLUMNS = [
        'name', 'address', 'phone', 'area', 'router', 'package', 'ppp_username', 'ppp_password', 'due_day',
    ];

    /** @var array<string, int> */
    protected array $areas = [];

    /** @var array<string, int> */
    protected array $routers = [];

    /** @var array<int, array<string, int>> */
    protected array $packagesByRouter = [];

    /** @var array<string, int> */
    protected array $packagesAny = [];

    public function __construct(
        protected CustomerService $customerService,
    ) {}

    /**
     * @return array{success: int, errors: array<int, array{row: int, message: string}>}
     */
    public function import(UploadedFile $file, bool $linkPppSecret = true): array
    {
        $rows = $this->readRows($file);

        $map = $this->buildColumnMap(array_shift($rows) ?? []);
        $this->validateColumns($map);

        $this->loadLookups();

        $result = ['success' => 0, 'errors' => []];
        $rowNumber = 1;

        foreach ($rows as $row) {
            $rowNumber++;

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            try {
                $data = $this->extractData($row, $map);
                $data['portal_enabled'] = true;
                $data['create_ppp_secret'] = $linkPppSecret;

                $this->customerService->create($data);

                $result['success']++;
            } catch (Throwable $e) {
                $result['errors'][] = [
                    'row' => $rowNumber,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $result;
    }

    protected function readRows(UploadedFile $file): array
    {
        $reader = IOFactory::createReaderForFile($file->getPathname());
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();

        return $sheet->toArray(null, true, true, false);
    }

    protected function buildColumnMap(array $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $index => $header) {
            $normalized = $this->normalize((string) $header);

            if ($normalized === '') {
                continue;
            }

            if (isset(self::COLUMN_ALIASES[$normalized])) {
                $map[self::COLUMN_ALIASES[$normalized]] = $index;
            }
        }

        return $map;
    }

    protected function validateColumns(array $map): void
    {
        $missing = array_values(array_diff(self::REQUIRED_COLUMNS, array_keys($map)));

        if ($missing) {
            throw new \RuntimeException('Kolom wajib tidak ditemukan di file: '.implode(', ', $missing));
        }
    }

    protected function loadLookups(): void
    {
        $this->areas = Area::all()->pluck('id', 'name')->mapWithKeys(
            fn ($id, $name) => [$this->normalize($name) => $id]
        )->all();

        $this->routers = Router::all()->pluck('id', 'name')->mapWithKeys(
            fn ($id, $name) => [$this->normalize($name) => $id]
        )->all();

        foreach (Package::all() as $package) {
            $this->packagesByRouter[(int) $package->router_id][$this->normalize($package->name)] = (int) $package->id;
            $this->packagesAny[$this->normalize($package->name)] = (int) $package->id;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractData(array $row, array $map): array
    {
        $value = fn (string $key) => $this->clean($row[$map[$key]] ?? null);

        $areaName = $value('area');
        $routerName = $value('router');
        $packageName = $value('package');

        $routerId = $this->routers[$this->normalize($routerName)] ?? null;
        if (! $routerId) {
            throw new \RuntimeException("Router '{$routerName}' tidak ditemukan.");
        }

        $areaId = $this->areas[$this->normalize($areaName)] ?? null;
        if (! $areaId) {
            throw new \RuntimeException("Area '{$areaName}' tidak ditemukan.");
        }

        $packageId = $this->packagesByRouter[$routerId][$this->normalize($packageName)]
            ?? $this->packagesAny[$this->normalize($packageName)]
            ?? null;
        if (! $packageId) {
            throw new \RuntimeException("Paket '{$packageName}' tidak ditemukan.");
        }

        $status = $value('status') ?: CustomerStatus::Active->value;
        if (! in_array($status, array_column(CustomerStatus::cases(), 'value'), true)) {
            throw new \RuntimeException("Status '{$status}' tidak valid.");
        }

        $dueDay = (int) $value('due_day');
        if ($dueDay < 1 || $dueDay > 31) {
            throw new \RuntimeException('Jatuh tempo harus angka 1-31.');
        }

        $isolationDay = $value('isolation_day');
        if ($isolationDay !== null && ((int) $isolationDay < 1 || (int) $isolationDay > 31)) {
            throw new \RuntimeException('Hari isolir harus angka 1-31.');
        }

        $phone = $value('phone');
        if (Customer::where('phone', $phone)->exists()) {
            throw new \RuntimeException("Nomor telepon '{$phone}' sudah digunakan.");
        }

        $pppUsername = $value('ppp_username');
        if (Customer::where('ppp_username', $pppUsername)->exists()) {
            throw new \RuntimeException("PPP Username '{$pppUsername}' sudah digunakan.");
        }

        return [
            'name' => $value('name'),
            'address' => $value('address'),
            'phone' => $phone,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'area_id' => $areaId,
            'router_id' => $routerId,
            'package_id' => $packageId,
            'ppp_username' => $pppUsername,
            'ppp_password' => $value('ppp_password'),
            'installation_date' => $this->parseDate($value('installation_date')),
            'due_day' => $dueDay,
            'isolation_day' => $isolationDay === null ? null : (int) $isolationDay,
            'status' => $status,
            'notes' => $value('notes'),
        ];
    }

    protected function parseDate(?string $value): string
    {
        if ($value === '' || $value === null) {
            return today()->format('Y-m-d');
        }

        try {
            $date = Carbon::parse($value);
        } catch (Throwable) {
            $date = \DateTime::createFromFormat('d/m/Y', $value);

            if (! $date) {
                throw new \RuntimeException("Tanggal pasang '{$value}' tidak valid (gunakan YYYY-MM-DD).");
            }

            $date = Carbon::instance($date);
        }

        return $date->format('Y-m-d');
    }

    protected function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function normalize(string $value): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/i', '', $value));
    }

    protected function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
