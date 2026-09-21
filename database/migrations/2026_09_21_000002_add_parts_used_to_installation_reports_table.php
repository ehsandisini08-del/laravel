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
        Schema::table('installation_reports', function (Blueprint $table) {
            $table->text('parts_used')->nullable()->after('device_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installation_reports', function (Blueprint $table) {
            $table->dropColumn('parts_used');
        });
    }
};
