<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecordPurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\Branch;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use App\Services\Support\BranchScopeFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\QueryBuilder;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseService $purchaseService
    ) {
    }

    /**
     * Display a listing of purchase orders.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Purchase::class);

        $baseQuery = BranchScopeFilter::apply(Purchase::query(), $request->user());

        $perPage = min($request->integer('per_page', 20), 100);

        $purchases = QueryBuilder::for($baseQuery)
            ->allowedFilters('status', 'supplier_id')
            ->allowedSorts('created_at', 'total_cost')
            ->allowedIncludes('items', 'supplier', 'branch')
            ->paginate($perPage);

        return $this->respondPaginated(
            PurchaseResource::collection($purchases),
            'Purchase orders retrieved successfully.'
        );
    }

    /**
     * Store a newly created purchase order in draft status.
     */
    public function store(RecordPurchaseRequest $request): JsonResponse
    {
        Gate::authorize('create', Purchase::class);

        $data = $request->validated();
        $branch = Branch::findOrFail($data['branch_id']);
        $supplier = !empty($data['supplier_id']) ? Supplier::find($data['supplier_id']) : null;

        $metadata = [
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ];

        $purchase = $this->purchaseService->create($branch, $supplier, $data['items'], $metadata);

        return $this->respondSuccess(
            new PurchaseResource($purchase),
            'Purchase order created successfully.'
        );
    }

    /**
     * Display the specified purchase order.
     */
    public function show(Request $request, Purchase $purchase): JsonResponse
    {
        Gate::authorize('view', $purchase);

        $purchaseModel = QueryBuilder::for(Purchase::class)
            ->allowedIncludes('items', 'supplier', 'branch')
            ->whereKey($purchase->id)
            ->firstOrFail();

        return $this->respondSuccess(
            new PurchaseResource($purchaseModel),
            'Purchase order retrieved successfully.'
        );
    }

    /**
     * Receive a draft purchase order.
     *
     * POST /api/v1/purchases/{purchase}/receive
     */
    public function receive(Request $request, Purchase $purchase): JsonResponse
    {
        Gate::authorize('update', $purchase);

        $received = $this->purchaseService->receive($purchase->id, $request->user()->id);

        return $this->respondSuccess(
            new PurchaseResource($received),
            'Purchase order received and inventory updated successfully.'
        );
    }

    /**
     * Cancel a draft purchase order.
     *
     * DELETE /api/v1/purchases/{purchase}
     */
    public function destroy(Request $request, Purchase $purchase): JsonResponse
    {
        Gate::authorize('delete', $purchase);

        $cancelled = $this->purchaseService->cancel($purchase->id);

        return $this->respondSuccess(
            new PurchaseResource($cancelled),
            'Purchase order cancelled successfully.'
        );
    }
}
