<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['code' => 'SRV001', 'category' => 'PREV', 'name' => 'Teeth Cleaning', 'price' => 20, 'duration' => 30],
            ['code' => 'SRV002', 'category' => 'REST', 'name' => 'Filling', 'price' => 35, 'duration' => 45],
            ['code' => 'SRV003', 'category' => 'SURG', 'name' => 'Extraction', 'price' => 40, 'duration' => 30],
            ['code' => 'SRV004', 'category' => 'REST', 'name' => 'Root Canal Treatment', 'price' => 120, 'duration' => 90],
            ['code' => 'SRV005', 'category' => 'PREV', 'name' => 'X-Ray', 'price' => 15, 'duration' => 15],
            ['code' => 'SRV006', 'category' => 'COSM', 'name' => 'Teeth Whitening', 'price' => 90, 'duration' => 60],
        ];

        foreach ($services as $service) {
            $categoryId = ServiceCategory::where('code', $service['category'])->value('id');

            Service::firstOrCreate(
                ['code' => $service['code']],
                [
                    'service_category_id' => $categoryId,
                    'name' => $service['name'],
                    'default_price' => $service['price'],
                    'duration' => $service['duration'],
                    'is_active' => true,
                ]
            );
        }
    }
}
