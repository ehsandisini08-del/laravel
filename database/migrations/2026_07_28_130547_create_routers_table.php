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
        Schema::create('routers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('host');
            $table->integer('api_port')->default(8728);
            $table->boolean('api_ssl')->default(false);
            $table->string('username');
            $table->text('password');
            $table->string('location')->nullable();
            $table->string('timezone')->nullable();
            $table->string('routeros_version')->nullable();
            $table->string('board_name')->nullable();
            $table->string('identity')->nullable();
            $table->string('architecture')->nullable();
            $table->string('cpu')->nullable();
            $table->bigInteger('total_memory')->nullable();
            $table->bigInteger('free_memory')->nullable();
            $table->string('uptime')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->enum('status', ['online', 'offline', 'checking'])->default('offline');
            $table->boolean('enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('priority')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('enabled');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routers');
    }
};
