<?php

namespace App\Services;

use App\Exceptions\VisitNotEditableException;
use App\Models\Tooth;
use App\Models\ToothCondition;
use App\Models\Visit;
use App\Models\VisitTooth;
use App\Services\Support\VisitEditabilityGuard;
use Illuminate\Support\Facades\DB;

class DentalChartService
{
    /**
     * @param  array  $data  tooth_surface_id?, visit_service_id?, notes?, created_by?
     *
     * @throws VisitNotEditableException
     */
    public function addEntry(
        Visit $visit,
        Tooth $tooth,
        ToothCondition $condition,
        string $entryType, // 'diagnosis' | 'treatment'
        array $data = []
    ): VisitTooth {
        return DB::transaction(function () use ($visit, $tooth, $condition, $entryType, $data) {
            $lockedVisit = Visit::query()->whereKey($visit->id)->lockForUpdate()->firstOrFail();

            VisitEditabilityGuard::assertEditable($lockedVisit);

            return VisitTooth::create([
                'visit_id' => $lockedVisit->id,
                'patient_id' => $lockedVisit->patient_id, // snapshot, same as the visit itself
                'tooth_id' => $tooth->id,
                'tooth_condition_id' => $condition->id,
                'tooth_surface_id' => $data['tooth_surface_id'] ?? null,
                'visit_service_id' => $data['visit_service_id'] ?? null,
                'entry_type' => $entryType,
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
        });
    }

    /**
     * @throws VisitNotEditableException
     */
    public function removeEntry(VisitTooth $entry): void
    {
        DB::transaction(function () use ($entry) {
            $lockedVisit = Visit::query()
                ->whereKey($entry->visit_id)
                ->lockForUpdate()
                ->firstOrFail();

            VisitEditabilityGuard::assertEditable($lockedVisit);

            $entry->delete();
        });
    }

    /**
     * Full chart history for a patient across ALL visits — the "current
     * state" of every tooth, most recent entry first. Used to render the
     * dental chart UI. Uses patient_id directly (not a join through
     * visits) thanks to the controlled denormalization on visit_teeth.
     */
    public function historyForPatient(int $patientId)
    {
        return VisitTooth::with(['tooth', 'condition', 'surface', 'visit'])
            ->where('patient_id', $patientId)
            ->orderByDesc('created_at')
            ->get();
    }
}
