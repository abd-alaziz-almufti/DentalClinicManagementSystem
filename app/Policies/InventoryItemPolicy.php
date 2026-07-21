<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;

class InventoryItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'inventory-manager', 'doctor', 'receptionist']);
    }

    public function view(User $user, InventoryItem $item): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'inventory-manager']);
    }

    public function update(User $user, InventoryItem $item): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, InventoryItem $item): bool
    {
        return $this->create($user);
    }
}
