<!--
Sync Impact Report
- Version: N/A → 1.0.0 (initial ratification)
- Rationale: MAJOR — first ratified version, establishing all foundational
  governing principles for the project.
- Principles established: I–X (see below)
- Source: distilled from architectural decisions made and validated across
  the Foundation and Clinical V1 build phases (see project PRD, sections
  5 "Architecture Principles" and 6 "ADR log") — these are not aspirational,
  they are principles already proven in working, tested code.
- Templates requiring updates:
  ✅ plan-template.md — Constitution Check section should reference Articles I–X
  ✅ spec-template.md — no functional-scope conflicts
  ⚠ tasks-template.md — pending: task categorization should reflect
    Article III (Service Layer) and Article IV (Concurrency Safety) as
    first-class task types once /speckit.tasks is run for the first time
-->

# Dental Clinic Management System Constitution

## Project Purpose

Build a production-grade, multi-branch dental clinic management system
(Laravel API + React/Next.js) covering patient records, scheduling, clinical
documentation (including a dental chart), billing, and inventory — designed
to be maintainable and extensible for years, not a prototype or a course
project.

## Core Principles

### I. No Hard Deletes for Medical or Financial Records

Records with medical or financial significance (patients, visits,
appointments, invoices, payments, visit_services, visit_teeth) MUST NOT be
physically deleted. Logical removal MUST be expressed through either a
`status` transition (e.g., `cancelled`, `no_show`) or `SoftDeletes`, never a
real `DELETE`. Pure reference/lookup tables (specialties, service_categories,
teeth, tooth_conditions, tooth_surfaces) are exempt — they carry no
patient-specific history to lose.

**Rationale**: A clinical or financial history that can silently disappear is
not just a data-quality bug in this domain — it is a compliance and patient
-safety failure.

### II. Point-of-Service Snapshot (Controlled Denormalization)

Any data consumed at the moment a service is rendered (price, treating
doctor, branch, patient identity) MUST be copied into the operational record
at creation time, never derived live from reference tables afterward.
Applies to (and constrains all future modules the same way): `visit_services
.unit_price`, `visits.{patient_id,doctor_profile_id,branch_id}`,
`visit_teeth.patient_id`, and — mandatorily — `invoice_items` when the
Financial module is built.

**Rationale**: Medical and financial systems must preserve the state of the
world as it was at the moment of the event, not as it is when someone later
queries it. A service's price changing next month must never rewrite last
month's invoice.

### III. Explicit Service Layer for Business Transactions

Any operation representing a business transaction (booking an appointment,
checking in a patient, recording a treatment) MUST be implemented as an
explicit Service class wrapped in `DB::transaction()`. Model Events
(`creating`, `saving`, Observers) MUST NOT carry business-transaction logic
— they may only be used for pure, side-effect-free data shaping.

**Rationale**: Explicit Service classes are traceable, testable in
isolation, and readable by a new developer without needing to know "hidden"
event wiring exists.

### IV. Concurrency Safety Is Not Optional

Any operation that generates a sequential identifier or reserves a
time-bound resource MUST be protected against race conditions under real
concurrent load — verified with an actual concurrent-request test, not
reasoning alone. The specific locking target MUST be chosen deliberately:

- When the contended resource may not exist as a row yet (e.g., first
  document number of the year), lock the resource counter row and handle
  the first-insert race explicitly (catch + re-read).
- When checking for a scheduling conflict where the "no conflict" case
  returns zero rows, locking the search results is insufficient — lock a
  **proxy row** that always exists (e.g., the doctor being booked) so all
  concurrent attempts serialize through it.
- When the target row already exists (e.g., checking in an existing
  appointment), lock that row directly.

**Rationale**: `lockForUpdate()` on a query that may return zero rows locks
nothing. This is a known, easy-to-miss trap; picking the correct lock target
is treated here as a designed decision, not an implementation detail.

### V. Centralized, Reusable Sequential Document Numbering

Every human-readable reference number (patient, visit, invoice, and any
future document type) MUST be generated through a single, general-purpose
number-generation service backed by a dedicated counters table, scoped by
branch + document type + year. A new document type MUST NOT introduce its
own bespoke numbering class.

**Rationale**: Concurrency-safe numbering logic is easy to get subtly wrong;
one hardened implementation, reused everywhere, means one place to fix it.

### VI. Extract Shared Business Rules on Second Duplication

