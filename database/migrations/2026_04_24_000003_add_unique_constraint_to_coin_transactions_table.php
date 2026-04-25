<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coin_transactions', function (Blueprint $table) {
            // Prevent double-credit/debit for the same reference — e.g. duplicate refund on failed transaction
            $table->unique(['reference_id', 'type'], 'coin_transactions_reference_type_unique');
        });
    }

    public function down(): void
    {
        Schema::table('coin_transactions', function (Blueprint $table) {
            $table->dropUnique('coin_transactions_reference_type_unique');
        });
    }
};
