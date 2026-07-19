<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            // Where the service is actually delivered — deliberately independent
            // from patients.registered_branch_id (see design discussion).
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->foreignId('doctor_profile_id')
                ->constrained('doctor_profiles')
                ->restrictOnDelete();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->restrictOnDelete(); // never cascade-delete appointment history

            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');

            $table->string('reason', 255)->nullable();
            $table->text('notes')->nullable();

            // scheduled: booked, awaiting the visit date
            // attended:  patient showed up -> a Visit was created for this appointment
            // no_show:   patient did not show up, no Visit created
            // cancelled: cancelled before the date, frees up the slot
            //
            // No soft-delete on this table on purpose: an appointment's history
            // (including cancellations/no-shows) has reporting value and must
            // never disappear — status is how we "remove" an appointment logically.
            $table->enum('status', ['scheduled', 'attended', 'no_show', 'cancelled'])
                ->default('scheduled');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Serves the conflict-check query directly: filter by doctor, then
            // date, then status, before evaluating the time-overlap condition.
            $table->index(['doctor_profile_id', 'appointment_date', 'status']);

            // Patient's appointment history / upcoming appointments lookup.
            $table->index(['patient_id', 'appointment_date']);

            // Branch's daily schedule view.
            $table->index(['branch_id', 'appointment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
