<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'doctor_profile_id' => $this->doctor_profile_id,
            'patient_id' => $this->patient_id,
            'appointment_date' => $this->appointment_date?->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'status' => $this->status,
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'doctor' => $this->whenLoaded('doctorProfile') ? [
                'id' => $this->doctorProfile->id,
                'license_number' => $this->doctorProfile->license_number,
                'user' => [
                    'id' => $this->doctorProfile->user?->id,
                    'name' => $this->doctorProfile->user?->name,
                ],
            ] : null,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
