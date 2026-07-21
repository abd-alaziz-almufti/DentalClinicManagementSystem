# Feature Specification: HTTP API Layer

**Feature Branch**: `004-http-api-layer`
**Created**: 2026-07-20
**Status**: Clarified — ready for `/speckit.plan`
**Depends on**: Foundation, Clinical V1, Financial Module, Inventory Module — all completed at the Service Layer

## Context

Every domain module built so far (Foundation, Clinical, Financial,
Inventory) exists only as Models and Service classes, verified manually
through Tinker. None of it is reachable over HTTP. This feature does not
add new business behavior — it exposes the existing, already-verified
business behavior through a consistent, versioned, authenticated API that
the Next.js frontend can be built against.

## User Scenarios & Testing

### Primary User Story

As a frontend developer building the Next.js application, I need every
existing backend capability (patient registration, appointment booking,
check-in, treatment recording, invoicing, payments, inventory) reachable
through a predictable HTTP contract — one authentication mechanism, one
response shape for success, one response shape for errors, one place
where "you don't have permission for this" is decided — so that the
frontend can be built once against a stable contract instead of adapting
to each module's quirks individually.

### Acceptance Scenarios

1. **Given** valid staff credentials, **When** a login request is
   submitted, **Then** the response is a success envelope containing the
   authenticated user, their role(s), and a bearer token usable for
   subsequent requests.

2. **Given** invalid credentials, **When** a login request is submitted,
   **Then** the response is an error envelope with an appropriate
   unauthorized status, and no token is issued.

3. **Given** a valid bearer token belonging to a `receptionist`, **When**
   a request is made to an action reserved for `accountant` (e.g.,
   recording a payment), **Then** the response is a uniform
   "forbidden" error envelope — the request MUST NOT reach the underlying
   Service class at all.

4. **Given** a request missing a required field (e.g., registering a
   patient without `first_name`), **When** submitted, **Then** the
   response is a uniform validation-error envelope identifying the
   offending field(s) — and no partial data is persisted.

5. **Given** a Service-layer business-rule exception already proven at
   the Service level (e.g., `AppointmentConflictException`,
   `VisitNotEditableException`, `VisitAlreadyInvoicedException`), **When**
   the corresponding endpoint triggers it, **Then** the HTTP response
   translates it into the same uniform error envelope with a status code
   appropriate to the failure (e.g., conflict), and a message localized
   per the request's language — without any endpoint-specific error
   handling code duplicating this translation.

6. **Given** the exact concurrent-booking scenario already verified at
   the Service layer (two simultaneous requests for the same doctor/time
   slot), **When** both are submitted through the HTTP endpoint instead of
   directly through the Service, **Then** the outcome is identical to the
   already-proven Service-layer guarantee — exactly one booking succeeds.
   The HTTP layer MUST NOT introduce a new race by, for example, fetching
   data outside the transaction the Service already manages.

7. **Given** a request with an `Accept-Language: ar` header, **When** any
   endpoint returns a message (success or error), **Then** the message
   text is returned in Arabic; the same request with `en` returns English
   — with no duplicated logic per endpoint (per Constitution Article IX).

8. **Given** an authenticated `doctor`, **When** listing appointments,
   **Then** only appointments belonging to that doctor are returned — not
   the clinic's full appointment list.

9. **Given** an authenticated `admin` (branch-scoped, not super-admin),
   **When** listing any branch-scoped resource (patients, appointments,
   invoices), **Then** only records belonging to their own branch are
   returned.

10. **Given** an authenticated `super-admin`, **When** listing the same
    resource, **Then** records across all branches are returned.

### Edge Cases

- What happens when a bearer token is expired or revoked? → Every
  protected endpoint MUST respond with the same uniform unauthorized
  envelope, not a framework-default HTML/error page.
- What happens when a list endpoint is requested with no records to
  return? → A valid success envelope with an empty collection, not an
  error.
- What happens when two different domain exceptions could both plausibly
  apply to one failure (e.g., a visit that is both not-found and,
  hypothetically, would also fail a business rule)? → Not-found MUST be
  checked and returned before any business-rule evaluation runs.

