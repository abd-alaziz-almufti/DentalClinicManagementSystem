<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\DoctorProfile;
use App\Models\User;
use App\Services\Support\BranchScopeFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $user = $request->user();
        $baseQuery = User::query();

        // Scope by branch for non-super-admin
        $baseQuery = BranchScopeFilter::apply($baseQuery, $user);

        $perPage = min($request->integer('per_page', 20), 100);

        $users = QueryBuilder::for($baseQuery->with('branch', 'doctorProfile.specialty'))
            ->allowedFilters(['name', 'email'])
            ->allowedSorts(['created_at', 'name'])
            ->allowedIncludes(['branch', 'doctorProfile'])
            ->paginate($perPage);

        return $this->respondPaginated(
            UserResource::collection($users),
            'Users retrieved successfully.'
        );
    }

    /**
     * Create a new user.
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        Gate::authorize('create', User::class);

        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']),
                'branch_id' => $data['branch_id'],
            ]);

            $user->assignRole($data['role']);
            $user->syncSuperAdminFlag();

            if ($data['role'] === 'doctor') {
                DoctorProfile::create([
                    'user_id'        => $user->id,
                    'license_number' => $data['license_number'] ?? 'LIC-' . rand(1000, 9999),
                    'specialty_id'   => $data['specialty_id'] ?? null,
                    'color'          => $data['color'] ?? '#0D9488',
                ]);
            }

            return $user;
        });

        return $this->respondCreated(
            new UserResource($user->load('branch', 'doctorProfile.specialty')),
            'User created successfully.'
        );
    }
}
