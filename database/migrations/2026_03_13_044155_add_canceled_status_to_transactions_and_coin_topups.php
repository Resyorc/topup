<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite implements enum() as a CHECK constraint.
            // Change to string to drop the constraint and allow 'canceled'.
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('status')->default('pending')->change();
            });
            Schema::table('coin_topups', function (Blueprint $table) {
                $table->string('status')->default('pending')->change();
            });
        } else {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('pending','paid','processing','success','failed','canceled') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE coin_topups MODIFY COLUMN status ENUM('pending','paid','failed','expired','canceled') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('pending','paid','processing','success','failed') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE coin_topups MODIFY COLUMN status ENUM('pending','paid','failed','expired') NOT NULL DEFAULT 'pending'");
        }
    }
};
