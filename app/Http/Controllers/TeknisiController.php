<?php

namespace App\Http\Controllers;

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

        return view('teknisi.buat-tugas');
    }

    /**
     * Menu Tugas Perbaikan
     */
    public function tugasPerbaikan(): View
    {
        $this->authorizeTeknisiAccess();

        return view('teknisi.tugas-perbaikan');
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
