<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Services\ActivityLoggerService;
use App\Services\Mikrotik\PPPActiveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PppActiveController extends Controller
{
    public function __construct(
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function index(Request $request)
    {
        $routerId = $request->input('router_id', session('selected_active_router_id'));

        if ($routerId) {
            session(['selected_active_router_id' => $routerId]);
        }

        $routers = Router::enabled()->get();
        $selectedRouter = $routerId ? Router::find($routerId) : null;

        $connections = [];
        $statistics = null;

        if ($selectedRouter) {
            try {
                $service = new PPPActiveService($selectedRouter);

                $connections = $service->getActiveConnections();

                $statistics = [
                    'total_active' => count($connections),
                ];
            } catch (\Exception $e) {
                Log::error('Failed to load active connections', [
                    'router_id' => $routerId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Get global stats
        $onlineRouters = Router::enabled()->online()->count();
        $offlineRouters = Router::enabled()->offline()->count();
        $totalRouters = Router::enabled()->count();

        return view('ppp-active.index', compact(
            'connections',
            'routers',
            'selectedRouter',
            'statistics',
            'onlineRouters',
            'offlineRouters',
            'totalRouters'
        ));
    }

    public function fetch(Request $request)
    {
        $routerId = $request->input('router_id');

        if (! $routerId) {
            return response()->json(['success' => false, 'message' => 'Router ID is required.'], 400);
        }

        try {
            $router = Router::findOrFail($routerId);
            $service = new PPPActiveService($router);
            $connections = $service->getActiveConnections();

            $service = new PPPActiveService($router);

            // Apply filters on server side
            $search = $request->input('search');
            $filterService = $request->input('service');

            $filtered = collect($connections);

            if ($search) {
                $filtered = $filtered->filter(fn ($c) => str_contains(strtolower($c['name'] ?? ''), strtolower($search)));
            }

            if ($filterService) {
                $filtered = $filtered->where('service', $filterService);
            }

            $services = collect($connections)->pluck('service')->unique()->filter()->values();

            return response()->json([
                'success' => true,
                'connections' => $filtered->values()->toArray(),
                'statistics' => [
                    'total_active' => count($filtered),
                ],
                'filters' => [
                    'services' => $services,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch active connections', [
                'router_id' => $routerId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active connections: '.$e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, string $userId)
    {
        $routerId = $request->input('router_id', session('selected_active_router_id'));

        if (! $routerId) {
            return redirect()->route('ppp-active.index')->with('error', 'Please select a router.');
        }

        try {
            $router = Router::findOrFail($routerId);
            $service = new PPPActiveService($router);

            $connections = $service->getActiveConnections();
            $connection = collect($connections)->firstWhere('id', $userId);

            if (! $connection) {
                return redirect()->route('ppp-active.index', ['router_id' => $routerId])
                    ->with('error', 'Active connection not found. User may already be disconnected.');
            }

            return view('ppp-active.show', compact('connection', 'router'));
        } catch (\Exception $e) {
            Log::error('Failed to show active connection detail', [
                'router_id' => $routerId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('ppp-active.index', ['router_id' => $routerId])
                ->with('error', 'Failed to load connection details: '.$e->getMessage());
        }
    }

    public function disconnect(Request $request)
    {
        $routerId = $request->input('router_id');
        $userId = $request->input('user_id');

        if (! $routerId || ! $userId) {
            return response()->json(['success' => false, 'message' => 'Router ID and User ID are required.'], 400);
        }

        try {
            $router = Router::findOrFail($routerId);
            $service = new PPPActiveService($router);
            $result = $service->disconnectUser($userId);

            Log::info('PPP active user disconnected via controller', [
                'router_id' => $routerId,
                'user_id' => $userId,
                'user_id' => auth()->id(),
            ]);

            $this->activityLogger->updated('PPP Active', "PPP active user {$userId} disconnected", null, [], $router);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to disconnect active user', [
                'router_id' => $routerId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect user: '.$e->getMessage(),
            ], 500);
        }
    }

    public function bulkDisconnect(Request $request)
    {
        $routerId = $request->input('router_id');
        $userIds = $request->input('user_ids', []);

        if (! $routerId || empty($userIds)) {
            return response()->json(['success' => false, 'message' => 'Router ID and User IDs are required.'], 400);
        }

        try {
            $router = Router::findOrFail($routerId);
            $service = new PPPActiveService($router);
            $result = $service->bulkDisconnect($userIds);

            Log::info('PPP bulk disconnect completed', [
                'router_id' => $routerId,
                'total' => count($userIds),
                'success_count' => $result['success_count'],
                'failed_count' => $result['failed_count'],
                'user_id' => auth()->id(),
            ]);

            $this->activityLogger->updated('PPP Active', "Bulk disconnected {$result['success_count']} user(s)", null, [], $router);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to bulk disconnect', [
                'router_id' => $routerId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect: '.$e->getMessage(),
            ], 500);
        }
    }
}
