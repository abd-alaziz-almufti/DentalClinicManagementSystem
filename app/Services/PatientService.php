<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Patient;
use App\Models\PatientMedicalProfile;
use Illuminate\Support\Facades\DB;

class PatientService
{
    private const TYPE = 'patient';
    private const PREFIX = 'PAT';

    public function __construct(
        private readonly DocumentNumberGenerator $numberGenerator,
    ) {
    }

    /**
     * Create a patient + its (initially empty) medical profile atomically.
     *
     * @param  array  $data  Validated patient fields (first_name, last_name,
     *                       gender, birth_date, phone, ... registered_branch_id, created_by)
     */
    public function register(array $data): Patient
    {
        return DB::transaction(function () use ($data) {
            $branch = Branch::findOrFail($data['registered_branch_id']);

            $patient = Patient::create([
                ...$data,
                'patient_number' => $this->numberGenerator->generate($branch, self::TYPE, self::PREFIX),
            ]);

            // Always create the medical profile row up front (even if empty),
            // so every patient consistently has one to update later —
            // avoids "does this patient have a profile yet?" null-checks
            // scattered across the codebase.
            PatientMedicalProfile::create([
                'patient_id' => $patient->id,
            ]);

            return $patient->load('medicalProfile');
        });
    }
}
