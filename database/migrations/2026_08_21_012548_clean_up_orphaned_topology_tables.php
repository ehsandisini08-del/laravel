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
        if (Schema::hasColumn('customers', 'odp_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('odp_id');
            });
        }

        Schema::dropIfExists('odps');
        Schema::dropIfExists('odcs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('odcs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_odc');
            $table->string('nama_odc');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
        });

        Schema::create('odps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('odc_id')->constrained('odcs')->cascadeOnDelete();
            $table->string('kode_odp');
            $table->string('nama_odp');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('odp_id')->nullable();
        });
    }
};
