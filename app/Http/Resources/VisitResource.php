<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'visit_number'     => $this->visit_number,
            'appointment_id'   => $this->appointment_id,
            'patient_id'       => $this->patient_id,
            'doctor_profile_id'=> $this->doctor_profile_id,
            'branch_id'        => $this->branch_id,
            'checked_in_at'    => $this->checked_in_at?->toDateTimeString(),
            'status'           => $this->status,
            'chief_complaint'  => $this->chief_complaint,
            'diagnosis'        => $this->diagnosis,
            'doctor_notes'     => $this->doctor_notes,
            'treatment_plan'   => $this->treatment_plan,
            'has_active_invoice' => $this->has_active_invoice,
            'patient'          => new PatientResource($this->whenLoaded('patient')),
            'services'         => VisitServiceResource::collection($this->whenLoaded('visitServices')),
            'teeth'            => VisitToothResource::collection($this->whenLoaded('visitTeeth')),
            'created_at'       => $this->created_at?->toDateTimeString(),
        ];
    }
}
