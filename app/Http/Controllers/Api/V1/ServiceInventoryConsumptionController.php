<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Service;
use App\Models\ServiceInventoryConsumption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ServiceInventoryConsumptionController extends Controller
{
    /**
     * Display consumption templates for a service.
     */
    public function index(Request $request, Service $service): JsonResponse
    {
        Gate::authorize('viewAny', InventoryItem::class);

        $templates = ServiceInventoryConsumption::with('inventoryItem')
            ->where('service_id', $service->id)
            ->get();

        return $this->respondSuccess(
            $templates,
            'Service inventory consumption templates retrieved successfully.'
        );
    }

    /**
     * Add or update a consumption template rule for a service.
     */
    public function store(Request $request, Service $service): JsonResponse
    {
        Gate::authorize('create', InventoryItem::class);

        $validated = $request->validate([
            'inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'quantity_per_service' => ['required', 'numeric', 'min:0.01'],
        ]);

        $template = ServiceInventoryConsumption::updateOrCreate(
            [
                'service_id' => $service->id,
                'inventory_item_id' => $validated['inventory_item_id'],
            ],
            [
                'quantity_per_service' => $validated['quantity_per_service'],
            ]
        );

        return $this->respondSuccess(
            $template->load('inventoryItem'),
            'Service inventory consumption template saved successfully.'
        );
    }

    /**
     * Remove a consumption template rule.
     */
    public function destroy(Request $request, Service $service, ServiceInventoryConsumption $consumption): JsonResponse
    {
        Gate::authorize('delete', InventoryItem::class);

        if ($consumption->service_id !== $service->id) {
            abort(404);
        }

        $consumption->delete();

        return $this->respondSuccess(null, 'Service inventory consumption template deleted successfully.');
    }
}
