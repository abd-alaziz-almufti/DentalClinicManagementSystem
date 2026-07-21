<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'inventory_item_id' => $this->inventory_item_id,
            'quantity'          => $this->quantity,
            'unit_cost'         => $this->unit_cost,
            'total_cost'        => $this->total_cost,
        ];
    }
}
