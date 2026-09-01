# HRIS — Pilot Readiness

Outcome of a readiness review: scoping this HRIS for an **internal pilot with real employees**, real HR workflows (hire/transfer/terminate/onboard/time-off), but **synthetic banking/payroll** — not a public production rollout. See [SPEC.md](SPEC.md) for the original product spec and [TEST_PLAN.md](TEST_PLAN.md) for the test suite this all runs against.

Single-tenant, open-ended timeline (no fixed deadline, but treated with real urgency).

---

## Decided and built

| Item | Decision | Status |
|---|---|---|
| Hosting | Managed PaaS + managed Postgres — Render recommended, starting from scratch | ✅ Packaged, not deployed — `Dockerfile`, `docker/nginx.conf`, `docker/entrypoint.sh`, `render.yaml` (Blueprint: web service + Postgres + accrual cron job). See [DEPLOYMENT.md](DEPLOYMENT.md). **Not build-tested** — Docker isn't available in the dev environment this was written in. |
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

These require an account, a purchase, or a judgment call outside engineering — I won't do these unilaterally. Follow [DEPLOYMENT.md](DEPLOYMENT.md) for the concrete steps:

1. **Build and smoke-test the Docker image locally** before trusting it — it's written but not build-verified (no Docker available in the dev environment).
2. **Push this repo to a GitHub remote**, then connect it to Render as a Blueprint (`render.yaml` provisions the web service, database, and cron job together).
3. **Create an S3 bucket + scoped IAM user**, drop the credentials into Render's env vars — `FILESYSTEM_DISK=s3` is already set in `render.yaml`.
4. **Create a Sentry account** (optional) and set `SENTRY_LARAVEL_DSN` — the code side is already done.
5. **Confirm the managed Postgres plan includes backups**, and do one restore drill before pilot go-live — don't trust a backup that's never been restored.
6. **Run `php artisan hris:create-admin`** once, right after first deploy, to create the one real admin account — never run `DemoDataSeeder` anywhere real employees will log in.
7. **Decide who the pilot cohort is** and run the mixed data-migration approach (a few via CSV import, rest via Hire UI).
8. **Brief HR on the two compliance caveats** above (I-9 stays paper, background checks stay parallel) — the UI now says this, but a real conversation matters more than a banner.

## Explicitly out of scope for this pilot (revisit before any move to full production)

Payroll gross-to-net, SSO/SAML, KMS-backed field encryption, continuous WAL/PITR backups, a dedicated admin-action audit log, a staging environment, role-permission granularity beyond the 4-role model, real e-signature/background-check/banking provider integration, mobile app verification.
