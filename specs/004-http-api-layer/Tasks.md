# Tasks: HTTP API Layer

**Input**: `plan.md`, `research.md`, `data-model.md`, `spec.md` (all Clarified/PASS)
**Convention**: `[P]` = safe to do in parallel with other `[P]` tasks in the
same phase (touches different files, no shared dependency). Tasks without
`[P]` must be done in the listed order.

> **⚠ Flag on Phase 6 (Inventory)**: this task group uses class/table
> names inferred from the original PRD plan (`inventory_items`,
> `service_inventory_consumption`, `inventory_transactions`), since the
> Inventory Module itself was implemented outside this conversation.
> **Confirm your actual Service/Model names before running Phase 6** —
> rename tasks to match your real implementation if it differs.

---

## Phase 1: Cross-Cutting Foundation (blocks every other phase)

Nothing in Phases 2–7 can start until this phase is complete — every
domain Controller depends on the middleware, exception contract, and
query-building integration built here.

- [ ] **T001** Install `spatie/laravel-query-builder` via Composer (R6)
- [ ] **T002** `[P]` Create `app/Exceptions/ApiExceptionInterface.php` (R3, data-model.md)
- [ ] **T003** `[P]` Create `lang/en/exceptions.php` and `lang/ar/exceptions.php` with keys for all 6 known exceptions (data-model.md catalog)
- [ ] **T004** Retrofit `AppointmentConflictException` to implement `ApiExceptionInterface`, replacing the hardcoded string in `forSlot()` with a translation key + params
- [ ] **T005** Retrofit `InvalidAppointmentStatusException` — same pattern as T004
- [ ] **T006** Retrofit `VisitNotEditableException` — same pattern as T004
- [ ] **T007** Retrofit `VisitAlreadyInvoicedException` — same pattern as T004
- [ ] **T008** Retrofit `InvalidInvoiceStatusException` — same pattern as T004
- [ ] **T009** Retrofit `PaymentExceedsBalanceException` — same pattern as T004
- [ ] **T010** Create `app/Http/Middleware/SetLocaleFromRequest.php` (R7 / PRD §10.1 Layer 1)
- [ ] **T011** Register `SetLocaleFromRequest` globally + configure centralized exception→envelope rendering (`withExceptions` in `bootstrap/app.php`) — single place implementing the Error Envelope shape (spec.md §API Contract Standard #1) for both `ApiExceptionInterface` exceptions and standard Laravel exceptions (`ValidationException` → `VALIDATION_ERROR`, `AuthenticationException` → `UNAUTHENTICATED`, `AuthorizationException` → `FORBIDDEN`, `ModelNotFoundException` → `NOT_FOUND`, `ThrottleRequestsException` → `TOO_MANY_REQUESTS`, anything else → generic `SERVER_ERROR` with no internal detail leaked)
- [ ] **T012** `[P]` Create a base API response helper (e.g. `app/Http/Controllers/Api/V1/Controller.php` with `respondSuccess()` / `respondPaginated()`) implementing the Success/Paginated envelope shapes (spec.md §API Contract Standard #1) — every domain Controller extends this, never builds its own envelope
- [ ] **T013** `[P]` Create `app/Services/Support/BranchScopeFilter.php` (R1) — one method, takes a query builder + the authenticated user, applies `where('branch_id', ...)` unless the user is `super-admin`
- [ ] **T014** `[P]` Set `config('sanctum.expiration')` to `480` (R4, FR-017)
- [ ] **T015** `[P]` Register `RateLimiter::for('login', ...)` in `AppServiceProvider`, keyed on `email` + `ip()`, 5/minute (R5, FR-015)
- [ ] **T016** Create `routes/api/v1.php`, register it in `bootstrap/app.php`'s `withRouting()`, apply `auth:sanctum` to all routes except login (FR-001, FR-007)
- [ ] **T017** `[P]` Configure CORS to allow only the Next.js frontend origin(s) (FR-016)

**Checkpoint**: `php artisan route:list` shows `/api/v1/*` registered; a
deliberately-thrown test exception implementing `ApiExceptionInterface`
returns the correct envelope shape with the correct `error_code`.

---

## Phase 2: Auth (Foundation domain)

- [ ] **T018** `[P]` `LoginRequest` (Form Request: email, password)
- [ ] **T019** `[P]` `UserResource` (id, name, email, roles, branch — **never** password hash, per FR-013)
- [ ] **T020** `AuthController@login` — validates via T018, authenticates, issues Sanctum token (respecting T014's expiration), returns success envelope with user (T019) + token (Acceptance Scenario 1/2)
- [ ] **T021** `AuthController@logout` — revokes the current token
- [ ] **T022** `AuthController@me` — returns the authenticated user via T019
- [ ] Manual verification: Acceptance Scenarios 1, 2, 7 (valid login, invalid login, expired/invalid token) from `spec.md`

---

## Phase 3: Patients

- [ ] **T023** `[P]` `PatientPolicy` — `viewAny`/`view` scoped per FR-010 (branch) and FR-011 (doctor restriction: only patients with an existing appointment/visit — see Clarification Q1)
- [ ] **T024** `[P]` `RegisterPatientRequest` (mirrors `PatientService::register()`'s expected fields)
- [ ] **T025** `[P]` `PatientResource` / `PatientMedicalProfileResource`
- [ ] **T026** `PatientController@index` — applies `BranchScopeFilter` (T013) + `spatie/laravel-query-builder` (allowed filters: `phone`, `national_id`; allowed sort: `created_at`, `last_name`; allowed includes: `medicalProfile`), paginated (FR-009, FR-020)
- [ ] **T027** `PatientController@store` — authorizes via T023, validates via T024, calls existing `PatientService::register()` unchanged (Article III/IV — no new business logic)
- [ ] **T028** `PatientController@show` — authorizes via T023 (enforces doctor restriction from Q1)
- [ ] Manual verification: Acceptance Scenario 8 (doctor sees only own patients) from `spec.md`

---

## Phase 4: Appointments

- [ ] **T029** `[P]` `AppointmentPolicy` — doctors restricted to their own (FR-011)
- [ ] **T030** `[P]` `BookAppointmentRequest`
- [ ] **T031** `[P]` `AppointmentResource`
- [ ] **T032** `AppointmentController@index` — `BranchScopeFilter` + query builder (filters: `status`, `doctor_profile_id`, `appointment_date`; sort: `appointment_date`, `start_time`)
- [ ] **T033** `AppointmentController@store` — calls existing `AppointmentService::book()` unchanged; verify `AppointmentConflictException` (now implementing `ApiExceptionInterface` per T004) surfaces as the correct envelope
- [ ] **T034** `AppointmentController@cancel` — calls existing `AppointmentService::cancel()`; documents that this is the `DELETE` verb's actual behavior per FR-022 (status transition, not row deletion)
- [ ] **T035** **Concurrency verification (mandatory, not optional)**: repeat the exact two-simultaneous-HTTP-request test already used for the Service-layer `AppointmentService::book()` test, this time against `POST /api/v1/appointments`, confirming Acceptance Scenario 6 — exactly one request succeeds

---

## Phase 5: Visits (Check-In, Treatment, Dental Chart)

- [ ] **T036** `[P]` `VisitPolicy`
- [ ] **T037** `[P]` `VisitResource`, `VisitServiceResource`, `VisitToothResource`
- [ ] **T038** `[P]` `RecordTreatmentRequest`, `DentalChartEntryRequest`
- [ ] **T039** `VisitController@checkIn` — calls existing `CheckInPatientService::checkIn()` unchanged
- [ ] **T040** `VisitController@show` — includes services/teeth only via `?include=` (FR-021), not by default
- [ ] **T041** `VisitServiceController@store` / `@destroy` — call existing `RecordTreatmentService` methods unchanged; verify `VisitNotEditableException` **and** the new invoice-lock condition (Financial R3's `has_active_invoice`, surfaced through the same guard) both return correct envelopes
- [ ] **T042** `DentalChartController@store` / `@destroy` — call existing `DentalChartService` methods unchanged

---

## Phase 6: Financial ⚠ (see flag at top of file — confirm names first)

- [ ] **T043** `[P]` `InvoicePolicy` — `admin` read-only within branch (FR-018), write actions restricted to `accountant`/`super-admin`
- [ ] **T044** `[P]` `InvoiceResource`, `InvoiceItemResource`, `PaymentResource`
- [ ] **T045** `[P]` `RecordPaymentRequest` (amount, payment_method, payment_date)
- [ ] **T046** `InvoiceController@store` (generate) — calls existing `GenerateInvoiceService`; verify `VisitAlreadyInvoicedException` envelope
- [ ] **T047** `InvoiceController@cancel` — calls existing `CancelInvoiceService`; verify `InvalidInvoiceStatusException` envelope on illegal transitions
- [ ] **T048** `PaymentController@store` — calls existing `RecordPaymentService`; verify `PaymentExceedsBalanceException` envelope
- [ ] **T049** **Concurrency verification (mandatory)**: repeat the concurrent-payment race test against `POST /api/v1/invoices/{invoice}/payments`, confirming Acceptance Scenario 9 from the Financial spec — no overpayment under real simultaneous requests

---

## Phase 7: Inventory ⚠ (names inferred — confirm before running)

- [ ] **T050** `[P]` Policies for `inventory_items` / `purchases` (scoped to `inventory-manager`, `super-admin`; read-only for others per role table)
- [ ] **T051** `[P]` Resources for the Inventory domain's existing Models
- [ ] **T052** `InventoryItemController@index` — `BranchScopeFilter` + query builder (filter: low-stock threshold, per PRD §8 Phase 4 "Low-Stock Items" report)
- [ ] **T053** `PurchaseController@store` — calls your existing Purchase-recording Service unchanged
- [ ] **T054** Endpoint(s) for recording actual consumption against `service_inventory_consumption` templates, calling your existing Service unchanged

---

## Phase 8: Polish & Cross-Feature Verification

- [ ] **T055** Full pass of `spec.md`'s Acceptance Scenarios 1–10, end-to-end over HTTP
- [ ] **T056** Confirm FR-013 (no password hash ever in a response) by inspecting every Resource class
- [ ] **T057** Confirm every list endpoint from Phases 3–7 is paginated by default (FR-009) — no endpoint returns an unbounded array
- [ ] **T058** Confirm every new/retrofitted exception is present in both the Error Code Catalog (`spec.md`) and `data-model.md`'s table — no orphaned `error_code`
- [ ] **T059** `Accept-Language: ar` pass over every endpoint touched in Phases 2–7 — confirm `message` localizes and `error_code` does not change (FR-006, FR-019)

---

## Dependency Graph

```
Phase 1 (Foundation) ──┬── Phase 2 (Auth)
                        ├── Phase 3 (Patients)
                        ├── Phase 4 (Appointments) ── depends on Patients existing (FK)
                        ├── Phase 5 (Visits) ── depends on Appointments + Patients
                        ├── Phase 6 (Financial) ── depends on Visits
                        └── Phase 7 (Inventory) ── mostly independent, but consumption
                                                     endpoint (T054) depends on Visits
                                                     existing (Phase 5)

Phase 8 (Polish) ── depends on ALL of the above
```

Phases 3 and 7 could technically proceed in parallel once Phase 1 is
done (different files, no shared dependency), but 4→5→6 must stay
sequential — each domain's Controller calls the previous domain's
already-verified Service, and the manual/concurrency verification steps
(T035, T049) are only meaningful once their dependency chain is real.