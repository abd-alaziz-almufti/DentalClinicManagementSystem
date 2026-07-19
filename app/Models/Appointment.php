<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'doctor_profile_id',
        'patient_id',
        'appointment_date',
        'start_time',
        'end_time',
        'reason',
        'notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'appointment_date' => 'date',
    ];

    // Statuses that actually occupy the doctor's schedule and must be
    // considered when checking for time-overlap conflicts.
    public const BLOCKING_STATUSES = ['scheduled', 'attended'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function doctorProfile(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
