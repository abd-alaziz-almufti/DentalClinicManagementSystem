<?php

namespace Tests\Feature;

use App\Exceptions\InvalidPurchaseStatusException;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\DoctorProfile;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\Models\Patient;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\ServiceInventoryConsumption;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitService;
use App\Services\InventoryDeductionService;
use App\Services\PurchaseService;
use App\Services\RecordTreatmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;
    private DoctorProfile $doctorProfile;
    private Branch $branch;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->branch = Branch::where('code', 'MAIN')->firstOrFail();
        $this->doctorUser = User::where('email', 'doctor@clinic.test')->firstOrFail();
        $this->doctorProfile = $this->doctorUser->doctorProfile;

        $this->patient = Patient::create([
            'patient_number' => 'PAT-TEST-999',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'gender' => 'female',
            'birth_date' => '1995-05-05',
            'phone' => '987654321',
            'registered_branch_id' => $this->branch->id,
            'created_by' => $this->doctorUser->id,
        ]);
    }

    private function createInprogressVisit(): Visit
    {
        $appointment = Appointment::create([
            'branch_id' => $this->branch->id,
            'doctor_profile_id' => $this->doctorProfile->id,
            'patient_id' => $this->patient->id,
            'appointment_date' => today()->toDateString(),
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'status' => 'attended',
            'reason' => 'Cleaning',
            'created_by' => $this->doctorUser->id,
        ]);

        return Visit::create([
            'visit_number' => 'VIS-TEST-INV-1',
            'appointment_id' => $appointment->id,
            'patient_id' => $this->patient->id,
            'doctor_profile_id' => $this->doctorProfile->id,
            'branch_id' => $this->branch->id,
            'checked_in_at' => now(),
            'status' => 'in_progress',
            'created_by' => $this->doctorUser->id,
        ]);
    }

    public function test_consumption_template_and_automatic_deduction(): void
    {
        // 1. Create global items
        $glove = InventoryItem::create([
            'code' => 'GLV-M',
            'name' => 'Dental Gloves Size M',
            'unit' => 'piece',
        ]);

        $anesthetic = InventoryItem::create([
            'code' => 'ANES-1',
            'name' => 'Local Anesthetic Ampoule',
            'unit' => 'piece',
        ]);

        // Set initial stock for gloves and anesthetic in Branch MAIN
        InventoryStock::create([
            'branch_id' => $this->branch->id,
            'inventory_item_id' => $glove->id,
            'quantity_on_hand' => 100.00,
            'reorder_level' => 10.00,
        ]);

        InventoryStock::create([
            'branch_id' => $this->branch->id,
            'inventory_item_id' => $anesthetic->id,
            'quantity_on_hand' => 50.00,
            'reorder_level' => 5.00,
        ]);

        // 2. Link service to item consumption
        $service = Service::firstOrFail();
        ServiceInventoryConsumption::create([
            'service_id' => $service->id,
            'inventory_item_id' => $glove->id,
            'quantity_per_service' => 2.00,
        ]);

        ServiceInventoryConsumption::create([
            'service_id' => $service->id,
            'inventory_item_id' => $anesthetic->id,
            'quantity_per_service' => 1.00,
        ]);

        // 3. Record treatment service
        $visit = $this->createInprogressVisit();
        $treatmentService = app(RecordTreatmentService::class);
        $visitService = $treatmentService->addService($visit, $service, ['quantity' => 3]);

        // 4. Assert stock deducted correctly (2 * 3 = 6 gloves, 1 * 3 = 3 anesthetic)
        $gloveStock = InventoryStock::where('branch_id', $this->branch->id)->where('inventory_item_id', $glove->id)->firstOrFail();
        $anestheticStock = InventoryStock::where('branch_id', $this->branch->id)->where('inventory_item_id', $anesthetic->id)->firstOrFail();

        $this->assertEquals(94.00, (float) $gloveStock->quantity_on_hand);
        $this->assertEquals(47.00, (float) $anestheticStock->quantity_on_hand);

        // 5. Assert transactions are recorded
        $this->assertDatabaseHas('inventory_transactions', [
            'branch_id' => $this->branch->id,
            'inventory_item_id' => $glove->id,
            'type' => 'consumption',
            'quantity' => -6.00,
            'reference_type' => VisitService::class,
            'reference_id' => $visitService->id,
        ]);

        $this->assertDatabaseHas('inventory_transactions', [
            'branch_id' => $this->branch->id,
            'inventory_item_id' => $anesthetic->id,
            'type' => 'consumption',
            'quantity' => -3.00,
            'reference_type' => VisitService::class,
            'reference_id' => $visitService->id,
        ]);
    }

    public function test_automatic_stock_return_on_treatment_removal(): void
    {
        // 1. Create global item & stock
        $glove = InventoryItem::create([
            'code' => 'GLV-L',
            'name' => 'Dental Gloves Size L',
            'unit' => 'piece',
        ]);

        InventoryStock::create([
            'branch_id' => $this->branch->id,
            'inventory_item_id' => $glove->id,
            'quantity_on_hand' => 10.00,
            'reorder_level' => 2.00,
        ]);

        // 2. Consumption template
        $service = Service::firstOrFail();
        ServiceInventoryConsumption::create([
            'service_id' => $service->id,
            'inventory_item_id' => $glove->id,
            'quantity_per_service' => 2.00,
        ]);

        // 3. Add service (deducts 2 * 2 = 4 gloves)
        $visit = $this->createInprogressVisit();
        $treatmentService = app(RecordTreatmentService::class);
        $visitService = $treatmentService->addService($visit, $service, ['quantity' => 2]);

        $gloveStock = InventoryStock::where('branch_id', $this->branch->id)->where('inventory_item_id', $glove->id)->firstOrFail();
        $this->assertEquals(6.00, (float) $gloveStock->quantity_on_hand);

        // 4. Remove service (returns 4 gloves)
        $treatmentService->removeService($visitService);

        $gloveStock->refresh();
        $this->assertEquals(10.00, (float) $gloveStock->quantity_on_hand);

        // 5. Verify transaction created for return
        $this->assertDatabaseHas('inventory_transactions', [
            'branch_id' => $this->branch->id,
            'inventory_item_id' => $glove->id,
            'type' => 'return',
            'quantity' => 4.00,
        ]);
    }

    public function test_purchase_order_lifecycle(): void
    {
        $supplier = Supplier::create(['name' => 'Dental Supplies Corp']);
        $item1 = InventoryItem::create(['code' => 'ITEM-A', 'name' => 'Item A', 'unit' => 'pcs']);
        $item2 = InventoryItem::create(['code' => 'ITEM-B', 'name' => 'Item B', 'unit' => 'pcs']);

        $purchaseService = app(PurchaseService::class);

        // 1. Create PO (Draft)
        $purchase = $purchaseService->create(
            $this->branch,
            $supplier,
            [
                ['inventory_item_id' => $item1->id, 'quantity' => 10, 'unit_cost' => 5.00],
                ['inventory_item_id' => $item2->id, 'quantity' => 20, 'unit_cost' => 10.00],
            ],
            ['notes' => 'First purchase order', 'created_by' => $this->doctorUser->id]
        );

        $this->assertInstanceOf(Purchase::class, $purchase);
        $this->assertEquals('draft', $purchase->status);
        $this->assertEquals(250.00, (float) $purchase->total_cost);
        $this->assertStringStartsWith('PUR-', $purchase->purchase_number);
        $this->assertCount(2, $purchase->items);

        // Verify stock is not modified yet
        $stock1 = InventoryStock::where('branch_id', $this->branch->id)->where('inventory_item_id', $item1->id)->first();
        $this->assertNull($stock1);

        // 2. Receive PO (Stock In)
        $purchase = $purchaseService->receive($purchase->id, $this->doctorUser->id);

        $this->assertEquals('received', $purchase->status);
        $this->assertNotNull($purchase->received_at);

        // Verify stock updated
        $stock1 = InventoryStock::where('branch_id', $this->branch->id)->where('inventory_item_id', $item1->id)->firstOrFail();
        $stock2 = InventoryStock::where('branch_id', $this->branch->id)->where('inventory_item_id', $item2->id)->firstOrFail();

        $this->assertEquals(10.00, (float) $stock1->quantity_on_hand);
        $this->assertEquals(20.00, (float) $stock2->quantity_on_hand);

        // Verify transaction logged
        $this->assertDatabaseHas('inventory_transactions', [
            'branch_id' => $this->branch->id,
            'inventory_item_id' => $item1->id,
            'type' => 'purchase_in',
            'quantity' => 10.00,
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
        ]);
    }

    public function test_purchase_order_cancellation_and_terminal_states(): void
    {
        $supplier = Supplier::create(['name' => 'Dental Supplies Corp']);
        $item1 = InventoryItem::create(['code' => 'ITEM-C', 'name' => 'Item C', 'unit' => 'pcs']);

        $purchaseService = app(PurchaseService::class);

        $purchase = $purchaseService->create(
            $this->branch,
            $supplier,
            [
                ['inventory_item_id' => $item1->id, 'quantity' => 5, 'unit_cost' => 10.00],
            ]
        );

        // Cancel PO
        $purchase = $purchaseService->cancel($purchase->id);
        $this->assertEquals('cancelled', $purchase->status);

        // Cannot cancel again or receive cancelled PO
        $this->expectException(InvalidPurchaseStatusException::class);
        $purchaseService->receive($purchase->id);
    }

    public function test_inventory_transaction_immutability(): void
    {
        $item = InventoryItem::create(['code' => 'TX-TEST', 'name' => 'TX Test', 'unit' => 'pcs']);

        $tx = InventoryTransaction::create([
            'inventory_item_id' => $item->id,
            'branch_id' => $this->branch->id,
            'type' => 'adjustment',
            'quantity' => 10.00,
            'item_name_snapshot' => $item->name,
            'item_unit_snapshot' => $item->unit,
            'notes' => 'Manual increase',
        ]);

        $this->expectException(\LogicException::class);
        $tx->delete();
    }
}
