<?php

namespace App\Services;

use App\Models\JobLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class JobLogService
{
    public function jobQueued(?string $jobId, string $class, ?string $queue): void
    {
        $this->safe(function () use ($jobId, $class, $queue) {
            JobLog::create([
                'type' => JobLog::TYPE_JOB,
                'job_id' => $jobId,
                'class' => $class,
                'queue' => $queue,
                'status' => JobLog::STATUS_QUEUED,
            ]);
        }, 'queued', $class);
    }

    public function jobProcessing(?string $jobId, string $class, int $tries): void
    {
        $this->safe(function () use ($jobId, $class, $tries) {
            $row = $this->findLatestJobRow($jobId);

            if ($row) {
                $row->update([
                    'status' => JobLog::STATUS_PROCESSING,
                    'tries' => $tries,
                    'started_at' => now(),
                ]);
            } else {
                JobLog::create([
                    'type' => JobLog::TYPE_JOB,
                    'job_id' => $jobId,
                    'class' => $class,
                    'status' => JobLog::STATUS_PROCESSING,
                    'tries' => $tries,
                    'started_at' => now(),
                ]);
            }
        }, 'processing', $class);
    }

    public function jobProcessed(?string $jobId, string $class, int $tries): void
    {
        $this->finishJob($jobId, $class, $tries, JobLog::STATUS_SUCCESS);
    }

    public function jobFailed(?string $jobId, string $class, int $tries, string $exception): void
    {
        $this->finishJob($jobId, $class, $tries, JobLog::STATUS_FAILED, $exception);
    }

    public function scheduleRunning(string $summary): void
    {
        $this->safe(function () use ($summary) {
            JobLog::create([
                'type' => JobLog::TYPE_SCHEDULE,
                'class' => $summary,
                'status' => JobLog::STATUS_RUNNING,
                'started_at' => now(),
            ]);
        }, 'schedule_running', $summary);
    }

    public function scheduleFinished(string $summary, string $status, ?string $error = null): void
    {
        $this->safe(function () use ($summary, $status, $error) {
            $row = JobLog::schedules()
                ->where('class', $summary)
                ->where('status', JobLog::STATUS_RUNNING)
                ->latest('id')
                ->first();

            if ($row) {
                $row->update([
                    'status' => $status,
                    'finished_at' => now(),
                    'duration_ms' => $this->duration($row->started_at),
                    'exception_message' => $error,
                ]);
            } else {
                JobLog::create([
                    'type' => JobLog::TYPE_SCHEDULE,
                    'class' => $summary,
                    'status' => $status,
                    'finished_at' => now(),
                    'duration_ms' => 0,
                    'exception_message' => $error,
                ]);
            }
        }, 'schedule_finished', $summary);
    }

    public function prune(int $retentionDays): int
    {
        try {
            return JobLog::where('created_at', '<', now()->subDays($retentionDays))->delete();
        } catch (\Throwable $e) {
            Log::warning('JobLog prune failed', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    protected function finishJob(?string $jobId, string $class, int $tries, string $status, ?string $exception = null): void
    {
        $this->safe(function () use ($jobId, $class, $tries, $status, $exception) {
            $row = $this->findLatestRow($jobId, $class);

            if (! $row) {
                JobLog::create([
                    'type' => JobLog::TYPE_JOB,
                    'job_id' => $jobId,
                    'class' => $class,
                    'status' => $status,
                    'tries' => $tries,
                    'finished_at' => now(),
                    'duration_ms' => 0,
                    'exception_message' => $exception,
                ]);

                return;
            }

            $row->update([
                'tries' => $tries,
                'status' => $status,
                'finished_at' => now(),
                'duration_ms' => $this->duration($row->started_at),
                'exception_message' => $exception,
            ]);
        }, 'finish', $class);
    }

    protected function findLatestJobRow(?string $jobId): ?JobLog
    {
        if (! $jobId) {
            return null;
        }

        return JobLog::jobs()->where('job_id', $jobId)->latest('id')->first();
    }

    protected function findLatestRow(?string $jobId, string $class): ?JobLog
    {
        return $this->findLatestJobRow($jobId)
            ?? JobLog::jobs()->where('class', $class)->whereNull('finished_at')->latest('id')->first();
    }

    protected function duration($startedAt): int
    {
        if (! $startedAt) {
            return 0;
        }

        return (int) ((int) now()->valueOf() - (int) Carbon::parse($startedAt)->valueOf());
    }

    protected function safe(\Closure $callback, string $action, string $class): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::warning('JobLog recording failed', [
                'action' => $action,
                'class' => $class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
