<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'invoice_id'          => $this->invoice_id,
            'type'                => $this->type,
            'reverses_payment_id' => $this->reverses_payment_id,
            'amount'              => $this->amount,
            'payment_method'      => $this->payment_method,
            'payment_date'        => $this->payment_date?->toDateString(),
            'notes'               => $this->notes,
            'created_at'          => $this->created_at?->toDateTimeString(),
        ];
    }
}
