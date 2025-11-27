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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('old_price', 10, 2)->nullable()->after('price'); // Старая цена (из .price.discount)
            $table->decimal('discounted_price', 10, 2)->nullable()->after('old_price'); // Новая цена со скидкой (из .discounted-price)
            $table->decimal('recommended_price', 10, 2)->nullable()->after('discounted_price'); // Рекомендованная цена (из .recommended-price)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['old_price', 'discounted_price', 'recommended_price']);
        });
    }
};
