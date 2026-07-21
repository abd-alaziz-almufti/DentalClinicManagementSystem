<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'visit_id' => $this->visit_id,
            'service_id' => $this->service_id,
            'tooth_number' => $this->tooth_number,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'discount_amount' => $this->discount_amount,
            'total' => $this->total,
            'notes' => $this->notes,
            'service' => $this->relationLoaded('service') ? [
                'id' => $this->service->id,
                'name' => $this->service->name,
                'code' => $this->service->code,
            ] : null,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
