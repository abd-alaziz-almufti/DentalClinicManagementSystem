<?php

namespace Database\Seeders;

use App\Exceptions\AppointmentConflictException;
use App\Models\Branch;
use App\Models\DoctorProfile;
use App\Models\Service;
use App\Models\Specialty;
use App\Models\Tooth;
use App\Models\ToothCondition;
use App\Models\ToothSurface;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\CheckInPatientService;
use App\Services\DentalChartService;
use App\Services\PatientService;
use App\Services\RecordTreatmentService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Populates the database with realistic, interconnected demo data by
 * driving it entirely through the existing Service Layer — never raw
 * Eloquent::create() for anything with a business rule attached — so the
 * seeded data is guaranteed to respect every constraint already built
 * (no appointment overlaps, correct patient_number/visit_number/
 * invoice_number sequencing, price snapshots, visit-editability locks,
 * etc.). This makes it safe as real frontend test data, not just
 * plausible-looking rows.
 *
 * FINANCIAL SECTION: GenerateInvoiceService / RecordPaymentService /
 * CancelInvoiceService signatures were confirmed against your actual
 * implementation on 2026-07-23 (see seedFinancials() below) — no longer
 * an assumption.
 *
 * ⚠ INVENTORY: intentionally NOT seeded here — same reason noted in
 * 004-http-api-layer/tasks.md Phase 7 (actual class names not visible in
 * this conversation). Add a seedInventory() method following this same
 * pattern once you share those class names, or write it yourself
 * following the "always go through the Service layer" rule above.
 *
 * ⚠ NOTE ON VISIT COMPLETION: there is currently no formal
 * "CompleteVisitService" — this seeder marks visits completed via a
 * direct ->update(['status' => 'completed']), which is the only path
 * that exists today. Worth building a real service for this later for
 * the same reasons every other status transition already has one.
 */
class DemoDataSeeder extends Seeder
{
    private array $maleFirstNames = [
        'Ahmad', 'Mohammed', 'Khaled', 'Yousef', 'Omar', 'Tariq', 'Samer',
        'Fadi', 'Nasser', 'Hassan', 'Karim', 'Rami', 'Bassel', 'Ziad',
    ];

    private array $femaleFirstNames = [
        'Sara', 'Rana', 'Lina', 'Dina', 'Noor', 'Hala', 'Maya', 'Reem',
        'Yasmin', 'Dana', 'Farah', 'Layla', 'Nadia', 'Salma',
    ];

    private array $lastNames = [
        'Khalil', 'Odeh', 'Hamdan', 'Awad', 'Barakat', 'Salem', 'Masri',
        'Qasem', 'Nabulsi', 'Zaid', 'Shaheen', 'Hijazi', 'Dweik', 'Sabbagh',
    ];

    private array $allergies = [
        null, null, null, 'Penicillin', 'Latex', 'Ibuprofen', 'Sulfa drugs',
    ];

    private array $chronicConditions = [
        null, null, null, 'Diabetes Type 2', 'Hypertension', 'Asthma',
    ];

    public function run(): void
    {
        $patientService = app(PatientService::class);
        $appointmentService = app(AppointmentService::class);
        $checkInService = app(CheckInPatientService::class);
        $treatmentService = app(RecordTreatmentService::class);
        $chartService = app(DentalChartService::class);

        $admin = User::where('email', 'admin@clinic.test')->firstOrFail();

        $branches = $this->seedBranches();
        $doctorProfiles = $this->seedDoctors($branches);
        $patients = $this->seedPatients($patientService, $branches, $admin);
        $services = Service::where('is_active', true)->get();
        $teeth = Tooth::all();
        $conditions = ToothCondition::all();
        $surfaces = ToothSurface::all();

        $this->seedAppointmentsAndClinicalData(
            $appointmentService,
            $checkInService,
            $treatmentService,
            $chartService,
            $branches,
            $doctorProfiles,
            $patients,
            $services,
            $teeth,
            $conditions,
            $surfaces,
            $admin,
        );

        $this->command?->info('Demo data seeding complete.');
    }

