<?php

namespace Database\Seeders;

use App\Models\Specialty;
use Illuminate\Database\Seeder;

class SpecialtySeeder extends Seeder
{
    public function run(): void
    {
        $specialties = [
            ['code' => 'GEN', 'name' => 'General Dentist'],
            ['code' => 'ORTHO', 'name' => 'Orthodontist'],
            ['code' => 'ORAL_SURG', 'name' => 'Oral Surgeon'],
            ['code' => 'ENDO', 'name' => 'Endodontist'],
            ['code' => 'PEDO', 'name' => 'Pediatric Dentist'],
        ];

        foreach ($specialties as $specialty) {
            Specialty::firstOrCreate(
                ['code' => $specialty['code']],
                ['name' => $specialty['name'], 'is_active' => true]
            );
        }
    }
}