## Requirements

### Functional Requirements

- **FR-001**: Every endpoint MUST be namespaced under a versioned path
  (`v1`), with no unversioned endpoint exposed, per Constitution Article
  X.
- **FR-002**: Every successful response MUST conform to a single uniform
  envelope shape, regardless of which module produced it.
- **FR-003**: Every error response (validation, authorization,
  authentication, business-rule, not-found) MUST conform to a single
  uniform error envelope shape, regardless of which module or exception
  type produced it.
- **FR-004**: Translation of a Service-layer exception into an HTTP error
  response MUST happen in exactly one centralized location — individual
  endpoints MUST NOT each implement their own try/catch-and-format logic
  for domain exceptions.
- **FR-005**: Every endpoint MUST enforce role-based authorization
  consistent with the roles already defined (Constitution/Foundation:
  `super-admin`, `admin`, `doctor`, `receptionist`, `accountant`,
  `inventory-manager|`), rejecting unauthorized requests before any
  Service class executes.
- **FR-006**: The response language MUST be resolved once per request
  (from a request header) and applied consistently to every message the
  response contains — success or error — per Constitution Article IX.
- **FR-007**: Authentication MUST be token-based (bearer tokens), issued
  on login and invalidated on logout, with every protected endpoint
  requiring a valid token.
- **FR-008**: Input MUST be validated before any Service class is
  invoked; a validation failure MUST prevent any write from occurring
  (no partial persistence).
- **FR-009**: List endpoints MUST return paginated results by default —
  no endpoint may return an unbounded result set.
- **FR-010**: Every list/detail endpoint MUST automatically scope results
  by the requesting user's branch (per Constitution Article VIII), except
  for `super-admin`, who sees all branches — this scoping MUST be
  enforced centrally, not re-implemented per endpoint.
- **FR-011**: A `doctor` role MUST only see patients, appointments, and
  visits where they are the treating doctor — not the full clinic patient
  or appointment directory. Browsing the complete patient directory is
  reserved for `receptionist`, `admin`, and `super-admin` (resolves
  Clarification Q1).
- **FR-012**: Any concurrency guarantee already verified at the Service
  layer (appointment booking, payment recording) MUST hold identically
  when the same operation is invoked through its HTTP endpoint — the HTTP
  layer MUST add no business logic of its own between validation and
  invoking the Service.
- **FR-013**: Password hashes and any other credential material MUST
  NEVER appear in any API response, under any role or endpoint.
- **FR-014**: Soft-deleted or cancelled/logically-removed records MUST be
  excluded from default list/detail responses unless a request explicitly
  asks to include them (and only for roles permitted to do so).
- **FR-015**: The login endpoint MUST be rate-limited to at most 5
  attempts per minute per email+IP combination; exceeding the limit MUST
  return a uniform "too many requests" error envelope, not a generic
  server error (resolves Clarification Q3).
- **FR-016**: Cross-origin requests MUST only be accepted from the
  Next.js frontend's configured origin(s) — not from an unrestricted
  wildcard.
- **FR-017**: Authentication tokens MUST expire automatically 8 hours
  after issuance (a single working shift), requiring re-authentication
  thereafter — no indefinitely-lived token may remain valid (resolves
  Clarification Q2).
- **FR-018**: An `admin` MUST have read-only access to financial records
  (invoices, payments) within their own branch; recording, modifying, or
  cancelling payments/invoices remains reserved for `accountant` and
  `super-admin` (resolves Clarification Q4).

### Key Entities (Response Contracts)

- **Success Envelope**: The shape every successful response shares —
  carries a success flag, a human-readable (localized) message, and a
  data payload specific to the endpoint.
- **Error Envelope**: The shape every failed response shares — carries a
  success flag (false), a localized message, a **stable, language-invariant
  error code** (see API Contract Standard below), and structured error
  detail (e.g., field-level validation errors) where applicable.
- **Paginated Collection Envelope**: A success envelope whose data payload
  additionally carries pagination metadata (current page, total, per-page,
  last page) for any list endpoint.
