<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_logs', function (Blueprint $table) {
            $table->id();
            $table->string('job_id')->nullable();
            $table->string('type')->default('job');
            $table->string('class')->nullable();
            $table->string('queue')->nullable();
            $table->string('status')->default('queued');
            $table->unsignedTinyInteger('tries')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('exception_message')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'status', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_logs');
    }
};
