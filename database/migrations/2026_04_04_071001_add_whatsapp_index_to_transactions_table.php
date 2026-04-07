<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('customer_whatsapp', 'transactions_customer_whatsapp_index');
        });

        Schema::table('coin_topups', function (Blueprint $table) {
            $table->index('customer_whatsapp', 'coin_topups_customer_whatsapp_index');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_customer_whatsapp_index');
        });

        Schema::table('coin_topups', function (Blueprint $table) {
            $table->dropIndex('coin_topups_customer_whatsapp_index');
        });
    }
};
