# Phase 1 Data Model: HTTP API Layer

## No New Database Tables

This feature introduces **no new persistent entities**. It exposes
already-modeled data (Foundation, Clinical, Financial, Inventory) over
HTTP. The two stateful mechanisms it relies on are already provided by
existing infrastructure, not new tables:

- **Token storage**: Laravel Sanctum's existing `personal_access_tokens`
  table (created in the Foundation phase). `expiration` (R4) is a config
  value, not a schema change.
- **Rate-limit state**: Laravel's cache layer (R5) — no table.

## Modified Entities (existing tables — additive only)

None. This feature does not alter any existing table's schema. (Contrast
with the Financial Module plan, which added `visits.has_active_invoice`
— this feature has no equivalent structural need.)

## New Non-Persistent Contract: `ApiExceptionInterface`

The one new "shape" this feature introduces is not a database entity but
a PHP interface every domain exception must implement (R3), so it is
documented here as this feature's closest equivalent to a data model:

| Method | Returns | Purpose |
|---|---|---|
| `errorCode(): string` | Fixed SCREAMING_SNAKE_CASE string | The language-invariant code from the Error Code Catalog (spec.md §API Contract Standard #2) |
| `httpStatus(): int` | HTTP status code | e.g. `409` for `AppointmentConflictException` |
| `translationKey(): string` | Laravel translation key | e.g. `exceptions.appointment_conflict` |
| `translationParams(): array` | Key-value pairs | Interpolated into the translated message (e.g. `['date' => ..., 'start' => ..., 'end' => ...]`) |

**Exceptions retrofitted to implement this interface (per R3):**

| Exception | error_code | HTTP status |
|---|---|---|
| `AppointmentConflictException` | `APPOINTMENT_CONFLICT` | 409 |
| `InvalidAppointmentStatusException` | `INVALID_APPOINTMENT_STATUS` | 409 |
| `VisitNotEditableException` | `VISIT_NOT_EDITABLE` | 409 |
| `VisitAlreadyInvoicedException` | `VISIT_ALREADY_INVOICED` | 409 |
| `InvalidInvoiceStatusException` | `INVALID_INVOICE_STATUS` | 409 |
| `PaymentExceedsBalanceException` | `PAYMENT_EXCEEDS_BALANCE` | 422 |

This table is the authoritative source for the Error Code Catalog defined
in `spec.md` — any future domain exception (Inventory, etc.) MUST be
added to both this table and the catalog before shipping, per FR-019.

## Response Envelope Shapes (restated from spec.md for implementation reference)

These are not database entities but are documented here since they are
the "data model" a frontend developer actually consumes — restated from
`spec.md`'s API Contract Standard section for a single implementation
-reference location:

- **Success**: `{ success: true, message: string, data: object|array }`
- **Success (paginated)**: adds `meta: { current_page, per_page, total, last_page }`
- **Error**: `{ success: false, message: string, error_code: string, errors?: object }`