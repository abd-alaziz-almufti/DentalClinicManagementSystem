<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'branch_id',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'item_name_snapshot',
        'item_unit_snapshot',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($transaction) {
            throw new \LogicException('Inventory transactions cannot be updated.');
        });

        static::deleting(function ($transaction) {
            throw new \LogicException('Inventory transactions cannot be deleted.');
        });
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
