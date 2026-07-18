<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    protected $fillable = [
        'branch_id',
        'type',
        'year',
        'last_number',
    ];

    // Intentionally no relations exposed here — this table is internal
    // plumbing for number generation, not a domain entity to browse/edit.
}
