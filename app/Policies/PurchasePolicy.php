<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'inventory-manager', 'accountant']);
    }

    public function view(User $user, Purchase $purchase): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $purchase->branch_id === $user->branch_id &&
               $user->hasAnyRole(['admin', 'inventory-manager', 'accountant']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'inventory-manager']);
    }

    public function update(User $user, Purchase $purchase): bool
    {
        if (!$this->create($user)) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $purchase->branch_id === $user->branch_id;
    }

    public function delete(User $user, Purchase $purchase): bool
    {
        return $this->update($user, $purchase);
    }
}
