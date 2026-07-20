<?php

namespace App\Services;

use App\Exceptions\InvalidPurchaseStatusException;
use App\Models\Branch;
use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    private const TYPE = 'purchase';
    private const PREFIX = 'PUR';

    public function __construct(
        private readonly DocumentNumberGenerator $numberGenerator,
    ) {
    }

    /**
     * Create a purchase order in draft status.
     *
     * @param  Branch  $branch
     * @param  Supplier|null  $supplier
     * @param  array  $items  Array of ['inventory_item_id', 'quantity', 'unit_cost']
     * @param  array  $metadata  notes, created_by
     * @return Purchase
     *
     * @throws \InvalidArgumentException
     */
    public function create(Branch $branch, ?Supplier $supplier, array $items, array $metadata = []): Purchase
    {
        if (empty($items)) {
            throw new \InvalidArgumentException('Purchase order must contain at least one item.');
        }

        return DB::transaction(function () use ($branch, $supplier, $items, $metadata) {
            // Generate purchase number
            $purchaseNumber = $this->numberGenerator->generate($branch, self::TYPE, self::PREFIX);

            // Calculate total cost
            $totalCost = 0.00;
            $itemsToCreate = [];

            foreach ($items as $itemData) {
                $qty = (float) $itemData['quantity'];
                $cost = (float) $itemData['unit_cost'];
                $itemTotal = round($qty * $cost, 2);

                if ($qty <= 0 || $cost <= 0) {
                    throw new \InvalidArgumentException('Quantity and unit cost must be greater than zero.');
                }

                $totalCost += $itemTotal;
                $itemsToCreate[] = [
                    'inventory_item_id' => $itemData['inventory_item_id'],
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'total_cost' => $itemTotal,
                ];
            }

            // Create purchase
            $purchase = Purchase::create([
                'purchase_number' => $purchaseNumber,
                'branch_id' => $branch->id,
                'supplier_id' => $supplier ? $supplier->id : null,
                'total_cost' => $totalCost,
                'status' => 'draft',
                'notes' => $metadata['notes'] ?? null,
                'created_by' => $metadata['created_by'] ?? null,
            ]);

            // Create purchase items
            foreach ($itemsToCreate as $itemData) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    ...$itemData,
                ]);
            }

            return $purchase;
        });
    }

    /**
     * Receive a draft purchase order, adding the items to the branch's inventory stocks.
     *
     * @param  int  $purchaseId
     * @param  int|null  $userId
     * @return Purchase
     *
     * @throws InvalidPurchaseStatusException
     */
    public function receive(int $purchaseId, ?int $userId = null): Purchase
    {
        return DB::transaction(function () use ($purchaseId, $userId) {
            // Lock the purchase row
            $purchase = Purchase::query()->whereKey($purchaseId)->lockForUpdate()->firstOrFail();

            if ($purchase->status !== 'draft') {
                throw InvalidPurchaseStatusException::cannotTransition($purchase->status, 'received');
            }

            $purchase->status = 'received';
            $purchase->received_at = now();
            $purchase->save();

            // Load items with catalog info
            $purchaseItems = $purchase->items()->with('inventoryItem')->get();

            foreach ($purchaseItems as $item) {
                // Lock stock row or create it
                try {
                    $stock = InventoryStock::query()
                        ->lockForUpdate()
                        ->firstOrCreate(
                            [
                                'branch_id' => $purchase->branch_id,
                                'inventory_item_id' => $item->inventory_item_id,
                            ],
                            ['quantity_on_hand' => 0.00, 'reorder_level' => 0.00]
                        );
                } catch (QueryException $e) {
                    $stock = InventoryStock::query()
                        ->where('branch_id', $purchase->branch_id)
                        ->where('inventory_item_id', $item->inventory_item_id)
                        ->lockForUpdate()
                        ->firstOrFail();
                }

                // Add to stock
                $stock->quantity_on_hand = round((float) $stock->quantity_on_hand + $item->quantity, 2);
                $stock->save();

                // Record audit transaction
                InventoryTransaction::create([
                    'inventory_item_id' => $item->inventory_item_id,
                    'branch_id' => $purchase->branch_id,
                    'type' => 'purchase_in',
                    'quantity' => $item->quantity,
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'item_name_snapshot' => $item->inventoryItem->name,
                    'item_unit_snapshot' => $item->inventoryItem->unit,
                    'notes' => "Received items via PO: {$purchase->purchase_number}",
                    'created_by' => $userId,
                ]);
            }

            return $purchase;
        });
    }

    /**
     * Cancel a draft purchase order.
     *
     * @param  int  $purchaseId
     * @return Purchase
     *
     * @throws InvalidPurchaseStatusException
     */
    public function cancel(int $purchaseId): Purchase
    {
        return DB::transaction(function () use ($purchaseId) {
            // Lock the purchase row
            $purchase = Purchase::query()->whereKey($purchaseId)->lockForUpdate()->firstOrFail();

            if ($purchase->status !== 'draft') {
                throw InvalidPurchaseStatusException::cannotTransition($purchase->status, 'cancelled');
            }

            $purchase->status = 'cancelled';
            $purchase->save();

            return $purchase;
        });
    }
}
