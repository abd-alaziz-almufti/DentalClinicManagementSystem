<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\Models\ServiceInventoryConsumption;
use App\Models\VisitService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryDeductionService
{
    /**
     * Deduct stock for a visit service based on the consumption template.
     *
     * @param  VisitService  $vs
     * @param  Branch  $branch
     * @param  int|null  $userId
     * @return array List of low-stock warnings generated during this deduction
     */
    public function deductForService(VisitService $vs, Branch $branch, ?int $userId = null): array
    {
        $warnings = [];
        $templates = ServiceInventoryConsumption::query()
            ->where('service_id', $vs->service_id)
            ->with('inventoryItem')
            ->get();

        if ($templates->isEmpty()) {
            return $warnings;
        }

        foreach ($templates as $template) {
            $qtyToDeduct = (float) $template->quantity_per_service * (int) $vs->quantity;

            // Lock the inventory stock row. Handle first-row creation race.
            try {
                $stock = InventoryStock::query()
                    ->lockForUpdate()
                    ->firstOrCreate(
                        [
                            'branch_id' => $branch->id,
                            'inventory_item_id' => $template->inventory_item_id,
                        ],
                        ['quantity_on_hand' => 0.00, 'reorder_level' => 0.00]
                    );
            } catch (QueryException $e) {
                $stock = InventoryStock::query()
                    ->where('branch_id', $branch->id)
                    ->where('inventory_item_id', $template->inventory_item_id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            // Adjust stock
            $stock->quantity_on_hand = round((float) $stock->quantity_on_hand - $qtyToDeduct, 2);
            $stock->save();

            // Record transaction
            InventoryTransaction::create([
                'inventory_item_id' => $template->inventory_item_id,
                'branch_id' => $branch->id,
                'type' => 'consumption',
                'quantity' => -$qtyToDeduct,
                'reference_type' => VisitService::class,
                'reference_id' => $vs->id,
                'item_name_snapshot' => $template->inventoryItem->name,
                'item_unit_snapshot' => $template->inventoryItem->unit,
                'notes' => "Consumed via visit service: {$template->inventoryItem->name} (x{$qtyToDeduct})",
                'created_by' => $userId,
            ]);

            // Track/Log low-stock warnings
            if ($stock->isLowStock()) {
                $warningMsg = "Low stock warning: Item '{$template->inventoryItem->name}' at branch '{$branch->name}'. Quantity remaining: {$stock->quantity_on_hand}.";
                Log::warning($warningMsg);
                $warnings[] = [
                    'item_id' => $template->inventory_item_id,
                    'name' => $template->inventoryItem->name,
                    'available' => $stock->quantity_on_hand,
                    'reorder_level' => $stock->reorder_level,
                    'message' => $warningMsg,
                ];
            }
        }

        return $warnings;
    }

    /**
     * Return stock for a deleted visit service based on the consumption template.
     *
     * @param  VisitService  $vs
     * @param  Branch  $branch
     * @param  int|null  $userId
     * @return void
     */
    public function returnForService(VisitService $vs, Branch $branch, ?int $userId = null): void
    {
        $templates = ServiceInventoryConsumption::query()
            ->where('service_id', $vs->service_id)
            ->with('inventoryItem')
            ->get();

        if ($templates->isEmpty()) {
            return;
        }

        foreach ($templates as $template) {
            $qtyToReturn = (float) $template->quantity_per_service * (int) $vs->quantity;

            // Lock and update the stock row. Since this returns stock, the row should exist.
            $stock = InventoryStock::query()
                ->where('branch_id', $branch->id)
                ->where('inventory_item_id', $template->inventory_item_id)
                ->lockForUpdate()
                ->firstOrFail();

            $stock->quantity_on_hand = round((float) $stock->quantity_on_hand + $qtyToReturn, 2);
            $stock->save();

            // Record transaction
            InventoryTransaction::create([
                'inventory_item_id' => $template->inventory_item_id,
                'branch_id' => $branch->id,
                'type' => 'return',
                'quantity' => $qtyToReturn,
                'reference_type' => VisitService::class,
                'reference_id' => $vs->id,
                'item_name_snapshot' => $template->inventoryItem->name,
                'item_unit_snapshot' => $template->inventoryItem->unit,
                'notes' => "Returned from deleted visit service: {$template->inventoryItem->name} (x{$qtyToReturn})",
                'created_by' => $userId,
            ]);
        }
    }
}
