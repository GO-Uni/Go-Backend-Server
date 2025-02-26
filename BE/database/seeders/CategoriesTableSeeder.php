<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Restaurant'],
            ['name' => 'Hotel'],
            ['name' => 'Shopping Mall'],
            ['name' => 'Entertainment'],
            ['name' => 'Historical Landmarks'],
            ['name' => 'Natural Wonders'],
            ['name' => 'Sports'],
            ['name' => 'Aquatic Destinations'],
            ['name' => 'Night Life'],
            ['name' => 'Outdoor Activities'],
            
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
