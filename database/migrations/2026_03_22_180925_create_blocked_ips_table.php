<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->unique()->index();
            $table->string('reason');
            $table->boolean('is_auto')->default(true)->comment('true = auto-block, false = manual');
            $table->timestamp('blocked_until')->nullable()->comment('Null = permanent');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_ips');
    }
};
