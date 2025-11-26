<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductVariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();

        foreach ($products as $product) {
            // Добавляем варианты только для товаров с типом perfume, cream, spray
            if (in_array($product->type, ['perfume', 'cream', 'spray'])) {
                // Вариант 1: Малый объем
                ProductVariant::create([
                    'product_id' => $product->id,
                    'variant_name' => '10 мл',
                    'price' => $product->price * 0.3, // 30% от основной цены
                    'stock_qty' => 20,
                ]);

                // Вариант 2: Средний объем (основной)
                ProductVariant::create([
                    'product_id' => $product->id,
                    'variant_name' => $product->volume ?? '50 мл',
                    'price' => $product->price,
                    'stock_qty' => $product->stock_qty ?? 30,
                ]);

                // Вариант 3: Большой объем
                if ($product->type === 'perfume' || $product->type === 'cream') {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'variant_name' => '100 мл',
                        'price' => $product->price * 1.8, // 180% от основной цены
                        'stock_qty' => 15,
                    ]);
                }
            }

            // Для supplement товаров добавляем варианты упаковки
            if ($product->type === 'supplement') {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'variant_name' => '30 шт',
                    'price' => $product->price * 0.6,
                    'stock_qty' => 25,
                ]);

                ProductVariant::create([
                    'product_id' => $product->id,
                    'variant_name' => $product->volume ?? '60 шт',
                    'price' => $product->price,
                    'stock_qty' => $product->stock_qty ?? 40,
                ]);

                ProductVariant::create([
                    'product_id' => $product->id,
                    'variant_name' => '120 шт',
                    'price' => $product->price * 1.8,
                    'stock_qty' => 20,
                ]);
            }
        }
    }
}
