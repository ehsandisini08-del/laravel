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
        Schema::create('cpes', function (Blueprint $table) {
            $table->id();
            $table->string('genieacs_id')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ppp_username')->nullable()->index();
            $table->string('serial_number')->nullable()->index();
            $table->string('manufacturer')->nullable();
            $table->string('model_name')->nullable();
            $table->string('model_number')->nullable();
            $table->string('hardware_version')->nullable();
            $table->string('software_version')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('mac_address')->nullable();
            $table->string('status')->default('unknown')->index();
            $table->timestamp('last_inform_at')->nullable();
            $table->unsignedBigInteger('uptime')->nullable();
            $table->json('signal_parameters')->nullable();
            $table->json('tags')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cpes');
    }
};
