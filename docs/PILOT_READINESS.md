# HRIS — Pilot Readiness

Outcome of a readiness review: scoping this HRIS for an **internal pilot with real employees**, real HR workflows (hire/transfer/terminate/onboard/time-off), but **synthetic banking/payroll** — not a public production rollout. See [SPEC.md](SPEC.md) for the original product spec and [TEST_PLAN.md](TEST_PLAN.md) for the test suite this all runs against.

Single-tenant, open-ended timeline (no fixed deadline, but treated with real urgency).

---

## Decided and built

| Item | Decision | Status |
|---|---|---|
| Hosting | Managed PaaS + managed Postgres | **Not started** — needs an account/platform choice, not something to do unilaterally |
| Time-off accrual | Build the real scheduled job before pilot | ✅ Built — `TimeOffAccrualService`, `time-off:accrue` Artisan command, scheduled daily in `routes/console.php`. 7 Pest tests. |
| Document storage | Enable S3 before pilot | **Ready, not enabled** — disk is pre-wired (`config/filesystems.php`); just needs real `AWS_*` env vars once a bucket exists |
| Error monitoring | Add basic tracking (Sentry) before going live | ✅ Wired — `sentry/sentry-laravel` installed, config is a no-op until `SENTRY_LARAVEL_DSN` is set in `.env` |
| CI gate | Add CI running `test:regression` on every push | ✅ Written — `.github/workflows/regression.yml`. **Needs a GitHub remote** to actually run; the repo has none configured yet |
| Backups | Nightly `pg_dump`, not continuous PITR | **Not started** — depends on the hosting choice above; do a restore drill before pilot goes live, not just take backups |
| General API rate limiting | Add a sane ceiling beyond login | ✅ Built — `throttle:api` at 300 req/min per user on the whole authenticated route group. (First attempt at 120/min broke under the E2E suite's own traffic — real signal that 120 was too tight for legitimate bursty admin use too.) |
| SSN/PII encryption | Keep app-level `encrypted` cast | No change needed — already matches SPEC.md's existing decision |
| Admin action audit log | Not needed at pilot scale | No change needed — `employee_events` + `created_by_user_id` columns already cover it |
| Mobile app | Out of scope for pilot | No change needed — stays unverified, web-only pilot |
| Data migration | Mixed: a few real employees via CSV import, rest fresh via Hire UI | No code needed — process decision for whoever runs the pilot's onboarding |
| Staging environment | None — CI-passing + manual check gates a direct deploy | No change needed |
| Accessibility | Light pass, no formal audit | ✅ Checked — no `<img>` tags anywhere (no alt-text risk), no clickable `<div>`s (all interaction goes through native `<button>`/`<a>`/form controls), every input already wrapped in a `<label>`, status badges convey state via text + color (not color alone), badge color pairs pass WCAG AA contrast, `<nav>`/`<main>` landmarks present. **No changes needed** — the app was already accessibly built. |

## Compliance caveats surfaced in the UI, not just docs

- **E-signature** (`DocumentsTab.tsx`): a banner states the current flow isn't legally binding; I-9 rows show "use paper process" instead of an active Sign button — the fake provider doesn't apply to I-9 at all.
- **Background checks** (`OnboardingTab.tsx`): a banner states the in-system status isn't authoritative — HR must still run the real background check/E-Verify process outside the system until a real provider replaces the fake one.

Both are fast-follow items — swap in a real provider (DocuSign/Dropbox Sign; Checkr/E-Verify API) shortly after pilot start, not before.

## What still needs a human, not more code

These require an account, a purchase, or a judgment call outside engineering — I won't do these unilaterally:

1. **Pick and set up the PaaS + managed Postgres.** Everything else (S3, backups, `.env` production values, `SANCTUM_STATEFUL_DOMAINS`/`FRONTEND_URL` for the real domain) flows from this choice.
2. **Create an S3 bucket** and drop the real `AWS_*` credentials into production `.env` — the code side is already done.
3. **Create a Sentry account** (or pick an alternative) and set `SENTRY_LARAVEL_DSN` — the code side is already done.
4. **Push this repo to a GitHub remote** so `.github/workflows/regression.yml` actually runs.
5. **Set up nightly `pg_dump` on the chosen hosting platform**, and do one restore drill before pilot go-live.
6. **Decide who the pilot cohort is** and run the mixed data-migration approach (a few via CSV import, rest via Hire UI).
7. **Brief HR on the two compliance caveats** above (I-9 stays paper, background checks stay parallel) — the UI now says this, but a real conversation matters more than a banner.

## Explicitly out of scope for this pilot (revisit before any move to full production)

Payroll gross-to-net, SSO/SAML, KMS-backed field encryption, continuous WAL/PITR backups, a dedicated admin-action audit log, a staging environment, role-permission granularity beyond the 4-role model, real e-signature/background-check/banking provider integration, mobile app verification.
