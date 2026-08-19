<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOdcRequest;
use App\Http\Requests\UpdateOdcRequest;
use App\Models\Odc;
use App\Services\ActivityLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class OdcController extends Controller
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
        return view('infrastruktur.odc.create');
    }

    public function store(StoreOdcRequest $request): RedirectResponse
    {
        try {
            $odc = Odc::create($request->validated());

            $this->activityLogger->created('Infrastruktur', "ODC '{$odc->nama_odc}' created", $odc);

            return redirect()->route('infrastruktur.index')
                ->with('success', 'ODC berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Failed to create ODC', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withInput()->with('error', 'Gagal menambahkan ODC: '.$e->getMessage());
        }
    }

    public function edit(Odc $odc)
    {
        return view('infrastruktur.odc.edit', compact('odc'));
    }

    public function update(UpdateOdcRequest $request, Odc $odc): RedirectResponse
    {
        try {
            $odc->update($request->validated());

            $this->activityLogger->updated('Infrastruktur', "ODC '{$odc->nama_odc}' updated", $odc);

            return redirect()->route('infrastruktur.index')
                ->with('success', 'ODC berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Failed to update ODC', [
                'odc_id' => $odc->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Gagal memperbarui ODC: '.$e->getMessage());
        }
    }

    public function destroy(Odc $odc): RedirectResponse
    {
        try {
            $odc->delete();

            $this->activityLogger->deleted('Infrastruktur', "ODC '{$odc->nama_odc}' deleted", $odc);

            return redirect()->route('infrastruktur.index')
                ->with('success', 'ODC berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Failed to delete ODC', [
                'odc_id' => $odc->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal menghapus ODC: '.$e->getMessage());
        }
    }
}
