<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;

class ProductImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();

        // Placeholder images URLs (в реальном проекте это будут реальные URL из медиа-библиотеки)
        $placeholderImages = [
            'https://via.placeholder.com/400x400?text=Product+Image+1',
            'https://via.placeholder.com/400x400?text=Product+Image+2',
            'https://via.placeholder.com/400x400?text=Product+Image+3',
        ];

        foreach ($products as $index => $product) {
            // Добавляем основное изображение
            ProductImage::create([
                'product_id' => $product->id,
                'url' => $placeholderImages[0],
                'order' => 0,
                'is_primary' => true,
            ]);

            // Добавляем дополнительные изображения для некоторых товаров
            if ($index % 2 === 0) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => $placeholderImages[1],
                    'order' => 1,
                    'is_primary' => false,
                ]);
            }

            if ($index % 3 === 0) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => $placeholderImages[2],
                    'order' => 2,
                    'is_primary' => false,
                ]);
            }
        }
    }
}
