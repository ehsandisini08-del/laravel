<?php

namespace App\Http\Controllers;

use App\Enums\RepairTaskStatus;
use App\Models\Customer;
use App\Models\RepairTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

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
    public function laporanPemasangan(): View
    {
        $this->authorizeTeknisiAccess();

        return view('teknisi.laporan-pemasangan');
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
