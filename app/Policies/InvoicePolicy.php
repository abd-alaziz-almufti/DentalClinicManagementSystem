<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Accountants and super-admins can write; admin gets read-only within branch.
     */
    private function canWrite(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'accountant']);
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'accountant']);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $invoice->branch_id === $user->branch_id &&
               $user->hasAnyRole(['admin', 'accountant']);
    }

    public function create(User $user): bool
    {
        return $this->canWrite($user);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        if (!$this->canWrite($user)) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $invoice->branch_id === $user->branch_id;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $this->update($user, $invoice);
    }
}