    /**
     * Adds two more branches beyond the MAIN one from BranchSeeder, so
     * multi-branch scoping (Constitution Article VIII) has something
     * real to filter against in the frontend.
     */
    private function seedBranches(): \Illuminate\Support\Collection
    {
        $extra = [
            ['code' => 'NBL', 'name' => 'Lumina Dental - Nablus', 'city' => 'Nablus'],
            ['code' => 'RAM', 'name' => 'Lumina Dental - Ramallah', 'city' => 'Ramallah'],
        ];

        foreach ($extra as $branch) {
            Branch::firstOrCreate(
                ['code' => $branch['code']],
                [
                    'name' => $branch['name'],
                    'phone' => '+970-59-' . fake()->numerify('#######'),
                    'email' => strtolower($branch['code']) . '@clinic.test',
                    'address' => $branch['city'],
                    'city' => $branch['city'],
                    'is_active' => true,
                ]
            );
        }

        return Branch::all();
    }

    /**
     * Adds 5 more doctors across specialties/branches, beyond the single
     * sample doctor from UserSeeder — enough variety to exercise
     * doctor-scoped filtering (FR-011) meaningfully.
     */
    private function seedDoctors(\Illuminate\Support\Collection $branches): \Illuminate\Support\Collection
    {
        $specialties = Specialty::all();
        $colors = ['#2563EB', '#DC2626', '#059669', '#7C3AED', '#EA580C'];

        $names = [
            ['first' => 'Layla', 'last' => 'Hamdan', 'gender' => 'female'],
            ['first' => 'Samer', 'last' => 'Masri', 'gender' => 'male'],
            ['first' => 'Rana', 'last' => 'Barakat', 'gender' => 'female'],
            ['first' => 'Tariq', 'last' => 'Nabulsi', 'gender' => 'male'],
            ['first' => 'Dana', 'last' => 'Shaheen', 'gender' => 'female'],
        ];

        foreach ($names as $i => $person) {
            $email = strtolower($person['first'] . '.' . $person['last']) . '@clinic.test';

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => "Dr. {$person['first']} {$person['last']}",
                    'password' => Hash::make('password'),
                    'branch_id' => $branches->random()->id,
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles(['doctor']);

            DoctorProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'specialty_id' => $specialties->random()->id,
                    'license_number' => 'LIC-' . str_pad((string) ($i + 2), 5, '0', STR_PAD_LEFT),
                    'color' => $colors[$i % count($colors)],
                ]
            );
        }

        return DoctorProfile::with('user')->get();
    }

    /**
     * Registers 35 patients via PatientService::register() — exercises
     * real patient_number generation across multiple branches.
     */
    private function seedPatients(
        PatientService $patientService,
        \Illuminate\Support\Collection $branches,
        User $admin
    ): \Illuminate\Support\Collection {
        $patients = collect();

        for ($i = 0; $i < 35; $i++) {
            $isMale = fake()->boolean();
            $firstName = $isMale
                ? fake()->randomElement($this->maleFirstNames)
                : fake()->randomElement($this->femaleFirstNames);
            $lastName = fake()->randomElement($this->lastNames);
            $branch = $branches->random();

            $patient = $patientService->register([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'gender' => $isMale ? 'male' : 'female',
                'birth_date' => fake()->dateTimeBetween('-75 years', '-5 years')->format('Y-m-d'),
                'phone' => '059' . fake()->numerify('#######'),
                'email' => fake()->optional(0.6)->safeEmail(),
                'address' => $branch->city,
                'registered_branch_id' => $branch->id,
                'created_by' => $admin->id,
            ]);

            $allergy = fake()->randomElement($this->allergies);
            $condition = fake()->randomElement($this->chronicConditions);
            if ($allergy || $condition) {
                $patient->medicalProfile->update([
                    'blood_type' => fake()->optional(0.5)->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
                    'allergies' => $allergy,
                    'chronic_diseases' => $condition,
                ]);
            }

            $patients->push($patient);
        }

        return $patients;
    }

    /**
     * The core of the seeder: books appointments spread across the past
     * 3 weeks and next 2 weeks, then — for past appointments — drives
     * roughly half of them all the way through check-in, treatment,
     * dental chart entries, visit completion, invoicing, and payment.
     * The other half are left as no_show/cancelled/still-scheduled for
     * realistic variety.
     */
    private function seedAppointmentsAndClinicalData(
        AppointmentService $appointmentService,
        CheckInPatientService $checkInService,
        RecordTreatmentService $treatmentService,
        DentalChartService $chartService,
        \Illuminate\Support\Collection $branches,
        \Illuminate\Support\Collection $doctorProfiles,
        \Illuminate\Support\Collection $patients,
        \Illuminate\Support\Collection $services,
        \Illuminate\Support\Collection $teeth,
        \Illuminate\Support\Collection $conditions,
        \Illuminate\Support\Collection $surfaces,
        User $admin,
    ): void {
        $startDate = Carbon::today()->subWeeks(3);
        $endDate = Carbon::today()->addWeeks(2);

        $completedVisits = collect();

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if ($date->isFriday()) {
                continue; // clinic closed
            }

            // 3-6 appointments per working day
            $appointmentsToday = fake()->numberBetween(3, 6);

            for ($i = 0; $i < $appointmentsToday; $i++) {
                $doctorProfile = $doctorProfiles->random();
                $patient = $patients->random();
                $branch = $branches->random();
                [$start, $end] = $this->randomSlot();

                $appointment = null;
                for ($attempt = 0; $attempt < 5 && ! $appointment; $attempt++) {
                    try {
                        $appointment = $appointmentService->book([
                            'branch_id' => $branch->id,
                            'doctor_profile_id' => $doctorProfile->id,
                            'patient_id' => $patient->id,
                            'appointment_date' => $date->toDateString(),
                            'start_time' => $start,
                            'end_time' => $end,
                            'reason' => fake()->randomElement([
                                'Routine Checkup', 'Toothache', 'Cleaning',
                                'Follow-up', 'Consultation', 'Filling',
                            ]),
                            'created_by' => $admin->id,
                        ]);
                    } catch (AppointmentConflictException) {
                        [$start, $end] = $this->randomSlot(); // retry a different slot
                    }
                }

                if (! $appointment) {
                    continue; // gave up after 5 attempts, skip — fine for demo data
                }

                if ($date->isFuture()) {
                    continue; // future appointments stay "scheduled"
                }

                // Past appointment: decide its outcome
                $outcome = fake()->randomElement(['attended', 'attended', 'attended', 'no_show', 'cancelled']);

                if ($outcome === 'no_show') {
                    $appointmentService->markNoShow($appointment);

                    continue;
                }

                if ($outcome === 'cancelled') {
                    $appointmentService->cancel($appointment);

                    continue;
                }

                // attended -> check in, treat, chart, complete
                $visit = $checkInService->checkIn($appointment, checkedInBy: $admin->id);

                $serviceCount = fake()->numberBetween(1, 3);
                foreach (range(1, $serviceCount) as $_) {
                    $service = $services->random();
                    $tooth = $teeth->random();

                    $visitService = $treatmentService->addService($visit, $service, [
                        'tooth_number' => $tooth->fdi_number,
                        'notes' => fake()->optional(0.3)->sentence(),
                        'created_by' => $doctorProfile->user_id,
                    ]);

                    if (fake()->boolean(60)) {
                        $chartService->addEntry(
                            $visit,
                            $tooth,
                            $conditions->random(),
                            fake()->randomElement(['diagnosis', 'treatment']),
                            [
                                'tooth_surface_id' => fake()->optional(0.7)->randomElement($surfaces->all())?->id,
                                'visit_service_id' => $visitService->id,
                                'created_by' => $doctorProfile->user_id,
                            ]
                        );
                    }
                }

                $visit->update([
                    'status' => 'completed',
                    'chief_complaint' => fake()->sentence(),
                    'diagnosis' => fake()->sentence(),
                    'treatment_plan' => fake()->optional(0.5)->sentence(),
                ]);

                $completedVisits->push($visit);
            }
        }

        $this->seedFinancials($completedVisits, $admin);
    }

    private function randomSlot(): array
    {
        $hour = fake()->numberBetween(9, 16);
        $minute = fake()->randomElement([0, 30]);
        $start = sprintf('%02d:%02d', $hour, $minute);
        $endMinute = $minute + 30;
        $endHour = $hour + intdiv($endMinute, 60);
        $end = sprintf('%02d:%02d', $endHour, $endMinute % 60);

        return [$start, $end];
    }

    /**
     * Generates invoices for ~70% of completed visits, with a realistic
     * payment-status spread: fully paid, partially paid, unpaid, one
     * cancelled invoice, and one payment reversal — enough variety to
     * exercise every invoice/payment state the frontend needs to render.
     *
     * Signatures confirmed against your actual implementation
     * (2026-07-23): GenerateInvoiceService::generate(Visit, ?int) matches
     * the original data-model.md exactly. RecordPaymentService splits
     * normal payments and reversals into two methods
     * (recordPayment/recordReversal), both keyed by invoice ID (int),
     * not an Invoice object. CancelInvoiceService::cancel() takes a
     * plain string reason, not an array.
     */
    private function seedFinancials(\Illuminate\Support\Collection $completedVisits, User $admin): void
    {
        if (! class_exists(\App\Services\GenerateInvoiceService::class)) {
            $this->command?->warn(
                'GenerateInvoiceService not found — skipping invoice/payment seeding. '
                . 'Run this seeder again after confirming the class exists.'
            );

            return;
        }

        $invoiceService = app(\App\Services\GenerateInvoiceService::class);
        $paymentService = app(\App\Services\RecordPaymentService::class);
        $cancelService = app(\App\Services\CancelInvoiceService::class);

        $eligible = $completedVisits->filter(fn ($v) => $v->visitServices()->sum('total') > 0);
        $toInvoice = $eligible->random(min($eligible->count(), (int) ($eligible->count() * 0.7)));

        $reversalDemoed = false;
        $cancellationDemoed = false;

        foreach ($toInvoice as $visit) {
            try {
                $invoice = $invoiceService->generate($visit, $admin->id);
            } catch (\Throwable $e) {
                continue; // already invoiced or ineligible — skip for demo purposes
            }

            $paymentMethod = fake()->randomElement(['cash', 'card', 'bank_transfer']);
            $outcome = fake()->randomElement(['paid', 'paid', 'partially_paid', 'partially_paid', 'unpaid']);

            $recordedPayment = null;

            if ($outcome === 'paid') {
                $recordedPayment = $paymentService->recordPayment($invoice->id, [
                    'amount' => $invoice->total,
                    'payment_method' => $paymentMethod,
                    'payment_date' => now()->toDateString(),
                    'recorded_by' => $admin->id,
                ]);
            } elseif ($outcome === 'partially_paid') {
                $partial = round($invoice->total * fake()->randomFloat(2, 0.3, 0.7), 2);
                $recordedPayment = $paymentService->recordPayment($invoice->id, [
                    'amount' => $partial,
                    'payment_method' => $paymentMethod,
                    'payment_date' => now()->toDateString(),
                    'recorded_by' => $admin->id,
                ]);
            }
            // 'unpaid' -> leave the invoice as issued, no payment

            // Demo one reversal, once. MUST target a still-partially-paid
            // invoice, never a fully 'paid' one — Paid is a terminal state
            // (spec.md Acceptance Scenario 8) and the real
            // RecordPaymentService correctly refuses to reverse into it,
            // exactly as designed.
            if (! $reversalDemoed && $outcome === 'partially_paid' && $recordedPayment) {
                $reversalDemoed = true;

                $paymentService->recordReversal($invoice->id, $recordedPayment->id, [
                    'payment_date' => now()->toDateString(),
                    'recorded_by' => $admin->id,
                    'notes' => 'Demo reversal — recorded in error, correcting.',
                ]);

                continue;
            }

            // Demo one cancelled invoice, once
            if (! $cancellationDemoed && $outcome !== 'paid') {
                $cancellationDemoed = true;

                $cancelService->cancel(
                    $invoice->id,
                    'Demo: patient disputed charge, invoice voided.'
                );
            }
        }
    }
}