<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AppUpdateUnlockCommand extends Command
{
    protected $signature = 'app:update-unlock';

    protected $description = 'Unlock update yang stuck (emergency command)';

    public function handle(): int
    {
        $lock = storage_path('app/update.lock');
        $status = storage_path('app/update-status.json');

        if (! file_exists($lock)) {
            $this->info('✓ Tidak ada lock file. Update tidak stuck.');

            return self::SUCCESS;
        }

        $lockAge = now()->timestamp - (int) file_get_contents($lock);
        $minutes = round($lockAge / 60);

        $this->warn("Lock file ditemukan (umur: {$minutes} menit)");

        if (! $this->confirm('Hapus lock file dan reset status?', true)) {
            return self::SUCCESS;
        }

        @unlink($lock);
        @unlink($status);

        $this->info('✓ Lock file dihapus. Update bisa dijalankan lagi.');

        return self::SUCCESS;
    }
}
