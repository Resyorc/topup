<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_knowledge_sources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type', 30); // policy | sop | template | faq | guide
            $table->longText('content');
            $table->string('source_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_sources');
    }
};