The moment a business rule (e.g., "this record is only editable while its
parent is in an active status") is needed by a second consumer, it MUST be
extracted into a single shared guard/policy class. It MUST NOT be
copy-pasted a second time.

**Rationale**: Business rules that live in two places drift; the first
duplication is the signal to consolidate, not the second or third.

### VII. Calculated Deferral, Not Rushed Scope-Cutting (YAGNI)

Features may be deliberately deferred (e.g., interactive SVG dental chart,
doctor working-hours schedules, guardian fields for minors) when there is no
present need. However, deferring a feature MUST NOT be allowed to force a
future breaking schema change — the underlying data structure MUST be shaped
so the deferred feature can be added as pure extension (new rows, new
nullable columns) rather than restructuring existing tables.

**Rationale**: YAGNI without this constraint becomes technical debt in
disguise; with it, it is a genuine complexity-reduction tool.

### VIII. Multi-Branch Awareness From Day One

Every operational table MUST carry an explicit `branch_id` representing
where the service was actually rendered, kept distinct from any
"registration branch" a patient or record may separately reference. This
applies even while the system is deployed to a single branch in practice.

**Rationale**: Retrofitting multi-branch support onto a single-branch schema
after the fact is materially more expensive than including the column now.

### IX. Localization Without Duplication

Static system text (validation messages, exception messages, status labels)
MUST be resolved through Laravel's standard `lang/{locale}/*.php` files via
a single centralized Exception Handler — exception classes MUST carry a
translation key plus data, never a hardcoded final-language string.
User-entered, dynamic multilingual content (branch names, service names,
category names) MUST use a single translatable JSON column per field
(`spatie/laravel-translatable`), never parallel `_ar`/`_en` columns. Dates
and numbers MUST be returned from the API in raw ISO 8601 / numeric form;
locale-specific formatting is a frontend-only concern.

**Rationale**: Splitting "which text needs translation" into exactly two
handling paths (fixed vs. dynamic) is what keeps N-language support at
O(1) code paths instead of O(N).

### X. Versioned API With a Uniform Response Envelope

All HTTP API routes MUST be namespaced under `/api/v1/...` from the first
implementation, regardless of whether a v2 is currently planned. Every
response MUST conform to a single JSON envelope shape
(`{success, message, data}` or `{success, message, errors}`), produced in
exactly one place (the centralized Exception Handler and a shared API
Resource base), never assembled ad hoc per controller.

**Rationale**: A predictable, versioned contract is what lets the Next.js
frontend be built against a stable target instead of chasing backend
response-shape changes feature by feature.

## Technology Constraints

- Backend: Laravel (current stable, presently 13) exposing a JSON API only
  — no server-rendered Blade views for application screens.
- Frontend: React.js via Next.js, consuming the API exclusively over HTTP.
- Database: MySQL/MariaDB. Schema decisions MUST NOT rely on
  PostgreSQL-only features (e.g., `EXCLUDE USING gist`) given this
  constraint (see Article IV for the MySQL-compatible alternative already
  adopted for appointment-conflict prevention).
- AuthN: Laravel Sanctum. AuthZ: Spatie Laravel Permission — a
  project-specific roles/permissions table MUST NOT be built manually.
- File storage: Laravel Filesystem abstraction only; code MUST NOT assume a
  specific disk (local vs. S3) anywhere outside configuration.

## Development Workflow

- Every feature MUST pass through `/speckit.specify` → `/speckit.plan` →
  `/speckit.tasks` → `/speckit.implement` before being considered complete;
  ad hoc implementation without a spec is out of process for this project.
- `/speckit.plan` MUST perform an explicit Constitution Check against
  Articles I–X before any technical design is finalized. A plan that cannot
  satisfy an article MUST document the violation and its justification
  rather than silently deviating.
- Manual verification (e.g., via Tinker, or a real concurrent-request test
  for anything touching Article IV) is required evidence of completion for
  any feature involving a business transaction — a plan/task is not "done"
  on code-compiles alone.

## Governance

This constitution supersedes ad hoc technical preference in any conflict.
Amendments require:

1. A documented rationale for the change.
2. A version bump per the policy below.
3. Propagation check against `plan-template.md`, `spec-template.md`, and
   `tasks-template.md` for consistency, recorded in a Sync Impact Report
   comment at the top of this file.

### Versioning Policy (Semantic Versioning: MAJOR.MINOR.PATCH)

- **MAJOR**: Backward-incompatible principle changes (a principle removed or
  redefined in a way that invalidates prior compliant work).
- **MINOR**: A new principle or constraint added.
- **PATCH**: Wording clarifications with no change in obligation.

### Compliance Review

- Every module's `/speckit.plan` output MUST include an explicit
  Constitution Check section referencing the specific Articles evaluated.
- Any accepted violation MUST be recorded as a tracked technical-debt item,
  not left implicit.

**Version**: 1.0.0 | **Ratified**: 2026-07-20 | **Last Amended**: 2026-07-20