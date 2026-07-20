<?php

namespace Tests\Feature;

use App\Exceptions\InvalidInvoiceStatusException;
use App\Exceptions\PaymentExceedsBalanceException;
use App\Exceptions\VisitAlreadyInvoicedException;
use App\Exceptions\VisitNotEditableException;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\DoctorProfile;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitService;
use App\Services\CancelInvoiceService;
use App\Services\GenerateInvoiceService;
use App\Services\RecordPaymentService;
use App\Services\RecordTreatmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialModuleTest extends TestCase
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
            'patient_number' => 'PAT-TEST-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'male',
            'birth_date' => '1990-01-01',
            'phone' => '123456789',
            'registered_branch_id' => $this->branch->id,
            'created_by' => $this->doctorUser->id,
        ]);
    }

    private function createCompletedVisitWithServices(array $servicesData): Visit
    {
        $appointment = Appointment::create([
            'branch_id' => $this->branch->id,
            'doctor_profile_id' => $this->doctorProfile->id,
            'patient_id' => $this->patient->id,
            'appointment_date' => today()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'attended',
            'reason' => 'Checkup',
            'created_by' => $this->doctorUser->id,
        ]);

        $visit = Visit::create([
            'visit_number' => 'VIS-TEST-001',
            'appointment_id' => $appointment->id,
            'patient_id' => $this->patient->id,
            'doctor_profile_id' => $this->doctorProfile->id,
            'branch_id' => $this->branch->id,
            'checked_in_at' => now(),
            'status' => 'completed',
            'created_by' => $this->doctorUser->id,
        ]);

        foreach ($servicesData as $data) {
            $service = $data['service'];
            $qty = $data['quantity'] ?? 1;
            $price = $data['unit_price'] ?? $service->default_price;
            $discount = $data['discount_amount'] ?? 0.00;
            $total = round(($price * $qty) - $discount, 2);

            VisitService::create([
                'visit_id' => $visit->id,
                'service_id' => $service->id,
                'tooth_number' => $data['tooth_number'] ?? null,
                'quantity' => $qty,
                'unit_price' => $price,
                'discount_amount' => $discount,
                'total' => $total,
                'created_by' => $this->doctorUser->id,
            ]);
        }

        return $visit;
    }

    public function test_generate_invoice_happy_path(): void
    {
        $service = Service::firstOrFail();
        $visit = $this->createCompletedVisitWithServices([
            ['service' => $service, 'quantity' => 2, 'unit_price' => 50.00, 'discount_amount' => 10.00]
        ]);

        $serviceInstance = app(GenerateInvoiceService::class);
        $invoice = $serviceInstance->generate($visit, $this->doctorUser->id);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals(90.00, (float) $invoice->total);
        $this->assertEquals('issued', $invoice->status);
        $this->assertStringStartsWith('INV-', $invoice->invoice_number);

        // Verify items
        $this->assertCount(1, $invoice->items);
        $item = $invoice->items->first();
        $this->assertEquals(2, $item->quantity);
        $this->assertEquals(50.00, (float) $item->unit_price);
        $this->assertEquals(10.00, (float) $item->discount_amount);
        $this->assertEquals(90.00, (float) $item->total);
        $this->assertEquals($service->name, $item->service_name['en']);

        // Verify visit locked
        $visit->refresh();
        $this->assertTrue((bool) $visit->has_active_invoice);
    }

    public function test_generate_invoice_empty_visit_rejected(): void
    {
        $appointment = Appointment::create([
            'branch_id' => $this->branch->id,
            'doctor_profile_id' => $this->doctorProfile->id,
            'patient_id' => $this->patient->id,
            'appointment_date' => today()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'attended',
            'reason' => 'Checkup',
            'created_by' => $this->doctorUser->id,
        ]);

        $visit = Visit::create([
            'visit_number' => 'VIS-TEST-EMPTY',
            'appointment_id' => $appointment->id,
            'patient_id' => $this->patient->id,
            'doctor_profile_id' => $this->doctorProfile->id,
            'branch_id' => $this->branch->id,
            'checked_in_at' => now(),
            'status' => 'completed',
            'created_by' => $this->doctorUser->id,
        ]);

        $serviceInstance = app(GenerateInvoiceService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot generate invoice: Visit has no billed services.');

        $serviceInstance->generate($visit, $this->doctorUser->id);
    }

    public function test_generate_invoice_not_completed_rejected(): void
    {
        $service = Service::firstOrFail();
        
        $appointment = Appointment::create([
            'branch_id' => $this->branch->id,
            'doctor_profile_id' => $this->doctorProfile->id,
            'patient_id' => $this->patient->id,
            'appointment_date' => today()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'attended',
            'reason' => 'Checkup',
            'created_by' => $this->doctorUser->id,
        ]);

        $visit = Visit::create([
            'visit_number' => 'VIS-TEST-OPEN',
            'appointment_id' => $appointment->id,
            'patient_id' => $this->patient->id,
            'doctor_profile_id' => $this->doctorProfile->id,
            'branch_id' => $this->branch->id,
            'checked_in_at' => now(),
            'status' => 'open',
            'created_by' => $this->doctorUser->id,
        ]);

        VisitService::create([
            'visit_id' => $visit->id,
            'service_id' => $service->id,
            'quantity' => 1,
            'unit_price' => 50.00,
            'discount_amount' => 0.00,
            'total' => 50.00,
            'created_by' => $this->doctorUser->id,
        ]);

        $serviceInstance = app(GenerateInvoiceService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot generate invoice: Visit must be completed.');

        $serviceInstance->generate($visit, $this->doctorUser->id);
    }

    public function test_generate_invoice_duplicate_prevented(): void
    {
        $service = Service::firstOrFail();
        $visit = $this->createCompletedVisitWithServices([
            ['service' => $service, 'quantity' => 1]
        ]);

        $serviceInstance = app(GenerateInvoiceService::class);
        $serviceInstance->generate($visit, $this->doctorUser->id);

        $this->expectException(VisitAlreadyInvoicedException::class);
        $serviceInstance->generate($visit, $this->doctorUser->id);
    }

    public function test_record_payments_happy_path(): void
    {
        $service = Service::firstOrFail();
        $visit = $this->createCompletedVisitWithServices([
            ['service' => $service, 'quantity' => 1, 'unit_price' => 100.00]
        ]);

        $invoiceService = app(GenerateInvoiceService::class);
        $invoice = $invoiceService->generate($visit, $this->doctorUser->id);

        $paymentService = app(RecordPaymentService::class);

        // Record first payment
        $payment1 = $paymentService->recordPayment($invoice->id, [
            'amount' => 40.00,
            'payment_method' => 'cash',
            'payment_date' => today()->toDateString(),
            'recorded_by' => $this->doctorUser->id,
        ]);

        $invoice->refresh();
        $this->assertEquals(60.00, $invoice->remaining_balance);
        $this->assertEquals('partially_paid', $invoice->status);

        // Record second payment
        $payment2 = $paymentService->recordPayment($invoice->id, [
            'amount' => 60.00,
            'payment_method' => 'card',
            'payment_date' => today()->toDateString(),
            'recorded_by' => $this->doctorUser->id,
        ]);

        $invoice->refresh();
        $this->assertEquals(0.00, $invoice->remaining_balance);
        $this->assertEquals('paid', $invoice->status);
    }

    public function test_record_payment_overpayment_prevented(): void
    {
        $service = Service::firstOrFail();
        $visit = $this->createCompletedVisitWithServices([
            ['service' => $service, 'quantity' => 1, 'unit_price' => 100.00]
        ]);

        $invoiceService = app(GenerateInvoiceService::class);
        $invoice = $invoiceService->generate($visit, $this->doctorUser->id);

        $paymentService = app(RecordPaymentService::class);

        $this->expectException(PaymentExceedsBalanceException::class);
        $paymentService->recordPayment($invoice->id, [
            'amount' => 110.00,
            'payment_method' => 'cash',
            'payment_date' => today()->toDateString(),
            'recorded_by' => $this->doctorUser->id,
        ]);
    }

    public function test_record_payment_terminal_status_rejected(): void
    {
        $service = Service::firstOrFail();
        $visit = $this->createCompletedVisitWithServices([
            ['service' => $service, 'quantity' => 1, 'unit_price' => 100.00]
        ]);

        $invoiceService = app(GenerateInvoiceService::class);
        $invoice = $invoiceService->generate($visit, $this->doctorUser->id);

        $paymentService = app(RecordPaymentService::class);
        
        // Pay fully to reach Paid (terminal state)
        $paymentService->recordPayment($invoice->id, [
            'amount' => 100.00,
            'payment_method' => 'cash',
            'payment_date' => today()->toDateString(),
            'recorded_by' => $this->doctorUser->id,
        ]);

        $this->expectException(InvalidInvoiceStatusException::class);
        $paymentService->recordPayment($invoice->id, [
            'amount' => 10.00,
            'payment_method' => 'cash',
            'payment_date' => today()->toDateString(),
            'recorded_by' => $this->doctorUser->id,
        ]);
    }

    public function test_reversal_happy_path(): void
    {
        $service = Service::firstOrFail();
        $visit = $this->createCompletedVisitWithServices([
            ['service' => $service, 'quantity' => 1, 'unit_price' => 100.00]
        ]);

        $invoiceService = app(GenerateInvoiceService::class);
        $invoice = $invoiceService->generate($visit, $this->doctorUser->id);

        $paymentService = app(RecordPaymentService::class);

        $payment = $paymentService->recordPayment($invoice->id, [
            'amount' => 40.00,
            'payment_method' => 'cash',
            'payment_date' => today()->toDateString(),
            'recorded_by' => $this->doctorUser->id,
        ]);

        $invoice->refresh();
        $this->assertEquals(60.00, $invoice->remaining_balance);
        $this->assertEquals('partially_paid', $invoice->status);

        // Record reversal
        $reversal = $paymentService->recordReversal($invoice->id, $payment->id, [
            'payment_date' => today()->toDateString(),
            'recorded_by' => $this->doctorUser->id,
            'notes' => 'Mistake payment',
        ]);

        $this->assertEquals('reversal', $reversal->type);
        $this->assertEquals($payment->id, $reversal->reverses_payment_id);
        $this->assertEquals(40.00, (float) $reversal->amount);

        $invoice->refresh();
        $this->assertEquals(100.00, $invoice->remaining_balance);
        $this->assertEquals('issued', $invoice->status);
    }

    public function test_reversal_duplicate_prevented(): void
    {
        $service = Service::firstOrFail();
        $visit = $this->createCompletedVisitWithServices([
            ['service' => $service, 'quantity' => 1, 'unit_price' => 100.00]
        ]);

        $invoiceService = app(GenerateInvoiceService::class);
        $invoice = $invoiceService->generate($visit, $this->doctorUser->id);

        $paymentService = app(RecordPaymentService::class);

        $payment = $paymentService->recordPayment($invoice->id, [
            'amount' => 40.00,
            'payment_method' => 'cash',
            'payment_date' => today()->toDateString(),
            'recorded_by' => $this->doctorUser->id,
        ]);

        // First reversal works
        $paymentService->recordReversal($invoice->id, $payment->id, [
            'payment_date' => today()->toDateString(),
            'recorded_by' => $this->doctorUser->id,
        ]);

        // Second reversal must fail (FR-019)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('This payment has already been reversed.');

        $paymentService->recordReversal($invoice->id, $payment->id, [
            'payment_date' => today()->toDateString(),
            'recorded_by' => $this->doctorUser->id,
        ]);
    }

    public function test_reversal_terminal_status_rejected(): void
    {
        $service = Service::firstOrFail();
        $visit = $this->createCompletedVisitWithServices([
            ['service' => $service, 'quantity' => 1, 'unit_price' => 100.00]
        ]);

        $invoiceService = app(GenerateInvoiceService::class);
        $invoice = $invoiceService->generate($visit, $this->doctorUser->id);

        $paymentService = app(RecordPaymentService::class);

        $payment = $paymentService->recordPayment($invoice->id, [
            'amount' => 100.00,
            'payment_method' => 'cash',
            'payment_date' => today()->toDateString(),
            'recorded_by' => $this->doctorUser->id,
        ]);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status); // Paid (terminal state)

        $this->expectException(InvalidInvoiceStatusException::class);
        $paymentService->recordReversal($invoice->id, $payment->id, [
            'payment_date' => today()->toDateString(),
            'recorded_by' => $this->doctorUser->id,
        ]);
    }

    public function test_cancel_invoice_happy_path(): void
    {
        $service = Service::firstOrFail();
        $visit = $this->createCompletedVisitWithServices([
            ['service' => $service, 'quantity' => 1]
        ]);

        $invoiceService = app(GenerateInvoiceService::class);
        $invoice = $invoiceService->generate($visit, $this->doctorUser->id);

        $cancelService = app(CancelInvoiceService::class);
        $cancelService->cancel($invoice->id, 'Request cancelled by patient');

        $invoice->refresh();
        $this->assertEquals('cancelled', $invoice->status);
        $this->assertNotNull($invoice->cancelled_at);
        $this->assertEquals('Request cancelled by patient', $invoice->cancellation_reason);

        // Visit must be unlocked
        $visit->refresh();
        $this->assertFalse((bool) $visit->has_active_invoice);
    }

    public function test_cancel_invoice_terminal_status_rejected(): void
    {
        $service = Service::firstOrFail();
        $visit = $this->createCompletedVisitWithServices([
            ['service' => $service, 'quantity' => 1, 'unit_price' => 100.00]
        ]);

        $invoiceService = app(GenerateInvoiceService::class);
        $invoice = $invoiceService->generate($visit, $this->doctorUser->id);

        $paymentService = app(RecordPaymentService::class);
        $paymentService->recordPayment($invoice->id, [
            'amount' => 100.00,
            'payment_method' => 'cash',
            'payment_date' => today()->toDateString(),
            'recorded_by' => $this->doctorUser->id,
        ]);

        $cancelService = app(CancelInvoiceService::class);

        $this->expectException(InvalidInvoiceStatusException::class);
        $cancelService->cancel($invoice->id, 'Patient refund request');
    }

    public function test_visit_editability_locked_after_invoicing(): void
    {
        $service = Service::firstOrFail();
        
        $appointment = Appointment::create([
            'branch_id' => $this->branch->id,
            'doctor_profile_id' => $this->doctorProfile->id,
            'patient_id' => $this->patient->id,
            'appointment_date' => today()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'attended',
            'reason' => 'Checkup',
            'created_by' => $this->doctorUser->id,
        ]);

        $visit = Visit::create([
            'visit_number' => 'VIS-TEST-GUARD',
            'appointment_id' => $appointment->id,
            'patient_id' => $this->patient->id,
            'doctor_profile_id' => $this->doctorProfile->id,
            'branch_id' => $this->branch->id,
            'checked_in_at' => now(),
            'status' => 'in_progress',
            'created_by' => $this->doctorUser->id,
        ]);

        // Manually lock it to simulate invoicing (R3 decoupling flag)
        $visit->has_active_invoice = true;
        $visit->save();

        $treatmentService = app(RecordTreatmentService::class);

        $this->expectException(VisitNotEditableException::class);
        $this->expectExceptionMessage('Cannot modify services on a visit that already has an active invoice.');

        $treatmentService->addService($visit, $service, ['quantity' => 1]);
    }
}
