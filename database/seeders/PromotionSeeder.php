<?php

namespace Database\Seeders;

use App\Models\Promotion;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $promotions = [
            [
                'name' => 'Новогодняя распродажа',
                'description' => 'Скидки до 30% на все товары категории Beauty',
                'start_date' => Carbon::now()->subDays(5),
                'end_date' => Carbon::now()->addDays(25),
                'is_active' => true,
            ],
            [
                'name' => 'Летняя акция',
                'description' => 'Специальные цены на товары для здоровья',
                'start_date' => Carbon::now()->addDays(10),
                'end_date' => Carbon::now()->addDays(60),
                'is_active' => false,
            ],
            [
                'name' => 'Новинки недели',
                'description' => 'Скидка 15% на все новинки',
                'start_date' => Carbon::now()->subDays(2),
                'end_date' => Carbon::now()->addDays(5),
                'is_active' => true,
            ],
            [
                'name' => 'Детские товары',
                'description' => 'Специальные предложения для детей',
                'start_date' => Carbon::now()->subDays(1),
                'end_date' => Carbon::now()->addDays(30),
                'is_active' => true,
            ],
        ];

        foreach ($promotions as $promotion) {
            $createdPromotion = Promotion::create($promotion);

            // Привязываем товары к акциям
            $products = Product::all();

            if ($promotion['name'] === 'Новогодняя распродажа') {
                // Привязываем товары категории Beauty
                $beautyProducts = $products->filter(function ($product) {
                    return in_array('beauty', $product->tags ?? []);
                });
                $createdPromotion->products()->attach($beautyProducts->pluck('id')->toArray());
            } elseif ($promotion['name'] === 'Летняя акция') {
                // Привязываем товары для здоровья
                $healthProducts = $products->filter(function ($product) {
                    return in_array('здоровье', $product->tags ?? []);
                });
                $createdPromotion->products()->attach($healthProducts->pluck('id')->toArray());
            } elseif ($promotion['name'] === 'Новинки недели') {
                // Привязываем новинки
                $newProducts = $products->filter(function ($product) {
                    return in_array('новинка', $product->tags ?? []);
                });
                $createdPromotion->products()->attach($newProducts->pluck('id')->toArray());
            } elseif ($promotion['name'] === 'Детские товары') {
                // Привязываем товары для детей
                $childrenProducts = $products->filter(function ($product) {
                    return $product->gender_target === 'children' || in_array('дети', $product->tags ?? []);
                });
                if ($childrenProducts->count() === 0) {
                    // Если нет детских товаров, привязываем первые 3 товара
                    $createdPromotion->products()->attach($products->take(3)->pluck('id')->toArray());
                } else {
                    $createdPromotion->products()->attach($childrenProducts->pluck('id')->toArray());
                }
            }
        }
    }
}
