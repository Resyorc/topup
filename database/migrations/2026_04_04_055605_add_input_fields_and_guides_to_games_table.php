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
        Schema::table('games', function (Blueprint $table) {
            $table->json('input_fields')->nullable()->after('icon_rules');
            $table->text('guide_content')->nullable()->after('input_fields');
            $table->string('guide_image')->nullable()->after('guide_content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['input_fields', 'guide_content', 'guide_image']);
        });
    }
};
