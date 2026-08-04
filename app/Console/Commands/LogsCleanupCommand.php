<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class LogsCleanupCommand extends Command
{
    protected $signature = 'logs:cleanup
        {--days= : Number of days to keep logs}';

    protected $description = 'Delete activity logs older than specified days';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? Setting::get('log_retention_days', config('activitylog.clean_after_days', 365)));

        if ($days <= 0) {
            $this->info('Log retention is set to "forever". Skipping cleanup.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);

        $deleted = Activity::where('created_at', '<', $cutoff)->delete();

        $this->info("Deleted {$deleted} activity log(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
