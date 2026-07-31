<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRouterRequest;
use App\Http\Requests\UpdateRouterRequest;
use App\Models\Router;
use App\Services\ActivityLoggerService;
use App\Services\Mikrotik\MikrotikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RouterController extends Controller
{
    public function __construct(
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function index(Request $request)
    {
        $query = Router::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('host', 'like', "%{$search}%")
                    ->orWhere('identity', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('enabled') && $request->input('enabled') !== '') {
            $query->where('enabled', $request->boolean('enabled'));
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $routers = $query->paginate(10)->withQueryString();

        return view('routers.index', compact('routers'));
    }

    public function create()
    {
        return view('routers.create');
    }

    public function store(StoreRouterRequest $request)
    {
        try {
            $router = Router::create($request->validated());

            try {
                $service = new MikrotikService($router);
                $service->syncRouterInformation();
            } catch (\Exception $e) {
                Log::warning('Failed to sync new router information', [
                    'router_id' => $router->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('Router created', [
                'router_id' => $router->id,
                'router_name' => $router->name,
                'user_id' => auth()->id(),
            ]);

            $this->activityLogger->created('Router', "Router '{$router->name}' created", $router, [], $router);

            return redirect()->route('routers.index')
                ->with('success', 'Router created successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to create router', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withInput()
                ->with('error', 'Failed to create router: '.$e->getMessage());
        }
    }

    public function show(Router $router)
    {
        return view('routers.show', compact('router'));
    }

    public function edit(Router $router)
    {
        return view('routers.edit', compact('router'));
    }

    public function update(UpdateRouterRequest $request, Router $router)
    {
        try {
            $data = $request->validated();

            if (empty($data['password'])) {
                unset($data['password']);
            }

            $router->update($data);

            try {
                $service = new MikrotikService($router);
                $service->syncRouterInformation();
            } catch (\Exception $e) {
                Log::warning('Failed to sync updated router information', [
                    'router_id' => $router->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('Router updated', [
                'router_id' => $router->id,
                'router_name' => $router->name,
                'user_id' => auth()->id(),
            ]);

            $this->activityLogger->updated('Router', "Router '{$router->name}' updated", $router, [], $router);

            return redirect()->route('routers.index')
                ->with('success', 'Router updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update router', [
                'router_id' => $router->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withInput()
                ->with('error', 'Failed to update router: '.$e->getMessage());
        }
    }

    public function destroy(Router $router)
    {
        try {
            $routerName = $router->name;

            $this->activityLogger->deleted('Router', "Router '{$routerName}' deleted", $router);

            $router->delete();

            Log::info('Router deleted', [
                'router_id' => $router->id,
                'router_name' => $routerName,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('routers.index')
                ->with('success', 'Router deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete router', [
                'router_id' => $router->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->with('error', 'Failed to delete router: '.$e->getMessage());
        }
    }

    public function testConnection(Router $router)
    {
        try {
            $router->markAsChecking();

            $service = new MikrotikService($router);
            $result = $service->testConnection();

            if ($result['success']) {
                $router->update([
                    'identity' => $result['data']['identity'] ?? $router->identity,
                    'routeros_version' => $result['data']['version'] ?? $router->routeros_version,
                    'board_name' => $result['data']['board_name'] ?? $router->board_name,
                    'architecture' => $result['data']['architecture'] ?? $router->architecture,
                    'cpu' => $result['data']['cpu'] ?? $router->cpu,
                    'total_memory' => $result['data']['total_memory'] ?? $router->total_memory,
                    'free_memory' => $result['data']['free_memory'] ?? $router->free_memory,
                    'uptime' => $result['data']['uptime'] ?? $router->uptime,
                    'status' => 'online',
                    'last_seen_at' => now(),
                ]);

                Log::info('Router connection test successful', [
                    'router_id' => $router->id,
                    'router_name' => $router->name,
                    'identity' => $result['data']['identity'] ?? null,
                    'version' => $result['data']['version'] ?? null,
                    'user_id' => auth()->id(),
                ]);

                $this->activityLogger->connected("Router '{$router->name}' connection test successful", $router);
            } else {
                $router->markAsOffline();

                Log::warning('Router connection test failed', [
                    'router_id' => $router->id,
                    'router_name' => $router->name,
                    'message' => $result['message'],
                    'user_id' => auth()->id(),
                ]);

                $this->activityLogger->connectionFailed("Router '{$router->name}' connection test failed", $router);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            $router->markAsOffline();

            Log::error('Router connection test error', [
                'router_id' => $router->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            $this->activityLogger->connectionFailed("Router '{$router->name}' connection test error", $router);

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function sync(Router $router)
    {
        try {
            $service = new MikrotikService($router);
            $success = $service->syncRouterInformation();

            if ($success) {
                $this->activityLogger->synced('Router', "Router '{$router->name}' synchronized", $router);

                return response()->json([
                    'success' => true,
                    'message' => 'Router information synchronized successfully.',
                    'router' => $router->fresh(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to synchronize router information.',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Router sync error', [
                'router_id' => $router->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sync failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:routers,id',
        ]);

        try {
            $count = Router::whereIn('id', $request->ids)->delete();

            Log::info('Bulk delete routers', [
                'count' => $count,
                'user_id' => auth()->id(),
            ]);

            $this->activityLogger->deleted('Router', "Bulk deleted {$count} router(s)");

            return response()->json([
                'success' => true,
                'message' => "{$count} router(s) deleted successfully.",
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk delete routers failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete routers.',
            ], 500);
        }
    }

    public function bulkEnable(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:routers,id',
        ]);

        try {
            $count = Router::whereIn('id', $request->ids)->update(['enabled' => true]);

            Log::info('Bulk enable routers', [
                'count' => $count,
                'user_id' => auth()->id(),
            ]);

            $this->activityLogger->updated('Router', "Bulk enabled {$count} router(s)");

            return response()->json([
                'success' => true,
                'message' => "{$count} router(s) enabled successfully.",
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk enable routers failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to enable routers.',
            ], 500);
        }
    }

    public function bulkDisable(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:routers,id',
        ]);

        try {
            $count = Router::whereIn('id', $request->ids)->update(['enabled' => false]);

            Log::info('Bulk disable routers', [
                'count' => $count,
                'user_id' => auth()->id(),
            ]);

            $this->activityLogger->updated('Router', "Bulk disabled {$count} router(s)");

            return response()->json([
                'success' => true,
                'message' => "{$count} router(s) disabled successfully.",
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk disable routers failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to disable routers.',
            ], 500);
        }
    }
}
