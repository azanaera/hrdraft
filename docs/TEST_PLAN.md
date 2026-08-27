# HRIS — Test Plan & Regression Process

How this system is tested, how to run the tests, and the process to follow on every future change. See [README.md](../README.md) for local setup and [SPEC.md](SPEC.md) for product scope.

---

## 1. Testing layers

Three layers, each catching a different class of bug:

| Layer | Tool | Location | What it catches |
|---|---|---|---|
| Backend feature tests | Pest 3 | `apps/api/tests/Feature/` | Business rules, authorization, validation, database constraints — one HTTP request/response or service call at a time |
| Frontend component tests | Vitest + Testing Library | `apps/web/src/**/*.test.tsx` | Individual React component rendering/behavior in isolation |
| End-to-end tests | Playwright | `apps/web/e2e/*.spec.ts` | Full user journeys through the real browser UI against the real backend — the only layer that catches integration bugs between frontend and backend (wrong field names, missing routes, race conditions, wrong assumptions about what the other side sends) |

The E2E layer has caught real bugs the other two layers missed, because it's the only one that exercises the actual UI → HTTP → Eloquent → Postgres path end to end. Concrete examples from this project: a model whose table name didn't match Eloquent's pluralization guess (500 on candidate creation), a NOT NULL database constraint reachable through a form that didn't collect the required field (500 on hiring from a requisition without a position), and out-of-order API responses clobbering newer UI state when a user acted quickly (stale data flashing in the employee list, time off list, and requisition detail page). None of these were visible from unit/feature tests alone, since each test used tidy, deliberately-ordered inputs.

## 2. Running the tests

**Prerequisites**: PostgreSQL running, `apps/api/.env` configured, dependencies installed (`composer install` in `apps/api`, `npm install` at the repo root). See [README.md](../README.md) for first-time setup.

### Everything, one command

```bash
npm run test:regression
```

Runs, in order: Pest (backend) → Vitest (frontend components) → Playwright (E2E). Stops at the first failing layer. This is a root-level `package.json` script — run it from the repo root.

Playwright's `webServer` config auto-starts `npm run dev` for the web app if it isn't already running, but **it does not start the backend**. Before running `test:regression` (or `test:e2e` alone), make sure `php artisan serve --port=8000` is running against a database you're OK with Playwright writing test data into — the E2E specs create real rows (with randomized suffixes, via `uniqueSuffix()` in `e2e/helpers.ts`) rather than relying on fragile seeded IDs, so they're safe to run repeatedly against the same database without conflicts, but not against production data.

### Individual layers

```bash
npm run test:api      # Pest — from repo root, or `composer test` from apps/api
npm run test          # Vitest across all workspaces (apps/web + apps/mobile)
npm run test:e2e      # Playwright — requires the backend running separately
```

To run a single Playwright spec or grep by test name:

```bash
cd apps/web
npx playwright test time-off.spec.ts
npx playwright test -g "forgot password"
```

To debug a failing Playwright test, use `trace: 'retain-on-failure'` (already configured) — a trace is saved to `test-results/` on failure:

```bash
npx playwright show-trace apps/web/test-results/<failed-test-dir>/trace.zip
```

### Fresh local deployment (full reset)

```bash
cd apps/api
php artisan migrate:fresh --seed --force
php artisan serve --port=8000
```

Then in another terminal, from the repo root: `npm run test:regression`.

## 3. What each Playwright scenario covers

One journey per file, `apps/web/e2e/`:

| File | Covers |
|---|---|
| `auth.spec.ts` | Login/logout for all 4 seeded roles, invalid-credentials error, forgot/reset-password flow reaching its confirmation screen, rate-limit lockout after 5 failed attempts |
| `hire-and-transfer.spec.ts` | Hiring an employee through the UI, verifying they appear in the list, transferring them to a new department/location/position, and that assignment history is preserved |
| `rehire.spec.ts` | Terminating an employee (access revoked), rehiring the same person, and verifying a new `employments` row exists while the old one's history is preserved |
| `compensation.spec.ts` | Applying a raise (old compensation record closes), and rejecting a rate below the assignment location's minimum wage |
| `time-off.spec.ts` | An employee submitting a request, their manager approving it; a non-manager employee never sees approve/deny controls |
| `documents-and-signature.spec.ts` | Uploading a document that requires signature, signing it through the fake e-signature provider, and the resulting status |
| `onboarding-and-background-check.spec.ts` | Onboarding auto-starting on hire, the fake background-check/E-Verify providers resolving, and completing tasks finishing the workflow |
| `offboarding.spec.ts` | Terminating starts an offboarding workflow, completing its tasks finishes it, and a final payout figure appears on the timeline |
| `ats-full-pipeline.spec.ts` | Creating a requisition, adding a candidate, moving them through the pipeline, hiring them, and verifying the resulting employee record, compensation, and auto-started onboarding all exist |
| `bulk-transfer.spec.ts` | Selecting multiple employees and bulk-transferring them to a new org unit in one action |
| `admin-crud.spec.ts` | Creating a location/department/position/time-off policy through the admin UI; a non-admin role is blocked from reaching the admin section |
| `data-import.spec.ts` | Uploading a CSV with one clean row and one broken row — the preview flags the bad row, and committing only creates the good one |
| `dashboard-and-reports.spec.ts` | Dashboard numbers reflecting a new hire; the turnover report reflecting a termination |
| `role-boundaries.spec.ts` | An employee-role user can't see admin/reports/onboarding/hiring nav links, is blocked by the API on direct navigation to a back-office page, only sees their own record in the employee list, and gets a 403 hitting another employee's record directly via the API |

