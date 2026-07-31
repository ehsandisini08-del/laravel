<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppp_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->onDelete('cascade');
            $table->string('mikrotik_id')->nullable();
            $table->string('name')->index();
            $table->string('local_address')->nullable();
            $table->string('remote_address')->nullable();
            $table->string('dns_server')->nullable();
            $table->string('rate_limit')->nullable();
            $table->string('parent_queue')->nullable();
            $table->boolean('only_one')->default(false);
            $table->boolean('change_tcp_mss')->default(false);
            $table->boolean('use_compression')->default(false);
            $table->boolean('use_encryption')->default(false);
            $table->boolean('use_ipv6')->default(false);
            $table->string('bridge')->nullable();
            $table->integer('bridge_path_cost')->nullable();
            $table->string('bridge_horizon')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['router_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppp_profiles');
    }
};
