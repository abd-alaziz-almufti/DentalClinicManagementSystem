<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_number',
        'appointment_id',
        'patient_id',
        'doctor_profile_id',
        'branch_id',
        'checked_in_at',
        'status',
        'chief_complaint',
        'diagnosis',
        'doctor_notes',
        'treatment_plan',
        'created_by',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctorProfile(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function visitServices(): HasMany
    {
        return $this->hasMany(VisitService::class);
    }

    public function visitTeeth(): HasMany
    {
        return $this->hasMany(VisitTooth::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
