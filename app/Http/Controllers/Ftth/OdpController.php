<?php

namespace App\Http\Controllers\Ftth;

use App\Http\Controllers\Controller;
use App\Models\Odc;
use App\Models\Odp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OdpController extends Controller
{
    public function index(): View
    {
        $odps = Odp::with('odc:id,kode,nama')
            ->withCount('customers')
            ->latest()
            ->paginate(20);

        return view('ftth.odp.index', compact('odps'));
    }

    public function create(): View
    {
        $statusOptions = Odp::statusOptions();
        $odcs = Odc::orderBy('kode')->get(['id', 'kode', 'nama']);

        return view('ftth.odp.create', compact('statusOptions', 'odcs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'odc_id' => 'required|exists:odcs,id',
            'kode' => 'required|string|max:50|unique:odps,kode',
            'nama' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'kapasitas' => 'required|integer|min:1',
            'port_terpakai' => 'required|integer|min:0',
            'keterangan' => 'nullable|string',
            'status' => 'required|string|in:ACTIVE,WARNING,DOWN,MAINTENANCE,INACTIVE',
        ]);

        Odp::create($validated);

        return redirect()->route('ftth.odp.index')
            ->with('success', "ODP {$validated['kode']} berhasil ditambahkan.");
    }

    public function show(Odp $odp): View
    {
        $odp->load(['odc:id,kode,nama', 'customers' => fn ($q) => $q->orderBy('port_odp')]);

        return view('ftth.odp.show', compact('odp'));
    }

    public function edit(Odp $odp): View
    {
        $statusOptions = Odp::statusOptions();
        $odcs = Odc::orderBy('kode')->get(['id', 'kode', 'nama']);

        return view('ftth.odp.edit', compact('odp', 'statusOptions', 'odcs'));
    }

    public function update(Request $request, Odp $odp): RedirectResponse
    {
        $validated = $request->validate([
            'odc_id' => 'required|exists:odcs,id',
            'kode' => 'required|string|max:50|unique:odps,kode,'.$odp->id,
            'nama' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'kapasitas' => 'required|integer|min:1',
            'port_terpakai' => 'required|integer|min:0',
            'keterangan' => 'nullable|string',
            'status' => 'required|string|in:ACTIVE,WARNING,DOWN,MAINTENANCE,INACTIVE',
        ]);

        $odp->update($validated);

        return redirect()->route('ftth.odp.show', $odp)
            ->with('success', "ODP {$odp->kode} berhasil diperbarui.");
    }

    public function destroy(Odp $odp): RedirectResponse
    {
        $kode = $odp->kode;
        $odp->delete();

        return redirect()->route('ftth.odp.index')
            ->with('success', "ODP {$kode} berhasil dihapus.");
    }
}
