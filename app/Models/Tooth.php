<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tooth extends Model
{
    protected $fillable = [
        'fdi_number',
        'name',
        'quadrant',
        'position_in_quadrant',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
