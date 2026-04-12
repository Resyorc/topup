<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('from_tier');
            $table->string('to_tier');
            $table->unsignedInteger('amount');
            $table->string('status')->default('pending'); // pending, paid, failed, expired
            $table->string('payment_method')->nullable();
            $table->string('payment_name')->nullable();
            $table->string('payment_url')->nullable();
            $table->string('pay_code')->nullable();
            $table->text('qr_url')->nullable();
            $table->string('pay_url')->nullable();
            $table->string('reference')->nullable(); // Tripay reference
            $table->json('api_logs')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_orders');
    }
};
