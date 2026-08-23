<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAreaRequest;
use App\Http\Requests\UpdateAreaRequest;
use App\Models\Area;
use App\Services\ActivityLoggerService;
use App\Services\AreaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AreaController extends Controller
{
    public function __construct(
        protected AreaService $areaService,
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function index(Request $request)
    {
        $this->denyTeknisi();

        $filters = $request->only(['search', 'status']);

        if (auth()->user()->isAdminArea()) {
            $filters['area_ids'] = auth()->user()->areaIds();
        }

        $areas = $this->areaService->getAll($filters);

        return view('areas.index', compact('areas'));
    }

    public function create()
    {
        $this->denyAdminArea();

        return view('areas.create');
    }

    public function store(StoreAreaRequest $request)
    {
        $this->denyAdminArea();

        try {
            $this->areaService->create($request->validated());

            $this->activityLogger->created('Area', 'Area created');

            return redirect()->route('areas.index')
                ->with('success', 'Area created successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to create area', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withInput()->with('error', 'Failed to create Area: '.$e->getMessage());
        }
    }

    public function show(Area $area)
    {
        $this->authorizeArea($area);

        return view('areas.show', compact('area'));
    }

    public function edit(Area $area)
    {
        $this->denyAdminArea();

        return view('areas.edit', compact('area'));
    }

    public function update(UpdateAreaRequest $request, Area $area)
    {
        $this->denyAdminArea();

        try {
            $this->areaService->update($area, $request->validated());

            $this->activityLogger->updated('Area', "Area #{$area->id} updated", $area);

            return redirect()->route('areas.index')
                ->with('success', 'Area updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update area', [
                'area_id' => $area->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Failed to update Area: '.$e->getMessage());
        }
    }

    public function destroy(Area $area)
    {
        $this->denyAdminArea();

        try {
            $this->areaService->delete($area);

            $this->activityLogger->deleted('Area', "Area #{$area->id} deleted", $area);

            return redirect()->route('areas.index')
                ->with('success', 'Area deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete area', [
                'area_id' => $area->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete Area: '.$e->getMessage());
        }
    }

    protected function denyAdminArea(): void
    {
        if (auth()->user()->isAdminArea() || auth()->user()->isTeknisi()) {
            abort(403, 'Akses ditolak.');
        }
    }

    protected function denyTeknisi(): void
    {
        if (auth()->user()->isTeknisi()) {
            abort(403, 'Akses ditolak.');
        }
    }

    protected function authorizeArea(Area $area): void
    {
        $this->denyTeknisi();

        if (auth()->user()->isAdminArea() && ! in_array($area->id, auth()->user()->areaIds(), true)) {
            abort(403, 'Akses ditolak.');
        }
    }
}
