<?php

namespace Database\Seeders;

use App\Models\ToothCondition;
use Illuminate\Database\Seeder;

class ToothConditionSeeder extends Seeder
{
    public function run(): void
    {
        $conditions = [
            ['code' => 'CARIES', 'name' => 'Caries'],
            ['code' => 'FILLING', 'name' => 'Filling'],
            ['code' => 'MISSING', 'name' => 'Missing'],
            ['code' => 'ROOT_CANAL', 'name' => 'Root Canal Treatment'],
            ['code' => 'CROWN', 'name' => 'Crown'],
            ['code' => 'EXTRACTION', 'name' => 'Extraction'],
            ['code' => 'IMPLANT', 'name' => 'Implant'],
            ['code' => 'IMPACTED', 'name' => 'Impacted'],
            ['code' => 'FRACTURE', 'name' => 'Fracture'],
            ['code' => 'BRIDGE', 'name' => 'Bridge'],
        ];

        foreach ($conditions as $condition) {
            ToothCondition::firstOrCreate(
                ['code' => $condition['code']],
                ['name' => $condition['name'], 'is_active' => true]
            );
        }
    }
}
