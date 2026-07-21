<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecordTreatmentRequest;
use App\Http\Resources\VisitServiceResource;
use App\Models\Service;
use App\Models\Visit;
use App\Models\VisitService;
use App\Services\RecordTreatmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VisitServiceController extends Controller
{
    public function __construct(
        private readonly RecordTreatmentService $treatmentService
    ) {
    }

    /**
     * Add a treatment service to a visit.
     *
     * POST /api/v1/visits/{visit}/services
     */
    public function store(RecordTreatmentRequest $request, Visit $visit): JsonResponse
    {
        Gate::authorize('update', $visit);

        $data    = $request->validated();
        $service = Service::findOrFail($data['service_id']);
        unset($data['service_id']);
        $data['created_by'] = $request->user()->id;

        $visitService = $this->treatmentService->addService($visit, $service, $data);

        return $this->respondSuccess(
            new VisitServiceResource($visitService),
            'Treatment service recorded successfully.'
        );
    }

    /**
     * Remove a treatment service from a visit.
     *
     * DELETE /api/v1/visits/{visit}/services/{visitService}
     */
    public function destroy(Request $request, Visit $visit, VisitService $visitService): JsonResponse
    {
        Gate::authorize('update', $visit);

        $this->treatmentService->removeService($visitService);

        return $this->respondSuccess(null, 'Treatment service removed successfully.');
    }
}
