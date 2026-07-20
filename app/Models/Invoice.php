<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'visit_id',
        'patient_id',
        'branch_id',
        'total',
        'status',
        'issued_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($invoice) {
            throw new \LogicException('Invoices cannot be deleted.');
        });
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getRemainingBalanceAttribute(): float
    {
        $paymentsSum = (float) $this->payments()
            ->where('type', 'payment')
            ->sum('amount');

        $reversalsSum = (float) $this->payments()
            ->where('type', 'reversal')
            ->sum('amount');

        return round((float) $this->total - $paymentsSum + $reversalsSum, 2);
    }
}
