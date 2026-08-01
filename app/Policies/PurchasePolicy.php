<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

/**
 * Final role model (2026-07-22): super-admin, admin, doctor only.
 * Same fix as InventoryItemPolicy — recording purchases is equally
 * available to admin and doctor, no separate inventory-manager role.
 *
 * ⚠ Assumes Purchase has a `branch_id` column — adjust if it differs.
 */
class PurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'doctor']);
    }

    public function view(User $user, Purchase $purchase): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $purchase->branch_id === $user->branch_id
            && $user->hasAnyRole(['admin', 'doctor']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'doctor']);
    }

    public function update(User $user, Purchase $purchase): bool
    {
        return $this->view($user, $purchase);
    }

    public function delete(User $user, Purchase $purchase): bool
    {
        return $this->view($user, $purchase);
    }
}
