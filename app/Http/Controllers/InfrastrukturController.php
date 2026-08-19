<?php

namespace App\Http\Controllers;

use App\Models\Odc;
use App\Models\Odp;

class InfrastrukturController extends Controller
{
    public function index()
    {
        $odcs = Odc::latest()->paginate(15);
        $odps = Odp::with('odc')->latest()->paginate(15);
        $mapOdcs = Odc::all(['id', 'kode_odc', 'nama_odc', 'latitude', 'longitude']);
        $mapOdps = Odp::all(['id', 'odc_id', 'kode_odp', 'nama_odp', 'latitude', 'longitude']);

        return view('infrastruktur.index', compact('odcs', 'odps', 'mapOdcs', 'mapOdps'));
    }
}
