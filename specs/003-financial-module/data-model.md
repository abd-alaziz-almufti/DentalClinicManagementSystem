# Phase 1 Data Model: Financial Module

Derived directly from `spec.md` (Functional Requirements) and `research.md`
(Decisions R1–R5). This is the design-level entity definition; actual
Laravel migrations are generated at `/speckit.implement`.

---

## Modified Entity: `visits` (existing table — additive change only)

| Field | Type | Notes |
|---|---|---|
| `has_active_invoice` | boolean, default `false` | **New.** Decoupling flag per R3. Set/cleared only by Financial-domain services; read-only from the Clinical domain's perspective. |

No other change to `visits`. This is a pure additive migration — no
existing column is altered, satisfying Constitution Article VII (deferral
must never force a breaking change to existing structure; here it isn't
even a deferral, just a minimal-surface addition).

---

## New Entity: `invoices`

| Field | Type | Constraints | Notes |
|---|---|---|---|
| `id` | bigint PK | | |
| `invoice_number` | string(40) | UNIQUE, immutable | Generated via `DocumentNumberGenerator` (Constitution Article V), type=`invoice`, prefix=`INV`. Never reused (FR-011, FR-017), even after cancellation. |
| `visit_id` | FK → visits | **UNIQUE**, restrict on delete | Enforces "at most one invoice per visit" at the DB level (R1). |
| `patient_id` | FK → patients | restrict on delete | Denormalized snapshot from `visits.patient_id` at generation time — same controlled-denormalization pattern as `visits` itself (Constitution Article II/VIII), enables direct patient-level invoice queries without joining through visits. |
| `branch_id` | FK → branches | restrict on delete | Denormalized snapshot from `visits.branch_id` (Article VIII). |
| `total` | decimal(10,2) | not null | Computed once at generation as `SUM(invoice_items.total)`, then frozen (FR-014). Never re-derived afterward. |
| `status` | enum | `issued` \| `partially_paid` \| `paid` \| `cancelled`, default `issued` | Forward-only state machine per spec.md. No `draft` state in this iteration. |
| `issued_by` | FK → users, nullable | null on delete | Staff member who generated the invoice. |
| `cancelled_at` | timestamp, nullable | | Set only on cancellation. |
| `cancellation_reason` | text, nullable | | Required by the application layer whenever `status` is set to `cancelled` (enforced in `CancelInvoiceService`, not a DB constraint). |
| `created_at` / `updated_at` | timestamps | | |

**No `SoftDeletes`** — per Constitution Article I, logical removal here is
fully expressed through `status = cancelled` (FR-010); adding
`SoftDeletes` on top would be a second, redundant removal mechanism for
the same concept.

**Indexes**: `patient_id`, `branch_id`, `status` (for "unpaid invoices"
dashboards/reports).

---

## New Entity: `invoice_items`

| Field | Type | Constraints | Notes |
|---|---|---|---|
| `id` | bigint PK | | |
| `invoice_id` | FK → invoices | restrict on delete | |
| `visit_service_id` | FK → visit_services, nullable | null on delete | **Traceability reference only** — never used to re-derive price or name (R5, FR-002). Nullable so this row's integrity never depends on the source `visit_services` row surviving indefinitely. |
| `service_id` | FK → services, nullable | null on delete | Kept only to support future "revenue by service" reporting joins — not for display. |
| `service_name` | JSON | not null | Frozen translation snapshot `{"en": "...", "ar": "..."}`, captured at generation time (R5). Never re-resolved from the live `services.name` translatable column. |
| `tooth_number` | string(10), nullable | | Carried over from `visit_services.tooth_number` for print/display context. |
| `quantity` | unsigned int | default 1 | |
| `unit_price` | decimal(10,2) | not null | Copied from `visit_services.unit_price` at generation (double-snapshot: already frozen once at treatment time, frozen again here — intentional per FR-002). |
| `discount_amount` | decimal(10,2) | default 0 | |
| `total` | decimal(10,2) | not null | `(unit_price * quantity) - discount_amount`, computed once at generation. |
| `created_at` / `updated_at` | timestamps | | |

**No `SoftDeletes`, no delete/update path at all after creation** — an
invoice's line items are immutable for the lifetime of the invoice
(FR-009). Correcting a line requires cancelling the whole invoice
(FR-010) and issuing a new one through a future adjustment flow (Out of
Scope in spec.md).

**Indexes**: `invoice_id`.

---

## New Entity: `payments`

| Field | Type | Constraints | Notes |
|---|---|---|---|
| `id` | bigint PK | | |
| `invoice_id` | FK → invoices | restrict on delete | |
| `type` | enum | `payment` \| `reversal`, default `payment` | R4. |
| `reverses_payment_id` | FK → payments (self), nullable | null on delete | Set only when `type = reversal`; points at the original mistaken payment. Application layer enforces this pairing (not a DB constraint, since "type implies this FK is set" is a cross-field rule). |
| `amount` | decimal(10,2) | not null, > 0 | Always positive regardless of `type` — sign semantics come from `type` alone (R4), never from a negative amount. |
| `payment_method` | enum | `cash` \| `card` \| `bank_transfer` | FR-015. Extensible enum — adding a method later is an additive migration, not a redesign. |
| `payment_date` | date | not null | Distinct from `created_at` — the date the money was actually received, which staff may backdate/confirm separately from when the record was entered. |
| `recorded_by` | FK → users, nullable | null on delete | FR-012. |
| `notes` | text, nullable | | |
| `created_at` / `updated_at` | timestamps | | |

**No `SoftDeletes`, no update, no delete — ever, for any reason**
(FR-018). This is stricter than the standard Article I treatment
(status-or-soft-delete): payments have no logical-removal state at all:
correction is only ever an additional `reversal` row, never a status
change on the original. `updated_at` is present only because Laravel adds
it by convention; the application layer must never call `->update()` on
an existing `Payment`.

**Indexes**: `invoice_id`, `payment_method` (for revenue-by-method
reporting per FR-015).

---

## Derived Value (not stored): Remaining Balance

```
remaining_balance(invoice) =
    invoice.total
  - SUM(payments.amount WHERE type = 'payment'  AND invoice_id = invoice.id)
  + SUM(payments.amount WHERE type = 'reversal' AND invoice_id = invoice.id)
```

Computed on every read inside `RecordPaymentService` (under the row lock,
R2) and in any read-only API Resource — **never** stored as a column, per
FR-006 and Constitution's general "don't store derived values without a
proven performance need" stance (PRD §5, principle 10 / ADR-008, now
extended from invoices-only to this module generally).

---

## Entity Relationship Summary

```
Visit (1) ────── (0..1) Invoice ────── (1..*) InvoiceItem ── (0..1) VisitService [reference only]
                     │                                    └── (0..1) Service    [reference only]
                     │
                     └────── (0..*) Payment ── (0..1) Payment [self-ref, reversal target]

Patient (1) ────── (0..*) Invoice   [denormalized snapshot, R-consistent with visits.patient_id]
Branch  (1) ────── (0..*) Invoice   [denormalized snapshot, R-consistent with visits.branch_id]
```