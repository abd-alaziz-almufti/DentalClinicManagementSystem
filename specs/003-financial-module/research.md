# Phase 0 Research: Financial Module

**Purpose**: Resolve every technical unknown the spec deliberately left open,
before committing to a data model. Each decision below is evaluated against
the project Constitution (`.specify/memory/constitution.md`).

---

## R1: How do we prevent two concurrent requests from generating two invoices for the same visit? (relates to FR-003)

**Options considered:**

- (a) Proxy-lock pattern identical to appointment booking (Constitution
  Article IV) — lock a row that always exists before checking/inserting.
- (b) A database-level `UNIQUE` constraint on `invoices.visit_id`, with the
  application catching the resulting duplicate-key error.

**Decision: (b) — UNIQUE constraint on `invoices.visit_id`.**

**Rationale**: The appointment-booking race required a proxy lock because
the conflict condition is a *time-range overlap* — something a UNIQUE
constraint cannot express. Here the constraint is much simpler: "at most
one invoice per visit," which a UNIQUE index enforces atomically at the
database level under any concurrency, with no locking logic needed at all.
This is the same failure-catching pattern already used successfully in
`DocumentNumberGenerator` for the first-counter-row race (Constitution
Article V) — reused here, not reinvented.

**Consequence**: `GenerateInvoiceService` wraps invoice creation in
`DB::transaction()`, attempts the insert, and catches `QueryException` on
the unique-key violation to translate it into a clean
`VisitAlreadyInvoicedException` — no explicit `lockForUpdate()` required
for this specific guarantee.

---

## R2: How do we prevent overpayment under concurrent payment submissions? (relates to FR-007, FR-008, FR-016)

**Decision: Lock the `invoices` row directly with `lockForUpdate()` inside the payment transaction.**

**Rationale**: Per Constitution Article IV, when the contended row already
exists, it must be locked directly — this is exactly the `invoices` row
here (unlike invoice *generation*, which faces a "row doesn't exist yet"
race). This is the same pattern already verified for
`CheckInPatientService` locking the `appointments` row directly.

**Flow inside `RecordPaymentService::record()`:**
1. `DB::transaction()` begins.
2. Lock the target `Invoice` row: `Invoice::whereKey($id)->lockForUpdate()->firstOrFail()`.
3. Recompute the current remaining balance from `payments` (signed sum, see R4) — never trust a value read before the lock.
4. Reject if the new payment would take the balance below zero (FR-007).
5. Insert the `Payment` row.
6. Update the invoice's `status` based on the recomputed balance (FR-008).
7. Commit.

Because every concurrent request serializes on step 2, step 3's balance
read is always accurate at the moment of validation — no
read-then-write race is possible.

---

## R3: How do we enforce "visit_services locked once invoiced" (FR-013) without coupling the Clinical domain to the Financial domain?

**Problem**: `RecordTreatmentService` and `DentalChartService` (Clinical
domain, built in a prior phase) must refuse edits once an invoice exists
for their visit — but they must not need to know about the `Invoice` model
to do it. A Clinical-domain class importing a Financial-domain model is an
upward/reversed dependency that will not age well as both domains grow
(see PRD §7, planned `Domain/` structure).

**Decision: A denormalized boolean flag on `visits`, `has_active_invoice`, kept in sync by the Financial services that change invoice state.**

This is the exact same pattern already used and proven for
`users.is_super_admin` (Constitution/ADR precedent: a denormalized
performance/decoupling flag, with a single real source of truth
maintained elsewhere, synced explicitly at every point that could change
it) — applied here for decoupling instead of performance, but the same
shape of solution.

**Sync points (both inside existing transactional services, no new locking needed — see R1/R2 transactions):**
- `GenerateInvoiceService::generate()` sets `visits.has_active_invoice = true` in the same transaction as the invoice insert.
- `CancelInvoiceService::cancel()` sets `visits.has_active_invoice = false` in the same transaction as the status change to `Cancelled`.

**Consequence for existing code**: `VisitEditabilityGuard::assertEditable()`
(Constitution Article VI — shared rule, single location) is extended with
one additional condition: a visit is only editable if its status is
open/in_progress **AND** `has_active_invoice` is false. `Visit` gains zero
new relationships to `Invoice` — the flag is the entire interface between
the two domains.

---

## R4: How is a mistaken payment corrected, given payments are immutable? (relates to FR-018)

**Decision: A `type` column (`payment` | `reversal`) plus an optional
self-referencing `reverses_payment_id`. Remaining balance is a signed sum.**

```
remaining_balance = invoice.total
                   - SUM(amount WHERE type = 'payment')
                   + SUM(amount WHERE type = 'reversal')
```

**Rationale**: `amount` stays a natural, always-positive number (simpler
for reporting and for staff entering it), while `type` carries the sign
semantics. A `reversal` row optionally points back at the payment it
corrects via `reverses_payment_id`, giving a full audit trail ("payment X
was reversed by entry Y") without ever mutating or deleting payment X.

---

## R5: What language does an invoice line item's service name freeze into, given service names are translatable catalog data? (relates to FR-002, Constitution Article IX)

**Problem**: `services.name` is a live-translatable JSON column
(`spatie/laravel-translatable`, per PRD §10.1 / ADR-010) that resolves to
the request's current locale. But FR-002 requires the invoice to preserve
the *exact* name shown at the time of billing — a printed invoice must
not change wording depending on which locale later views it.

**Decision: `invoice_items.service_name` is its own frozen JSON snapshot
(`{"en": "...", "ar": "..."}`), copied from the service's full translation
set at generation time — not a live relation to `services.name`.**

**Rationale**: This looks structurally similar to the live-translatable
pattern but is semantically different: it is captured once and never
re-resolved, consistent with Constitution Article II (point-of-service
snapshot) taking precedence over Article IX (localization) for anything
that has already become a financial record. Article IX governs *catalog*
content; once content is copied into a financial snapshot, Article II
governs it exclusively.

---

## Summary of Decisions

| # | Question | Decision |
|---|---|---|
| R1 | Prevent duplicate invoices | DB `UNIQUE` on `invoices.visit_id` + catch |
| R2 | Prevent overpayment races | `lockForUpdate()` on `invoices` row directly |
| R3 | Lock visit_services post-invoice, without domain coupling | Denormalized `visits.has_active_invoice` flag |
| R4 | Correct a mistaken payment | `payments.type` (payment/reversal) + signed sum |
| R5 | Freeze a translatable service name | Frozen JSON snapshot on `invoice_items`, not a live relation |