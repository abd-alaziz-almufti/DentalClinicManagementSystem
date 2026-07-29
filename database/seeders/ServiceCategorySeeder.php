<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'PREV', 'name' => 'Preventive'],
            ['code' => 'REST', 'name' => 'Restorative'],
            ['code' => 'SURG', 'name' => 'Surgery'],
            ['code' => 'COSM', 'name' => 'Cosmetic'],
        ];

        foreach ($categories as $category) {
            ServiceCategory::firstOrCreate(
                ['code' => $category['code']],
                ['name' => $category['name'], 'is_active' => true]
            );
        }
    }
}
