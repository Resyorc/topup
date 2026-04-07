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
        Schema::create('digiflazz_skus', function (Blueprint $table) {
            $table->id();
            $table->string('sku_code')->unique();
            $table->string('product_name');
            $table->integer('price');
            $table->string('seller_name');
            $table->boolean('is_active')->default(true);
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digiflazz_skus');
    }
};
