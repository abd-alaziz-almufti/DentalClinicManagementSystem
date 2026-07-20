# API Contracts — Deferred

Per PRD §14/§15 and the project's own stated sequencing, the HTTP layer
(Controllers, Form Requests, API Resources, versioned routes under
`/api/v1/...`) is being built as one cross-cutting pass **after** the
Financial and Inventory domains are complete at the Service-Layer level —
not per-module as each domain is designed.

This directory is intentionally left as a placeholder so the structure
matches the standard Spec Kit layout. When the HTTP layer effort begins,
contracts for this module (`POST /api/v1/visits/{visit}/invoice`,
`POST /api/v1/invoices/{invoice}/payments`, `POST
/api/v1/invoices/{invoice}/cancel`, etc.) will be added here, following
the uniform response envelope defined in Constitution Article X.