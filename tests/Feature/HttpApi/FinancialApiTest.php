<?php

namespace Tests\Feature\HttpApi;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use App\Services\RecordTreatmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function createTestAppointment(User $admin, Branch $branch, DoctorProfile $doctor, Patient $patient): Appointment
    {
        return Appointment::create([
            'branch_id' => $branch->id,
            'doctor_profile_id' => $doctor->id,
            'patient_id' => $patient->id,
            'appointment_date' => now()->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'reason' => 'Financial Test Appointment',
            'status' => 'attended',
            'created_by' => $admin->id,
        ]);
    }

    private function createCompletedVisitWithService(User $admin, Branch $branch, DoctorProfile $doctor, Patient $patient, Service $service, string $visitNumber, float $unitPrice = 100): Visit
    {
        $appointment = $this->createTestAppointment($admin, $branch, $doctor, $patient);

        $visit = Visit::create([
            'visit_number' => $visitNumber,
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'branch_id' => $branch->id,
            'checked_in_at' => now(),
            'status' => 'open',
            'created_by' => $admin->id,
        ]);

        app(RecordTreatmentService::class)->addService($visit, $service, ['quantity' => 1, 'unit_price' => $unitPrice]);

        $visit->update(['status' => 'completed']);

        return $visit;
    }

    public function test_can_generate_invoice_for_completed_visit(): void
    {
        $admin = User::where('email', 'admin@clinic.test')->first();
        $branch = Branch::where('code', 'MAIN')->first();
        $doctor = DoctorProfile::first();
        $service = Service::first();

        $patient = Patient::create([
            'patient_number' => 'PAT-300',
            'first_name' => 'Eve',
            'last_name' => 'Adams',
            'gender' => 'female',
            'birth_date' => '1995-05-05',
            'national_id' => 'NAT-300',
            'phone' => '888999000',
            'registered_branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $visit = $this->createCompletedVisitWithService($admin, $branch, $doctor, $patient, $service, 'VIS-300');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/visits/{$visit->id}/invoice");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'issued');
    }

    public function test_cannot_generate_duplicate_invoice(): void
    {
        $admin = User::where('email', 'admin@clinic.test')->first();
        $branch = Branch::where('code', 'MAIN')->first();
        $doctor = DoctorProfile::first();
        $service = Service::first();

        $patient = Patient::create([
            'patient_number' => 'PAT-301',
            'first_name' => 'Frank',
            'last_name' => 'Ocean',
            'gender' => 'male',
            'birth_date' => '1990-10-10',
            'national_id' => 'NAT-301',
            'phone' => '333444555',
            'registered_branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $visit = $this->createCompletedVisitWithService($admin, $branch, $doctor, $patient, $service, 'VIS-301');

        // First invoice
        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/visits/{$visit->id}/invoice")->assertStatus(200);

        // Second invoice
        $response = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/visits/{$visit->id}/invoice");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error_code' => 'VISIT_ALREADY_INVOICED',
            ]);
    }

    public function test_can_record_payment_against_invoice(): void
    {
        $admin = User::where('email', 'admin@clinic.test')->first();
        $branch = Branch::where('code', 'MAIN')->first();
        $doctor = DoctorProfile::first();
        $service = Service::first();

        $patient = Patient::create([
            'patient_number' => 'PAT-302',
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'gender' => 'female',
            'birth_date' => '1987-07-07',
            'national_id' => 'NAT-302',
            'phone' => '666777888',
            'registered_branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $visit = $this->createCompletedVisitWithService($admin, $branch, $doctor, $patient, $service, 'VIS-302', 100);

        $invoice = app(\App\Services\GenerateInvoiceService::class)->generate($visit, $admin->id);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", [
                'amount' => 50,
                'payment_method' => 'cash',
                'payment_date' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.amount', '50.00');
    }

    public function test_payment_exceeding_balance_returns_error_envelope(): void
    {
        $admin = User::where('email', 'admin@clinic.test')->first();
        $branch = Branch::where('code', 'MAIN')->first();
        $doctor = DoctorProfile::first();
        $service = Service::first();

        $patient = Patient::create([
            'patient_number' => 'PAT-303',
            'first_name' => 'Henry',
            'last_name' => 'Ford',
            'gender' => 'male',
            'birth_date' => '1980-01-01',
            'national_id' => 'NAT-303',
            'phone' => '999000111',
            'registered_branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $visit = $this->createCompletedVisitWithService($admin, $branch, $doctor, $patient, $service, 'VIS-303', 100);
        $invoice = app(\App\Services\GenerateInvoiceService::class)->generate($visit, $admin->id);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", [
                'amount' => 150,
                'payment_method' => 'cash',
                'payment_date' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'PAYMENT_EXCEEDS_BALANCE',
            ]);
    }
}
