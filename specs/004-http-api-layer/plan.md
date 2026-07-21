# Implementation Plan: HTTP API Layer

**Branch**: `004-http-api-layer` | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification (Clarified) from `/speckit.specify` + `/speckit.clarify`

## Summary

Expose every already-built, already-verified domain capability
(Foundation, Clinical, Financial, Inventory) over a single, versioned,
authenticated HTTP contract — reusing native Laravel/Sanctum features and
one additional Spatie package wherever they already fully satisfy a
requirement, introducing custom code only where no existing mechanism
fits (branch-scoping, the exception→envelope contract).

## Technical Context

- **Framework mechanisms reused, not reinvented**: Sanctum token
  expiration config (R4), Laravel's native rate limiter (R5), Laravel
  Policies (R2)
- **New dependency**: `spatie/laravel-query-builder` (R6) — same vendor
  already used twice, no new tooling philosophy introduced
- **Testing**: Manual Tinker/HTTP verification per endpoint group, plus a
  mandatory real concurrent-request test proving FR-012 (booking and
  payment-recording races are unchanged when invoked over HTTP) — same
  method already proven for `AppointmentService::book()`

## Constitution Check

| Article | Requirement | Status | How this plan satisfies it |
|---|---|---|---|
| **I** — No hard deletes | N/A for most of this feature's own state | ✅ PASS | This feature adds no new persisted medical/financial state; it does enforce, at the route level (FR-022), that `DELETE` verbs on existing hard-delete-forbidden resources perform the correct status transition instead |
| **II** — Point-of-service snapshot | N/A | ✅ N/A | No new point-of-service data is introduced by this feature |
| **III** — Explicit Service Layer | Business logic stays in existing Services; Controllers stay thin | ✅ PASS | Controllers validate (Form Requests), authorize (Policies), then call the existing Service classes unchanged — no business logic duplicated into Controllers. Branch-scoping (R1) is deliberately an explicit, invoked-per-call helper, not implicit Global Scope magic |
| **IV** — Concurrency safety | HTTP layer must not introduce a new race around an already-verified guarantee | ✅ PASS | Controllers call `AppointmentService::book()` / `RecordPaymentService::record()` etc. directly and let those methods manage their own `DB::transaction()` exactly as already built — no data is fetched by the Controller outside that transaction and re-used inside it |
| **V** — Centralized numbering | No bespoke numbering introduced | ✅ N/A | This feature generates no new document type |
| **VI** — Extract shared rules on 2nd duplication | Don't duplicate cross-cutting logic per endpoint | ✅ PASS | `BranchScopeFilter` (R1) and the `spatie/laravel-query-builder` integration (R6) each centralize a rule that would otherwise be copy-pasted into every domain Controller |
| **VII** — Calculated deferral (YAGNI) | Deferred items must not force a future breaking change | ✅ PASS | Attachment upload endpoints, advanced search, refresh tokens, and general rate limiting are deferred (spec.md Out of Scope); none require a schema or contract change to add later — they slot into the same envelope/pagination/error-code contract already fixed |
| **VIII** — Multi-branch awareness | Enforced at the API layer, not just present as a column | ✅ PASS | This is the first feature that actually *activates* branch-scoping as enforced behavior (FR-010, FR-011, FR-018) — `branch_id` stops being a passive column and becomes a real access boundary |
| **IX** — Localization without duplication | Static text via lang files, one centralized translation point, language-invariant error codes | ✅ PASS (resolves prior debt) | Confirms PRD §10.1's design; additionally retrofits the 6 existing exception classes (R3) to finally drop their hardcoded English strings — closing a gap that existed since those exceptions were first written, before this feature gave a reason to fix it |
| **X** — Versioned API, uniform envelope | `/api/v1/...`, single envelope shape | ✅ PASS (primary deliverable) | This feature *is* the implementation of Article X — full envelope, error catalog, pagination/filter/sort/include standard, all fixed in spec.md's API Contract Standard |

**Overall gate result: PASS.** No violations. One notable outcome:
Article IX compliance debt (hardcoded exception messages, known since the
i18n plan in PRD §10.1) is resolved as a direct consequence of this
feature, not deferred further.

## Project Structure (files this feature will touch at `/implement`)

```
bootstrap/app.php                          # MODIFIED — register SetLocaleFromRequest,
                                            #   central exception rendering, API routing

routes/
└── api/
    └── v1.php                             # NEW — all versioned routes

app/Http/
├── Middleware/
│   └── SetLocaleFromRequest.php           # NEW
├── Controllers/Api/V1/
│   ├── AuthController.php                 # NEW
│   ├── PatientController.php              # NEW
│   ├── AppointmentController.php          # NEW
│   ├── VisitController.php                # NEW
│   ├── InvoiceController.php              # NEW
│   ├── PaymentController.php              # NEW
│   └── ...                                # one per domain resource
├── Requests/
│   └── ...                                # one Form Request per write action
└── Resources/
    └── ...                                # one API Resource per domain entity

app/Exceptions/
├── ApiExceptionInterface.php              # NEW (R3)
├── AppointmentConflictException.php       # MODIFIED — implements interface, drops hardcoded string
├── InvalidAppointmentStatusException.php  # MODIFIED — same
├── VisitNotEditableException.php          # MODIFIED — same
├── VisitAlreadyInvoicedException.php      # MODIFIED — same
├── InvalidInvoiceStatusException.php      # MODIFIED — same
└── PaymentExceedsBalanceException.php     # MODIFIED — same

app/Policies/
└── ...                                    # one per domain Model (R2)

app/Services/Support/
└── BranchScopeFilter.php                  # NEW (R1)

lang/
├── en/exceptions.php                      # NEW
└── ar/exceptions.php                      # NEW

config/sanctum.php                         # MODIFIED — expiration = 480 (R4)
app/Providers/AppServiceProvider.php       # MODIFIED — RateLimiter::for('login', ...) (R5)
```

## Phase Outputs (this plan)

- ✅ `research.md` — Phase 0, all 7 questions resolved
- ✅ `data-model.md` — Phase 1 (confirms no new tables; documents the `ApiExceptionInterface` contract and error code catalog source-of-truth)
- ⏸ `contracts/` — per-endpoint contracts deferred to `/tasks`/`/implement` per domain (contracts/README.md)

## Next Steps

1. `/speckit.tasks` — break this plan into an ordered task list, almost
   certainly grouped by domain (Auth → Patients → Appointments → Visits →
   Financial → Inventory), with the cross-cutting pieces (middleware,
   exception interface, `BranchScopeFilter`, query-builder integration)
   as a first task group everything else depends on.
2. `/speckit.analyze` (recommended given this feature touches every prior
   module) — cross-check this plan against `spec.md` and the eventual
   `tasks.md` before `/implement`.
3. `/speckit.implement`.