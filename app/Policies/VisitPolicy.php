<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Visit;

class VisitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'receptionist', 'doctor', 'accountant']);
    }

    public function view(User $user, Visit $visit): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($visit->branch_id !== $user->branch_id) {
            return false;
        }

        if ($user->hasRole('doctor')) {
            return $visit->doctor_profile_id === $user->doctorProfile?->id;
        }

        return $user->hasAnyRole(['admin', 'receptionist', 'accountant']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'receptionist']);
    }

    public function update(User $user, Visit $visit): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($visit->branch_id !== $user->branch_id) {
            return false;
        }

        if ($user->hasRole('doctor')) {
            return $visit->doctor_profile_id === $user->doctorProfile?->id;
        }

        return $user->hasAnyRole(['admin', 'receptionist']);
    }

    public function delete(User $user, Visit $visit): bool
    {
        return $this->update($user, $visit);
    }
}
