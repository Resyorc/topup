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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('customer_email')->nullable()->after('customer_name');
            $table->string('failure_reason')->nullable()->after('sn');
            $table->string('payment_method')->nullable()->after('payment_status');
            $table->string('payment_name')->nullable()->after('payment_method');
            $table->string('pay_code')->nullable()->after('payment_name');
            $table->text('qr_url')->nullable()->after('pay_code');
            $table->text('pay_url')->nullable()->after('qr_url');
            $table->timestamp('expired_at')->nullable()->after('reference_id_provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'customer_email', 'failure_reason', 'payment_method',
                'payment_name', 'pay_code', 'qr_url', 'pay_url', 'expired_at',
            ]);
        });
    }
};
