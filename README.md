# HRIS — Draft MVP

A from-scratch HR system: Laravel API + PostgreSQL backend, a React (Vite) web app, and a React Native (Expo) mobile app, sharing types and an API client through a single npm-workspaces monorepo.

Covers: onboarding and offboarding (mirrored workflow-task models, with final-payout calculation), an applicant tracking system (deliberately decoupled — see `HireCandidateService` below, with rehire detection via email/name+DOB match), compensation management with effective-dated pay history and per-location minimum-wage validation, document storage with e-signature acknowledgment, an employee notes/timeline, time-off tracking, in-app + email notifications, banking-info tokenization, background-check/E-Verify tracking, bulk transfer, CSV data import, turnover reporting, and a dashboard. The e-signature, background-check, and banking integrations are all built as swappable provider interfaces with a `Fake*Provider` bound for local use — swapping in a real SDK later is a single binding change, not a rewrite (see `AppServiceProvider::register()`). The data model treats transfers and rehires as first-class, non-destructive events (see **Data model notes** below) since the business has high turnover, frequent field transfers, and frequent rehires.

This is a draft MVP with its full first-iteration spec implemented (see [docs/SPEC.md](docs/SPEC.md)) and a working automated test suite — not a finished product. Payroll gross-to-net calculation, SSO/SAML, and a few other explicitly-deferred items remain open; see **What's not built yet** below.

## Repo layout

```
apps/
  api/      Laravel backend (PHP, PostgreSQL, Sanctum auth)
  web/      React web app (Vite + TypeScript)
  mobile/   React Native app (Expo) — scoped v1: self-service + manager approvals
packages/
  shared-types/   TS interfaces mirroring the API's JSON responses
  api-client/     Typed fetch wrapper used by both web and mobile
  ui-tokens/      Shared colors/spacing/typography
```

## Current state on this machine

**Backend, web, and login are fully working and verified end-to-end on this machine right now.** Node/npm/git came pre-installed; PHP, Composer, and PostgreSQL were added afterward via `scoop` (a user-space package manager already present — no admin rights, no Docker, no restart needed):

```bash
scoop install php composer postgresql
```

Since there's no Docker here, the backend runs **natively** rather than via Laravel Sail: PHP's built-in server (`php artisan serve`) talks directly to a local PostgreSQL instance started with `pg_ctl`. Composer resolved **Laravel 12.64** / **Sanctum 4.3** / **Pest 3.8** rather than the originally-planned Laravel 11/Pest 2 line — Composer's security-advisory audit blocks installing any Laravel 11.x release at this point, so the whole stack moved to the current major version. Everything in the codebase (the Laravel 11-style slim `bootstrap/app.php` structure, Sanctum SPA+token auth, etc.) is unaffected by that bump.

If you're setting this up somewhere else — a teammate's machine, CI, a server — you have two options:

