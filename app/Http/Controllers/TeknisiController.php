<?php

namespace App\Http\Controllers;

use App\Enums\RepairTaskStatus;
use App\Models\Customer;
use App\Models\RepairTask;
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
    public function laporanHarian(): View
    {
        $this->authorizeTeknisiAccess();

        return view('teknisi.laporan-harian');
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
