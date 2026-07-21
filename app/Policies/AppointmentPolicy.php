<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'receptionist', 'doctor', 'accountant']);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($appointment->branch_id !== $user->branch_id) {
            return false;
        }

        if ($user->hasRole('doctor')) {
            return $appointment->doctor_profile_id === $user->doctorProfile?->id;
        }

        return $user->hasAnyRole(['admin', 'receptionist', 'accountant']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'receptionist']);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($appointment->branch_id !== $user->branch_id) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'receptionist']);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($appointment->branch_id !== $user->branch_id) {
            return false;
        }

        if ($user->hasRole('doctor')) {
            return $appointment->doctor_profile_id === $user->doctorProfile?->id;
        }

        return $user->hasAnyRole(['admin', 'receptionist']);
    }
}
