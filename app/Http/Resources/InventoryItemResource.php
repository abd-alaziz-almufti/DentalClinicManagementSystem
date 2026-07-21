<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'code'        => $this->code,
            'name'        => $this->name,
            'unit'        => $this->unit,
            'description' => $this->description,
            'is_active'   => $this->is_active,
            'stocks'      => $this->whenLoaded('stocks', function () {
                return $this->stocks->map(fn($s) => [
                    'branch_id'        => $s->branch_id,
                    'quantity_on_hand'  => $s->quantity_on_hand,
                    'reorder_level'    => $s->reorder_level,
                    'is_low_stock'     => $s->isLowStock(),
                ]);
            }),
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];
    }
}
