<?php

namespace Tests\Feature\HttpApi;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Tooth;
use App\Models\ToothCondition;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_can_check_in_patient_from_appointment(): void
    {
        $admin = User::where('email', 'admin@clinic.test')->first();
        $branch = Branch::where('code', 'MAIN')->first();
        $doctor = DoctorProfile::first();

        $patient = Patient::create([
            'patient_number' => 'PAT-200',
            'first_name' => 'Bob',
            'last_name' => 'Marley',
            'gender' => 'male',
            'birth_date' => '1985-02-06',
            'national_id' => 'NAT-200',
            'phone' => '555666777',
            'registered_branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'branch_id' => $branch->id,
            'doctor_profile_id' => $doctor->id,
            'patient_id' => $patient->id,
            'appointment_date' => now()->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'reason' => 'Cleaning',
            'status' => 'scheduled',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/appointments/{$appointment->id}/check-in");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'attended',
        ]);
    }

    public function test_can_record_treatment_service_and_dental_chart(): void
    {
        $admin = User::where('email', 'admin@clinic.test')->first();
        $branch = Branch::where('code', 'MAIN')->first();
        $doctor = DoctorProfile::first();
        $service = Service::first();
        $tooth = Tooth::first();
        $condition = ToothCondition::first();

        $patient = Patient::create([
            'patient_number' => 'PAT-201',
            'first_name' => 'Charlie',
            'last_name' => 'Brown',
            'gender' => 'male',
            'birth_date' => '1998-04-04',
            'national_id' => 'NAT-201',
            'phone' => '111333555',
            'registered_branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'branch_id' => $branch->id,
            'doctor_profile_id' => $doctor->id,
            'patient_id' => $patient->id,
            'appointment_date' => now()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'reason' => 'Treatment',
            'status' => 'attended',
            'created_by' => $admin->id,
        ]);

        $visit = Visit::create([
            'visit_number' => 'VIS-201',
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'branch_id' => $branch->id,
            'checked_in_at' => now(),
            'status' => 'open',
            'created_by' => $admin->id,
        ]);

        // Add service
        $svcResponse = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/visits/{$visit->id}/services", [
                'service_id' => $service->id,
                'quantity' => 1,
            ]);

        $svcResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        // Add dental chart entry
        $chartResponse = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/visits/{$visit->id}/teeth", [
                'tooth_id' => $tooth->id,
                'tooth_condition_id' => $condition->id,
                'entry_type' => 'diagnosis',
            ]);

        $chartResponse->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_cannot_modify_completed_visit(): void
    {
        $admin = User::where('email', 'admin@clinic.test')->first();
        $branch = Branch::where('code', 'MAIN')->first();
        $doctor = DoctorProfile::first();
        $service = Service::first();

        $patient = Patient::create([
            'patient_number' => 'PAT-202',
            'first_name' => 'David',
            'last_name' => 'Lee',
            'gender' => 'male',
            'birth_date' => '1993-09-09',
            'national_id' => 'NAT-202',
            'phone' => '222444666',
            'registered_branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'branch_id' => $branch->id,
            'doctor_profile_id' => $doctor->id,
            'patient_id' => $patient->id,
            'appointment_date' => now()->format('Y-m-d'),
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'reason' => 'Completed Visit Test',
            'status' => 'attended',
            'created_by' => $admin->id,
        ]);

        $visit = Visit::create([
            'visit_number' => 'VIS-202',
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'branch_id' => $branch->id,
            'checked_in_at' => now(),
            'status' => 'completed',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/visits/{$visit->id}/services", [
                'service_id' => $service->id,
                'quantity' => 1,
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error_code' => 'VISIT_NOT_EDITABLE',
            ]);
    }
}
