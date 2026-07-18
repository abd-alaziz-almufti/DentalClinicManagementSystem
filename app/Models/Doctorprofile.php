<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DoctorProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'specialty_id',
        'license_number',
        'color',
        'signature',
        'bio',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    // Convenience accessor — branch_id lives on the linked User, not here directly.
    // Not an Eloquent relation (can't eager-load with ->with('branch') this way);
    // use $doctorProfile->user->branch for that, or eager-load 'user.branch'.
    public function getBranchAttribute(): ?Branch
    {
        return $this->user?->branch;
    }
}
