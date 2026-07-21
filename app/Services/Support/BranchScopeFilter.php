<?php

namespace App\Services\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BranchScopeFilter
{
    /**
     * Scope a query by the user's branch unless they are a super-admin.
     */
    public static function apply(Builder $query, ?User $user, string $column = 'branch_id'): Builder
    {
        if (!$user) {
            return $query;
        }

        if ($user->hasRole('super-admin')) {
            return $query;
        }

        // Handle case where column is on a relation or specific table
        return $query->where($column, $user->branch_id);
    }
}
