<?php

namespace App\Http\Controllers;

use App\Enums\RepairTaskStatus;
use App\Models\Customer;
use App\Models\InstallationReport;
use App\Models\RepairTask;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeknisiController extends Controller
{
    /**
     * Menu Buat Tugas - Khusus Developer & Superadmin
     */
    public function buatTugas(): View
    {
        if (! auth()->user()->canManageTeknisiTasks()) {
            abort(403, 'Halaman ini hanya dapat diakses oleh Developer dan Superadmin.');
        }

        $customers = Customer::with(['area', 'package'])
            ->orderBy('name')
            ->get();

        return view('teknisi.buat-tugas', compact('customers'));
    }

    /**
     * Menu Tugas Perbaikan
     */
    public function tugasPerbaikan(): View
    {
        $this->authorizeTeknisiAccess();

        $user = auth()->user();

        if ($user->canManageTeknisiTasks()) {
            $tasks = RepairTask::with(['customer', 'assignedBy', 'takenBy'])
                ->latest()
                ->paginate(20);

            $stats = [
                'baru' => RepairTask::where('status', RepairTaskStatus::Baru)->count(),
                'proses' => RepairTask::where('status', RepairTaskStatus::Proses)->count(),
                'selesai_hari_ini' => RepairTask::where('status', RepairTaskStatus::Selesai)
                    ->whereDate('completed_at', today())
                    ->count(),
            ];
        } else {
            $tasks = RepairTask::with(['customer', 'assignedBy', 'takenBy'])
                ->where(function ($query) use ($user) {
                    $query->where('status', RepairTaskStatus::Baru)
                        ->orWhere('taken_by_user_id', $user->id);
                })
                ->latest()
                ->paginate(20);

            $stats = [
                'tersedia' => RepairTask::where('status', RepairTaskStatus::Baru)->count(),
                'tugas_saya' => RepairTask::where('status', RepairTaskStatus::Proses)
                    ->where('taken_by_user_id', $user->id)
                    ->count(),
                'selesai_bulan_ini' => RepairTask::where('status', RepairTaskStatus::Selesai)
                    ->where('taken_by_user_id', $user->id)
                    ->whereMonth('completed_at', now()->month)
                    ->count(),
            ];
        }

        return view('teknisi.tugas-perbaikan', compact('tasks', 'stats'));
    }

    /**
     * Menu Laporan Harian
     */
    public function laporanHarian(Request $request): View
    {
        $this->authorizeTeknisiAccess();

        $user = auth()->user();
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $teknisiFilter = $request->input('teknisi');
        $search = $request->input('search');

        $query = RepairTask::with(['takenBy', 'assignedBy', 'technicians', 'customer'])
            ->selesai()
            ->latest('completed_at');

        if ($dateFrom) {
            $query->whereDate('completed_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('completed_at', '<=', $dateTo);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_customer', 'like', "%{$search}%")
                    ->orWhere('no_telp', 'like', "%{$search}%")
                    ->orWhere('alamat', 'like', "%{$search}%")
                    ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        if (! $user->canManageTeknisiTasks()) {
            $query->where(function ($q) use ($user) {
                $q->where('taken_by_user_id', $user->id)
                    ->orWhereHas('technicians', fn ($sq) => $sq->where('users.id', $user->id));
            });
        } elseif ($teknisiFilter) {
            $query->where(function ($q) use ($teknisiFilter) {
                $q->where('taken_by_user_id', $teknisiFilter)
                    ->orWhereHas('technicians', fn ($sq) => $sq->where('users.id', $teknisiFilter));
            });
        }

        $laporans = $query->paginate(25)->withQueryString();

        $baseStatsQuery = fn () => RepairTask::selesai()
            ->when(! $user->canManageTeknisiTasks(), fn ($q) => $q->where(function ($inner) use ($user) {
                $inner->where('taken_by_user_id', $user->id)
                    ->orWhereHas('technicians', fn ($sq) => $sq->where('users.id', $user->id));
            }));

        $stats = [
            'total_selesai' => $baseStatsQuery()->count(),
            'total_bulan_ini' => $baseStatsQuery()
                ->whereMonth('completed_at', now()->month)
                ->whereYear('completed_at', now()->year)
                ->count(),
            'total_hari_ini' => $baseStatsQuery()
                ->whereDate('completed_at', today())
                ->count(),
        ];

        $teknisiList = $user->canManageTeknisiTasks()
            ? User::orderBy('name')->get(['id', 'name'])
            : collect();

        return view('teknisi.laporan-harian', compact(
            'laporans', 'stats', 'dateFrom', 'dateTo',
            'teknisiFilter', 'teknisiList', 'search'
        ));
    }

    /**
     * Export Laporan Harian sebagai CSV (khusus developer/superadmin)
     */
    public function exportLaporanHarian(Request $request): Response
    {
        $this->authorizeTeknisiAccess();

        if (! auth()->user()->canManageTeknisiTasks()) {
            abort(403, 'Hanya developer dan superadmin yang dapat mengekspor laporan.');
        }

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $teknisiFilter = $request->input('teknisi');
        $search = $request->input('search');

        $query = RepairTask::with(['takenBy', 'assignedBy', 'technicians'])
            ->selesai()
            ->latest('completed_at');

        if ($dateFrom) {
            $query->whereDate('completed_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('completed_at', '<=', $dateTo);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_customer', 'like', "%{$search}%")
                    ->orWhere('no_telp', 'like', "%{$search}%")
                    ->orWhere('alamat', 'like', "%{$search}%");
            });
        }
        if ($teknisiFilter) {
            $query->where(function ($q) use ($teknisiFilter) {
                $q->where('taken_by_user_id', $teknisiFilter)
                    ->orWhereHas('technicians', fn ($sq) => $sq->where('users.id', $teknisiFilter));
            });
        }

        $tasks = $query->get();

        $suffix = ($dateFrom || $dateTo)
            ? '-'.str_replace('-', '', $dateFrom ?? 'all').'-sd-'.str_replace('-', '', $dateTo ?? 'now')
            : '-semua';
        $filename = 'laporan-perbaikan'.$suffix.'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = [
            'No', 'ID Tiket', 'Nama Pelanggan', 'No. Telepon',
            'Alamat', 'Kendala / Masalah', 'Keterangan Penyelesaian',
            'Teknisi Lead', 'Rekan Kerja', 'Dibuat Oleh',
            'Tgl Selesai', 'Waktu Ambil', 'Waktu Selesai', 'Durasi (menit)',
        ];

        $csv = "\xEF\xBB\xBF".implode(',', $columns)."\n";

        foreach ($tasks as $index => $task) {
            $takenAt = $task->taken_at;
            $completedAt = $task->completed_at;
            $duration = ($takenAt && $completedAt) ? $takenAt->diffInMinutes($completedAt) : '-';

            $partners = $task->technicians
                ->where('id', '!=', $task->taken_by_user_id)
                ->pluck('name')
                ->join('; ');

            $row = [
                $index + 1,
                '#'.$task->id,
                '"'.str_replace('"', '""', $task->nama_customer).'"',
                $task->no_telp,
                '"'.str_replace('"', '""', $task->alamat).'"',
                '"'.str_replace('"', '""', $task->keterangan).'"',
                '"'.str_replace('"', '""', $task->keterangan_teknisi ?? '-').'"',
                '"'.str_replace('"', '""', $task->takenBy?->name ?? '-').'"',
                '"'.str_replace('"', '""', $partners).'"',
                '"'.str_replace('"', '""', $task->assignedBy?->name ?? '-').'"',
                $completedAt ? $completedAt->format('d/m/Y') : '-',
                $takenAt ? $takenAt->format('H:i') : '-',
                $completedAt ? $completedAt->format('H:i') : '-',
                $duration,
            ];

            $csv .= implode(',', $row)."\n";
        }

        return response($csv, 200, $headers);
    }

    /**
     * Menu Laporan Pemasangan
     */
    public function laporanPemasangan(Request $request): View
    {
        $this->authorizeTeknisiAccess();

        $search = $request->input('search');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $bulan = $request->input('bulan');

        $query = InstallationReport::with(['customer.area', 'customer.package', 'customer.odp', 'user'])
            ->latest();

        if ($search) {
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('customer_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($bulan) {
            [$year, $month] = explode('-', $bulan);
            $query->whereMonth('installation_date', (int) $month)
                ->whereYear('installation_date', (int) $year);
        } else {
            if ($dateFrom) {
                $query->whereDate('installation_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('installation_date', '<=', $dateTo);
            }
        }

        $reports = $query->paginate(25)->withQueryString();

        $stats = [
            'bulan_ini' => InstallationReport::whereMonth('installation_date', now()->month)
                ->whereYear('installation_date', now()->year)
                ->count(),
            'total' => InstallationReport::count(),
        ];

        return view('teknisi.laporan-pemasangan', compact('reports', 'stats', 'search', 'dateFrom', 'dateTo', 'bulan'));
    }

    /**
     * Export Laporan Pemasangan sebagai Excel
     */
    public function exportLaporanPemasangan(Request $request): StreamedResponse
    {
        $this->authorizeTeknisiAccess();

        $bulan = $request->input('bulan');

        $query = InstallationReport::with(['customer.area', 'customer.package', 'customer.odp', 'user'])
            ->latest();

        if ($bulan) {
            [$year, $month] = explode('-', $bulan);
            $query->whereMonth('installation_date', (int) $month)
                ->whereYear('installation_date', (int) $year);
            $suffix = '-'.$bulan;
        } else {
            $suffix = '-semua';
        }

        $reports = $query->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Pemasangan');

        $headers = [
            'No', 'Nama Pelanggan', 'Kode', 'Alamat', 'Paket', 'Area',
            'ODP', 'Port', 'Tgl Pemasangan', 'RX Power',
            'Perangkat / Merk', 'Part Yang Digunakan', 'Dibuat Oleh', 'Catatan',
        ];
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
        ];
        $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);

        foreach ($reports as $index => $report) {
            $row = $index + 2;
            $sheet->setCellValue("A{$row}", $index + 1);
            $sheet->setCellValue("B{$row}", $report->customer?->name ?? '-');
            $sheet->setCellValue("C{$row}", $report->customer?->customer_code ?? '-');
            $sheet->setCellValue("D{$row}", $report->customer?->address ?? '-');
            $sheet->setCellValue("E{$row}", $report->customer?->package?->name ?? '-');
            $sheet->setCellValue("F{$row}", $report->customer?->area?->name ?? '-');
            $sheet->setCellValue("G{$row}", $report->customer?->odp?->kode ?? '-');
            $sheet->setCellValue("H{$row}", $report->port_odp ?? $report->customer?->port_odp ?? '-');
            $sheet->setCellValue("I{$row}", $report->installation_date ? Carbon::parse($report->installation_date)->format('d/m/Y') : '-');
            $sheet->setCellValue("J{$row}", $report->rx_power ?? '-');
            $sheet->setCellValue("K{$row}", $report->device_name ?? '-');
            $sheet->setCellValue("L{$row}", $report->parts_used ?? '-');
            $sheet->setCellValue("M{$row}", $report->user?->name ?? '-');
            $sheet->setCellValue("N{$row}", $report->notes ?? '-');
        }

        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'laporan-pemasangan'.$suffix.'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Menu Pekerjaan
     */
    public function pekerjaan(): View
    {
        $this->authorizeTeknisiAccess();

        return view('teknisi.pekerjaan');
    }

    /**
     * Authorize user access to general Teknisi module
     */
    protected function authorizeTeknisiAccess(): void
    {
        if (! auth()->user()->canAccessTeknisi()) {
            abort(403, 'Akses ditolak.');
        }
    }
}
