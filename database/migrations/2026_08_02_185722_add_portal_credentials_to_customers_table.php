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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('portal_password')->nullable()->after('ppp_password');
            $table->boolean('portal_enabled')->default(true)->after('portal_password');
            $table->timestamp('portal_last_login_at')->nullable()->after('portal_enabled');
            $table->string('remember_token', 100)->nullable()->after('portal_last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['portal_password', 'portal_enabled', 'portal_last_login_at', 'remember_token']);
        });
    }
};
