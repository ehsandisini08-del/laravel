<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\JobLogService;
use Illuminate\Console\Scheduling\Events\ScheduledTaskFailed;
use Illuminate\Console\Scheduling\Events\ScheduledTaskFinished;
use Illuminate\Console\Scheduling\Events\ScheduledTaskStarting;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $settings = $this->loadSettings();

        $this->applyTimezone($settings);
        $this->applyDebug($settings);
        $this->applySmtp($settings);

        Blade::directive('currency', function (string $expression) {
            return "<?php echo \\App\\Support\\Currency::format($expression); ?>";
        });

        Blade::directive('terbilang', function (string $expression) {
            return "<?php echo \\App\\Support\\Currency::terbilang($expression); ?>";
        });

        $this->registerJobMonitoring();
    }

    protected function registerJobMonitoring(): void
    {
        Event::listen(JobQueued::class, function (JobQueued $event): void {
            $class = is_object($event->job) ? get_class($event->job) : (string) $event->job;
            app(JobLogService::class)->jobQueued($event->id, $class, $event->queue);
        });

        Event::listen(JobProcessing::class, function (JobProcessing $event): void {
            app(JobLogService::class)->jobProcessing($event->job->getJobId(), (string) $event->job->resolveName(), $event->job->attempts());
        });

        Event::listen(JobProcessed::class, function (JobProcessed $event): void {
            app(JobLogService::class)->jobProcessed($event->job->getJobId(), (string) $event->job->resolveName(), $event->job->attempts());
        });

        Event::listen(JobFailed::class, function (JobFailed $event): void {
            app(JobLogService::class)->jobFailed(
                $event->job->getJobId(),
                (string) $event->job->resolveName(),
                $event->job->attempts(),
                $event->exception?->getMessage() ?? 'Job gagal tanpa pesan error.'
            );
        });

        if (class_exists(ScheduledTaskStarting::class)) {
            Event::listen(ScheduledTaskStarting::class, function (ScheduledTaskStarting $event): void {
                app(JobLogService::class)->scheduleRunning($this->scheduleSummary($event->task));
            });

            Event::listen(ScheduledTaskFinished::class, function (ScheduledTaskFinished $event): void {
                app(JobLogService::class)->scheduleFinished($this->scheduleSummary($event->task), 'success');
            });

            Event::listen(ScheduledTaskFailed::class, function (ScheduledTaskFailed $event): void {
                app(JobLogService::class)->scheduleFinished($this->scheduleSummary($event->task), 'failed', (string) ($event->exitCode ?? ''));
            });
        }
    }

    protected function scheduleSummary(object $task): string
    {
        if (method_exists($task, 'getSummaryForDisplay')) {
            return (string) $task->getSummaryForDisplay();
        }

        if (method_exists($task, 'summaryForDisplay')) {
            return (string) $task->summaryForDisplay();
        }

        $command = $task->command ?? $task->description ?? null;

        return is_string($command) ? $command : get_class($task);
    }

    /**
     * @return array<string, string>
     */
    protected function loadSettings(): array
    {
        try {
            return Setting::allSettings();
        } catch (\Throwable $e) {
            Log::debug('Unable to load settings during bootstrap: '.$e->getMessage());

            return [];
        }
    }

    /**
     * @param  array<string, string>  $settings
     */
    protected function applyTimezone(array $settings): void
    {
        $timezone = $settings['timezone'] ?? null;

        if ($timezone && in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            date_default_timezone_set($timezone);
            config(['app.timezone' => $timezone]);
        }
    }

    /**
     * @param  array<string, string>  $settings
     */
    protected function applyDebug(array $settings): void
    {
        if (array_key_exists('debug_mode', $settings)) {
            config(['app.debug' => $settings['debug_mode'] === '1']);
        }
    }

    /**
     * @param  array<string, string>  $settings
     */
    protected function applySmtp(array $settings): void
    {
        $host = $settings['mail_host'] ?? null;

        if (! $host) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => (int) ($settings['mail_port'] ?? 587),
            'mail.mailers.smtp.username' => $settings['mail_username'] ?? null,
            'mail.mailers.smtp.password' => $settings['mail_password'] ?? null,
            'mail.mailers.smtp.encryption' => ($settings['mail_encryption'] ?? 'tls') ?: null,
            'mail.from.address' => $settings['mail_from_address'] ?? config('mail.from.address'),
            'mail.from.name' => $settings['mail_from_name'] ?? config('mail.from.name'),
        ]);
    }
}
