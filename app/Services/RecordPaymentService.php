<?php

namespace App\Services;

use App\Exceptions\InvalidInvoiceStatusException;
use App\Exceptions\PaymentExceedsBalanceException;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class RecordPaymentService
{
    /**
     * Record a standard payment against an invoice.
     *
     * @param  int  $invoiceId
     * @param  array  $data  amount, payment_method, payment_date, recorded_by, notes
     * @return Payment
     *
     * @throws InvalidInvoiceStatusException
     * @throws PaymentExceedsBalanceException
     */
    public function recordPayment(int $invoiceId, array $data): Payment
    {
        return DB::transaction(function () use ($invoiceId, $data) {
            // Step 1: lock the invoice row itself to serialize all payment/reversal requests.
            $invoice = Invoice::query()->whereKey($invoiceId)->lockForUpdate()->firstOrFail();

            // Step 2: verify invoice is not in a terminal state
            if (in_array($invoice->status, ['paid', 'cancelled'], true)) {
                throw new InvalidInvoiceStatusException(
                    "Cannot record payment against a transaction in '{$invoice->status}' state."
                );
            }

            // Step 3: validate payment amount
            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0) {
                throw new \InvalidArgumentException('Payment amount must be greater than zero.');
            }

            $remainingBalance = $invoice->remaining_balance;

            if ($amount > $remainingBalance) {
                throw PaymentExceedsBalanceException::forAmount($amount, $remainingBalance);
            }

            // Step 4: insert Payment row
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'type' => 'payment',
                'reverses_payment_id' => null,
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'payment_date' => $data['payment_date'],
                'recorded_by' => $data['recorded_by'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Step 5: update Invoice status based on recomputed balance
            $this->updateInvoiceStatus($invoice);

            return $payment;
        });
    }

    /**
     * Record a payment reversal to correct a mistake (immutable correction).
     *
     * @param  int  $invoiceId
     * @param  int  $paymentIdToReverse  ID of the original payment being reversed
     * @param  array  $data  payment_date, recorded_by, notes
     * @return Payment
     *
     * @throws InvalidInvoiceStatusException
     * @throws \InvalidArgumentException
     */
    public function recordReversal(int $invoiceId, int $paymentIdToReverse, array $data): Payment
    {
        return DB::transaction(function () use ($invoiceId, $paymentIdToReverse, $data) {
            // Step 1: lock the invoice row
            $invoice = Invoice::query()->whereKey($invoiceId)->lockForUpdate()->firstOrFail();

            // Step 2: verify invoice is not cancelled or paid
            if (in_array($invoice->status, ['paid', 'cancelled'], true)) {
                throw new InvalidInvoiceStatusException(
                    "Cannot reverse payment against a transaction in '{$invoice->status}' state."
                );
            }

            // Step 3: verify the target payment exists, belongs to this invoice, and is of type 'payment'
            $originalPayment = Payment::query()
                ->whereKey($paymentIdToReverse)
                ->where('invoice_id', $invoice->id)
                ->firstOrFail();

            if ($originalPayment->type !== 'payment') {
                throw new \InvalidArgumentException('Only payments can be reversed.');
            }

            // Step 4: FR-019 — Check if this payment has already been reversed
            $alreadyReversed = Payment::query()
                ->where('invoice_id', $invoice->id)
                ->where('type', 'reversal')
                ->where('reverses_payment_id', $originalPayment->id)
                ->exists();

            if ($alreadyReversed) {
                throw new \InvalidArgumentException('This payment has already been reversed.');
            }

            // Step 5: insert the reversal record (snapshots amount and payment_method)
            $reversal = Payment::create([
                'invoice_id' => $invoice->id,
                'type' => 'reversal',
                'reverses_payment_id' => $originalPayment->id,
                'amount' => $originalPayment->amount,
                'payment_method' => $originalPayment->payment_method,
                'payment_date' => $data['payment_date'],
                'recorded_by' => $data['recorded_by'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Step 6: update Invoice status
            $this->updateInvoiceStatus($invoice);

            return $reversal;
        });
    }

    /**
     * Compute remaining balance and update the status of the invoice.
     */
    private function updateInvoiceStatus(Invoice $invoice): void
    {
        $remainingBalance = $invoice->remaining_balance;
        $total = (float) $invoice->total;

        if ($remainingBalance <= 0) {
            $invoice->status = 'paid';
        } elseif ($remainingBalance === $total) {
            $invoice->status = 'issued';
        } else {
            $invoice->status = 'partially_paid';
        }

        $invoice->save();
    }
}
