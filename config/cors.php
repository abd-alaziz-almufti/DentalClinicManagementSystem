<?php

return [

    /*
     * Only the API and Sanctum's CSRF cookie route need CORS — no other
     * paths should be reachable cross-origin.
     */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
     * FR-016 (004-http-api-layer/spec.md): only the Next.js frontend's
     * configured origin(s) — never a wildcard. Set CORS_ALLOWED_ORIGINS
     * in .env as a comma-separated list for multiple environments, e.g.
     * "http://localhost:3000,https://app.yourclinic.com".
     */
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
     * false: this project uses Sanctum bearer tokens (Authorization
     * header), not cookie-based SPA sessions (per
     * 005-nextjs-frontend-foundation/spec.md Clarification Q2) — no
     * credentialed cross-origin requests are needed.
     */
    'supports_credentials' => false,

];
