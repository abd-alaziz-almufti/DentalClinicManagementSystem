<?php

namespace App\Services;

use App\Exceptions\InvalidAppointmentStatusException;
use App\Models\Appointment;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

/**
 * The Check-In use case: turning a scheduled Appointment into an active
 * Visit. This is one business transaction, not "change a status" + a
 * separate "create a record" — the appointment status flip and the Visit
 * creation must succeed or fail together.
 *
 * Deliberately NOT event-driven: creating the Visit is core to this
 * operation, not a side effect of it. Genuine side effects (notifications,
 * audit logging, queued jobs) belong in a listener on a VisitCreated event
 * fired AFTER this transaction commits — not implemented yet, add only
 * when an actual side effect is needed.
 */
class CheckInPatientService
{
    private const TYPE = 'visit';
    private const PREFIX = 'VIS';

    public function __construct(
        private readonly DocumentNumberGenerator $numberGenerator,
    ) {
    }

    /**
     * @param  int|null  $checkedInBy  user id of the receptionist performing check-in
     *
     * @throws InvalidAppointmentStatusException
     */
    public function checkIn(Appointment $appointment, ?int $checkedInBy = null): Visit
    {
        return DB::transaction(function () use ($appointment, $checkedInBy) {
            // Lock the appointment row itself — unlike booking (where the
            // row didn't exist yet and we had to lock the doctor as a
            // proxy), here the row already exists, so a direct lock is
            // correct and sufficient. This prevents two simultaneous
            // "check in" clicks on the same appointment from both succeeding.
            $lockedAppointment = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAppointment->status !== 'scheduled') {
                throw InvalidAppointmentStatusException::cannotCheckIn($lockedAppointment->status);
            }

            $lockedAppointment->update(['status' => 'attended']);

            $branch = $lockedAppointment->branch;

            $visit = Visit::create([
                'visit_number' => $this->numberGenerator->generate($branch, self::TYPE, self::PREFIX),
                'appointment_id' => $lockedAppointment->id,
                'patient_id' => $lockedAppointment->patient_id,
                'doctor_profile_id' => $lockedAppointment->doctor_profile_id,
                'branch_id' => $lockedAppointment->branch_id,
                'checked_in_at' => now(),
                'status' => 'open',
                'created_by' => $checkedInBy,
            ]);

            // TODO once real side effects exist:
            // event(new VisitCreated($visit));

            return $visit;
        });
    }
}
