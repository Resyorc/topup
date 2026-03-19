<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->enum('type', ['percent', 'flat'])->default('flat');
            $table->unsignedInteger('value');           // % atau Rp
            $table->unsignedInteger('min_amount')->default(0);
            $table->unsignedInteger('max_discount')->nullable(); // cap untuk tipe percent
            $table->unsignedInteger('usage_limit')->nullable();  // null = unlimited
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
