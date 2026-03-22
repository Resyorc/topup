<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failed_login_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->index();
            $table->string('email_attempted')->index();
            $table->text('user_agent')->nullable();
            $table->timestamp('attempted_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_login_logs');
    }
};
