<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the authenticated user can view any users list.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * Determine whether the authenticated user can view a specific user.
     */
    public function view(User $user, User $targetUser): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * Determine whether the authenticated user can create users.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * Determine whether the authenticated user can update a user.
     */
    public function update(User $user, User $targetUser): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * Determine whether the authenticated user can delete a user.
     */
    public function delete(User $user, User $targetUser): bool
    {
        return $user->hasRole('super-admin');
    }
}
