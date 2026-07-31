<?php

namespace App\Http\Controllers;

use App\Models\Router;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index()
    {
        $recentLogs = Activity::with('causer')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $totalRouters = Router::count();
        $onlineRouters = Router::where('status', 'online')->count();
        $offlineRouters = Router::where('status', 'offline')->count();

        return view('dashboard', compact('recentLogs', 'totalRouters', 'onlineRouters', 'offlineRouters'));
    }
}
