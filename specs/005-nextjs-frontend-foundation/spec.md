# Feature Specification: Next.js Frontend Foundation

**Feature Branch**: `005-nextjs-frontend-foundation`
**Created**: 2026-07-29
**Status**: Drafting — pending `/speckit.plan`
**Depends on**: HTTP API Layer (`004-http-api-layer`) — must be fully operational

## Context

The backend API is fully built, tested, and versioned under `/api/v1/...`.
This feature builds the React/Next.js frontend that consumes it — not a
prototype, but a production-grade SPA aligned with the same engineering
standards as the backend.

The system now has exactly **three roles**: `super-admin`, `admin`, `doctor`.
There are no Receptionist screens, no Operations Manager screens, and no
Accountant or Inventory Manager personas. All screen design and routing MUST
be derived from these three roles only.

## User Scenarios & Testing

### Primary User Story

As a clinic staff member (admin or doctor), I need a fast, role-aware web
interface that shows me only the functions relevant to my role — so I can
register patients, manage appointments, record treatments, issue invoices, and
view inventory without seeing screens or actions that do not apply to my
access level.

As a super-admin, I need cross-branch visibility over all the same
functions — without a different UI paradigm.

### Role-to-Screen Mapping

| Role | Accessible Areas |
|---|---|
| `super-admin` | All screens across all branches; branch switcher visible |
| `admin` | Patients, Appointments, Visits (read), Invoices & Payments, Inventory — scoped to own branch |
| `doctor` | My Appointments, My Patients, Visit Treatment Recording, Dental Chart — scoped to own patients/visits |

> **Note**: The `doctor` role is the only role with write access to clinical
> treatment records (visit services, dental chart). The UI MUST reflect this:
> treatment recording controls (add service, update dental chart) are rendered
> only when the authenticated user is a `doctor`. Admins see treatment data
> read-only.

### Acceptance Scenarios

1. **Given** a user with the `admin` role logs in, **When** the dashboard
   loads, **Then** only admin-relevant navigation items are shown —
   no treatment-recording screens are accessible from the sidebar.

2. **Given** a user with the `doctor` role logs in, **When** a visit detail
   page loads, **Then** treatment recording controls (add service, dental
   chart entry) are rendered and interactive — and invoice/payment write
   actions are also available to the doctor within their visit scope.

3. **Given** an `admin` navigates to a visit detail page, **When** the page
   renders, **Then** treatment recording controls are absent or disabled —
   the admin can view but not write clinical data.

4. **Given** any authenticated user, **When** they attempt to access a route
   outside their role's permitted scope (e.g., by direct URL), **Then** they
   are redirected to an appropriate "forbidden" or "not found" page — never
   shown partial data.

5. **Given** a `super-admin` user, **When** they select a branch from the
   branch switcher, **Then** all subsequent data shown is scoped to that
   branch — identical UX to the admin, but with the ability to switch.

### Edge Cases

- What if a token expires mid-session? → The frontend MUST detect the
  `UNAUTHENTICATED` error code from the API and redirect to the login page
  without showing a generic error.
- What if a list endpoint returns an empty collection? → A friendly empty
  state (not an error) is shown.
- What if the user's role changes server-side while they are logged in? →
  On next API call, the `FORBIDDEN` response MUST trigger a session refresh
  and role re-evaluation.

## Requirements

### Functional Requirements

