<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

/**
 * Final role model (2026-07-22): super-admin, admin, doctor only.
 * admin and doctor are operationally equal for Patients (both register
 * and manage patients) — the only distinction anywhere in the system is
 * clinical/medical data writes (see VisitPolicy::recordClinicalData()),
 * which does not apply here since Patient demographic fields are not
 * clinical data.
 */
class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'doctor']);
    }

    public function view(User $user, Patient $patient): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($patient->registered_branch_id !== $user->branch_id) {
            return false;
        }

        if ($user->hasRole('doctor')) {
            $doctorProfileId = $user->doctorProfile?->id;

            return $doctorProfileId !== null && (
                $patient->appointments()->where('doctor_profile_id', $doctorProfileId)->exists()
                || $patient->visits()->where('doctor_profile_id', $doctorProfileId)->exists()
            );
        }

        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Both admin and doctor register patients themselves — no
        // separate receptionist role exists in this clinic.
        return $user->hasAnyRole(['super-admin', 'admin', 'doctor']);
    }

    public function update(User $user, Patient $patient): bool
    {
        // Demographic edits only (name, phone, address) — not medical
        // history, which lives on PatientMedicalProfile and is editable
        // by the same rule (non-clinical, so no extra restriction here).
        return $this->view($user, $patient);
    }

    public function delete(User $user, Patient $patient): bool
    {
        // Soft-delete only (Constitution Article I) — same access as update.
        return $this->view($user, $patient);
    }
}
