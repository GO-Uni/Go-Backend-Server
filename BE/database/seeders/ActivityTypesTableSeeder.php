<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ActivityType;

class ActivityTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $activityTypes = [
            ['name' => 'save'],
            ['name' => 'rate'],
            ['name' => 'review'],
        ];

        foreach ($activityTypes as $type) {
            ActivityType::create($type);
        }
    }
}
