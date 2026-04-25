<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('module', 50);
            $table->string('action_type', 80);
            $table->string('target_type', 80)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data');
            $table->text('reason')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'executed', 'failed', 'cancelled'])
                ->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['module', 'action_type']);
            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_actions');
    }
};
