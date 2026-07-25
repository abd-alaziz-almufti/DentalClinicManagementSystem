<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryItemResource;
use App\Models\InventoryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class InventoryItemController extends Controller
{
    /**
     * Display a listing of inventory items.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', InventoryItem::class);

        $perPage = min($request->integer('per_page', 20), 100);

        $items = QueryBuilder::for(InventoryItem::query())
            ->allowedFilters(
                'name',
                'code',
                'is_active',
                AllowedFilter::callback('low_stock', function ($query, $value) use ($request) {
                    if ($value) {
                        $branchId = $request->user()->branch_id;
                        $query->whereHas('stocks', function ($q) use ($branchId, $request) {
                            if (!$request->user()->hasRole('super-admin') && $branchId) {
                                $q->where('branch_id', $branchId);
                            }
                            $q->whereColumn('quantity_on_hand', '<=', 'reorder_level');
                        });
                    }
                })
            )
            ->allowedSorts('name', 'code', 'created_at')
            ->allowedIncludes('stocks', 'consumptionTemplates')
            ->paginate($perPage);

        return $this->respondPaginated(
            InventoryItemResource::collection($items),
            'Inventory items retrieved successfully.'
        );
    }

    /**
     * Display the specified inventory item.
     */
    public function show(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        Gate::authorize('view', $inventoryItem);

        $item = QueryBuilder::for(InventoryItem::class)
            ->allowedIncludes('stocks', 'consumptionTemplates')
            ->whereKey($inventoryItem->id)
            ->firstOrFail();

        return $this->respondSuccess(
            new InventoryItemResource($item),
            'Inventory item retrieved successfully.'
        );
    }
}
