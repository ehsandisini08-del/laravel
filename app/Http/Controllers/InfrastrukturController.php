<?php

namespace App\Http\Controllers;

use App\Models\Odc;

class InfrastrukturController extends Controller
{
    public function index()
    {
        $odcs = Odc::latest()->paginate(15);

        return view('infrastruktur.index', compact('odcs'));
    }
}
