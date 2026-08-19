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

        return view('infrastruktur.index', compact('odcs', 'odps'));
    }
}
