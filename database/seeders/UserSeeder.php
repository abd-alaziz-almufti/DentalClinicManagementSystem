<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DoctorProfile;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $mainBranch = Branch::where('code', 'MAIN')->firstOrFail();

        // --- Super Admin (global, no single branch) ---
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@clinic.test'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'), // CHANGE before any real use
                'branch_id' => null,
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->syncRoles(['super-admin']);
        $superAdmin->syncSuperAdminFlag();

        // --- Sample Doctor (belongs to main branch) ---
        $doctorUser = User::firstOrCreate(
            ['email' => 'doctor@clinic.test'],
            [
                'name' => 'Dr. Sample Doctor',
                'password' => Hash::make('password'), // CHANGE before any real use
                'branch_id' => $mainBranch->id,
                'email_verified_at' => now(),
            ]
        );
        $doctorUser->syncRoles(['doctor']);
        $doctorUser->syncSuperAdminFlag(); // will be false — confirms the flag logic works both ways

        $generalDentist = Specialty::where('code', 'GEN')->firstOrFail();

        DoctorProfile::firstOrCreate(
            ['user_id' => $doctorUser->id],
            [
                'specialty_id' => $generalDentist->id,
                'license_number' => 'LIC-00001',
                'color' => '#2563EB',
            ]
        );
    }
}
