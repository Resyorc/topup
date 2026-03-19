<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('pending','paid','processing','success','failed','canceled') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE coin_topups MODIFY COLUMN status ENUM('pending','paid','failed','expired','canceled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('pending','paid','processing','success','failed') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE coin_topups MODIFY COLUMN status ENUM('pending','paid','failed','expired') NOT NULL DEFAULT 'pending'");
    }
};
