<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BranchController extends Controller
{
    /**
     * FR-006: also used by other admin-only screens needing a branch
     * selector (e.g., the Users creation form from
     * 006-admin-management) — same scoping rule applied once, here.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Branch::class);

        $query = Branch::query()->where('is_active', true);

        if (! $request->user()->hasRole('super-admin')) {
            $query->where('id', $request->user()->branch_id);
        }

        return $this->respondSuccess(BranchResource::collection($query->get()));
    }

    public function show(Branch $branch)
    {
        Gate::authorize('view', $branch);

        return $this->respondSuccess(new BranchResource($branch));
    }

    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        $branch->update($request->validated());

        return $this->respondSuccess(
            new BranchResource($branch),
            __('Branch updated successfully.')
        );
    }
}
