<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobLog extends Model
{
    public const TYPE_JOB = 'job';

    public const TYPE_SCHEDULE = 'schedule';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_RUNNING = 'running';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'job_id',
        'type',
        'class',
        'queue',
        'status',
        'tries',
        'started_at',
        'finished_at',
        'duration_ms',
        'exception_message',
    ];

    protected $casts = [
        'tries' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_ms' => 'integer',
    ];

    public function scopeJobs($query)
    {
        return $query->where('type', self::TYPE_JOB);
    }

    public function scopeSchedules($query)
    {
        return $query->where('type', self::TYPE_SCHEDULE);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_SUCCESS => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_QUEUED, self::STATUS_RUNNING, self::STATUS_PROCESSING => 'warning',
            default => 'default',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_SUCCESS => 'Success',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_QUEUED => 'Queued',
            self::STATUS_RUNNING => 'Running',
            self::STATUS_PROCESSING => 'Processing',
            default => ucfirst($this->status),
        };
    }
}
