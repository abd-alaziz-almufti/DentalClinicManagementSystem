<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'unit',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function consumptionTemplates(): HasMany
    {
        return $this->hasMany(ServiceInventoryConsumption::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }
}
