# HRIS — Draft MVP

A from-scratch HR system: Laravel API + PostgreSQL backend, a React (Vite) web app, and a React Native (Expo) mobile app, sharing types and an API client through a single npm-workspaces monorepo.

Covers: onboarding, an applicant tracking system (deliberately decoupled — see `HireCandidateService` below), compensation management with effective-dated pay history, document storage, an employee notes/timeline, and time-off tracking. The data model treats transfers and rehires as first-class, non-destructive events (see **Data model notes** below) since the business has high turnover, frequent field transfers, and frequent rehires.

This is a draft, built to be extended as requirements firm up — not a finished product.

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

**Backend — verified working on this machine:**
```bash
php artisan test           # 17/17 Pest feature tests passing, against a real Postgres db —
                            # covers rehire-creates-new-employment, transfer-preserves-history,
                            # overlapping-compensation-rejected (DB constraint), and role-boundary auth
php artisan migrate --seed # done — all 14 migrations + 3 seeders ran clean
php artisan serve          # done — running on :8000, /api/v1/auth/me correctly returns 401 unauthenticated
```

**Web app — verified working on this machine:**
```bash
npm install                                    # done — no errors
npm run --workspace=apps/web typecheck          # done — passes
npm run --workspace=apps/web test               # done — passing
npm run dev:web                                 # done — loads at localhost:5173
```

**Login — verified end-to-end in the browser**: signed in as `admin@example.com` through the actual running UI, and the employee list rendered the real seeded records (Jamie Rivera, Morgan Lee, Casey Nguyen) fetched live from Postgres through the API.

Two real bugs were caught and fixed by this end-to-end pass (worth knowing about, since they're the kind of thing that only shows up once you run the real thing rather than just reading the code):
- **CSRF header wasn't attached.** Sanctum's SPA auth needs the `XSRF-TOKEN` cookie (set by `/sanctum/csrf-cookie`) echoed back as an `X-XSRF-TOKEN` request header on every state-changing request. The original `api-client` fetched the cookie but never read it back — fixed in `packages/api-client/src/index.ts`.
- **Unauthenticated API requests without an `Accept: application/json` header 500'd instead of 401'ing**, because Laravel's default `Authenticate` middleware tried to redirect to a named `login` route that doesn't exist in a JSON-only API. Fixed via `Authenticate::redirectUsing(fn () => null)` in `AppServiceProvider`. Doesn't affect the real frontends (they always send `Accept: application/json`), but matters for anything hitting the API directly (curl, health checks, Postman).

**Mobile app — scaffolded but not installed/run in this environment** (no simulator/device available here). To verify: `npm run dev:mobile`, scan the QR with Expo Go, log in as `casey.nguyen@example.com`, and confirm the Profile / Time Off tabs work; log in as `people.manager@example.com` and confirm the Team tab shows pending approvals.

**Not yet walked end-to-end**: the full ATS flow (create requisition → add candidate → move through pipeline → hire) and confirming the resulting employee's timeline/onboarding/compensation records — the pieces are all unit/feature-tested individually but no one has clicked through that full journey in the UI yet.

## Data model notes

- **`people` vs `employments` vs `assignments`**: a `person` is the human and persists forever; an `employment` is one hire stint (a rehire creates a brand-new row, never overwrites the old one); an `assignment` is one department/location/position/manager period (a transfer closes the current assignment and opens a new one). This is why rehires and transfers don't destroy history.
- **Compensation** is effective-dated with a Postgres `EXCLUDE USING gist` constraint that makes overlapping pay periods for the same employment impossible at the database level, not just in application code.
- **`employee_events`** is a single, append-only timeline table that every module writes into (hires, transfers, comp changes, notes, document uploads, time-off decisions), so an employee's whole history reads as one chronological feed.
- **ATS is intentionally decoupled**: `App\Domain\ATS\Services\HireCandidateService` is the *only* code path that writes ATS data into core employee records. If a third-party ATS replaces this module later, only that one integration point needs to change.

## What's not built yet

This is a draft. Known gaps to revisit as requirements solidify: multi-step time-off approval chains, real accrual scheduling (the ledger/balance mechanism is in place but nothing runs it on a cron yet), S3 document storage (disk is pre-wired, not enabled), push notifications on mobile, and an admin UI for managing onboarding templates/time-off policies (currently seeded, not editable in the UI).
