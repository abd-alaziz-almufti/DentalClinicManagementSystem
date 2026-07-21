# Phase 0 Research: HTTP API Layer

## R1: How is branch-scoping (FR-010) enforced "centrally," without silently altering Service-layer or Tinker/queue behavior?

**Options considered:**
- (a) An Eloquent Global Scope on every branch-scoped model, auto-filtering by `auth()->user()->branch_id`.
- (b) A single reusable, explicitly-invoked query-scoping helper, called once per Controller's listing method.

**Decision: (b).**

**Rationale**: A Global Scope is implicit — it would silently alter every
existing, already-verified Service-layer call (Tinker sessions, future
queue jobs, `/speckit.plan`-verified concurrency-critical queries in
`AppointmentService`/`RecordPaymentService`) the moment `auth()->user()`
happens to resolve, even outside an HTTP request. That risk is
unacceptable for logic that has already been proven correct. Per
Constitution Article III's "explicit over implicit" spirit, branch
scoping is implemented as a single `BranchScopeFilter` class with one
method, invoked explicitly by each Controller's `index()` action — the
logic is centralized (one class), but its *application* is explicit at
every call site, never magic.

## R2: How are role-based authorization checks (FR-005) enforced before a Service class executes?

**Decision: Laravel Policies, one per domain Model, backed by Spatie role checks (`$user->hasRole(...)` / `$user->can(...)`), invoked via `$this->authorize()` inside each Controller action before validation.**

**Rationale**: Policies are Laravel's standard mechanism for exactly this
question ("can this user do this to this resource"), and they compose
naturally with Spatie Permission (already the project's sole
authorization tool per Constitution Technology Constraints — no new
package). No custom authorization middleware is introduced.

## R3: How is the fixed, language-invariant `error_code` (FR-019) attached to each domain exception without a growing if/else chain in the central Handler?

**Decision: A small `ApiExceptionInterface` (methods: `errorCode(): string`, `httpStatus(): int`, `translationKey(): string`, `translationParams(): array`) implemented by every domain exception. The central Handler has exactly one branch: "if the exception implements this interface, use its methods to build the envelope" — not a per-exception-class switch.**

**Consequence — required modification to existing code**: The three
exception classes already built (`AppointmentConflictException`,
`VisitNotEditableException`, `InvalidAppointmentStatusException`) — plus
`VisitAlreadyInvoicedException`, `InvalidInvoiceStatusException`,
`PaymentExceedsBalanceException` from the Financial plan — currently
construct a final, hardcoded **English** message string directly (e.g.
`AppointmentConflictException::forSlot()` builds `"The doctor already
has..."` inline). This is exactly the compliance gap flagged in PRD
§10.1 ("exception classes MUST carry a translation key plus data, never
a hardcoded final-language string") and in Constitution Article IX — it
was deferred until a concrete consumer (this HTTP layer) needed it. This
plan retrofits all six exception classes to implement
`ApiExceptionInterface` and drop their hardcoded strings in favor of
translation keys, resolved by the Handler at response-build time.

## R4: How is the 8-hour token expiration (FR-017) enforced?

**Decision: Laravel Sanctum's native `expiration` config value (minutes), not custom code.**

**Rationale**: Sanctum already checks token expiration natively when
`config('sanctum.expiration')` is set (minutes since issuance); setting
it to `480` satisfies FR-017 with a one-line config change and zero new
logic — deliberately the simplest sufficient solution rather than a
custom expiry-checking middleware.

## R5: How is the login rate limit (FR-015: 5/minute per email+IP) enforced?

**Decision: Laravel's native named rate limiter (`RateLimiter::for('login', ...)`), keyed on `email` + `request()->ip()`, applied via the built-in `throttle:login` middleware on the login route only.**

**Rationale**: Built into the framework already in use; no new package,
no custom middleware logic — consistent with preferring native/existing
tooling over bespoke solutions wherever it fully satisfies the
requirement (same reasoning as R4).

## R6: How are pagination, sorting, filtering, and `include` (FR-020, FR-021) implemented without duplicating query-building logic per domain Controller?

**Decision: `spatie/laravel-query-builder`.**

**Rationale**: This package implements, out of the box, precisely the
contract already fixed in `spec.md`'s API Contract Standard —
`?filter[x]=`, `?sort=`, `?include=`, all whitelist-based per query. It
is the same vendor as `spatie/laravel-permission` (Foundation) and
`spatie/laravel-translatable` (PRD §10.1/ADR-010) — continuing an
established project pattern of adopting a well-maintained Spatie package
over hand-rolling equivalent, easy-to-get-subtly-wrong query logic.
Per-endpoint whitelists (allowed filters/sorts/includes) are declared
once per Controller action, satisfying FR-020/FR-021's "per-endpoint
whitelist, not arbitrary dynamic query construction" requirement
directly via the package's own design.

## R7: How is per-request locale resolution (FR-006) implemented?

**Decision: A single `SetLocaleFromRequest` middleware (already
specified conceptually in PRD §10.1, Layer 1), registered globally in
`bootstrap/app.php`, reading `Accept-Language`, validating against
`['ar','en']`, calling `app()->setLocale(...)`. No per-controller locale
handling anywhere.**

This research point has no open question — it confirms the approach
already decided in PRD §10.1 carries over unchanged into this feature's
implementation, with no new decision needed.

## Summary of Decisions

| # | Question | Decision |
|---|---|---|
| R1 | Branch scoping without side effects elsewhere | Explicit `BranchScopeFilter`, invoked per-Controller, not a Global Scope |
| R2 | Authorization before Service execution | Laravel Policies + Spatie role checks |
| R3 | Language-invariant error codes without a growing switch | `ApiExceptionInterface`; retrofits 6 existing exceptions to drop hardcoded English strings |
| R4 | 8-hour token expiration | Sanctum's native `expiration` config — no custom code |
| R5 | Login rate limiting | Laravel's native named rate limiter — no custom code |
| R6 | Pagination/sort/filter/include | `spatie/laravel-query-builder` — same vendor pattern as R2/PRD §10.1 |
| R7 | Locale resolution | Confirms PRD §10.1 Layer 1 middleware unchanged |