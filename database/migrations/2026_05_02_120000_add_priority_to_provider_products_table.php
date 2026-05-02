<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_products', function (Blueprint $table) {
            if (! Schema::hasColumn('provider_products', 'priority')) {
                $table->unsignedSmallInteger('priority')
                    ->default(100)
                    ->after('product_id');
            }

            $table->index(
                ['product_id', 'is_active', 'priority', 'price'],
                'provider_products_product_selection_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('provider_products', function (Blueprint $table) {
            $table->dropIndex('provider_products_product_selection_idx');

            if (Schema::hasColumn('provider_products', 'priority')) {
                $table->dropColumn('priority');
            }
        });
    }
};
