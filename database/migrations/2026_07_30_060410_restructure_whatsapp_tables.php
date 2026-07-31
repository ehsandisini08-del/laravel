<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_name');
            $table->string('session_name')->unique();
            $table->string('phone_number')->nullable();
            $table->string('profile_name')->nullable();
            $table->string('profile_picture')->nullable();
            $table->string('status')->default('disconnected');
            $table->timestamp('last_seen')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamps();
        });

        Schema::create('wa_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_settings');
        Schema::dropIfExists('wa_devices');
    }
};
