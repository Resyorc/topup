<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('guest_token', 64)->nullable()->unique()->after('invoice_id');
        });

        Schema::table('coin_topups', function (Blueprint $table) {
            $table->string('guest_token', 64)->nullable()->unique()->after('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('guest_token');
        });

        Schema::table('coin_topups', function (Blueprint $table) {
            $table->dropColumn('guest_token');
        });
    }
};
