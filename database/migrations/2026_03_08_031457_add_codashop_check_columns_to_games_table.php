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
            $table->boolean('is_check_id')->default(false)->after('is_active');
            $table->string('codashop_voucher_id')->nullable()->after('is_check_id');
            $table->string('codashop_price')->nullable()->after('codashop_voucher_id');
            $table->string('codashop_voucher_type')->nullable()->after('codashop_price');
            $table->boolean('codashop_need_zone')->default(false)->after('codashop_voucher_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn([
                'is_check_id',
                'codashop_voucher_id',
                'codashop_price',
                'codashop_voucher_type',
                'codashop_need_zone'
            ]);
        });
    }
};
