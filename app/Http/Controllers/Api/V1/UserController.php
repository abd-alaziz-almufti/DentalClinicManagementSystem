<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\CreateUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $query = User::query()->with(['branch', 'doctorProfile.specialty', 'roles']);

        // Simple, explicit branch scoping (Constitution Article III/VI —
        // explicit over implicit; matches the same rule applied
        // everywhere else, kept inline here rather than assuming
        // BranchScopeFilter's exact method signature).
        if (! $request->user()->hasRole('super-admin')) {
            $query->where('branch_id', $request->user()->branch_id);
        }

        $users = QueryBuilder::for($query)
            ->allowedFilters(
                AllowedFilter::exact('branch_id'),
                AllowedFilter::partial('name'),
                AllowedFilter::callback('role', fn ($q, $value) => $q->whereHas(
                    'roles',
                    fn ($r) => $r->where('name', $value)
                )),
            )
            ->allowedSorts('name', 'created_at')
            ->defaultSort('-created_at')
            ->paginate(min((int) $request->integer('per_page', 20), 100));

        return $this->respondPaginated(UserResource::collection($users));
    }

    public function store(StoreUserRequest $request, CreateUserService $service)
    {
        $data = $request->validated();

        // An admin (not super-admin) can only ever create users within
        // their own branch — whatever branch_id they submitted is
        // ignored and overridden here, never trusted from input.
        if (! $request->user()->hasRole('super-admin')) {
            $data['branch_id'] = $request->user()->branch_id;
        }

        $user = $service->create($data);

        return $this->respondSuccess(
            new UserResource($user),
            __('Users created successfully.')
        );
    }
}
