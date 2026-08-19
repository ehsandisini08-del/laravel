<?php

namespace App\Http\Controllers;

class GudangController extends Controller
{
    public function stok()
    {
        return view('gudang.stok');
    }

    public function barangMasuk()
    {
        return view('gudang.barang-masuk');
    }

    public function barangKeluar()
    {
        return view('gudang.barang-keluar');
    }
}