- **Locale Context**: The resolved request language, established once per
  request and available to every part of the response-building process.

## API Contract Standard

*Resolved now, per review, to remove all remaining architectural
ambiguity before `/speckit.plan`. This standard governs every endpoint in
this feature and every endpoint added by any future module — it is not
re-decided per module.*

### 1. Response Envelope (exact shape)

**Success — single resource:**
```json
{ "success": true, "message": "Patient registered successfully.", "data": { } }
```

**Success — collection (paginated):**
```json
{
  "success": true,
  "message": "...",
  "data": [ ],
  "meta": { "current_page": 1, "per_page": 20, "total": 143, "last_page": 8 }
}
```

**Error:**
```json
{
  "success": false,
  "message": "The doctor already has an appointment overlapping this time.",
  "error_code": "APPOINTMENT_CONFLICT",
  "errors": { }
}
```

`message` is ALWAYS localized per Article IX. `error_code` is ALWAYS a
fixed, English, SCREAMING_SNAKE_CASE string that never changes regardless
of locale — this is what frontend logic branches on, never the message
text. `errors` is present only for validation failures (field → message
list); absent otherwise.

### 2. Error Code Catalog (FR-019)

Every domain exception raised anywhere in the system MUST be mapped, in
the single centralized handler (FR-004), to exactly one fixed
`error_code` and HTTP status. Known mappings established by exceptions
already built:

| error_code | HTTP status | Source exception |
|---|---|---|
| `VALIDATION_ERROR` | 422 | Form Request failure |
| `UNAUTHENTICATED` | 401 | Missing/expired/invalid token |
| `FORBIDDEN` | 403 | Authorization failure |
| `NOT_FOUND` | 404 | Route-model binding miss |
| `TOO_MANY_REQUESTS` | 429 | Rate limit exceeded |
| `APPOINTMENT_CONFLICT` | 409 | `AppointmentConflictException` |
| `INVALID_APPOINTMENT_STATUS` | 409 | `InvalidAppointmentStatusException` |
| `VISIT_NOT_EDITABLE` | 409 | `VisitNotEditableException` |
| `VISIT_ALREADY_INVOICED` | 409 | `VisitAlreadyInvoicedException` |
| `INVALID_INVOICE_STATUS` | 409 | `InvalidInvoiceStatusException` |
| `PAYMENT_EXCEEDS_BALANCE` | 422 | `PaymentExceedsBalanceException` |
| `SERVER_ERROR` | 500 | Any uncaught/unexpected exception (message MUST be generic — internals MUST NOT leak) |

Any new domain exception introduced by a future module MUST be added to
this catalog before it ships — an exception without a cataloged
`error_code` is an incomplete feature, not an acceptable fallback to
`SERVER_ERROR`.

### 3. Pagination, Sorting, Filtering, Searching (FR-020)

- **Pagination**: `?page=1&per_page=20` — `per_page` capped at a maximum
  of 100 server-side regardless of what is requested; default 20 if
  omitted.
- **Sorting**: `?sort=<field>&direction=asc|desc` — explicit two-parameter
  form (not a compact `-field` prefix), for clarity and easier
  server-side validation of the allowed field. Each endpoint declares its
  own whitelist of sortable fields; an unlisted field MUST return
  `VALIDATION_ERROR`, not be silently ignored.
- **Filtering**: `?filter[<field>]=<value>` (namespaced to avoid
  colliding with other query parameters). Each endpoint declares its own
  whitelist of filterable fields — arbitrary/dynamic filtering against any
  column is explicitly NOT supported (avoids leaking internal columns and
  unbounded query cost).
- **Searching**: `?search=<term>` — a single free-text parameter, only on
  endpoints that explicitly document which fields it searches (e.g.,
  patient name + phone). Full-text/fuzzy search engines remain out of
  scope (unchanged from the earlier Out of Scope section) — this only
  standardizes the *parameter shape* for the simple `LIKE`-based search
  already sufficient for V1.

### 4. Relationship Inclusion Policy (FR-021)

