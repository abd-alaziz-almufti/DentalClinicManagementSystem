<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'purchase_number' => $this->purchase_number,
            'branch_id'       => $this->branch_id,
            'supplier_id'     => $this->supplier_id,
            'total_cost'      => $this->total_cost,
            'status'          => $this->status,
            'received_at'     => $this->received_at?->toDateTimeString(),
            'notes'           => $this->notes,
            'items'           => PurchaseItemResource::collection($this->whenLoaded('items')),
            'created_at'      => $this->created_at?->toDateTimeString(),
        ];
    }
}
