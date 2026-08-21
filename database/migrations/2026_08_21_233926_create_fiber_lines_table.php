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
        Schema::create('fiber_lines', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('tipe_kabel')->nullable()->comment('SM, MM, etc');
            $table->string('source_type')->nullable()->comment('odc, odp');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('destination_type')->nullable()->comment('odc, odp, customer');
            $table->unsignedBigInteger('destination_id')->nullable();
            $table->text('geometry')->nullable()->comment('GeoJSON LineString sebagai JSON string');
            $table->string('status')->default('ACTIVE')->comment('ACTIVE, INACTIVE, DAMAGE');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['destination_type', 'destination_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiber_lines');
    }
};
