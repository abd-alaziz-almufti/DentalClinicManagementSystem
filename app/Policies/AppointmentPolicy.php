<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

/**
 * Final role model (2026-07-22): super-admin, admin, doctor only.
 * Doctors book and manage their own appointments themselves — there is
 * no separate receptionist role. Fixed bug: create() previously checked
 * for the now-nonexistent 'receptionist' role and omitted 'doctor'
 * entirely, silently blocking every doctor from booking appointments.
 */
class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'doctor']);
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

        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'doctor']);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        // Reschedule / edit reason / notes.
        return $this->view($user, $appointment);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        // Maps to cancel (status transition, never a hard delete — Article I).
        return $this->view($user, $appointment);
    }
}
