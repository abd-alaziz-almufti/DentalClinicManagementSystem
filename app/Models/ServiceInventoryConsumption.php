<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceInventoryConsumption extends Model
{
    use HasFactory;

    protected $table = 'service_inventory_consumption';

    protected $fillable = [
        'service_id',
        'inventory_item_id',
        'quantity_per_service',
    ];

    protected $casts = [
        'quantity_per_service' => 'decimal:2',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
