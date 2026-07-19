<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();

            $table->string('visit_number', 40)->unique();

            // Strict 1-to-1: an appointment produces at most one visit.
            $table->foreignId('appointment_id')
                ->unique()
                ->constrained('appointments')
                ->restrictOnDelete();

            // Controlled denormalization: copied from the appointment AT
            // CHECK-IN TIME and never updated afterward, even if the
            // appointment is edited later. A visit is a snapshot of who
            // was treated, by whom, and where — not a live view through
            // the appointment. This mirrors visit_services.price, which
            // snapshots the service price at time of use for the same reason.
            $table->foreignId('patient_id')
                ->constrained('patients')
                ->restrictOnDelete();
            $table->foreignId('doctor_profile_id')
                ->constrained('doctor_profiles')
                ->restrictOnDelete();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->dateTime('checked_in_at');

            // open:        just checked in, doctor hasn't finished yet
            // in_progress: doctor actively recording the visit
            // completed:   diagnosis/treatment recorded, ready for invoicing
            // cancelled:   visit started but aborted (e.g. patient left) —
            //              kept for the record, never hard-deleted
            $table->enum('status', ['open', 'in_progress', 'completed', 'cancelled'])
                ->default('open');

            $table->text('chief_complaint')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('doctor_notes')->nullable();
            $table->text('treatment_plan')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            // No softDeletes: a visit is a medical record. "Removing" one
            // logically is done via status = cancelled, never a real delete.

            $table->index(['patient_id', 'created_at']);
            $table->index(['branch_id', 'created_at']);
            $table->index(['doctor_profile_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
