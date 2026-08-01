<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Visit;

/**
 * Final role model (2026-07-22): super-admin, admin, doctor only.
 *
 * Two DIFFERENT permission questions live on this policy, and must stay
 * separate — this was the second confirmed bug (VisitServiceController /
 * DentalChartController were reusing update() for clinical writes,
 * incorrectly granting admin write access to diagnosis/treatment data):
 *
 *  - update(): general, non-clinical visit fields (e.g. correcting which
 *    branch a visit is logged under, administrative notes). admin and
 *    doctor are equal here.
 *  - recordClinicalData(): diagnosis, doctor's notes, treatment plan,
 *    visit_services, dental chart entries — per FR-018
 *    (004-http-api-layer/spec.md), this is DOCTOR-ONLY (their own visit)
 *    plus super-admin as an override. admin is explicitly excluded —
 *    read access only, enforced by NOT granting this ability, not by a
 *    separate read-only flag.
 */
class VisitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'doctor']);
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

        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Check-in. Both admin and doctor perform this themselves — no
        // separate receptionist role exists in this clinic.
        return $user->hasAnyRole(['super-admin', 'admin', 'doctor']);
    }

    public function update(User $user, Visit $visit): bool
    {
        return $this->view($user, $visit);
    }

    /**
     * FR-018: writing clinical/medical data is doctor-only (their own
     * visit) or super-admin. admin has read access via view()/update()
     * for administrative/billing purposes, but never this ability.
     */
    public function recordClinicalData(User $user, Visit $visit): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if (! $user->hasRole('doctor')) {
            return false;
        }

        return $visit->branch_id === $user->branch_id
            && $visit->doctor_profile_id === $user->doctorProfile?->id;
    }

    public function delete(User $user, Visit $visit): bool
    {
        // Visits are never hard-deleted (Article I) — kept for symmetry
        // with the other policies; not currently routed to.
        return $this->update($user, $visit);
    }
}
