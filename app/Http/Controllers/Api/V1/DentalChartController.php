<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DentalChartEntryRequest;
use App\Http\Resources\VisitToothResource;
use App\Models\Tooth;
use App\Models\ToothCondition;
use App\Models\Visit;
use App\Models\VisitTooth;
use App\Services\DentalChartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DentalChartController extends Controller
{
    public function __construct(
        private readonly DentalChartService $chartService
    ) {
    }

    /**
     * Add a dental chart entry to a visit.
     *
     * POST /api/v1/visits/{visit}/teeth
     */
    public function store(DentalChartEntryRequest $request, Visit $visit): JsonResponse
    {
        Gate::authorize('update', $visit);

        $data      = $request->validated();
        $tooth     = Tooth::findOrFail($data['tooth_id']);
        $condition = ToothCondition::findOrFail($data['tooth_condition_id']);

        $data['created_by'] = $request->user()->id;
        unset($data['tooth_id'], $data['tooth_condition_id']);

        $entry = $this->chartService->addEntry(
            $visit,
            $tooth,
            $condition,
            $data['entry_type'],
            $data
        );

        return $this->respondSuccess(
            new VisitToothResource($entry),
            'Dental chart entry added successfully.'
        );
    }

    /**
     * Remove a dental chart entry from a visit.
     *
     * DELETE /api/v1/visits/{visit}/teeth/{visitTooth}
     */
    public function destroy(Request $request, Visit $visit, VisitTooth $visitTooth): JsonResponse
    {
        Gate::authorize('update', $visit);

        $this->chartService->removeEntry($visitTooth);

        return $this->respondSuccess(null, 'Dental chart entry removed successfully.');
    }
}
