<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_number',
        'branch_id',
        'supplier_id',
        'total_cost',
        'status',
        'received_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
        'received_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($purchase) {
            throw new \LogicException('Purchases cannot be deleted.');
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
