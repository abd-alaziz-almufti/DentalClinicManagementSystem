<?php

namespace Tests\Feature\HttpApi;

use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Service;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_can_list_inventory_items_and_filter_low_stock(): void
    {
        $admin = User::where('email', 'admin@clinic.test')->first();
        $branch = Branch::where('code', 'MAIN')->first();

        $item1 = InventoryItem::create([
            'code' => 'ITEM-01',
            'name' => 'Gloves',
            'unit' => 'box',
            'is_active' => true,
        ]);

        InventoryStock::create([
            'inventory_item_id' => $item1->id,
            'branch_id' => $branch->id,
            'quantity_on_hand' => 2,
            'reorder_level' => 5,
        ]);

        $item2 = InventoryItem::create([
            'code' => 'ITEM-02',
            'name' => 'Masks',
            'unit' => 'box',
            'is_active' => true,
        ]);

        InventoryStock::create([
            'inventory_item_id' => $item2->id,
            'branch_id' => $branch->id,
            'quantity_on_hand' => 50,
            'reorder_level' => 10,
        ]);

        // Regular list
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/inventory/items');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Low stock list — must include the low-stock item and NOT include the well-stocked item
        $lowStockResponse = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/inventory/items?filter[low_stock]=1');

        $lowStockResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['code' => 'ITEM-01'])
            ->assertJsonMissing(['code' => 'ITEM-02']);
    }

    public function test_can_create_and_receive_purchase_order(): void
    {
        $admin = User::where('email', 'admin@clinic.test')->first();
        $branch = Branch::where('code', 'MAIN')->first();

        $item = InventoryItem::create([
            'code' => 'ITEM-PO-01',
            'name' => 'Dental Composite',
            'unit' => 'syringe',
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'name' => 'Dental Supply Co',
            'contact_person' => 'John',
            'phone' => '123456',
        ]);

        // Create PO
        $poResponse = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/purchases', [
                'branch_id' => $branch->id,
                'supplier_id' => $supplier->id,
                'items' => [
                    [
                        'inventory_item_id' => $item->id,
                        'quantity' => 10,
                        'unit_cost' => 15.00,
                    ],
                ],
            ]);

        $poResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'draft');

        $purchaseId = $poResponse->json('data.id');

        // Receive PO
        $receiveResponse = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/purchases/{$purchaseId}/receive");

        $receiveResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'received');

        $this->assertDatabaseHas('inventory_stocks', [
            'inventory_item_id' => $item->id,
            'branch_id' => $branch->id,
            'quantity_on_hand' => 10,
        ]);
    }

    public function test_can_manage_service_consumption_templates(): void
    {
        $admin = User::where('email', 'admin@clinic.test')->first();
        $service = Service::first();

        $item = InventoryItem::create([
            'code' => 'ITEM-TMP-01',
            'name' => 'Anesthetic Needle',
            'unit' => 'piece',
            'is_active' => true,
        ]);

        // Save consumption rule
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/services/{$service->id}/consumption", [
                'inventory_item_id' => $item->id,
                'quantity_per_service' => 2.0,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('service_inventory_consumption', [
            'service_id' => $service->id,
            'inventory_item_id' => $item->id,
            'quantity_per_service' => 2.0,
        ]);
    }
}
