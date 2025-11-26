<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class SubcategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $beauty = Category::where('slug', 'beauty')->first();
        $health = Category::where('slug', 'health')->first();
        $home = Category::where('slug', 'home')->first();
        $personalCare = Category::where('slug', 'personal-care')->first();
        $supplements = Category::where('slug', 'supplements')->first();

        $subcategories = [
            // Beauty subcategories
            [
                'category_id' => $beauty->id,
                'name' => 'Aloe Vera',
                'slug' => 'aloe-vera',
            ],
            [
                'category_id' => $beauty->id,
                'name' => 'Colostrum',
                'slug' => 'colostrum',
            ],
            [
                'category_id' => $beauty->id,
                'name' => 'Must Have Edition',
                'slug' => 'must-have-edition',
            ],
            [
                'category_id' => $beauty->id,
                'name' => 'Perfumes',
                'slug' => 'perfumes',
            ],
            [
                'category_id' => $beauty->id,
                'name' => 'Creams',
                'slug' => 'creams',
            ],

            // Health subcategories
            [
                'category_id' => $health->id,
                'name' => 'Vitamins',
                'slug' => 'vitamins',
            ],
            [
                'category_id' => $health->id,
                'name' => 'Immune Support',
                'slug' => 'immune-support',
            ],
            [
                'category_id' => $health->id,
                'name' => 'Digestive Health',
                'slug' => 'digestive-health',
            ],

            // Home subcategories
            [
                'category_id' => $home->id,
                'name' => 'Cleaning Products',
                'slug' => 'cleaning-products',
            ],
            [
                'category_id' => $home->id,
                'name' => 'Air Fresheners',
                'slug' => 'air-fresheners',
            ],

            // Personal Care subcategories
            [
                'category_id' => $personalCare->id,
                'name' => 'Body Care',
                'slug' => 'body-care',
            ],
            [
                'category_id' => $personalCare->id,
                'name' => 'Hair Care',
                'slug' => 'hair-care',
            ],
            [
                'category_id' => $personalCare->id,
                'name' => 'Face Care',
                'slug' => 'face-care',
            ],

            // Supplements subcategories
            [
                'category_id' => $supplements->id,
                'name' => 'Protein',
                'slug' => 'protein',
            ],
            [
                'category_id' => $supplements->id,
                'name' => 'Omega-3',
                'slug' => 'omega-3',
            ],
        ];

        foreach ($subcategories as $subcategory) {
            Subcategory::create($subcategory);
        }
    }
}
