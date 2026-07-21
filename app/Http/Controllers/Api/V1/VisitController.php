<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VisitResource;
use App\Models\Appointment;
use App\Models\Visit;
use App\Services\CheckInPatientService;
use App\Services\Support\BranchScopeFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\QueryBuilder;

class VisitController extends Controller
{
    public function __construct(
        private readonly CheckInPatientService $checkInService
    ) {
    }

    /**
     * List visits for the authenticated user's branch / doctor.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Visit::class);

        $user     = $request->user();
        $baseQuery = Visit::query();

        $baseQuery = BranchScopeFilter::apply($baseQuery, $user);

        if ($user->hasRole('doctor')) {
            $baseQuery->where('doctor_profile_id', $user->doctorProfile?->id);
        }

        $perPage = min($request->integer('per_page', 20), 100);

        $visits = QueryBuilder::for($baseQuery)
            ->allowedFilters(['status', 'doctor_profile_id', 'patient_id'])
            ->allowedSorts(['checked_in_at', 'created_at'])
            ->allowedIncludes(['patient', 'visitServices', 'visitTeeth'])
            ->paginate($perPage);

        return $this->respondPaginated(
            VisitResource::collection($visits),
            'Visits retrieved successfully.'
        );
    }

    /**
     * Check-in a patient from a scheduled appointment.
     *
     * POST /api/v1/appointments/{appointment}/check-in
     */
    public function checkIn(Request $request, Appointment $appointment): JsonResponse
    {
        Gate::authorize('create', Visit::class);

        $visit = $this->checkInService->checkIn($appointment, $request->user()->id);

        return $this->respondSuccess(
            new VisitResource($visit),
            'Patient checked in successfully.'
        );
    }

    /**
     * Show a single visit.
     */
    public function show(Request $request, Visit $visit): JsonResponse
    {
        Gate::authorize('view', $visit);

        $visit = QueryBuilder::for(Visit::class)
            ->allowedIncludes(['patient', 'visitServices', 'visitTeeth', 'visitServices.service'])
            ->whereKey($visit->id)
            ->firstOrFail();

        return $this->respondSuccess(
            new VisitResource($visit),
            'Visit retrieved successfully.'
        );
    }
}
