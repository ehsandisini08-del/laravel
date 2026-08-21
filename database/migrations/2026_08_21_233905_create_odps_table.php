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
        Schema::create('odps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('odc_id')->constrained('odcs')->cascadeOnDelete();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->text('alamat')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('kapasitas')->default(16)->comment('Kapasitas port');
            $table->integer('port_terpakai')->default(0);
            $table->text('keterangan')->nullable();
            $table->string('status')->default('ACTIVE')->comment('ACTIVE, WARNING, DOWN, MAINTENANCE, INACTIVE');
            $table->timestamps();

            $table->index('odc_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('odps');
    }
};
