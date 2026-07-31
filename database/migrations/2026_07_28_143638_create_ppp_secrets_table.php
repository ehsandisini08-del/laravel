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
        Schema::create('ppp_secrets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->onDelete('cascade');
            $table->string('mikrotik_id')->nullable();
            $table->string('name')->index();
            $table->text('password');
            $table->string('service')->nullable();
            $table->string('profile')->nullable();
            $table->string('local_address')->nullable();
            $table->string('remote_address')->nullable();
            $table->string('caller_id')->nullable();
            $table->boolean('disabled')->default(false);
            $table->text('comment')->nullable();
            $table->timestamp('last_logged_out')->nullable();
            $table->timestamps();

            $table->unique(['router_id', 'name']);
            $table->index('disabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppp_secrets');
    }
};
