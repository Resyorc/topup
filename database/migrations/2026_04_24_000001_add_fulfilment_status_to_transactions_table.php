<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('fulfilment_status', 20)
                ->default('pending')
                ->after('payment_status')
                ->comment('pending | processing | success | failed');

            $table->index('fulfilment_status');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['fulfilment_status']);
            $table->dropColumn('fulfilment_status');
        });
    }
};
