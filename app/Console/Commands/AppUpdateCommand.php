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
            $lockAge = now()->timestamp - (int) file_get_contents($lock);
            if ($lockAge > 1800) {
                @unlink($lock);
                @unlink($statusPath);
                $this->warn('⚠️ Lock file stuck dihapus (> 30 menit). Update dilanjutkan.');
            } else {
                $this->error('Update lain sedang berjalan.');

                return self::FAILURE;
            }
        }

        file_put_contents($lock, (string) now()->timestamp);
        $this->writeStatus($statusPath, [
            'started_at' => now()->toIso8601String(),
            'finished_at' => null,
            'success' => null,
            'failed_steps' => [],
        ]);

        $this->info('Update dimulai...');

        $failures = [];

        foreach ($this->steps() as $label => $command) {
            $this->line("[{$label}] {$command}");

            $result = Process::path(base_path())->timeout(600)->run($command);

            if (! $result->successful()) {
                $output = trim($result->output().$result->errorOutput());
                $failures[$label] = $output;
                $this->error("Langkah '{$label}' gagal (exit {$result->exitCode()}).");

                if (strlen($output) > 500) {
                    $this->line(substr($output, 0, 250).'...[truncated]...'.substr($output, -250));
                } else {
                    $this->line($output);
                }

                if ($label === 'git' && str_contains($output, 'fatal')) {
                    $this->warn('Git error detected. Pastikan repository sudah di-clone dari GitHub dan branch "main" ada.');
                }

                if ($label === 'composer' && (str_contains($output, 'permission') || str_contains($output, 'Could not delete'))) {
                    $this->newLine();
                    $this->error('❌ COMPOSER ERROR: Tidak bisa delete files di vendor/');
                    $this->newLine();
                    $this->warn('🔧 SOLUSI CEPAT via SSH:');
                    $this->line('   ssh root@your-server');
                    $this->line('   cd /var/www/billnet');
                    $this->line('   chown -R www-data:www-data .');
                    $this->line('   chmod -R 775 vendor storage');
                    $this->line('   rm -rf vendor/webmozart vendor/phpunit vendor/pestphp');
                    $this->line('   sudo -u www-data composer install --no-dev --no-cache');
                    $this->newLine();
                }

                if ($label === 'ownership' && ! $result->successful()) {
                    $this->warn('⚠️ Tidak bisa fix ownership. Pastikan command dijalankan sebagai root atau www-data.');
                }
            } else {
                $this->info("✓ {$label} berhasil");
            }
        }

        $success = $failures === [];

        $status = json_decode((string) file_get_contents($statusPath), true);

        $this->writeStatus($statusPath, [
            'started_at' => is_array($status) && isset($status['started_at']) ? $status['started_at'] : now()->toIso8601String(),
            'finished_at' => now()->toIso8601String(),
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
        $composer = $this->findComposer();
        $isWindows = DIRECTORY_SEPARATOR === '\\';

        $steps = [
            'git' => 'git fetch origin main && git reset --hard origin/main',
        ];

        if ($isWindows) {
            $steps['permissions'] = 'icacls vendor /grant "Users:(OI)(CI)F" /T /C /Q >nul 2>&1 || echo "Permission fix attempted"';
        } else {
            $basePath = base_path();
            $steps['ownership'] = "chown -R www-data:www-data {$basePath} 2>/dev/null || true";
            $steps['permissions'] = 'chmod -R 775 vendor storage bootstrap/cache 2>/dev/null || true';
            $steps['vendor-cleanup'] = 'rm -rf vendor/webmozart vendor/phpunit vendor/pestphp vendor/sebastian vendor/theseer vendor/mockery 2>/dev/null || true';
        }

        if (! $isWindows) {
            $composerHome = storage_path('app/.composer');
            $steps['composer'] = "mkdir -p {$composerHome} && COMPOSER_HOME={$composerHome} {$composer} install --no-dev --optimize-autoloader --no-interaction --no-cache 2>&1";
        } else {
            $steps['composer'] = "{$composer} install --no-dev --optimize-autoloader --no-interaction --no-cache";
        }
        $steps['migrate'] = "{$php} artisan migrate --force";
        $steps['storage'] = "{$php} artisan storage:link";

        if (! $this->option('no-build')) {
            if ($isWindows) {
                $steps['npm-clean'] = 'if exist node_modules rmdir /s /q node_modules';
                $steps['npm'] = 'npm install --include=dev --no-audit --no-fund && npm run build';
            } else {
                $npmCache = storage_path('app/npm-cache');
                $steps['npm'] = "rm -rf node_modules && mkdir -p {$npmCache} && npm install --include=dev --no-audit --no-fund --cache {$npmCache} && node node_modules/vite/bin/vite.js build";
            }
        }

        $steps['optimize'] = "{$php} artisan optimize";
        $steps['queue'] = "{$php} artisan queue:restart";

        if (! $isWindows) {
            $steps['systemd'] = 'systemctl restart billnet-queue || true';
            $steps['php-fpm'] = 'sudo -n systemctl restart php8.4-fpm 2>/dev/null || true';
        }

        return $steps;
    }

    protected function findComposer(): string
    {
        $candidates = [
            base_path('composer.phar'),
            '/usr/local/bin/composer',
            'composer',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return 'composer';
    }

    protected function writeStatus(string $path, array $data): void
    {
        file_put_contents($path, (string) json_encode($data));
    }
}
