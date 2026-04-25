<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('module', 50);
            $table->string('feature', 80);
            $table->string('model', 60);
            $table->text('prompt')->nullable();
            $table->text('response')->nullable();
            $table->string('status', 20)->default('success'); // success | error
            $table->text('error_message')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index(['module', 'created_at']);
            $table->index(['admin_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_logs');
    }
};
