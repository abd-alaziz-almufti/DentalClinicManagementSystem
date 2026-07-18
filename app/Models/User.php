<?php

namespace App\Models;

// Illuminate\Foundation\Auth\User as Authenticatable
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'branch_id',
        'is_super_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_super_admin' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function doctorProfile(): HasOne
    {
        return $this->hasOne(DoctorProfile::class);
    }

    /**
     * Keep the is_super_admin performance flag in sync with the
     * "super-admin" Spatie role. Call this after assigning/removing
     * the role (see UserSeeder for example usage). Can later be wired
     * to Spatie's RoleAttached / RoleDetached events for full automation.
     */
    public function syncSuperAdminFlag(): void
    {
        $this->is_super_admin = $this->hasRole('super-admin');
        $this->saveQuietly();
    }
}
