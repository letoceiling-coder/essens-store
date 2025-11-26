<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Beauty',
                'slug' => 'beauty',
            ],
            [
                'name' => 'Health',
                'slug' => 'health',
            ],
            [
                'name' => 'Home',
                'slug' => 'home',
            ],
            [
                'name' => 'Personal Care',
                'slug' => 'personal-care',
            ],
            [
                'name' => 'Supplements',
                'slug' => 'supplements',
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
