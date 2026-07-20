<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'visit_service_id',
        'service_id',
        'service_name',
        'tooth_number',
        'quantity',
        'unit_price',
        'discount_amount',
        'total',
    ];

    protected $casts = [
        'service_name' => 'array',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($item) {
            throw new \LogicException('Invoice items cannot be updated.');
        });

        static::deleting(function ($item) {
            throw new \LogicException('Invoice items cannot be deleted.');
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function visitService(): BelongsTo
    {
        return $this->belongsTo(VisitService::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
