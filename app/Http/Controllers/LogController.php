<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Models\User;
use App\Services\ActivityLoggerService;
use App\Support\SettingSupport;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class LogController extends Controller
{
    public function __construct(
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function index(Request $request)
    {
        $query = Activity::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('causer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->input('user_id'))
                ->where('causer_type', 'App\Models\User');
        }

        if ($request->filled('module')) {
            $query->where('properties->module', $request->input('module'));
        }

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('router_id')) {
            $query->where('properties->router_id', $request->input('router_id'));
        }

        $sortField = $request->input('sort_field', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $query->orderBy($sortField, $sortOrder === 'asc' ? 'asc' : 'desc');

        $logs = $query->with('causer')->paginate(SettingSupport::perPage())->withQueryString();

        $modules = Activity::query()
            ->selectRaw("DISTINCT json_extract(properties, '$.module') as module")
            ->whereNotNull('properties->module')
            ->pluck('module')
            ->filter()
            ->values();

        $users = User::orderBy('name')->get(['id', 'name']);
        $routers = Router::orderBy('name')->get(['id', 'name']);

        $totalLogs = Activity::count();
        $loginToday = Activity::where('event', 'Login Success')
            ->whereDate('created_at', today())
            ->count();
        $errorToday = Activity::where(function ($q) {
            $q->where('event', 'Connection Failed')
                ->orWhere('description', 'like', '%Failed%');
        })->whereDate('created_at', today())->count();
        $routerActivity = Activity::where('properties->module', 'Router')
            ->whereDate('created_at', today())
            ->count();

        return view('logs.index', compact(
            'logs', 'modules', 'users', 'routers',
            'totalLogs', 'loginToday', 'errorToday', 'routerActivity',
        ));
    }

    public function show(Activity $log)
    {
        $log->load('causer', 'subject');

        return view('logs.show', compact('log'));
    }

    public function exportCsv(Request $request)
    {
        $logs = $this->buildExportQuery($request)->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="logs-'.now()->format('Y-m-d-His').'.csv"',
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Time', 'User', 'Module', 'Action', 'Description', 'IP Address', 'User Agent', 'URL']);

            foreach ($logs as $log) {
                $props = $log->properties ?? [];
                fputcsv($file, [
                    $log->created_at->toDateTimeString(),
                    $log->causer?->name ?? 'System',
                    $props['module'] ?? '-',
                    $log->event ?? '-',
                    $log->description,
                    $props['ip_address'] ?? '-',
                    $props['user_agent'] ?? '-',
                    $props['url'] ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportExcel(Request $request)
    {
        $logs = $this->buildExportQuery($request)->get();

        $rows = [['Time', 'User', 'Module', 'Action', 'Description', 'IP Address', 'User Agent', 'URL']];

        foreach ($logs as $log) {
            $props = $log->properties ?? [];
            $rows[] = [
                $log->created_at->toDateTimeString(),
                $log->causer?->name ?? 'System',
                $props['module'] ?? '-',
                $log->event ?? '-',
                $log->description,
                $props['ip_address'] ?? '-',
                $props['user_agent'] ?? '-',
                $props['url'] ?? '-',
            ];
        }

        $csv = implode("\r\n", array_map(fn ($row) => implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', $v).'"', $row)), $rows));

        return response($csv, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="logs-'.now()->format('Y-m-d-His').'.xls"',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $logs = $this->buildExportQuery($request)->get();

        $html = view('logs.export-pdf', compact('logs'))->render();

        return response($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'attachment; filename="logs-'.now()->format('Y-m-d-His').'.html"',
        ]);
    }

    public function clear()
    {
        Activity::truncate();

        $this->activityLogger->log('System', 'Cleared', 'All activity logs were cleared.');

        return redirect()->route('logs.index')
            ->with('success', 'All logs cleared successfully.');
    }

    private function buildExportQuery(Request $request)
    {
        $query = Activity::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('causer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->input('user_id'))
                ->where('causer_type', 'App\Models\User');
        }

        if ($request->filled('module')) {
            $query->where('properties->module', $request->input('module'));
        }

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        return $query->orderBy('created_at', 'desc');
    }
}
