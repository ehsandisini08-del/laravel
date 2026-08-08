<?php

namespace App\Http\Controllers;

use App\Models\JobLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class JobMonitorController extends Controller
{
    public function index()
    {
        return view('monitoring.index', [
            'recentJobs' => $this->recentJobs(),
            'recentSchedules' => $this->recentSchedules(),
            'stats' => $this->stats(),
        ]);
    }

    public function status()
    {
        return response()->json([
            'recent_jobs' => $this->recentJobs()->map(fn ($log) => $this->summaryRow($log)),
            'recent_schedules' => $this->recentSchedules()->map(fn ($log) => $this->summaryRow($log)),
            'stats' => $this->stats(),
        ]);
    }

    protected function recentJobs()
    {
        return JobLog::jobs()->latest()->limit(50)->get();
    }

    protected function recentSchedules()
    {
        return JobLog::schedules()->latest()->limit(30)->get();
    }

    protected function stats(): array
    {
        $pending = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
        $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;

        return [
            'today_success' => JobLog::jobs()->whereDate('created_at', today())->successful()->count(),
            'today_failed' => JobLog::jobs()->whereDate('created_at', today())->failed()->count(),
            'pending_jobs' => $pending,
            'failed_jobs' => $failed,
            'avg_duration_ms' => (int) JobLog::jobs()->whereNotNull('duration_ms')->avg('duration_ms'),
        ];
    }

    protected function summaryRow(JobLog $log): array
    {
        return [
            'id' => $log->id,
            'class' => $log->class,
            'queue' => $log->queue,
            'status' => $log->status,
            'status_label' => $log->statusLabel(),
            'status_color' => $log->statusColor(),
            'tries' => $log->tries,
            'created_at' => $log->created_at?->format('d M H:i:s'),
            'duration_ms' => $log->duration_ms,
            'started_at' => $log->started_at?->format('d M H:i:s'),
            'finished_at' => $log->finished_at?->format('d M H:i:s'),
            'exception' => $log->exception_message,
        ];
    }
}
