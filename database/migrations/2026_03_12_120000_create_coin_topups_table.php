<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coin_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_id')->unique();
            $table->unsignedInteger('amount');
            $table->enum('status', ['pending', 'paid', 'failed', 'expired'])->default('pending');
            $table->string('failure_reason')->nullable();
            $table->string('customer_whatsapp')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_name')->nullable();
            $table->text('payment_url')->nullable();
            $table->string('pay_code')->nullable();
            $table->text('qr_url')->nullable();
            $table->text('pay_url')->nullable();
            $table->string('reference_id_provider')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('api_logs')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_topups');
    }
};
