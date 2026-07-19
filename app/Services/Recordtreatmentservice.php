<?php

namespace App\Services;

use App\Exceptions\VisitNotEditableException;
use App\Models\Service;
use App\Models\Visit;
use App\Models\VisitService;
use App\Services\Support\VisitEditabilityGuard;
use Illuminate\Support\Facades\DB;

/**
 * Manages the treatment line items (visit_services) recorded during a
 * visit. Business rule (enforced by VisitEditabilityGuard, shared with
 * DentalChartService): services can be added, changed, or removed freely
 * while the visit is still open/in_progress, but become immutable once the
 * visit is completed (invoicing has happened by then — any correction
 * after that point is a reversal/adjustment, not a plain edit).
 *
 * TODO (Inventory phase): when a service consumes tracked materials and
 * inventory linkage is enabled, deduct stock here inside the same
 * transaction. Not implemented yet — Inventory tables don't exist.
 */
class RecordTreatmentService
{
    /**
     * @param  array  $data  tooth_number?, quantity?, unit_price?, discount_amount?, notes?, created_by?
     *
     * @throws VisitNotEditableException
     */
    public function addService(Visit $visit, Service $service, array $data): VisitService
    {
        return DB::transaction(function () use ($visit, $service, $data) {
            $lockedVisit = Visit::query()->whereKey($visit->id)->lockForUpdate()->firstOrFail();

            VisitEditabilityGuard::assertEditable($lockedVisit);

            $quantity = $data['quantity'] ?? 1;
            // Default to the service's current default_price, but the caller
            // (doctor, via the UI) may override it per case — this is exactly
            // the "doctor can adjust the price" rule from the original spec.
            $unitPrice = $data['unit_price'] ?? $service->default_price;
            $discount = $data['discount_amount'] ?? 0;

            $total = round(($unitPrice * $quantity) - $discount, 2);

            if ($total < 0) {
                throw new \InvalidArgumentException('Discount cannot exceed the service subtotal.');
            }

            return VisitService::create([
                'visit_id' => $lockedVisit->id,
                'service_id' => $service->id,
                'tooth_number' => $data['tooth_number'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => $discount,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
        });
    }

    /**
     * @throws VisitNotEditableException
     */
    public function removeService(VisitService $visitService): void
    {
        DB::transaction(function () use ($visitService) {
            $lockedVisit = Visit::query()
                ->whereKey($visitService->visit_id)
                ->lockForUpdate()
                ->firstOrFail();

            VisitEditabilityGuard::assertEditable($lockedVisit);

            $visitService->delete();
        });
    }
}
