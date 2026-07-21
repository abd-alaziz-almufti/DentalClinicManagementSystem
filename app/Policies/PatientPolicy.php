<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'receptionist', 'doctor', 'accountant']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Patient $patient): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Must belong to the same branch
        if ($patient->registered_branch_id !== $user->branch_id) {
            return false;
        }

        if ($user->hasRole('doctor')) {
            $doctorId = $user->doctorProfile?->id;
            if (!$doctorId) {
                return false;
            }

            return $patient->appointments()->where('doctor_profile_id', $doctorId)->exists() ||
                   $patient->visits()->where('doctor_profile_id', $doctorId)->exists();
        }

        return $user->hasAnyRole(['admin', 'receptionist', 'accountant']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'receptionist']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Patient $patient): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($patient->registered_branch_id !== $user->branch_id) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'receptionist']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Patient $patient): bool
    {
        return $this->update($user, $patient);
    }
}
