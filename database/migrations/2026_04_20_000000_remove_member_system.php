<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop membership_orders table
        Schema::dropIfExists('membership_orders');

        // Remove tier from users
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tier');
        });

        // Remove min_tier from vouchers
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('min_tier');
        });

        // Simplify products: rename price_guest → price_sell, margin_guest_flat → margin_flat, drop tier columns
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('price_guest', 'price_sell');
            $table->renameColumn('margin_guest_flat', 'margin_flat');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'price_bronze',
                'price_silver',
                'price_gold',
                'price_platinum',
                'margin_bronze_flat',
                'margin_silver_flat',
                'margin_gold_flat',
                'margin_platinum_flat',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('price_sell', 'price_guest');
            $table->renameColumn('margin_flat', 'margin_guest_flat');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('price_bronze')->default(0);
            $table->unsignedBigInteger('price_silver')->default(0);
            $table->unsignedBigInteger('price_gold')->default(0);
            $table->unsignedBigInteger('price_platinum')->default(0);
            $table->decimal('margin_bronze_flat', 15, 2)->default(0);
            $table->decimal('margin_silver_flat', 15, 2)->default(0);
            $table->decimal('margin_gold_flat', 15, 2)->default(0);
            $table->decimal('margin_platinum_flat', 15, 2)->default(0);
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('min_tier')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('tier')->default('bronze');
        });
    }
};
