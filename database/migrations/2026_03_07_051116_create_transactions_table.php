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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('customer_game_id');
            $table->string('customer_zone_id')->nullable();
            $table->string('customer_whatsapp')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('profit', 12, 2)->default(0);
            $table->enum('status', ['pending', 'paid', 'processing', 'success', 'failed'])->default('pending');
            $table->string('sn')->nullable();
            $table->text('payment_url')->nullable();
            $table->string('reference_id_provider')->nullable();
            $table->json('api_logs')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
