<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePppSecretRequest;
use App\Http\Requests\UpdatePppSecretRequest;
use App\Models\PppProfile;
use App\Models\PppSecret;
use App\Models\Router;
use App\Services\ActivityLoggerService;
use App\Services\Mikrotik\PPPSecretService;
use App\Support\SettingSupport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PppSecretController extends Controller
{
    public function __construct(
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function index(Request $request)
    {
        $routerId = $request->input('router_id', session('selected_router_id'));

        if ($routerId) {
            session(['selected_router_id' => $routerId]);
        }

        $query = PppSecret::with('router');

        if ($routerId) {
            $query->forRouter($routerId);
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('profile', 'like', "%{$search}%")
                    ->orWhere('remote_address', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('disabled', $request->input('status') === 'disabled');
        }

        if ($request->has('profile') && $request->input('profile') !== '') {
            $query->where('profile', $request->input('profile'));
        }

        $pppSecrets = $query->latest()->paginate(SettingSupport::perPage())->withQueryString();
        $routers = Router::enabled()->get();
        $selectedRouter = $routerId ? Router::find($routerId) : null;

        return view('ppp-secrets.index', compact('pppSecrets', 'routers', 'selectedRouter'));
    }

    public function create(Request $request)
    {
        $routerId = $request->input('router_id', session('selected_router_id'));
        $routers = Router::enabled()->get();
        $selectedRouter = $routerId ? Router::find($routerId) : null;

        $profiles = [];
        if ($selectedRouter) {
            $profiles = PppProfile::forRouter($selectedRouter->id)
                ->synced()
                ->pluck('name')
                ->toArray();
        }

        return view('ppp-secrets.create', compact('routers', 'selectedRouter', 'profiles'));
    }

    public function store(StorePppSecretRequest $request)
    {
        try {
            $router = Router::findOrFail($request->router_id);
            $service = new PPPSecretService($router);

            $result = $service->createSecret($request->validated());

            if (! $result['success']) {
                return back()->withInput()->with('error', $result['message']);
            }

            $service->syncSecrets();

            Log::info('PPP Secret created', [
                'router_id' => $router->id,
                'secret_name' => $request->name,
                'user_id' => auth()->id(),
            ]);

            $this->activityLogger->created('PPP Secret', "PPP Secret '{$request->name}' created", null, [], $router);

            return redirect()->route('ppp-secrets.index', ['router_id' => $router->id])
                ->with('success', 'PPP Secret created successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to create PPP secret', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withInput()->with('error', 'Failed to create PPP Secret: '.$e->getMessage());
        }
    }

    public function edit(PppSecret $pppSecret)
    {
        $routers = Router::enabled()->get();
        $profiles = [];

        if ($pppSecret->router) {
            $profiles = PppProfile::forRouter($pppSecret->router_id)
                ->synced()
                ->pluck('name')
                ->toArray();
        }

        return view('ppp-secrets.edit', compact('pppSecret', 'routers', 'profiles'));
    }

    public function update(UpdatePppSecretRequest $request, PppSecret $pppSecret)
    {
        try {
            $service = new PPPSecretService($pppSecret->router);

            $data = $request->validated();

            $result = $service->updateSecret($pppSecret->mikrotik_id, $data);

            if (! $result['success']) {
                return back()->withInput()->with('error', $result['message']);
            }

            $updateData = array_filter($data, fn ($value) => $value !== null && $value !== '');

            if (isset($data['password']) && $data['password'] !== '') {
                $updateData['password'] = $data['password'];
            }

            if (! empty($updateData)) {
                $pppSecret->update($updateData);
            }

            Log::info('PPP Secret updated', [
                'secret_id' => $pppSecret->id,
                'router_id' => $pppSecret->router_id,
                'user_id' => auth()->id(),
            ]);

            $this->activityLogger->updated('PPP Secret', "PPP Secret #{$pppSecret->id} updated", $pppSecret, [], $pppSecret->router);

            return redirect()->route('ppp-secrets.index', ['router_id' => $pppSecret->router_id])
                ->with('success', 'PPP Secret updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update PPP secret', [
                'secret_id' => $pppSecret->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with('error', 'Failed to update PPP Secret: '.$e->getMessage());
        }
    }

    public function destroy(PppSecret $pppSecret)
    {
        try {
            $service = new PPPSecretService($pppSecret->router);
            $result = $service->deleteSecret($pppSecret->mikrotik_id);

            if (! $result['success']) {
                return back()->with('error', $result['message']);
            }

            $routerId = $pppSecret->router_id;
            $pppSecret->delete();

            Log::info('PPP Secret deleted', [
                'secret_id' => $pppSecret->id,
                'user_id' => auth()->id(),
            ]);

            $this->activityLogger->deleted('PPP Secret', "PPP Secret #{$pppSecret->id} deleted", $pppSecret, [], $pppSecret->router);

            return redirect()->route('ppp-secrets.index', ['router_id' => $routerId])
                ->with('success', 'PPP Secret deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete PPP secret', [
                'secret_id' => $pppSecret->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete PPP Secret: '.$e->getMessage());
        }
    }

    public function enable(PppSecret $pppSecret)
    {
        try {
            $service = new PPPSecretService($pppSecret->router);
            $result = $service->enableSecret($pppSecret->mikrotik_id);

            if (! $result['success']) {
                return response()->json(['success' => false, 'message' => $result['message']], 500);
            }

            $pppSecret->update(['disabled' => false]);

            Log::info('PPP Secret enabled', [
                'secret_id' => $pppSecret->id,
                'user_id' => auth()->id(),
            ]);

            $this->activityLogger->updated('PPP Secret', "PPP Secret #{$pppSecret->id} enabled", $pppSecret, [], $pppSecret->router);

            return response()->json(['success' => true, 'message' => 'PPP Secret enabled successfully.']);
        } catch (\Exception $e) {
            Log::error('Failed to enable PPP secret', [
                'secret_id' => $pppSecret->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to enable PPP Secret.'], 500);
        }
    }

    public function disable(PppSecret $pppSecret)
    {
        try {
            $service = new PPPSecretService($pppSecret->router);
            $result = $service->disableSecret($pppSecret->mikrotik_id);

            if (! $result['success']) {
                return response()->json(['success' => false, 'message' => $result['message']], 500);
            }

            $pppSecret->update(['disabled' => true]);

            Log::info('PPP Secret disabled', [
                'secret_id' => $pppSecret->id,
                'user_id' => auth()->id(),
            ]);

            $this->activityLogger->updated('PPP Secret', "PPP Secret #{$pppSecret->id} disabled", $pppSecret, [], $pppSecret->router);

            return response()->json(['success' => true, 'message' => 'PPP Secret disabled successfully.']);
        } catch (\Exception $e) {
            Log::error('Failed to disable PPP secret', [
                'secret_id' => $pppSecret->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to disable PPP Secret.'], 500);
        }
    }

    public function sync(Request $request)
    {
        $routerId = $request->input('router_id');

        if (! $routerId) {
            Log::warning('Sync attempt without router_id', [
                'user_id' => auth()->id(),
            ]);

            return response()->json(['success' => false, 'message' => 'Router ID is required.'], 400);
        }

        try {
            $router = Router::findOrFail($routerId);

            Log::info('Starting PPP Secret sync from controller', [
                'router_id' => $router->id,
                'router_name' => $router->name,
                'host' => $router->host,
                'identity' => $router->identity,
                'user_id' => auth()->id(),
            ]);

            $service = new PPPSecretService($router);
            $count = $service->syncSecrets();

            Log::info('PPP Secrets sync completed from controller', [
                'router_id' => $routerId,
                'router_name' => $router->name,
                'count' => $count,
                'user_id' => auth()->id(),
            ]);

            $this->activityLogger->synced('PPP Secret', "PPP Secrets synced for router '{$router->name}'", $router);

            return response()->json([
                'success' => true,
                'message' => "{$count} PPP Secret(s) synchronized successfully.",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync PPP secrets from controller', [
                'router_id' => $routerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to sync PPP Secrets: '.$e->getMessage()], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:ppp_secrets,id']);

        try {
            $secrets = PppSecret::whereIn('id', $request->ids)->get();
            $deleted = 0;

            foreach ($secrets as $secret) {
                $service = new PPPSecretService($secret->router);
                $result = $service->deleteSecret($secret->mikrotik_id);

                if ($result['success']) {
                    $secret->delete();
                    $deleted++;
                }
            }

            $this->activityLogger->deleted('PPP Secret', "Bulk deleted {$deleted} PPP Secret(s)");

            return response()->json(['success' => true, 'message' => "{$deleted} PPP Secret(s) deleted successfully."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete PPP Secrets.'], 500);
        }
    }

    public function bulkEnable(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:ppp_secrets,id']);

        try {
            $secrets = PppSecret::whereIn('id', $request->ids)->get();
            $enabled = 0;

            foreach ($secrets as $secret) {
                $service = new PPPSecretService($secret->router);
                $result = $service->enableSecret($secret->mikrotik_id);

                if ($result['success']) {
                    $secret->update(['disabled' => false]);
                    $enabled++;
                }
            }

            $this->activityLogger->updated('PPP Secret', "Bulk enabled {$enabled} PPP Secret(s)");

            return response()->json(['success' => true, 'message' => "{$enabled} PPP Secret(s) enabled successfully."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to enable PPP Secrets.'], 500);
        }
    }

    public function bulkDisable(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:ppp_secrets,id']);

        try {
            $secrets = PppSecret::whereIn('id', $request->ids)->get();
            $disabled = 0;

            foreach ($secrets as $secret) {
                $service = new PPPSecretService($secret->router);
                $result = $service->disableSecret($secret->mikrotik_id);

                if ($result['success']) {
                    $secret->update(['disabled' => true]);
                    $disabled++;
                }
            }

            $this->activityLogger->updated('PPP Secret', "Bulk disabled {$disabled} PPP Secret(s)");

            return response()->json(['success' => true, 'message' => "{$disabled} PPP Secret(s) disabled successfully."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to disable PPP Secrets.'], 500);
        }
    }
}
