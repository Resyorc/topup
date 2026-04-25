<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type', 30); // daily | weekly | monthly
            $table->string('title');
            $table->text('summary');
            $table->longText('content');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['report_type', 'period_start']);
            $table->index('generated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_reports');
    }
};
