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
        Schema::rename('digiflazz_skus', 'provider_products');
        Schema::table('provider_products', function (Blueprint $table) {
            $table->string('provider_name')->default('digiflazz')->after('id');
            $table->renameColumn('sku_code', 'provider_sku');
        });
    }

    public function down(): void
    {
        Schema::table('provider_products', function (Blueprint $table) {
            $table->dropColumn('provider_name');
            $table->renameColumn('provider_sku', 'sku_code');
        });
        Schema::rename('provider_products', 'digiflazz_skus');
    }
};
