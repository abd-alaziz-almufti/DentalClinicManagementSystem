<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // super-admin: sees ALL branches. admin: scoped to their own branch
        // (branch scoping itself is enforced in policies/controllers, not here).
        $roles = [
            'super-admin',
            'admin',
            'doctor',
            'receptionist',
            'accountant',
            'inventory-manager',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Permissions can be added incrementally per module as it's built, e.g.:
        // Permission::firstOrCreate(['name' => 'patients.view']);
        // Permission::firstOrCreate(['name' => 'patients.create']);
        // then: Role::findByName('receptionist')->givePermissionTo(['patients.view', 'patients.create']);
        // Left empty for now — will fill in as each module (Patients, Appointments...) is designed.
    }
}
