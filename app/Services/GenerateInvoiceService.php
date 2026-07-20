<?php

namespace App\Services;

use App\Exceptions\VisitAlreadyInvoicedException;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Visit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class GenerateInvoiceService
{
    private const TYPE = 'invoice';
    private const PREFIX = 'INV';

    public function __construct(
        private readonly DocumentNumberGenerator $numberGenerator,
    ) {
    }

    /**
     * Generate an invoice for a completed visit.
     *
     * @param  Visit  $visit
     * @param  int|null  $issuedBy  User ID of the staff generating the invoice
     * @return Invoice
     *
     * @throws \InvalidArgumentException
     * @throws VisitAlreadyInvoicedException
     */
    public function generate(Visit $visit, ?int $issuedBy = null): Invoice
    {
        return DB::transaction(function () use ($visit, $issuedBy) {
            // Step 1: Early checks before running numbering or database inserts.
            // Check status
            if ($visit->status !== 'completed') {
                throw new \InvalidArgumentException('Cannot generate invoice: Visit must be completed.');
            }

            // Fetch and check billed services
            $visitServices = $visit->visitServices()->with('service')->get();
            if ($visitServices->isEmpty()) {
                throw new \InvalidArgumentException('Cannot generate invoice: Visit has no billed services.');
            }

            // Calculate total in memory
            $total = round($visitServices->sum('total'), 2);
            if ($total <= 0) {
                throw new \InvalidArgumentException('Cannot generate invoice: Visit has a zero total.');
            }

            // Step 2: Generate unique invoice number
            $branch = $visit->branch;
            $invoiceNumber = $this->numberGenerator->generate($branch, self::TYPE, self::PREFIX);

            // Step 3: Insert Invoice row (Unique constraint on visit_id prevents duplicate invoicing)
            try {
                $invoice = Invoice::create([
                    'invoice_number' => $invoiceNumber,
                    'visit_id' => $visit->id,
                    'patient_id' => $visit->patient_id,
                    'branch_id' => $visit->branch_id,
                    'total' => $total,
                    'status' => 'issued',
                    'issued_by' => $issuedBy,
                ]);
            } catch (QueryException $e) {
                // Check if it's a unique constraint violation (SQLSTATE 23000)
                if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                    throw VisitAlreadyInvoicedException::forVisit($visit->id);
                }
                throw $e;
            }

            // Step 4: Copy VisitServices to InvoiceItems (frozen snapshot)
            foreach ($visitServices as $visitService) {
                $service = $visitService->service;
                
                // Frozen name snapshot
                $serviceName = [
                    'en' => $service ? $service->name : 'Unknown Service',
                ];

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'visit_service_id' => $visitService->id,
                    'service_id' => $visitService->service_id,
                    'service_name' => $serviceName,
                    'tooth_number' => $visitService->tooth_number,
                    'quantity' => $visitService->quantity,
                    'unit_price' => $visitService->unit_price,
                    'discount_amount' => $visitService->discount_amount,
                    'total' => $visitService->total,
                ]);
            }

            // Step 5: Mark the visit as invoiced to lock clinical edits
            $visit->has_active_invoice = true;
            $visit->save();

            return $invoice;
        });
    }
}
