<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('products', 'provider_sku')) {
            if (DB::getDriverName() === 'sqlite') {
                DB::statement('DROP INDEX IF EXISTS products_provider_sku_unique');
            } else {
                try {
                    DB::statement('ALTER TABLE products DROP INDEX products_provider_sku_unique');
                } catch (Throwable) {
                    // Index may already be absent on older databases.
                }
            }
        }

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'provider_sku')) {
                $table->dropColumn('provider_sku');
            }

            // Margins per tier
            $table->decimal('margin_guest_flat', 12, 2)->default(0)->after('price_cost');
            $table->decimal('margin_bronze_flat', 12, 2)->default(0)->after('margin_guest_flat');
            $table->decimal('margin_silver_flat', 12, 2)->default(0)->after('margin_bronze_flat');
            $table->decimal('margin_gold_flat', 12, 2)->default(0)->after('margin_silver_flat');
            $table->decimal('margin_platinum_flat', 12, 2)->default(0)->after('margin_gold_flat');

            // Prices per tier
            $table->integer('price_guest')->default(0)->after('margin_platinum_flat');
            $table->integer('price_bronze')->default(0)->after('price_guest');
            $table->integer('price_silver')->default(0)->after('price_bronze');
            $table->integer('price_gold')->default(0)->after('price_silver');
            $table->integer('price_platinum')->default(0)->after('price_gold');

            $table->string('logo_url')->nullable()->after('is_available');

            // Drop existing margin and single price
            $dropCols = array_filter(
                ['margin_flat', 'margin_percent', 'price_sell'],
                fn ($col) => Schema::hasColumn('products', $col)
            );
            if (!empty($dropCols)) {
                $table->dropColumn(array_values($dropCols));
            }
        });

        Schema::table('games', function (Blueprint $table) {
            $table->string('banner_url')->nullable()->after('thumbnail');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('provider_sku')->nullable();
            
            $table->decimal('margin_flat', 12, 2)->default(0);
            $table->decimal('margin_percent', 5, 2)->default(0);
            $table->integer('price_sell')->default(0);

            $table->dropColumn([
                'margin_guest_flat', 'margin_bronze_flat', 'margin_silver_flat', 'margin_gold_flat', 'margin_platinum_flat',
                'price_guest', 'price_bronze', 'price_silver', 'price_gold', 'price_platinum',
                'logo_url'
            ]);
        });

        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('banner_url');
        });
    }
};
