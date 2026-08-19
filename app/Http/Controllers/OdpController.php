<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOdpRequest;
use App\Http\Requests\UpdateOdpRequest;
use App\Models\Odc;
use App\Models\Odp;
use App\Services\ActivityLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class OdpController extends Controller
{
    public function __construct(
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function index(): RedirectResponse
    {
        return redirect()->route('infrastruktur.index');
    }

    public function create()
    {
        $odcs = Odc::orderBy('nama_odc')->get();

        return view('infrastruktur.odp.create', compact('odcs'));
    }

    public function store(StoreOdpRequest $request): RedirectResponse
    {
        try {
            $odp = Odp::create($request->validated());

            $this->activityLogger->created('Infrastruktur', "ODP '{$odp->nama_odp}' created", $odp);

            return redirect()->route('infrastruktur.index')
                ->with('success', 'ODP berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Failed to create ODP', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withInput()->with('error', 'Gagal menambahkan ODP: '.$e->getMessage());
        }
    }

    public function edit(Odp $odp)
    {
        $odcs = Odc::orderBy('nama_odc')->get();

        return view('infrastruktur.odp.edit', compact('odp', 'odcs'));
    }

    public function update(UpdateOdpRequest $request, Odp $odp): RedirectResponse
    {
        try {
            $odp->update($request->validated());

            $this->activityLogger->updated('Infrastruktur', "ODP '{$odp->nama_odp}' updated", $odp);

            return redirect()->route('infrastruktur.index')
                ->with('success', 'ODP berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Failed to update ODP', [
                'odp_id' => $odp->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Gagal memperbarui ODP: '.$e->getMessage());
        }
    }

    public function destroy(Odp $odp): RedirectResponse
    {
        try {
            $odp->delete();

            $this->activityLogger->deleted('Infrastruktur', "ODP '{$odp->nama_odp}' deleted", $odp);

            return redirect()->route('infrastruktur.index')
                ->with('success', 'ODP berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Failed to delete ODP', [
                'odp_id' => $odp->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal menghapus ODP: '.$e->getMessage());
        }
    }
}
