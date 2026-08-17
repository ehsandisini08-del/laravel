<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columns holding naive timestamps written while the app ran on UTC.
     *
     * They are shifted by +7 hours once to become correct Asia/Jakarta values.
     */
    protected const COLUMNS = [
        'users' => ['created_at', 'updated_at', 'email_verified_at'],
        'routers' => ['created_at', 'updated_at', 'last_seen_at'],
        'ppp_secrets' => ['created_at', 'updated_at', 'last_logged_out'],
        'ppp_profiles' => ['created_at', 'updated_at', 'synced_at'],
        'areas' => ['created_at', 'updated_at'],
        'packages' => ['created_at', 'updated_at'],
        'activity_log' => ['created_at', 'updated_at'],
        'customers' => ['created_at', 'updated_at', 'portal_last_login_at'],
        'wa_templates' => ['created_at', 'updated_at'],
        'wa_messages' => ['created_at', 'updated_at', 'sent_at', 'delivered_at', 'read_at'],
        'wa_devices' => ['created_at', 'updated_at', 'last_seen', 'connected_at', 'disconnected_at'],
        'wa_settings' => ['created_at', 'updated_at'],
        'invoice_items' => ['created_at', 'updated_at'],
        'invoices' => ['created_at', 'updated_at', 'paid_at'],
        'billing_logs' => ['created_at', 'updated_at'],
        'isolation_logs' => ['created_at', 'updated_at', 'executed_at'],
        'settings' => ['created_at', 'updated_at'],
        'payments' => ['created_at', 'updated_at', 'paid_at'],
        'device_tokens' => ['created_at', 'updated_at', 'last_seen_at'],
        'invoice_reminders' => ['created_at', 'updated_at', 'sent_at'],
        'job_logs' => ['created_at', 'updated_at', 'started_at', 'finished_at'],
    ];

    public function up(): void
    {
        $cutoff = '2026-08-18 00:00:00';

        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::statement($this->shiftStatement($table, $column, $cutoff));
            }
        }
    }

    public function down(): void
    {
        // Data was shifted irreversibly; nothing to restore.
    }

    protected function shiftStatement(string $table, string $column, string $cutoff): string
    {
        if (DB::getDriverName() === 'sqlite') {
            return "UPDATE {$table} SET {$column} = datetime({$column}, '+7 hours') WHERE {$column} IS NOT NULL AND {$column} < '{$cutoff}'";
        }

        return "UPDATE {$table} SET {$column} = DATE_ADD({$column}, INTERVAL 7 HOUR) WHERE {$column} IS NOT NULL AND {$column} < '{$cutoff}'";
    }
};
