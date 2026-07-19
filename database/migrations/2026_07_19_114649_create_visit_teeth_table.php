<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_teeth', function (Blueprint $table) {
            $table->id();

            $table->foreignId('visit_id')
                ->constrained('visits')
                ->restrictOnDelete();

            // Controlled denormalization (same reasoning as visits.patient_id):
            // lets us query a patient's full dental history across ALL visits
            // with a direct index, without joining through visits every time.
            $table->foreignId('patient_id')
                ->constrained('patients')
                ->restrictOnDelete();

            $table->foreignId('tooth_id')
                ->constrained('teeth')
                ->restrictOnDelete();

            $table->foreignId('tooth_condition_id')
                ->constrained('tooth_conditions')
                ->restrictOnDelete();

            // Nullable: not every condition is surface-specific
            // (e.g. "missing", "extraction" apply to the whole tooth).
            $table->foreignId('tooth_surface_id')
                ->nullable()
                ->constrained('tooth_surfaces')
                ->nullOnDelete();

            // Nullable: links this chart entry to the billed treatment line,
            // when one exists. A diagnostic note (e.g. "caries observed")
            // may have no billable service attached to it yet.
            $table->foreignId('visit_service_id')
                ->nullable()
                ->constrained('visit_services')
                ->nullOnDelete();

            // diagnosis:  a finding recorded during examination
            // treatment:  a procedure actually performed
            // This distinction is what V2's "current tooth state" logic
            // will be built on, without any schema change.
            $table->enum('entry_type', ['diagnosis', 'treatment']);

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            // No softDeletes: mutability governed by the parent Visit's
            // status (see VisitEditabilityGuard), same rule as visit_services.

            $table->index(['patient_id', 'tooth_id']); // full history of one tooth
            $table->index('visit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_teeth');
    }
};
