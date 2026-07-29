<?php

namespace Database\Seeders;

use App\Models\ToothSurface;
use Illuminate\Database\Seeder;

class ToothSurfaceSeeder extends Seeder
{
    public function run(): void
    {
        $surfaces = [
            ['code' => 'M', 'name' => 'Mesial'],
            ['code' => 'D', 'name' => 'Distal'],
            ['code' => 'O', 'name' => 'Occlusal'],
            ['code' => 'B', 'name' => 'Buccal'],
            ['code' => 'L', 'name' => 'Lingual'],
            ['code' => 'I', 'name' => 'Incisal'],
        ];

        foreach ($surfaces as $surface) {
            ToothSurface::firstOrCreate(
                ['code' => $surface['code']],
                ['name' => $surface['name'], 'is_active' => true]
            );
        }
    }
}
