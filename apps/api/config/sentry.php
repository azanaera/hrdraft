<?php

// Deliberately minimal — Sentry is opt-in for this app. Every value here
// falls back to a safe no-op when SENTRY_LARAVEL_DSN isn't set (local dev,
// CI, and any environment where nobody's created a Sentry project yet), so
// installing the SDK has zero effect until a real DSN is dropped in.
return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),

    // Only send errors when a DSN is actually configured — the SDK no-ops
    // without one anyway, but being explicit avoids any surprise in an
    // environment that sets APP_ENV=production without also setting a DSN.
    'send_default_pii' => false,

    // Sample the full breadcrumb trail on error but keep performance-trace
    // sampling low; this is a small internal-pilot app, not a
    // high-throughput service that needs tracing insight yet.
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.0),

    'environment' => env('APP_ENV'),

    'release' => env('SENTRY_RELEASE'),
];
