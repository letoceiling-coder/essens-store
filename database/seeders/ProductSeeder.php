<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subcategories = Subcategory::all();

        $products = [
            // Beauty products
            [
                'subcategory_id' => $subcategories->where('slug', 'aloe-vera')->first()->id,
                'name' => 'Алоэ Вера Гель 100 мл',
                'sku' => 'm041',
                'type' => 'cream',
                'gender_target' => 'unisex',
                'volume' => '100 мл',
                'price' => 1290.00,
                'currency' => 'RUB',
                'in_stock' => true,
                'stock_qty' => 50,
                'description' => 'Натуральный гель алоэ вера для увлажнения и ухода за кожей. Подходит для всех типов кожи.',
                'tags' => ['новинка', 'beauty', 'натуральный'],
            ],
            [
                'subcategory_id' => $subcategories->where('slug', 'perfumes')->first()->id,
                'name' => 'Парфюм Essens Classic 50 мл',
                'sku' => 'w184',
                'type' => 'perfume',
                'gender_target' => 'unisex',
                'volume' => '50 мл',
                'price' => 2990.00,
                'currency' => 'RUB',
                'in_stock' => true,
                'stock_qty' => 30,
                'description' => 'Классический парфюм с изысканным ароматом. Долговременная стойкость.',
                'tags' => ['sale', 'beauty'],
            ],
            [
                'subcategory_id' => $subcategories->where('slug', 'creams')->first()->id,
                'name' => 'Увлажняющий крем для лица 50 мл',
                'sku' => 'c205',
                'type' => 'cream',
                'gender_target' => 'unisex',
                'volume' => '50 мл',
                'price' => 1890.00,
                'currency' => 'RUB',
                'in_stock' => true,
                'stock_qty' => 40,
                'description' => 'Интенсивно увлажняющий крем для всех типов кожи. Содержит гиалуроновую кислоту.',
                'tags' => ['beauty', 'увлажнение'],
            ],
            [
                'subcategory_id' => $subcategories->where('slug', 'colostrum')->first()->id,
                'name' => 'Колострум в капсулах 60 шт',
                'sku' => 'col001',
                'type' => 'supplement',
                'gender_target' => 'unisex',
                'volume' => '60 капсул',
                'price' => 2490.00,
                'currency' => 'RUB',
                'in_stock' => true,
                'stock_qty' => 25,
                'description' => 'Натуральный колострум для поддержки иммунитета. Высокое содержание иммуноглобулинов.',
                'tags' => ['здоровье', 'иммунитет', 'новинка'],
            ],

            // Health products
            [
                'subcategory_id' => $subcategories->where('slug', 'vitamins')->first()->id,
                'name' => 'Витамин D3 2000 МЕ 60 капсул',
                'sku' => 'vit001',
                'type' => 'supplement',
                'gender_target' => 'unisex',
                'volume' => '60 капсул',
                'price' => 890.00,
                'currency' => 'RUB',
                'in_stock' => true,
                'stock_qty' => 100,
                'description' => 'Витамин D3 для поддержки костной системы и иммунитета.',
                'tags' => ['здоровье', 'витамины'],
            ],
            [
                'subcategory_id' => $subcategories->where('slug', 'immune-support')->first()->id,
                'name' => 'Иммуномодулятор на основе эхинацеи',
                'sku' => 'imm001',
                'type' => 'supplement',
                'gender_target' => 'unisex',
                'volume' => '30 таблеток',
                'price' => 1290.00,
                'currency' => 'RUB',
                'in_stock' => true,
                'stock_qty' => 60,
                'description' => 'Натуральный иммуномодулятор для укрепления защитных сил организма.',
                'tags' => ['здоровье', 'иммунитет'],
            ],

            // Home products
            [
                'subcategory_id' => $subcategories->where('slug', 'cleaning-products')->first()->id,
                'name' => 'Универсальное чистящее средство 500 мл',
                'sku' => 'clean001',
                'type' => 'cleaning',
                'gender_target' => null,
                'volume' => '500 мл',
                'price' => 490.00,
                'currency' => 'RUB',
                'in_stock' => true,
                'stock_qty' => 80,
                'description' => 'Экологичное чистящее средство для всех поверхностей. Безопасно для детей и животных.',
                'tags' => ['дом', 'экологично'],
            ],
            [
                'subcategory_id' => $subcategories->where('slug', 'air-fresheners')->first()->id,
                'name' => 'Освежитель воздуха "Лаванда" 250 мл',
                'sku' => 'air001',
                'type' => 'spray',
                'gender_target' => null,
                'volume' => '250 мл',
                'price' => 390.00,
                'currency' => 'RUB',
                'in_stock' => true,
                'stock_qty' => 70,
                'description' => 'Натуральный освежитель воздуха с ароматом лаванды.',
                'tags' => ['дом', 'аромат'],
            ],

            // Personal Care products
            [
                'subcategory_id' => $subcategories->where('slug', 'body-care')->first()->id,
                'name' => 'Гель для душа "Морская свежесть" 250 мл',
                'sku' => 'body001',
                'type' => 'cleaning',
                'gender_target' => 'unisex',
                'volume' => '250 мл',
                'price' => 590.00,
                'currency' => 'RUB',
                'in_stock' => true,
                'stock_qty' => 90,
                'description' => 'Очищающий гель для душа с освежающим ароматом.',
                'tags' => ['личная гигиена', 'beauty'],
            ],
            [
                'subcategory_id' => $subcategories->where('slug', 'hair-care')->first()->id,
                'name' => 'Шампунь для всех типов волос 300 мл',
                'sku' => 'hair001',
                'type' => 'cleaning',
                'gender_target' => 'unisex',
                'volume' => '300 мл',
                'price' => 690.00,
                'currency' => 'RUB',
                'in_stock' => true,
                'stock_qty' => 55,
                'description' => 'Универсальный шампунь для ежедневного использования.',
                'tags' => ['beauty', 'волосы'],
            ],

            // Supplements products
            [
                'subcategory_id' => $subcategories->where('slug', 'protein')->first()->id,
                'name' => 'Протеиновый коктейль "Ваниль" 500 г',
                'sku' => 'prot001',
                'type' => 'supplement',
                'gender_target' => 'unisex',
                'volume' => '500 г',
                'price' => 1990.00,
                'currency' => 'RUB',
                'in_stock' => true,
                'stock_qty' => 35,
                'description' => 'Высококачественный протеиновый порошок с ванильным вкусом.',
                'tags' => ['спорт', 'белок'],
            ],
            [
                'subcategory_id' => $subcategories->where('slug', 'omega-3')->first()->id,
                'name' => 'Омега-3 капсулы 60 шт',
                'sku' => 'omg001',
                'type' => 'supplement',
                'gender_target' => 'unisex',
                'volume' => '60 капсул',
                'price' => 1490.00,
                'currency' => 'RUB',
                'in_stock' => true,
                'stock_qty' => 45,
                'description' => 'Высококонцентрированные капсулы Омега-3 для поддержки сердечно-сосудистой системы.',
                'tags' => ['здоровье', 'омега-3'],
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
