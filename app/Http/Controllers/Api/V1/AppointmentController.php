<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use App\Services\Support\BranchScopeFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\QueryBuilder;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentService $appointmentService
    ) {
    }

    /**
     * Display a listing of appointments.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Appointment::class);

        $user = $request->user();
        $baseQuery = Appointment::query();

        // Scope branch
        $baseQuery = BranchScopeFilter::apply($baseQuery, $user);

        // Scope doctor relation
        if ($user->hasRole('doctor')) {
            $doctorId = $user->doctorProfile?->id;
            $baseQuery->where('doctor_profile_id', $doctorId);
        }

        $perPage = min($request->integer('per_page', 20), 100);

        $appointments = QueryBuilder::for($baseQuery)
            ->allowedFilters(['status', 'doctor_profile_id', 'appointment_date'])
            ->allowedSorts(['appointment_date', 'start_time'])
            ->allowedIncludes(['patient', 'doctorProfile', 'doctorProfile.user'])
            ->paginate($perPage);

        return $this->respondPaginated(
            AppointmentResource::collection($appointments),
            'Appointments retrieved successfully.'
        );
    }

    /**
     * Book a new appointment.
     */
    public function store(BookAppointmentRequest $request): JsonResponse
    {
        Gate::authorize('create', Appointment::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $appointment = $this->appointmentService->book($data);

        return $this->respondSuccess(
            new AppointmentResource($appointment),
            'Appointment booked successfully.'
        );
    }

    /**
     * Cancel an appointment.
     */
    public function destroy(Request $request, Appointment $appointment): JsonResponse
    {
        Gate::authorize('delete', $appointment);

        $cancelledAppointment = $this->appointmentService->cancel($appointment);

        return $this->respondSuccess(
            new AppointmentResource($cancelledAppointment),
            'Appointment cancelled successfully.'
        );
    }
}
