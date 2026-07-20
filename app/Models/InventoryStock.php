<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'branch_id',
        'quantity_on_hand',
        'reorder_level',
    ];

    protected $casts = [
        'quantity_on_hand' => 'decimal:2',
        'reorder_level' => 'decimal:2',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isLowStock(): bool
    {
        return (float) $this->quantity_on_hand <= (float) $this->reorder_level;
    }
}
