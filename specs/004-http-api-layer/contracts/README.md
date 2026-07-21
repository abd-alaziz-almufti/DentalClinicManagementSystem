# API Contracts — Scope Note

Unlike a typical feature with a handful of endpoints, this feature's
"contract" is the *pattern* every domain's endpoints must follow — fully
specified in `spec.md`'s API Contract Standard and this plan's Constitution
Check. Enumerating every individual endpoint (dozens, across Foundation,
Clinical, Financial, and Inventory) as a separate contract file here would
duplicate what `/speckit.tasks` will already break out per domain.

Per-endpoint contracts (exact request/response bodies per route) will be
authored incrementally inside each domain's task group during
`/speckit.tasks` and `/speckit.implement`, always conforming to the
envelope/error-code/pagination rules fixed here — not re-decided per
endpoint.