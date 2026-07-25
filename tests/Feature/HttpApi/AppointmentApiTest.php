<?php

namespace Tests\Feature\HttpApi;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_can_book_appointment(): void
    {
        $admin = User::where('email', 'admin@clinic.test')->first();
        $branch = Branch::where('code', 'MAIN')->first();
        $doctor = DoctorProfile::first();

        $patient = Patient::create([
            'patient_number' => 'PAT-100',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'gender' => 'female',
            'birth_date' => '1992-03-10',
            'national_id' => 'NAT-100',
            'phone' => '111222333',
            'registered_branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/appointments', [
                'doctor_profile_id' => $doctor->id,
                'patient_id' => $patient->id,
                'appointment_date' => now()->addDays(2)->format('Y-m-d'),
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'reason' => 'Routine checkup',
                'branch_id' => $branch->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reason', 'Routine checkup');
    }

    public function test_appointment_conflict_returns_correct_envelope(): void
    {
        $admin = User::where('email', 'admin@clinic.test')->first();
        $branch = Branch::where('code', 'MAIN')->first();
        $doctor = DoctorProfile::first();

        $patient = Patient::create([
            'patient_number' => 'PAT-101',
            'first_name' => 'Mark',
            'last_name' => 'Taylor',
            'gender' => 'male',
            'birth_date' => '1988-08-08',
            'national_id' => 'NAT-101',
            'phone' => '444555666',
            'registered_branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $date = now()->addDays(3)->format('Y-m-d');

        // First booking
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/appointments', [
                'doctor_profile_id' => $doctor->id,
                'patient_id' => $patient->id,
                'appointment_date' => $date,
                'start_time' => '14:00:00',
                'end_time' => '15:00:00',
                'reason' => 'First booking',
                'branch_id' => $branch->id,
            ])->assertStatus(200);

        // Overlapping booking
        $response = $this->actingAs($admin, 'sanctum')
            ->withHeader('Accept-Language', 'en')
            ->postJson('/api/v1/appointments', [
                'doctor_profile_id' => $doctor->id,
                'patient_id' => $patient->id,
                'appointment_date' => $date,
                'start_time' => '14:30:00',
                'end_time' => '15:30:00',
                'reason' => 'Overlapping booking',
                'branch_id' => $branch->id,
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error_code' => 'APPOINTMENT_CONFLICT',
            ]);
    }

    public function test_can_cancel_appointment(): void
    {
        $admin = User::where('email', 'admin@clinic.test')->first();
        $branch = Branch::where('code', 'MAIN')->first();
        $doctor = DoctorProfile::first();

        $patient = Patient::create([
            'patient_number' => 'PAT-102',
            'first_name' => 'Alice',
            'last_name' => 'Wonder',
            'gender' => 'female',
            'birth_date' => '1991-01-01',
            'national_id' => 'NAT-102',
            'phone' => '777888999',
            'registered_branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'branch_id' => $branch->id,
            'doctor_profile_id' => $doctor->id,
            'patient_id' => $patient->id,
            'appointment_date' => now()->addDays(4)->format('Y-m-d'),
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'reason' => 'Consultation',
            'status' => 'scheduled',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/appointments/{$appointment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
        ]);
    }
}
