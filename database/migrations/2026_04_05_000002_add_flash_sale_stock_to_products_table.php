<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('flash_sale_stock')->nullable()->after('flash_sale_ends_at')
                ->comment('Stok maksimal flash sale. Null = tidak terbatas.');
            $table->unsignedInteger('flash_sale_purchased')->default(0)->after('flash_sale_stock')
                ->comment('Jumlah yang sudah dibeli saat flash sale.');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['flash_sale_stock', 'flash_sale_purchased']);
        });
    }
};
