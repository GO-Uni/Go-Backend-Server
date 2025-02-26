<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesTableSeeder::class,
            CategoriesTableSeeder::class,
            AdminUserSeeder::class,
            ActivityTypesTableSeeder::class,
        ]);
    }
}

/**
 * To run the seeders, execute the following command:
 * php artisan db:seed
 */
