<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientMedicalProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'blood_type',
        'allergies',
        'medical_history',
        'chronic_diseases',
        'current_medications',
        'notes',
        'questionnaire_answers',
    ];

    protected $casts = [
        'questionnaire_answers' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
