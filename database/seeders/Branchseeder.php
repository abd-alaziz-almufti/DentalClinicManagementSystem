<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::firstOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Dental Clinic - Main Branch',
                'phone' => '+970-000-0000',
                'email' => 'info@clinic.test',
                'address' => 'Nablus',
                'city' => 'Nablus',
                'is_active' => true,
            ]
        );
    }
}
