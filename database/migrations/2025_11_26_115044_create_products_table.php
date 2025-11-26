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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcategory_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->string('type')->nullable(); // perfume, cream, spray, supplement, cleaning, makeup, set
            $table->string('gender_target')->nullable(); // male, female, unisex, children
            $table->string('volume')->nullable(); // 50 ml, 10 ml, комплект, 100 г
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('RUB');
            $table->boolean('in_stock')->default(true);
            $table->integer('stock_qty')->nullable();
            $table->text('description')->nullable();
            $table->json('tags')->nullable(); // массив строк
            $table->timestamps();
            
            $table->index('subcategory_id');
            $table->index('in_stock');
            $table->index('type');
            $table->index('gender_target');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
