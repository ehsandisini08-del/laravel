<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePackageRequest;
use App\Http\Requests\UpdatePackageRequest;
use App\Models\Area;
use App\Models\Package;
use App\Models\PppProfile;
use App\Models\Router;
use App\Services\ActivityLoggerService;
use App\Services\PackageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PackageController extends Controller
{
    public function __construct(
        protected PackageService $packageService,
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'router_id', 'area_id', 'status']);

        if (auth()->user()->isAdminArea()) {
            $filters['area_ids'] = auth()->user()->areaIds();
        }

        $packages = $this->packageService->getAll($filters);
        $routers = Router::enabled()->get();
        $areas = Area::active()->orderBy('name')->get(['id', 'code', 'name']);

        return view('packages.index', compact('packages', 'routers', 'areas'));
    }

    public function create()
    {
        $this->denyAdminArea();

        $routers = Router::enabled()->get();
        $areas = Area::active()->orderBy('name')->get(['id', 'code', 'name']);

        return view('packages.create', compact('routers', 'areas'));
    }

    public function store(StorePackageRequest $request)
    {
        $this->denyAdminArea();

        try {
            $package = $this->packageService->create($request->validated());

            $this->activityLogger->created('Package', "Package #{$package->id} created", $package);

            return redirect()->route('packages.index')
                ->with('success', 'Package created successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to create package', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withInput()->with('error', 'Failed to create Package: '.$e->getMessage());
        }
    }

    public function show(Package $package)
    {
        $this->authorizePackage($package);

        $package->load(['router', 'pppProfile', 'areas']);

        return view('packages.show', compact('package'));
    }

    public function edit(Package $package)
    {
        $this->denyAdminArea();

        $package->load(['router', 'pppProfile', 'areas']);
        $routers = Router::enabled()->get();
        $areas = Area::active()->orderBy('name')->get(['id', 'code', 'name']);
        $profiles = PppProfile::forRouter($package->router_id)
            ->synced()
            ->get(['id', 'name']);

        return view('packages.edit', compact('package', 'routers', 'areas', 'profiles'));
    }

    public function update(UpdatePackageRequest $request, Package $package)
    {
        $this->denyAdminArea();

        try {
            $this->packageService->update($package, $request->validated());

            $this->activityLogger->updated('Package', "Package #{$package->id} updated", $package);

            return redirect()->route('packages.index')
                ->with('success', 'Package updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update package', [
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Failed to update Package: '.$e->getMessage());
        }
    }

    public function destroy(Package $package)
    {
        $this->denyAdminArea();

        try {
            $this->packageService->delete($package);

            $this->activityLogger->deleted('Package', "Package #{$package->id} deleted", $package);

            return redirect()->route('packages.index')
                ->with('success', 'Package deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete package', [
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete Package: '.$e->getMessage());
        }
    }

    protected function denyAdminArea(): void
    {
        if (auth()->user()->isAdminArea()) {
            abort(403, 'Akses ditolak.');
        }
    }

    protected function authorizePackage(Package $package): void
    {
        if (auth()->user()->isAdminArea()) {
            $inArea = $package->areas()
                ->whereIn('areas.id', auth()->user()->areaIds())
                ->exists();

            abort_unless($inArea, 403, 'Akses ditolak.');
        }
    }

    public function profilesByRouter(Router $router)
    {
        $profiles = PppProfile::forRouter($router->id)
            ->synced()
            ->get(['id', 'name']);

        return response()->json($profiles);
    }
}
