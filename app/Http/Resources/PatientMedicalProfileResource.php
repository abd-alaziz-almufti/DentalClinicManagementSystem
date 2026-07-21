<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientMedicalProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'blood_type' => $this->blood_type,
            'allergies' => $this->allergies,
            'medical_history' => $this->medical_history,
            'chronic_diseases' => $this->chronic_diseases,
            'current_medications' => $this->current_medications,
            'notes' => $this->notes,
            'questionnaire_answers' => $this->questionnaire_answers,
        ];
    }
}
