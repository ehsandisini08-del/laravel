<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class AppUpdateCommand extends Command
{
    protected $signature = 'app:update {--no-build : Skip npm install & build}';

    protected $description = 'Update aplikasi dari repository git (pull, composer, migrate, build, cache, restart queue).';

    public function handle(): int
    {
        $lock = storage_path('app/update.lock');
        $statusPath = storage_path('app/update-status.json');

        if (file_exists($lock)) {
            $this->error('Update lain sedang berjalan.');

            return self::FAILURE;
        }

        file_put_contents($lock, (string) now()->timestamp);
        $this->writeStatus($statusPath, [
            'started_at' => now()->toDateTimeString(),
            'finished_at' => null,
            'success' => null,
            'failed_steps' => [],
        ]);

        $this->info('Update dimulai...');

        $failures = [];

        foreach ($this->steps() as $label => $command) {
            $this->line("[{$label}] {$command}");

            $result = Process::path(base_path())->run($command);

            if (! $result->successful()) {
                $failures[$label] = trim($result->output().$result->errorOutput());
                $this->error("Langkah '{$label}' gagal (exit {$result->exitCode()}).");
                $this->line($failures[$label]);
            }
        }

        $success = $failures === [];

        $status = json_decode((string) file_get_contents($statusPath), true);

        $this->writeStatus($statusPath, [
            'started_at' => is_array($status) && isset($status['started_at']) ? $status['started_at'] : now()->toDateTimeString(),
            'finished_at' => now()->toDateTimeString(),
            'success' => $success,
            'failed_steps' => $failures,
        ]);

        @unlink($lock);

        if ($success) {
            $this->info('Update selesai.');

            return self::SUCCESS;
        }

        $this->error('Update selesai dengan kegagalan: '.implode(', ', $failures));

        return self::FAILURE;
    }

    protected function steps(): array
    {
        $php = PHP_BINARY;
        $composer = is_file('/usr/local/bin/composer') ? '/usr/local/bin/composer' : 'composer';

        $steps = [
            'git' => 'git fetch origin main && git reset --hard origin/main',
            'composer' => "{$composer} install --no-dev --optimize-autoloader --no-interaction",
            'migrate' => "{$php} artisan migrate --force",
            'storage' => "{$php} artisan storage:link",
        ];

        if (! $this->option('no-build')) {
            $npmCache = storage_path('app/npm-cache');
            $steps['npm'] = "mkdir -p {$npmCache} && npm ci --no-audit --no-fund --cache {$npmCache} && npm run build";
        }

        $steps['optimize'] = "{$php} artisan optimize";
        $steps['queue'] = "{$php} artisan queue:restart";
        $steps['systemd'] = 'systemctl restart billnet-queue || true';
        $steps['php-fpm'] = 'sudo -n systemctl restart php8.4-fpm 2>/dev/null || true';

        return $steps;
    }

    protected function writeStatus(string $path, array $data): void
    {
        file_put_contents($path, (string) json_encode($data));
    }
}
