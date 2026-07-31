<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePppProfileRequest;
use App\Http\Requests\UpdatePppProfileRequest;
use App\Models\PppProfile;
use App\Models\Router;
use App\Services\ActivityLoggerService;
use App\Services\Mikrotik\PPPProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PppProfileController extends Controller
{
    public function __construct(
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function index(Request $request)
    {
        $routerId = $request->input('router_id', session('selected_ppp_profile_router_id'));

        if ($routerId) {
            session(['selected_ppp_profile_router_id' => $routerId]);
        }

        $query = PppProfile::with('router');

        if ($routerId) {
            $query->forRouter($routerId);
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('rate_limit', 'like', "%{$search}%")
                    ->orWhere('comment', 'like', "%{$search}%");
            });
        }

        $profiles = $query->latest()->paginate(15)->withQueryString();
        $routers = Router::enabled()->get();
        $selectedRouter = $routerId ? Router::find($routerId) : null;

        return view('ppp-profiles.index', compact('profiles', 'routers', 'selectedRouter'));
    }

    public function create(Request $request)
    {
        $routerId = $request->input('router_id', session('selected_ppp_profile_router_id'));
        $routers = Router::enabled()->get();
        $selectedRouter = $routerId ? Router::find($routerId) : null;

        return view('ppp-profiles.create', compact('routers', 'selectedRouter'));
    }

    public function store(StorePppProfileRequest $request)
    {
        try {
            $router = Router::findOrFail($request->router_id);
            $service = new PPPProfileService($router);

            $result = $service->createProfile($request->validated());

            if (! $result['success']) {
                return back()->withInput()->with('error', $result['message']);
            }

            $service->syncProfiles();

            Log::info('PPP Profile created', [
                'router_id' => $router->id,
                'profile_name' => $request->name,
                'user_id' => auth()->id(),
            ]);

            $this->activityLogger->created('PPP Profile', "PPP Profile '{$request->name}' created", null, [], $router);

            return redirect()->route('ppp-profiles.index', ['router_id' => $router->id])
                ->with('success', 'PPP Profile created successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to create PPP profile', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withInput()->with('error', 'Failed to create PPP Profile: '.$e->getMessage());
        }
    }

    public function show(PppProfile $pppProfile)
    {
        $pppProfile->load('router');
        $secretsCount = $pppProfile->router->pppSecrets()
            ->where('profile', $pppProfile->name)
            ->count();

        return view('ppp-profiles.show', compact('pppProfile', 'secretsCount'));
    }

    public function edit(PppProfile $pppProfile)
    {
        $routers = Router::enabled()->get();

        return view('ppp-profiles.edit', compact('pppProfile', 'routers'));
    }

    public function update(UpdatePppProfileRequest $request, PppProfile $pppProfile)
    {
        try {
            $service = new PPPProfileService($pppProfile->router);

            $data = $request->validated();

            $result = $service->updateProfile($pppProfile->mikrotik_id, $data);

            if (! $result['success']) {
                return back()->withInput()->with('error', $result['message']);
            }

            $updateData = array_filter($data, fn ($value) => $value !== null && $value !== '');
            if (! empty($updateData)) {
                $pppProfile->update($updateData);
            }

            Log::info('PPP Profile updated', [
                'profile_id' => $pppProfile->id,
                'user_id' => auth()->id(),
            ]);

            $this->activityLogger->updated('PPP Profile', "PPP Profile #{$pppProfile->id} updated", $pppProfile, [], $pppProfile->router);

            return redirect()->route('ppp-profiles.index', ['router_id' => $pppProfile->router_id])
                ->with('success', 'PPP Profile updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update PPP profile', [
                'profile_id' => $pppProfile->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Failed to update PPP Profile: '.$e->getMessage());
        }
    }

    public function destroy(PppProfile $pppProfile)
    {
        try {
            $service = new PPPProfileService($pppProfile->router);
            $result = $service->deleteProfile($pppProfile->mikrotik_id);

            if (! $result['success']) {
                return back()->with('error', $result['message']);
            }

            $routerId = $pppProfile->router_id;
            $pppProfile->delete();

            Log::info('PPP Profile deleted', [
                'profile_id' => $pppProfile->id,
                'user_id' => auth()->id(),
            ]);

            $this->activityLogger->deleted('PPP Profile', "PPP Profile #{$pppProfile->id} deleted", $pppProfile, [], $pppProfile->router);

            return redirect()->route('ppp-profiles.index', ['router_id' => $routerId])
                ->with('success', 'PPP Profile deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete PPP profile', [
                'profile_id' => $pppProfile->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete PPP Profile: '.$e->getMessage());
        }
    }

    public function sync(Request $request)
    {
        $routerId = $request->input('router_id');

        if (! $routerId) {
            Log::warning('Sync attempt without router_id', ['user_id' => auth()->id()]);

            return response()->json(['success' => false, 'message' => 'Router ID is required.'], 400);
        }

        try {
            $router = Router::findOrFail($routerId);

            Log::info('Starting PPP Profile sync from controller', [
                'router_id' => $router->id,
                'user_id' => auth()->id(),
            ]);

            $service = new PPPProfileService($router);
            $count = $service->syncProfiles();

            Log::info('PPP Profiles sync completed from controller', [
                'router_id' => $routerId,
                'count' => $count,
                'user_id' => auth()->id(),
            ]);

            $this->activityLogger->synced('PPP Profile', "PPP Profiles synced for router '{$router->name}'", $router);

            return response()->json([
                'success' => true,
                'message' => "{$count} PPP Profile(s) synchronized successfully.",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync PPP profiles from controller', [
                'router_id' => $routerId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to sync PPP Profiles: '.$e->getMessage()], 500);
        }
    }
}
