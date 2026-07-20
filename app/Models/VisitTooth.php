<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitTooth extends Model
{
    use HasFactory;

    protected $table = 'visit_teeth';

    protected $fillable = [
        'visit_id',
        'patient_id',
        'tooth_id',
        'tooth_condition_id',
        'tooth_surface_id',
        'visit_service_id',
        'entry_type',
        'notes',
        'created_by',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function tooth(): BelongsTo
    {
        return $this->belongsTo(Tooth::class);
    }

    public function condition(): BelongsTo
    {
        return $this->belongsTo(ToothCondition::class, 'tooth_condition_id');
    }

    public function surface(): BelongsTo
    {
        return $this->belongsTo(ToothSurface::class, 'tooth_surface_id');
    }

    public function visitService(): BelongsTo
    {
        return $this->belongsTo(VisitService::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
