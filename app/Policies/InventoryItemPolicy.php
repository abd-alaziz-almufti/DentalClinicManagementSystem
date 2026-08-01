<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;

/**
 * Final role model (2026-07-22): super-admin, admin, doctor only.
 * Inventory management is equally available to admin and doctor — there
 * is no separate inventory-manager role in this clinic.
 *
 * Fixed bug: every write check previously required 'inventory-manager',
 * which no longer exists — only super-admin could actually create,
 * update, or delete inventory items.
 *
 * ⚠ Assumes InventoryItem has a `branch_id` column (consistent with
 * every other operational table per Constitution Article VIII). Adjust
 * the branch-scoping line if your actual schema differs (e.g. if
 * inventory is intentionally clinic-wide rather than per-branch).
 */
class InventoryItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'doctor']);
    }

    public function view(User $user, InventoryItem $item): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $item->branch_id === $user->branch_id
            && $user->hasAnyRole(['admin', 'doctor']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'doctor']);
    }

    public function update(User $user, InventoryItem $item): bool
    {
        return $this->view($user, $item);
    }

    public function delete(User $user, InventoryItem $item): bool
    {
        return $this->view($user, $item);
    }
}
