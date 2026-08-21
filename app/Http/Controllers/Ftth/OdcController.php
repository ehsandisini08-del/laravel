<?php

namespace App\Http\Controllers\Ftth;

use App\Http\Controllers\Controller;
use App\Models\Odc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OdcController extends Controller
{
    public function index(): View
    {
        $odcs = Odc::withCount('odps')->latest()->paginate(20);

        return view('ftth.odc.index', compact('odcs'));
    }

    public function create(): View
    {
        $statusOptions = Odc::statusOptions();

        return view('ftth.odc.create', compact('statusOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:50|unique:odcs,kode',
            'nama' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'kapasitas' => 'required|integer|min:1',
            'keterangan' => 'nullable|string',
            'status' => 'required|string|in:ACTIVE,WARNING,DOWN,MAINTENANCE,INACTIVE',
        ]);

        Odc::create($validated);

        return redirect()->route('ftth.odc.index')
            ->with('success', "ODC {$validated['kode']} berhasil ditambahkan.");
    }

    public function show(Odc $odc): View
    {
        $odc->load(['odps' => fn ($q) => $q->withCount('customers')->orderBy('kode')]);

        return view('ftth.odc.show', compact('odc'));
    }

    public function edit(Odc $odc): View
    {
        $statusOptions = Odc::statusOptions();

        return view('ftth.odc.edit', compact('odc', 'statusOptions'));
    }

    public function update(Request $request, Odc $odc): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:50|unique:odcs,kode,'.$odc->id,
            'nama' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'kapasitas' => 'required|integer|min:1',
            'keterangan' => 'nullable|string',
            'status' => 'required|string|in:ACTIVE,WARNING,DOWN,MAINTENANCE,INACTIVE',
        ]);

        $odc->update($validated);

        return redirect()->route('ftth.odc.show', $odc)
            ->with('success', "ODC {$odc->kode} berhasil diperbarui.");
    }

    public function destroy(Odc $odc): RedirectResponse
    {
        $kode = $odc->kode;
        $odc->delete();

        return redirect()->route('ftth.odc.index')
            ->with('success', "ODC {$kode} berhasil dihapus.");
    }
}
