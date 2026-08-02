<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

/**
 * Same access boundary as Users (006-admin-management): doctor has no
 * access to this module at all, not even read-only.
 */
class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function view(User $user, Branch $branch): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasRole('admin') && $branch->id === $user->branch_id;
    }

    public function update(User $user, Branch $branch): bool
    {
        return $this->view($user, $branch);
    }
}
