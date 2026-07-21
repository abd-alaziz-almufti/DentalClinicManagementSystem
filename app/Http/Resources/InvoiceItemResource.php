<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'invoice_id'      => $this->invoice_id,
            'visit_service_id'=> $this->visit_service_id,
            'service_id'      => $this->service_id,
            'service_name'    => $this->service_name,
            'tooth_number'    => $this->tooth_number,
            'quantity'        => $this->quantity,
            'unit_price'      => $this->unit_price,
            'discount_amount' => $this->discount_amount,
            'total'           => $this->total,
        ];
    }
}