- `?include=<relation1>,<relation2>` — comma-separated, explicit.
- Each endpoint declares its own whitelist of includable relations; an
  unlisted relation MUST return `VALIDATION_ERROR`.
- Default response (no `include`) returns each endpoint's own documented
  minimal shape — nested relations are never returned by default,
  preventing accidental over-fetching. Any field a list view needs by
  default (e.g., a patient's name on an appointment row) is defined as
  part of that endpoint's base shape, not as an implicit relation load.

### 5. Route Organization & Versioning (FR-022)

- All routes under `/api/v1/...`, resource-named and no more than one
  level of nesting (e.g., `/api/v1/visits/{visit}/services` is
  acceptable; a second nested level is not — flatten with query filtering
  instead).
- Standard REST verbs throughout (`GET`, `POST`, `PATCH`, `DELETE`). Per
  Constitution Article I, a `DELETE` route on a record with no
  hard-delete semantics (e.g., `DELETE /api/v1/appointments/{id}`) MUST
  perform the equivalent logical status transition (e.g., cancellation),
  never a physical row delete — this MUST be documented per-endpoint so
  the verb's meaning is never ambiguous to a frontend consumer.

### Additional Functional Requirements (from this standard)

- **FR-019**: Every error response MUST include a fixed, language
  -invariant `error_code` in addition to the localized `message`; frontend
  logic MUST be able to branch on `error_code` alone.
- **FR-020**: Every list endpoint MUST support the standardized pagination
  parameters, and MAY support sorting/filtering/search only through the
  documented, whitelisted parameter conventions above — never arbitrary
  dynamic query construction from client input.
- **FR-021**: Every endpoint returning a resource with relations MUST
  support the standardized `include` parameter with a per-endpoint
  whitelist, and MUST NOT return unrequested relations by default.
- **FR-022**: A `DELETE` verb on any endpoint backed by a record with no
  true hard-delete (per Constitution Article I) MUST perform the
  equivalent status transition, never a physical row deletion — and this
  behavior MUST be documented at the endpoint level.

## Out of Scope (this iteration)

- Attachment upload endpoints (file upload for X-rays/photos/documents) —
  already explicitly deferred to Clinical V1.5 in the PRD; this feature
  wires up existing domain modules only.
- Advanced search/filtering beyond basic pagination (e.g., full-text
  search, multi-field composite filters) — first iteration ships
  pagination only; richer querying is a follow-up spec if needed.
- Refresh-token rotation or long-lived session strategies beyond Sanctum's
  standard token issuance/revocation.
- General-purpose API rate limiting beyond the login endpoint specifically.
- Machine-readable API documentation generation (OpenAPI/Swagger).
- GraphQL or any non-REST interface.

## Clarifications

### Session 2026-07-21 — Confirmed

- **Q1: Does the `doctor` role's patient restriction mean a doctor can
  only view patients they have an existing appointment/visit with, or the
  full clinic patient list?**
  **A1**: Restricted to patients they have an existing
  appointment/visit relationship with (FR-011).

- **Q2: What is the required bearer token lifetime / expiration policy?**
  **A2**: 8 hours from issuance, matching a single work shift (FR-017).

- **Q3: What are the specific rate-limit thresholds for the login
  endpoint?**
  **A3**: 5 attempts per minute per email+IP combination (FR-015).

- **Q4: Should `admin` (branch-scoped) be allowed to view invoices/
  payments for their branch?**
  **A4**: Yes, read-only, scoped to their own branch. Write actions
  remain exclusive to `accountant` and `super-admin` (FR-018).

## Review & Acceptance Checklist

- [x] No implementation details (no controller/route/middleware class
      names) — verified: this document describes contract and behavior
      only.
- [x] Every functional requirement is independently testable.
- [x] Scope is bounded — Out of Scope section is explicit.
- [x] API Contract Standard resolved — envelope shape, error code
      catalog, pagination/filtering/sorting/search conventions, `include`
      policy, and route/versioning rules are all specified above,
      removing the last open architectural ambiguity before `/plan`.
- [x] Clarifications resolved — all four questions confirmed by
      project owner on 2026-07-21.