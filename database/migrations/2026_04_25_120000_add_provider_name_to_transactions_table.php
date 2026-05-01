<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('provider_name')->nullable()->after('provider_sku');
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::table('transactions')
                ->whereNull('provider_name')
                ->update(['provider_name' => 'digiflazz']);

            return;
        }

        DB::statement("
            UPDATE transactions t
            LEFT JOIN provider_products pp ON pp.provider_sku = t.provider_sku
            SET t.provider_name = COALESCE(pp.provider_name, 'digiflazz')
            WHERE t.provider_name IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('provider_name');
        });
    }
};
