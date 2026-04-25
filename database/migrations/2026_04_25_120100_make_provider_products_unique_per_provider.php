<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(DB::select('SHOW INDEX FROM provider_products'))
            ->pluck('Key_name')
            ->unique()
            ->values()
            ->all();

        foreach (['provider_products_provider_sku_unique', 'digiflazz_skus_sku_code_unique', 'sku_code'] as $indexName) {
            if (in_array($indexName, $indexes, true)) {
                DB::statement("ALTER TABLE provider_products DROP INDEX {$indexName}");
            }
        }

        Schema::table('provider_products', function (Blueprint $table) {
            $table->unique(['provider_name', 'provider_sku'], 'provider_products_provider_name_sku_unique');
        });
    }

    public function down(): void
    {
        Schema::table('provider_products', function (Blueprint $table) {
            $table->dropUnique('provider_products_provider_name_sku_unique');
            $table->unique('provider_sku');
        });
    }
};
