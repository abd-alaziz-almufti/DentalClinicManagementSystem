<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BranchSeeder::class,
            SpecialtySeeder::class,
            ServiceCategorySeeder::class,
            ServiceSeeder::class,
            TeethSeeder::class,          // reference data — order-independent, grouped with other lookups
            ToothConditionSeeder::class,
            ToothSurfaceSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class, // must run AFTER Branch, Specialty, RolePermission
        ]);
    }
}
