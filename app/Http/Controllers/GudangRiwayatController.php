<?php

namespace App\Http\Controllers;

use App\Services\GudangService;
use Illuminate\Http\Request;

class GudangRiwayatController extends Controller
{
    public function __construct(
        protected GudangService $gudangService,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['item_id', 'type', 'from', 'to']);

        return view('gudang.riwayat', [
            'movements' => $this->gudangService->getMovements($filters),
            'items' => $this->gudangService->getActiveItems(),
        ]);
    }
}
