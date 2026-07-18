<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_medical_profiles', function (Blueprint $table) {
            $table->id();

            // Strict 1-to-1: a patient has exactly one medical profile.
            $table->foreignId('patient_id')
                ->unique()
                ->constrained('patients')
                ->cascadeOnDelete(); // real delete only — patient soft-delete never cascades

            $table->enum('blood_type', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])
                ->nullable();
            $table->text('allergies')->nullable();
            $table->text('medical_history')->nullable();
            $table->text('chronic_diseases')->nullable();
            $table->text('current_medications')->nullable();
            $table->text('notes')->nullable();

            // Forward-looking extensibility point: structured answers to a future
            // medical questionnaire (e.g. pregnancy, smoking, infectious disease flags)
            // without needing a schema migration for every new question.
            $table->json('questionnaire_answers')->nullable();

            $table->timestamps();
            // No softDeletes here on purpose: this row's lifecycle is fully owned by
            // its patient. A soft-deleted patient keeps this row automatically;
            // a hard-deleted patient cascades and removes it — there is no
            // independent "delete medical profile only" operation.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_medical_profiles');
    }
};
