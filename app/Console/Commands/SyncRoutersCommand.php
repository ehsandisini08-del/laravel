<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Services\Mikrotik\MikrotikService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncRoutersCommand extends Command
{
    protected $signature = 'mikrotik:sync {--router=* : Specific router IDs to sync}';

    protected $description = 'Synchronize router information and update status';

    public function handle(): int
    {
        $this->info('Starting router synchronization...');

        $query = Router::query()->enabled();

        if ($this->option('router')) {
            $routerIds = $this->option('router');
            $query->whereIn('id', $routerIds);
        }

        $routers = $query->get();

        if ($routers->isEmpty()) {
            $this->warn('No routers found to sync.');

            return self::SUCCESS;
        }

        $this->info("Found {$routers->count()} router(s) to sync.");

        $successCount = 0;
        $failedCount = 0;

        $progressBar = $this->output->createProgressBar($routers->count());
        $progressBar->start();

        foreach ($routers as $router) {
            try {
                $service = new MikrotikService($router);
                $success = $service->syncRouterInformation();

                if ($success) {
                    $successCount++;
                    $this->newLine();
                    $this->line("✓ <info>{$router->name}</info> - Synced successfully");
                } else {
                    $failedCount++;
                    $this->newLine();
                    $this->line("✗ <error>{$router->name}</error> - Failed to sync");
                }
            } catch (\Exception $e) {
                $failedCount++;
                $this->newLine();
                $this->line("✗ <error>{$router->name}</error> - Error: {$e->getMessage()}");

                Log::error('Router sync command error', [
                    'router_id' => $router->id,
                    'router_name' => $router->name,
                    'error' => $e->getMessage(),
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('Synchronization completed!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Success', $successCount],
                ['Failed', $failedCount],
                ['Total', $routers->count()],
            ]
        );

        Log::info('Router sync command completed', [
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'total_count' => $routers->count(),
        ]);

        return self::SUCCESS;
    }
}