- **Native (what's running here)**: install PHP 8.2+, Composer, and PostgreSQL directly (via scoop on Windows, `brew` on macOS, your package manager on Linux). No Docker needed.
- **Docker + Laravel Sail**: if you'd rather containerize, `apps/api` already ships with `laravel/sail` as a dependency — `composer install`, then `./vendor/bin/sail up -d` instead of running Postgres/PHP natively. Requires Docker Desktop (+ WSL2 on Windows).

For the mobile app: the **Expo Go** app on your phone (easiest), or Xcode/Android Studio simulators. No native toolchain is required for the scoped v1 as long as you use Expo Go. The mobile app has been scaffolded but not installed/run in this environment (no simulator or device available here).

## First-time setup (native path — what's running here)

```bash
# 1. Install JS dependencies for web + mobile + shared packages
npm install

# 2. Install PHP/Composer/PostgreSQL (skip if already installed)
scoop install php composer postgresql
# enable required PHP extensions in the php.ini scoop installs (openssl, pdo_pgsql,
# pgsql, mbstring, fileinfo, curl are all present but commented out by default)

# 3. Start PostgreSQL and create the database once
pg_ctl -D "$HOME/scoop/apps/postgresql/current/data" -l pg_log.txt start
psql -U postgres -d postgres -c "CREATE ROLE sail WITH LOGIN PASSWORD 'password' CREATEDB;"
psql -U postgres -d postgres -c "CREATE DATABASE hris OWNER sail;"
psql -U postgres -d postgres -c "CREATE DATABASE hris_testing OWNER sail;"

# 4. Backend: install PHP dependencies, configure, migrate, seed
cd apps/api
composer install
cp .env.example .env   # then set DB_HOST=127.0.0.1 and APP_URL=http://localhost:8000
php artisan key:generate
php artisan migrate --seed
cd ../..
```

The seeder prints four demo logins (all password `password`):
`admin@example.com`, `hr.manager@example.com`, `people.manager@example.com`, `casey.nguyen@example.com` (a regular hourly employee).

## Running it day-to-day

Three terminals:

```bash
# Terminal 1 — backend (from apps/api)
php artisan serve --port=8000

# Terminal 2 — web app (from repo root)
npm run dev:web
# → http://localhost:5173

# Terminal 3 — mobile app (from repo root)
npm run dev:mobile
# → scan the QR code with Expo Go on your phone
```

The web app's Vite dev server proxies `/api` and `/sanctum` to `http://localhost:8000` — see `apps/web/vite.config.ts`. The mobile app talks to `http://localhost:8000/api` by default (`apps/mobile/app.json` → `extra.apiBaseUrl`); update that to your machine's LAN IP when testing on a physical device, since `localhost` on the phone means the phone itself.

(If you switch to the Docker/Sail path instead, swap terminal 1 for `./vendor/bin/sail up`, which serves on port 80 — then point the web proxy and mobile `apiBaseUrl` at that instead of `:8000`.)

## Verification

**Full automated regression — passing on this machine right now:**
```bash
npm run test:regression   # one command: Pest → Vitest → Playwright, see docs/TEST_PLAN.md
```
- **Backend**: 52 Pest feature tests / 137 assertions, against a real Postgres db — covers every module (employee core, compensation + wage compliance, time off, onboarding/offboarding, ATS, banking/signature/background-check providers, admin CRUD, data import, dashboard/reporting, auth + password reset + rate limiting) plus role-boundary checks throughout.
- **Frontend components**: Vitest + Testing Library.
- **End-to-end**: 25 Playwright scenarios driving the real browser UI against the real backend — one journey per major flow (hire, transfer, rehire, terminate → offboard, raise + wage-compliance rejection, time-off submit/approve, document sign, ATS full pipeline, bulk transfer, admin CRUD, CSV import, dashboard/reports, role boundaries). See [docs/TEST_PLAN.md](docs/TEST_PLAN.md) for what each one covers and the process for adding more.

**Manually walked in the browser**: the full ATS-hire-to-timeline flow — create requisition → add candidate → hire → confirm the resulting employee record, initial compensation, auto-started onboarding workflow, and auto-run background/E-Verify checks all show up correctly on the Timeline and Onboarding tabs. This same journey caught a real backend bug (see below) before it was automated as `ats-full-pipeline.spec.ts`.

**Real bugs caught and fixed by end-to-end testing** (worth knowing about — none of these were visible from unit/feature tests alone, since those use tidy, deliberately-ordered inputs):
- A model (`ApplicationStageHistory`) whose table name didn't match Eloquent's default pluralization guess — 500'd the moment a real candidate-creation request exercised it.
- `assignments.position_id` is a NOT NULL database column, reachable through a UI form (create requisition → hire) that didn't collect it — 500'd on hire. Fixed by requiring a position on requisition creation, matching the constraint's intent.
- Out-of-order API responses clobbering newer UI state when a user acted quickly, in the employee list, time-off list, requisition detail, and employee detail pages — fixed with a request-sequencing guard.
- The ATS "former employee" badge was being set to `true` for *every* hired candidate, not just genuine rehires, because the hire flow reused the same `linked_person_id` column that pre-hire rehire-detection uses.
- Two `AuthController::logout()` crashes (a `TransientToken` that isn't a real Sanctum token instance; a bearer-token request with no session to invalidate), and a login rate-limiter that counted successful logins against the same bucket as failures.

**Mobile app — scaffolded but not installed/run in this environment** (no simulator/device available here). To verify: `npm run dev:mobile`, scan the QR with Expo Go, log in as `casey.nguyen@example.com`, and confirm the Profile / Time Off tabs work; log in as `people.manager@example.com` and confirm the Team tab shows pending approvals.

## Data model notes

- **`people` vs `employments` vs `assignments`**: a `person` is the human and persists forever; an `employment` is one hire stint (a rehire creates a brand-new row, never overwrites the old one); an `assignment` is one department/location/position/manager period (a transfer closes the current assignment and opens a new one). This is why rehires and transfers don't destroy history.
- **Compensation** is effective-dated with a Postgres `EXCLUDE USING gist` constraint that makes overlapping pay periods for the same employment impossible at the database level, not just in application code.
- **`employee_events`** is a single, append-only timeline table that every module writes into (hires, transfers, comp changes, notes, document uploads, time-off decisions), so an employee's whole history reads as one chronological feed.
- **ATS is intentionally decoupled**: `App\Domain\ATS\Services\HireCandidateService` is the *only* code path that writes ATS data into core employee records. If a third-party ATS replaces this module later, only that one integration point needs to change.

## Testing & regression

See [docs/TEST_PLAN.md](docs/TEST_PLAN.md) for the full breakdown of what's covered, how to run each layer individually, and the process to follow on every future change (run `npm run test:regression` before and after, add a test for every new feature or fix). Short version:

```bash
npm run test:regression   # everything — needs the backend running separately (php artisan serve)
npm run test:api          # Pest only
npm run test              # Vitest only (all workspaces)
npm run test:e2e          # Playwright only — needs the backend running
```

This repo is git-initialized (`git log` for history). There's no remote configured yet — set one up when you're ready to collaborate or back this up off the local machine.

## What's not built yet

This is a draft MVP, not a finished product. Explicitly deferred per [docs/SPEC.md](docs/SPEC.md) — do not assume these are oversights:
- **Payroll gross-to-net calculation** (tax withholding, pay stub/W-2 generation) — SPEC.md calls this out as its own major phase, an order of magnitude bigger than everything else here combined.
- **SSO/SAML integration** — the auth layer is kept pluggable, but nothing beyond Sanctum email/password is built.
- **EEO-1 demographic capture, timeline/event archiving policy, WCAG certification** — open questions in SPEC.md §8, not decisions to act on yet.
- **Multi-tenancy, mobile offline support, i18n, SMS/push notifications, multi-level approval chains, fine-grained role permissions** — explicitly out of scope for this phase.

Smaller known gaps: real accrual scheduling (the time-off ledger/balance mechanism is in place but nothing runs it on a cron yet), S3 document storage (disk is pre-wired, not enabled), mobile app has no E2E coverage yet (see docs/TEST_PLAN.md §5).
