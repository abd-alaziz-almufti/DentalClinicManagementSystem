# Implementation Plan: Financial Module (Invoicing & Payments)

**Branch**: `003-financial-module` | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification (Clarified) from `/speckit.specify` + `/speckit.clarify`

## Summary

Generate an immutable invoice from a completed visit, accept one or more
payments against it under full concurrency safety, and lock the source
visit's clinical treatment records once invoiced — all reusing existing
project infrastructure (`DocumentNumberGenerator`, the
Service-Layer-in-a-transaction pattern, the shared `VisitEditabilityGuard`)
rather than introducing new mechanisms where an existing one already fits.

## Technical Context

- **Language/Framework**: PHP / Laravel (per Constitution, Technology Constraints)
- **Storage**: MySQL/MariaDB (per Constitution) — confirmed no PostgreSQL-only feature is required by this module (unlike appointments, which needed the MySQL-compatible proxy-lock workaround; this module's concurrency needs are simpler, see research.md R1/R2)
- **New dependencies**: none — reuses `spatie/laravel-permission` (already installed) and the project's own `DocumentNumberGenerator`
- **Testing**: Manual verification via Tinker for correctness, plus a real concurrent-HTTP-request test for FR-008/FR-016 (same method already used and proven for `AppointmentService::book()`)
- **Scale/scope**: Single clinic, multi-branch-ready — consistent with existing Foundation/Clinical scope, no new scaling assumptions introduced

## Constitution Check

*Gate evaluated before finalizing this plan, per Constitution Governance §"Compliance Review." Full text: `.specify/memory/constitution.md`.*

| Article | Requirement | Status | How this plan satisfies it |
|---|---|---|---|
| **I** — No hard deletes | Financial records must use status/SoftDeletes, never physical delete | ✅ PASS | `invoices.status = cancelled` (not delete); `payments` go further — no delete or edit at all, corrections via `type = reversal` (data-model.md, stricter than the baseline requirement, not a violation of it) |
| **II** — Point-of-service snapshot | Data used at time of service must be copied, not live-derived | ✅ PASS | `invoice_items` freezes name/price independently of `visit_services` (FR-002); `invoices.total` frozen at generation (FR-014) |
| **III** — Explicit Service Layer | Business transactions as explicit Service classes in `DB::transaction()` | ✅ PASS | Three new services planned: `GenerateInvoiceService`, `RecordPaymentService`, `CancelInvoiceService` — no Observers involved |
| **IV** — Concurrency safety | Correct lock target chosen deliberately, verified under real concurrency | ✅ PASS | R1 (unique constraint, row doesn't exist yet) and R2 (`lockForUpdate()`, row exists) each map to the correct rule from Article IV's own decision tree — see research.md |
| **V** — Centralized numbering | No bespoke numbering class per document type | ✅ PASS | `invoice_number` generated via the existing `DocumentNumberGenerator`, type=`invoice` — zero new numbering code |
| **VI** — Extract shared rules on 2nd duplication | Don't duplicate a business rule across consumers | ✅ PASS | FR-013's "visit locked once invoiced" check is added to the *existing* `VisitEditabilityGuard` rather than a second guard class (research.md R3) |
| **VII** — Calculated deferral (YAGNI) | Deferred features must not force future breaking schema changes | ✅ PASS | Full adjustment/reversal-of-invoice workflow is deferred (spec.md Out of Scope), but payment-level reversal is NOT deferred (FR-018) since it was identified as needed now; `visits.has_active_invoice` is a pure additive column, not a breaking change |
| **VIII** — Multi-branch awareness | Every operational table carries explicit `branch_id` | ✅ PASS | `invoices.branch_id` denormalized from the source visit, same pattern as `visits.branch_id` itself |
| **IX** — Localization without duplication | Static text via lang files; dynamic content via one translatable column; no backend-formatted dates/numbers | ⚠ NOTED, NOT VIOLATED | `invoice_items.service_name` intentionally does NOT use the live-translatable pattern — see research.md R5 for why this is a deliberate, documented exception (Article II supersedes Article IX once content is a frozen financial record), not an oversight |
| **X** — Versioned API, uniform envelope | `/api/v1/...`, single response envelope | ⏸ DEFERRED | No HTTP layer is being built in this pass (contracts/README.md) — will apply when the cross-cutting HTTP-layer effort reaches this module; no violation, simply not yet in scope for this plan |

**Overall gate result: PASS.** No unjustified violations. One deliberate,
documented exception (Article IX interaction, R5) and one explicitly
deferred article (X, out of this plan's scope by project-level sequencing
decision, not an oversight).

## Project Structure (files this feature will touch at `/implement`)

```
database/migrations/
├── xxxx_add_has_active_invoice_to_visits_table.php   # additive only
├── xxxx_create_invoices_table.php
├── xxxx_create_invoice_items_table.php
└── xxxx_create_payments_table.php

app/Models/
├── Invoice.php
├── InvoiceItem.php
└── Payment.php

app/Services/
├── GenerateInvoiceService.php
├── RecordPaymentService.php
└── CancelInvoiceService.php

app/Services/Support/
└── VisitEditabilityGuard.php     # MODIFIED — add has_active_invoice check

app/Exceptions/
├── VisitAlreadyInvoicedException.php
├── InvalidInvoiceStatusException.php   # for illegal state transitions
└── PaymentExceedsBalanceException.php
```

No modification to any existing Clinical-domain Service class other than
the one guard extension listed above — confirms R3's goal of zero new
coupling from Clinical into Financial.

## Phase Outputs (this plan)

- ✅ `research.md` — Phase 0, all 5 open questions resolved
- ✅ `data-model.md` — Phase 1, full entity design
- ⏸ `contracts/` — placeholder only, deferred per project sequencing (see contracts/README.md)

## Next Steps

1. `/speckit.tasks` — break this plan into an ordered, dependency-mapped task list (migrations → models → services → manual + concurrency tests, mirroring the exact sequence already used for Appointments/Visits).
2. `/speckit.analyze` (optional but recommended, per Constitution Development Workflow) — cross-check `spec.md`, this `plan.md`, and the eventual `tasks.md` for gaps before `/speckit.implement`.
3. `/speckit.implement`.