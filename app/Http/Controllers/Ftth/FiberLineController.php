<?php

namespace App\Http\Controllers\Ftth;

use App\Http\Controllers\Controller;
use App\Models\FiberLine;
use App\Models\Odc;
use App\Models\Odp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FiberLineController extends Controller
{
    public function index(): View
    {
        $fibers = FiberLine::latest()->paginate(20);

        return view('ftth.fiber.index', compact('fibers'));
    }

    public function create(): View
    {
        $statusOptions = FiberLine::statusOptions();
        $odcs = Odc::orderBy('kode')->get(['id', 'kode', 'nama']);
        $odps = Odp::orderBy('kode')->get(['id', 'kode', 'nama']);

        return view('ftth.fiber.create', compact('statusOptions', 'odcs', 'odps'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'tipe_kabel' => 'nullable|string|max:50',
            'source_type' => 'nullable|string|in:odc,odp',
            'source_id' => 'nullable|integer',
            'destination_type' => 'nullable|string|in:odc,odp,customer',
            'destination_id' => 'nullable|integer',
            'geometry' => 'nullable|json',
            'status' => 'required|string|in:ACTIVE,INACTIVE,DAMAGE',
            'keterangan' => 'nullable|string',
        ]);

        FiberLine::create($validated);

        return redirect()->route('ftth.fiber.index')
            ->with('success', 'Jalur fiber berhasil ditambahkan.');
    }

    public function show(FiberLine $fiber): View
    {
        return view('ftth.fiber.show', compact('fiber'));
    }

    public function edit(FiberLine $fiber): View
    {
        $statusOptions = FiberLine::statusOptions();
        $odcs = Odc::orderBy('kode')->get(['id', 'kode', 'nama']);
        $odps = Odp::orderBy('kode')->get(['id', 'kode', 'nama']);

        return view('ftth.fiber.edit', compact('fiber', 'statusOptions', 'odcs', 'odps'));
    }

    public function update(Request $request, FiberLine $fiber): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'tipe_kabel' => 'nullable|string|max:50',
            'source_type' => 'nullable|string|in:odc,odp',
            'source_id' => 'nullable|integer',
            'destination_type' => 'nullable|string|in:odc,odp,customer',
            'destination_id' => 'nullable|integer',
            'geometry' => 'nullable|json',
            'status' => 'required|string|in:ACTIVE,INACTIVE,DAMAGE',
            'keterangan' => 'nullable|string',
        ]);

        $fiber->update($validated);

        return redirect()->route('ftth.fiber.index')
            ->with('success', 'Jalur fiber berhasil diperbarui.');
    }

    public function destroy(FiberLine $fiber): RedirectResponse
    {
        $fiber->delete();

        return redirect()->route('ftth.fiber.index')
            ->with('success', 'Jalur fiber berhasil dihapus.');
    }
}
