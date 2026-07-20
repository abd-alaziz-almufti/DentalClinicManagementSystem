<?php

namespace App\Services;

use App\Exceptions\InvalidInvoiceStatusException;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class CancelInvoiceService
{
    /**
     * Cancel an invoice and release the associated visit's clinical record lock.
     *
     * @param  int  $invoiceId
     * @param  string  $reason  The reason for cancellation
     * @return Invoice
     *
     * @throws InvalidInvoiceStatusException
     * @throws \InvalidArgumentException
     */
    public function cancel(int $invoiceId, string $reason): Invoice
    {
        if (empty(trim($reason))) {
            throw new \InvalidArgumentException('Cancellation reason is required.');
        }

        return DB::transaction(function () use ($invoiceId, $reason) {
            // Step 1: lock the invoice row
            $invoice = Invoice::query()->whereKey($invoiceId)->lockForUpdate()->firstOrFail();

            // Step 2: verify invoice is in a state that allows cancellation
            if (in_array($invoice->status, ['paid', 'cancelled'], true)) {
                throw new InvalidInvoiceStatusException(
                    "Cannot cancel an invoice with status '{$invoice->status}'."
                );
            }

            // Step 3: transition state
            $invoice->status = 'cancelled';
            $invoice->cancelled_at = now();
            $invoice->cancellation_reason = $reason;
            $invoice->save();

            // Step 4: unlock the visit's clinical records
            $visit = $invoice->visit;
            if ($visit) {
                $visit->has_active_invoice = false;
                $visit->save();
            }

            return $invoice;
        });
    }
}
