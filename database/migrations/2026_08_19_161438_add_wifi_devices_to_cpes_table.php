<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cpes', function (Blueprint $table) {
            $table->json('wifi_devices')->nullable()->after('wifi_clients');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cpes', function (Blueprint $table) {
            $table->dropColumn('wifi_devices');
        });
    }
};
