<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 30)->unique();
            $table->enum('type', ['in', 'out', 'adjustment']);
            $table->string('reference', 100)->nullable();
            $table->string('supplier', 150)->nullable();
            $table->string('recipient', 150)->nullable();
            $table->string('reason', 200)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('transaction_date')->nullable();
            $table->timestamps();

            $table->index(['type', 'transaction_date']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
    }
};
