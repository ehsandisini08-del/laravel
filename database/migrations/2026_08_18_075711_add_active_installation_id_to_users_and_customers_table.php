<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('active_installation_id')->nullable()->after('active_session_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('active_installation_id')->nullable()->after('active_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('active_installation_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('active_installation_id');
        });
    }
};