## 4. What each Pest suite covers

`apps/api/tests/Feature/`, one file per capability. Each covers the happy path plus the key business-rule edge case and/or a role-boundary check:

- `AuthTest.php` — login/logout, including the logout token/session bug regressions
- `EmployeeCoreTest.php` — rehire creates a new employment row (never mutates the old one), refuses rehiring someone already active, transfer preserves assignment history + logs a timeline event, role-based view boundaries
- `CompensationTest.php` — closing the prior record on a new one, rejecting overlapping date ranges at the database level, timeline visibility
- `TimeOffTest.php` — self-service submission, blocking requests on someone else's behalf, manager-only approval scoped to direct reports, ledger balance deduction
- `TerminationTest.php` — token/session revocation, the `active.employment` middleware blocking an already-issued token, offboarding auto-start
- `OffboardingTest.php` — workflow completion, final payout calculation from unused time-off balance
- `WageComplianceTest.php` — rejecting below-minimum-wage hourly rates (service level and HTTP 422), allowing at-or-above, skipping the check for salaried pay
- `BankingInfoTest.php` — tokenization (raw account number never persisted), employee can view their own info without back-office access
- `SignatureTest.php` — acknowledgment routes through the signature provider only when a document requires it
- `BackgroundCheckTest.php` — both background-check and E-Verify auto-run when onboarding starts
- `BulkTransferTest.php` — multi-employee transfer in one call, role boundary
- `DataImportTest.php` — preview flags row-level errors without writing, commit only writes valid rows
- `DashboardTest.php` — accurate headcount/open-requisitions/pending-time-off counts
- `TurnoverReportTest.php` — termination reflected in the summary, role boundary
- `AdminCrudTest.php` — admin CRUD endpoints
- `PasswordResetTest.php` — same response regardless of whether the email is registered (no user enumeration), valid-token reset revokes existing tokens, invalid token rejected
- `RateLimitTest.php` — login lockout after 5 failed attempts/minute, general authenticated-API throttle (300 req/min per user)
- `TimeOffAccrualTest.php` — first accrual posts once a period has elapsed since hire, no double-accrual before the next period, accrual respects the policy's max-balance cap, `accrual_method: none` never accrues, terminated employments are skipped, `--dry-run` reports without writing
- `AtsPipelineTest.php` — candidate creation seeds an initial stage-history row, moving stages closes the old row and opens a new one, a requisition without a position is rejected at validation (not a 500 at hire time), hiring uses the requisition's position for the new assignment

## 5. What isn't automated, and why

- **Actual email delivery** — the app uses Laravel's `log` mail driver locally (see `storage/logs/laravel.log`), so "an email was attempted with the right content" is covered by Pest assertions on the mailable, but nothing here proves a real SMTP send works. Out of scope until a real mail provider is configured (see SPEC.md open items).
- **Real third-party integrations** (e-signature, background check, banking) — all three are tested against their `Fake*Provider` implementations, which is the intended design (SPEC.md's "build real workflow + swappable mock provider" decision). Swapping in a real SDK later needs its own contract test against that SDK's sandbox, not covered here.
- **Payroll gross-to-net calculation** — explicitly deferred per SPEC.md §1/§2.1; nothing to test yet.
- **Mobile app (React Native/Expo)** — has its own Jest test runner wired into `test:regression` (currently `--passWithNoTests`, since no mobile tests exist yet), but no E2E coverage. Add mobile-specific tests when the mobile app has features to test.
- **Cross-browser/mobile-viewport rendering** — Playwright is configured for Chromium desktop only. Add more `projects` entries in `apps/web/playwright.config.ts` if/when that matters.

## 6. Process for future changes

1. **Before starting work**: run `npm run test:regression` to confirm you're starting from a green baseline. If it's already red, fix or note that first — don't build on top of an unknown failure.
2. **While building**: add a Pest test for every new backend business rule or bug fix (happy path + the specific edge case that motivated the change), and a Playwright scenario for every new user-facing journey (or extend an existing spec file if the journey is a variant of one already covered). Prefer extending an existing spec over adding a new file when the new behavior is a step within an existing journey (e.g. a new field on an existing form) rather than a new journey.
3. **Before considering it done**: run `npm run test:regression` again. All three layers must pass.
4. **If a test fails**: reproduce it deliberately before touching anything — for a Playwright failure, run that one spec file with `--workers=1` in isolation first to rule out cross-test interference, then attach network/console listeners (`page.on('response', ...)`, `page.on('console', ...)`) if the failure reason isn't obvious from the error alone, and check `apps/api/storage/logs/laravel.log` for the actual backend exception. Fix the root cause, not the symptom — if a test is flaky because of a race condition in the app code, fix the app code, not just the test's timing.
5. **Every schema or validation change**: re-run `php artisan migrate:fresh --seed --force` locally before the next full regression run, since Pest's `RefreshDatabase` trait only wraps individual test runs in transactions — it doesn't catch a migration that's broken relative to the actual seeded demo data the way a fresh migrate+seed does.