- **FR-001**: The application MUST authenticate via the backend's `/api/v1/
  login` endpoint and store the bearer token securely (HttpOnly cookie or
  secure in-memory store — never `localStorage`).
- **FR-002**: Every API request MUST include the bearer token; any `401`
  response MUST trigger an immediate redirect to the login page.
- **FR-003**: Navigation and rendered controls MUST be gated by the
  authenticated user's role — server-side role data from `/api/v1/auth/me`
  is the source of truth, never client-side assumptions.
- **FR-004**: The `doctor` role is the ONLY role permitted to render
  treatment-write controls (add/edit visit services, add/edit dental chart
  entries). All other roles see these areas as read-only.
- **FR-005**: All list views MUST use the paginated API response (`meta`
  envelope) — no list screen may render an unbounded array.
- **FR-006**: Every API error response MUST be handled by branching on
  `error_code` (the stable, language-invariant field), never the localized
  `message` string.
- **FR-007**: The frontend MUST send the `Accept-Language` header on every
  request, derived from the user's selected locale — English and Arabic MUST
  both be supported at launch (per Constitution Article IX).
- **FR-008**: The UI MUST display all text that originates from the backend
  (localized `message` fields) as-is — it MUST NOT re-translate or override
  backend-localized strings with its own translations.
- **FR-009**: Route protection MUST be enforced server-side (Next.js
  middleware or equivalent) in addition to UI-level hiding — a direct URL
  navigation by a forbidden role MUST result in a redirect, not just hidden
  UI elements.
- **FR-010**: No Receptionist, Operations Manager, Accountant, or Inventory
  Manager screens MUST exist in this application. Any Stitch-generated or
  AI-assisted screen scaffold that references these personas MUST be deleted
  before implementation begins.

### Key Screens (by role)

**Shared (all roles after login):**
- Login page
- Dashboard (role-aware summary)
- Profile / change password

**Admin + Super-Admin:**
- Patient list + registration form
- Appointment list + booking form
- Visit list (read-only treatment view)
- Invoice list + generate invoice + record payment
- Inventory item list + purchase recording

**Doctor:**
- My appointments (filtered to own)
- My patients (filtered to own)
- Visit detail with treatment recording (add/remove services, dental chart)
- Invoice list for own visits (read + write payments)

**Super-Admin additionally:**
- Branch switcher (persistent, session-scoped)
- Cross-branch patient/appointment/invoice views

## Clarifications

### Session 2026-07-29

- **Q1: What authentication persistence strategy is used?**
  **A1**: HttpOnly cookie via Sanctum's cookie-based SPA authentication, or
  bearer token stored in memory (not localStorage). Decision deferred to
  `/speckit.plan` — must be consistent with the backend's Sanctum config.

- **Q2: Should the app be a full SPA or use Next.js server-side rendering?**
  **A2**: Next.js App Router with server components for initial page loads and
  client components for interactive sections — not a pure SPA. This enables
  server-side route protection (FR-009) without duplicating it in client JS.

- **Q3: Is there a design system / component library in use?**
  **A3**: To be decided in `/speckit.plan`. Candidates: shadcn/ui (preferred
  for composability), or plain CSS Modules. Tailwind is acceptable if it
  does not conflict with team convention.

- **Q4: Which locale is the default?**
  **A4**: Arabic (`ar`) as the default, with English (`en`) as the secondary.
  RTL layout MUST be supported for the Arabic locale.

- **Q5d: Which Stitch-generated screens are authoritative, and which must
  be deleted?**
  **A5d**: **Final ruling** — the system has exactly three roles.
  Any Stitch scaffold that generated screens for `Receptionist`,
  `Operations Manager`, `Accountant`, or `Inventory Manager` personas MUST
  be deleted in their entirety before implementation begins. The authoritative
  screen list is the Role-to-Screen Mapping table in this document. Do not
  adapt or rename discarded screens — delete them. Start only from the
  three-role mapping defined here.

## Out of Scope (this iteration)

- SVG interactive dental chart UI (data is ready; the visual chart is V2).
- File/attachment upload UI (schema is ready; upload UI is V1.5).
- Patient-facing booking portal.
- Mobile application.
- Push notifications, SMS/WhatsApp reminders.
- Advanced analytics dashboards beyond basic summary counts.

## Review & Acceptance Checklist

- [ ] No implementation details (no component library names finalized,
      no file/folder structure decided) — verify before marking done.
- [ ] Every functional requirement is independently testable.
- [ ] Scope is bounded — Out of Scope section is explicit.
- [ ] Role-to-Screen Mapping reviewed and confirmed: exactly 3 roles,
      no legacy persona screens remain.
- [ ] Q5d ruling recorded and communicated to anyone using Stitch or an
      AI screen generator for this feature.
