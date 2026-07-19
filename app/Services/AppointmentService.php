<?php

namespace App\Services;

use App\Exceptions\AppointmentConflictException;
use App\Models\Appointment;
use App\Models\DoctorProfile;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    /**
     * Book an appointment for a doctor, guaranteeing no time-overlap with
     * that doctor's existing schedule — safe under real concurrency.
     *
     * Locking strategy: we lock the DOCTOR row itself (a "proxy lock"), not
     * the search results of the conflict query. If we only locked the rows
     * returned by the overlap check, two concurrent requests booking the
     * SAME free slot would both see zero conflicting rows (nothing to lock)
     * and both would insert successfully — the exact race we're preventing.
     * Locking the doctor row first forces every booking attempt for that
     * doctor to be processed one at a time.
     *
     * @param  array  $data  branch_id, doctor_profile_id, patient_id,
     *                       appointment_date, start_time, end_time,
     *                       reason, notes, created_by
     *
     * @throws AppointmentConflictException
     */
    public function book(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            // Step 1: proxy lock — serializes all booking attempts for this doctor.
            DoctorProfile::query()
                ->whereKey($data['doctor_profile_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Step 2: now that we're the only writer for this doctor, check
            // for overlap. Half-open interval: [start, end) — a new
            // appointment starting exactly when another ends is allowed.
            $conflict = Appointment::query()
                ->where('doctor_profile_id', $data['doctor_profile_id'])
                ->where('appointment_date', $data['appointment_date'])
                ->whereIn('status', Appointment::BLOCKING_STATUSES)
                ->where('start_time', '<', $data['end_time'])
                ->where('end_time', '>', $data['start_time'])
                ->exists();

            if ($conflict) {
                throw AppointmentConflictException::forSlot(
                    $data['appointment_date'],
                    $data['start_time'],
                    $data['end_time'],
                );
            }

            // Step 3: safe to insert — no concurrent request could have
            // slipped in between the check and this insert, because they're
            // all waiting on the doctor-row lock from Step 1 until we commit.
            return Appointment::create([
                ...$data,
                'status' => 'scheduled',
            ]);
        });
    }

    /**
     * NOTE: there is no markAttended() here. Marking an appointment as
     * attended is not a standalone status change — it always creates a
     * Visit as part of the same business transaction. See
     * CheckInPatientService::checkIn() instead.
     */

    public function markNoShow(Appointment $appointment): Appointment
    {
        $appointment->update(['status' => 'no_show']);

        return $appointment;
    }

    public function cancel(Appointment $appointment): Appointment
    {
        $appointment->update(['status' => 'cancelled']);

        return $appointment;
    }
}
