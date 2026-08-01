<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'invoice_number'      => $this->invoice_number,
            'visit_id'            => $this->visit_id,
            'patient_id'          => $this->patient_id,
            'branch_id'           => $this->branch_id,
            'total'               => $this->total,
            'paid_amount'         => (string) number_format((float)$this->total - (float)$this->remaining_balance, 2, '.', ''),
            'remaining_balance'   => $this->remaining_balance,
            'status'              => $this->status,
            'cancelled_at'        => $this->cancelled_at?->toDateTimeString(),
            'cancellation_reason' => $this->cancellation_reason,
            'patient'             => new PatientResource($this->whenLoaded('patient')),
            'items'               => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments'            => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at'          => $this->created_at?->toDateTimeString(),
        ];
    }
}
