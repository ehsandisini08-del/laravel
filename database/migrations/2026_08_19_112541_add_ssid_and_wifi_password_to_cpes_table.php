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
            $table->string('ssid')->nullable()->after('mac_address');
            $table->string('wifi_password')->nullable()->after('ssid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cpes', function (Blueprint $table) {
            $table->dropColumn(['ssid', 'wifi_password']);
        });
    }
};
