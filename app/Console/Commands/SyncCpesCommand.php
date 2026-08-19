<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\Genieacs\CpeSyncService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('genieacs:sync')]
#[Description('Synchronize CPE devices from GenieACS and update their status')]
class SyncCpesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (Setting::get('genieacs_sync_enabled', '0') !== '1') {
            $this->warn('Sinkronisasi GenieACS terjadwal dinonaktifkan (genieacs_sync_enabled).');

            return self::SUCCESS;
        }

        $this->info('Starting CPE synchronization from GenieACS...');

        try {
            $result = app(CpeSyncService::class)->sync();

            if (! $result['success']) {
                $this->error("Sync failed: {$result['error']}");

                Log::error('CPE sync command failed', [
                    'error' => $result['error'],
                ]);

                return self::FAILURE;
            }

            $this->info("✓ {$result['total']} device(s) synced, {$result['matched']} matched to customers.");

            Log::info('CPE sync command completed', [
                'total' => $result['total'],
                'matched' => $result['matched'],
            ]);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Sync error: {$e->getMessage()}");

            Log::error('CPE sync command error', [
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }
}
