<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

/**
 * Final role model (2026-07-22): super-admin, admin, doctor only.
 * Financial actions (generate/cancel invoices, record payments) are
 * equally available to admin and doctor — there is no separate
 * accountant/operations-manager role in this clinic. The ONLY
 * distinction anywhere in the system is clinical/medical data writes
 * (see VisitPolicy::recordClinicalData()), which is unrelated to
 * invoicing.
 *
 * Fixed bug: every write check previously required the 'accountant'
 * role, which no longer exists — only super-admin could actually
 * generate invoices, cancel them, or record payments. admin and doctor
 * were both silently blocked from all financial actions.
 */
class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'doctor']);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $invoice->branch_id === $user->branch_id
            && $user->hasAnyRole(['admin', 'doctor']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'doctor']);
    }

    /**
     * Generating an invoice, recording a payment, and cancelling an
     * invoice all share this single write check — none of them are
     * clinical actions, so admin and doctor are equal here.
     */
    public function canWrite(User $user, Invoice $invoice): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $invoice->branch_id === $user->branch_id
            && $user->hasAnyRole(['admin', 'doctor']);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->canWrite($user, $invoice);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        // Maps to CancelInvoiceService (status transition — Article I,
        // never a hard delete).
        return $this->canWrite($user, $invoice);
    }
}
