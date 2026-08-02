<?php

namespace App\Services;

use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Creating a user is a business transaction, not a plain insert: it may
 * also require creating a DoctorProfile in the same breath (role =
 * 'doctor'), and must always assign exactly one Spatie role. Kept as an
 * explicit Service per Constitution Article III, consistent with every
 * other creation flow (PatientService::register(),
 * GenerateInvoiceService::generate(), ...).
 */
class CreateUserService
{
    /**
     * @param  array  $data  name, email, password, role, branch_id,
     *                       specialty_id? (required if role=doctor),
     *                       license_number?, color?
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'branch_id' => $data['branch_id'],
                'email_verified_at' => now(),
            ]);

            // syncRoles() with a single role — this endpoint never grants
            // 'super-admin' (see StoreUserRequest: role is restricted to
            // admin/doctor by validation, on purpose — privilege
            // escalation via a plain HTTP form is not acceptable; a
            // super-admin account is provisioned outside the API).
            $user->syncRoles([$data['role']]);

            if ($data['role'] === 'doctor') {
                DoctorProfile::create([
                    'user_id' => $user->id,
                    'specialty_id' => $data['specialty_id'],
                    'license_number' => $data['license_number'] ?? null,
                    'color' => $data['color'] ?? '#2563EB',
                ]);
            }

            return $user->load(['branch', 'doctorProfile.specialty', 'roles']);
        });
    }
}
