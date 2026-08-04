# HRIS — Command Reference

Every command needed to set up, run, and test this app. See [README.md](../README.md) for narrative setup context and [TEST_PLAN.md](TEST_PLAN.md) for what each test covers.

---

## 1. First-time setup

```bash
# 1. Install JS dependencies for web + mobile + shared packages
npm install
```

```bash
# 2. Install PHP/Composer/PostgreSQL (Windows via scoop — skip if already installed)
scoop install php composer postgresql
```

```bash
# 3. Start PostgreSQL and create the database + role once
pg_ctl -D "$HOME/scoop/apps/postgresql/current/data" -l pg_log.txt start
psql -U postgres -d postgres -c "CREATE ROLE sail WITH LOGIN PASSWORD 'password' CREATEDB;"
psql -U postgres -d postgres -c "CREATE DATABASE hris OWNER sail;"
psql -U postgres -d postgres -c "CREATE DATABASE hris_testing OWNER sail;"
```

```bash
# 4. Backend: install PHP dependencies, configure, migrate, seed
cd apps/api
composer install
cp .env.example .env
# then edit .env: set DB_HOST=127.0.0.1 and APP_URL=http://localhost:8000
php artisan key:generate
php artisan migrate --seed
cd ../..
```

Seeded demo logins (all password `password`): `admin@example.com`, `hr.manager@example.com`, `people.manager@example.com`, `casey.nguyen@example.com`.

---

## 2. Running the app

Three separate terminals:

```bash
# Terminal 1 — backend (from apps/api)
php artisan serve --port=8000
```

```bash
# Terminal 2 — web app (from repo root)
npm run dev:web
# → http://localhost:5173
```

```bash
# Terminal 3 — mobile app (from repo root)
npm run dev:mobile
# → scan the QR code with Expo Go on your phone
```

Docker/Sail alternative for the backend, instead of Terminal 1:

```bash
cd apps/api
./vendor/bin/sail up -d
# serves on port 80 — point apps/web/vite.config.ts's proxy and
# apps/mobile/app.json's extra.apiBaseUrl at :80 instead of :8000
```

---

## 3. Resetting the database

```bash
cd apps/api
php artisan migrate:fresh --seed --force
```

Run this before a full regression pass, and after any migration or seeder change.

---

## 4. Testing

### Everything, one command

```bash
npm run test:regression
```

Runs Pest → Vitest → Playwright, in that order, stopping at the first failure. Run from the repo root. **Requires the backend already running** (`php artisan serve --port=8000` in a separate terminal) — Playwright auto-starts the web dev server itself, but not the backend.

### Each layer individually

```bash
npm run test:api      # Pest (backend) — equivalent to: composer test --working-dir=apps/api
npm run test          # Vitest, all workspaces (apps/web + apps/mobile)
npm run test:e2e      # Playwright — requires the backend running separately
```

Direct equivalents, run from inside each package:

```bash
cd apps/api && composer test          # same as npm run test:api
cd apps/api && php artisan test       # same thing, one level lower
```

```bash
cd apps/web && npm run test           # Vitest, web only
cd apps/web && npm run test:e2e       # Playwright, web only — same as npm run test:e2e from root
```

```bash
cd apps/mobile && npm run test        # Jest (currently --passWithNoTests, no mobile tests yet)
```

### A single Pest test file or test name

```bash
cd apps/api
php artisan test --filter=AtsPipelineTest
php artisan test --filter="rejects a requisition without a position"
```

### A single Playwright spec, or by test name

```bash
cd apps/web
npx playwright test time-off.spec.ts
npx playwright test -g "forgot password"
npx playwright test time-off.spec.ts --workers=1   # isolate from cross-test interference
```

### Debugging a failing Playwright test

```bash
npx playwright show-trace apps/web/test-results/<failed-test-dir>/trace.zip
```

### Type checking

```bash
npm run typecheck    # all workspaces, from repo root
```

---

## 5. Building for production

```bash
npm run build:web    # from repo root — builds apps/web
```

```bash
cd apps/web
npm run preview      # serve the production build locally
```
