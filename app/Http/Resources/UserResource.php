<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'branch' => $this->branch ? [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
                'code' => $this->branch->code,
            ] : null,
            'doctor_profile' => $this->doctorProfile ? [
                'id' => $this->doctorProfile->id,
                'license_number' => $this->doctorProfile->license_number,
                'color' => $this->doctorProfile->color,
                'specialty' => $this->doctorProfile->specialty ? [
                    'id' => $this->doctorProfile->specialty->id,
                    'name' => $this->doctorProfile->specialty->name,
                    'code' => $this->doctorProfile->specialty->code,
                ] : null,
            ] : null,
            'roles' => $this->getRoleNames(),
        ];
    }
}
