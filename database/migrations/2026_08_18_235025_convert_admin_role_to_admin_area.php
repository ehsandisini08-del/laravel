<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'admin')
            ->update(['role' => 'admin_area']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('admin_area')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('role', 'admin_area')
            ->update(['role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('admin')->change();
        });
    }
};
