<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterPatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use App\Services\PatientService;
use App\Services\Support\BranchScopeFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\QueryBuilder;

class PatientController extends Controller
{
    public function __construct(
        private readonly PatientService $patientService
    ) {
    }

    /**
     * Display a listing of patients.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Patient::class);

        $user = $request->user();
        $baseQuery = Patient::query();

        // Scope branch
        $baseQuery = BranchScopeFilter::apply($baseQuery, $user, 'registered_branch_id');

        // Scope doctor relation (FR-011)
        if ($user->hasRole('doctor')) {
            $doctorId = $user->doctorProfile?->id;
            $baseQuery->where(function ($q) use ($doctorId) {
                $q->whereHas('appointments', fn($aq) => $aq->where('doctor_profile_id', $doctorId))
                  ->orWhereHas('visits', fn($vq) => $vq->where('doctor_profile_id', $doctorId));
            });
        }

        $perPage = min($request->integer('per_page', 20), 100);

        $patients = QueryBuilder::for($baseQuery)
            ->allowedFilters(['phone', 'national_id'])
            ->allowedSorts(['created_at', 'last_name'])
            ->allowedIncludes(['medicalProfile'])
            ->paginate($perPage);

        return $this->respondPaginated(
            PatientResource::collection($patients),
            'Patients retrieved successfully.'
        );
    }

    /**
     * Store a newly created patient in storage.
     */
    public function store(RegisterPatientRequest $request): JsonResponse
    {
        Gate::authorize('create', Patient::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $patient = $this->patientService->register($data);

        return $this->respondSuccess(
            new PatientResource($patient),
            'Patient registered successfully.'
        );
    }

    /**
     * Display the specified patient.
     */
    public function show(Request $request, Patient $patient): JsonResponse
    {
        Gate::authorize('view', $patient);

        // Support relations query parameter using spatie query builder
        $patientModel = QueryBuilder::for(Patient::class)
            ->allowedIncludes(['medicalProfile'])
            ->whereKey($patient->id)
            ->firstOrFail();

        return $this->respondSuccess(
            new PatientResource($patientModel),
            'Patient retrieved successfully.'
        );
    }
}
