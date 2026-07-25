<?php

namespace Tests\Feature\HttpApi;

use App\Models\Branch;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_can_register_patient(): void
    {
        $admin = User::where('email', 'admin@clinic.test')->first();
        $branch = Branch::where('code', 'MAIN')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/patients', [
                'first_name' => 'John',
                'middle_name' => 'A.',
                'last_name' => 'Doe',
                'gender' => 'male',
                'birth_date' => '1990-05-15',
                'national_id' => 'NAT-12345678',
                'phone' => '+1234567890',
                'registered_branch_id' => $branch->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.first_name', 'John')
            ->assertJsonPath('data.national_id', 'NAT-12345678');

        $this->assertDatabaseHas('patients', [
            'national_id' => 'NAT-12345678',
        ]);
    }

    public function test_patient_registration_fails_with_validation_error(): void
    {
        $admin = User::where('email', 'admin@clinic.test')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/patients', [
                'first_name' => '',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'VALIDATION_ERROR',
            ])
            ->assertJsonStructure(['errors' => ['first_name']]);
    }

    public function test_arabic_localization_header_translates_response(): void
    {
        $admin = User::where('email', 'admin@clinic.test')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->withHeader('Accept-Language', 'ar')
            ->postJson('/api/v1/patients', [
                'first_name' => '',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'VALIDATION_ERROR',
            ]);
    }

    public function test_doctor_cannot_view_unrelated_patient(): void
    {
        $doctor = User::where('email', 'doctor@clinic.test')->first();
        $branch = Branch::where('code', 'MAIN')->first();

        $patient = Patient::create([
            'patient_number' => 'PAT-TEST-01',
            'first_name' => 'Unrelated',
            'last_name' => 'Patient',
            'gender' => 'female',
            'birth_date' => '1995-01-01',
            'national_id' => 'NAT-99999',
            'phone' => '00000000',
            'registered_branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($doctor, 'sanctum')
            ->getJson("/api/v1/patients/{$patient->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error_code' => 'FORBIDDEN',
            ]);
    }
}
