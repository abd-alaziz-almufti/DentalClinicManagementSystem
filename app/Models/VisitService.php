<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitService extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'service_id',
        'tooth_number',
        'quantity',
        'unit_price',
        'discount_amount',
        'total',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    // The CURRENT service definition (name/category/etc may have changed
    // since this row was created — this relation is for navigation only,
    // never for re-deriving the price).
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
