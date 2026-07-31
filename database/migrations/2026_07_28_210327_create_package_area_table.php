<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_area', function (Blueprint $table) {
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->foreignId('area_id')->constrained()->onDelete('restrict');

            $table->unique(['package_id', 'area_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_area');
    }
};
