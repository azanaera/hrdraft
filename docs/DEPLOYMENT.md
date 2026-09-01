# HRIS — Deployment Runbook

The concrete "when you're ready" checklist for the pilot deployment scoped in [PILOT_READINESS.md](PILOT_READINESS.md). Everything here is prepared and committed; nothing has been deployed yet, and nothing in this doc requires me to hold any account or credential.

**Not yet build-tested**: Docker isn't available in the environment this was written in, so the Dockerfile below is carefully reasoned from well-established patterns (multi-stage build, official `php:8.2-fpm` base, standard nginx+php-fpm coexistence) but hasn't actually been built and run. Build it locally and smoke-test it (`docker build . -t hris && docker run -p 8080:8080 hris`) before trusting it in production.

## What's already built

- **`Dockerfile`** (repo root) — single image, two stages: builds the React app, then serves it via nginx + php-fpm from one origin (same topology as Vite's local dev proxy — `/api` and `/sanctum` go to Laravel, everything else is the SPA). No CORS complexity for Sanctum's cookie auth as a result.
- **`docker/nginx.conf`**, **`docker/entrypoint.sh`** — supporting config. The entrypoint runs migrations + cache-warming on every boot (safe, idempotent), then either starts the web server or runs a passed-in command (used by the cron job below).
- **`render.yaml`** — a Render Blueprint: the web service, a managed Postgres database, and a daily cron job for time-off accrual, all from one file. Values that can't be known ahead of time (real domain, AWS/Sentry credentials) are marked `sync: false` — Render prompts for those on first deploy or lets you set them after.
- **`apps/api/app/Console/Commands/CreateAdminCommand.php`** (`hris:create-admin`) — creates one real admin account with a real password. The entrypoint deliberately never runs `DemoDataSeeder` (its "System Admin" account has the password `password` — fine for local dev, never acceptable somewhere real employees log in).

## First deploy — Render path

1. **Push this repo to GitHub** (you said you'd handle this).
2. In the Render dashboard: **New → Blueprint**, point it at the repo. Render reads `render.yaml` and provisions the `hris-db` Postgres instance, the `hris` web service, and the `hris-accrue-time-off` cron job together.
3. Render will prompt for the `sync: false` values during setup, or you can fill them in under each service's Environment tab afterward:
   - `APP_URL`, `FRONTEND_URL`, `SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN` — all four should be the same value: the Render-assigned URL for the `hris` service (e.g. `hris-xyz.onrender.com`) once you see it, or your own custom domain if you attach one. `SANCTUM_STATEFUL_DOMAINS`/`SESSION_DOMAIN` want just the host (no `https://`); `APP_URL`/`FRONTEND_URL` want the full URL with scheme.
   - `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET` — from the S3 bucket you create (see below).
   - `SENTRY_LARAVEL_DSN` — from the Sentry project you create (optional; the app runs fine without it).
4. First deploy runs, migrations apply automatically (via `docker/entrypoint.sh`).
5. **Create the real admin account** — from Render's shell for the `hris` service:
   ```bash
   php artisan hris:create-admin
   ```
   Follow the prompts (name, email, password). This is the only account that should exist until real HR/admin staff are added through the app itself.
6. **Backups**: Render's managed Postgres includes automatic daily backups on paid plans — confirm the plan you're on includes this, and do one **restore drill** (restore to a throwaway database, verify the data's actually there) before treating it as a real safety net. This satisfies Q6 from the pilot-readiness review (nightly backup, not continuous PITR).
7. **S3 setup** (for document storage — Q10): create an S3 bucket, an IAM user scoped to just that bucket (`s3:GetObject`, `s3:PutObject`, `s3:DeleteObject`), and drop the credentials into the env vars above. `FILESYSTEM_DISK=s3` is already set in `render.yaml`.
8. **Sentry setup** (optional, Q12): create a Sentry project (Laravel platform), copy its DSN into `SENTRY_LARAVEL_DSN`. No code change needed — `apps/api/config/sentry.php` already reads it.

## Alternative: Fly.io

The same `Dockerfile` works unchanged — `fly launch` in the repo root will detect it and generate a `fly.toml`. You'd still need `fly postgres create` for a managed database and `fly.toml`'s `[[statics]]`/routing config to reproduce the same "serve the SPA + proxy /api" behavior nginx.conf handles here (or just keep this Dockerfile's nginx layer, which already does it). A Fly.io-specific cron job would use [Fly Machines' scheduled runs](https://fly.io/docs/machines/) rather than Render's built-in `type: cron` service. Not built out in detail here since Render was the concrete recommendation — ask if you'd rather commit to this path instead and I'll build the equivalent `fly.toml`.

## After first deploy — verify

1. Visit the app URL, confirm the login page loads (not a 404 or the API's JSON).
2. Log in with the account created in step 5.
3. Walk the same manual smoke test used during development: create a requisition → hire a candidate → confirm onboarding auto-starts → check the timeline.
4. Confirm the cron job ran (`hris-accrue-time-off` in Render's dashboard shows its last run) — or trigger it manually once via the shell: `php artisan time-off:accrue --dry-run` to check it sees the right policies without writing anything yet.
5. Point `npm run test:regression`'s Playwright layer at the deployed URL as a one-off sanity check if you want extra confidence (`PLAYWRIGHT_BASE_URL` isn't wired up yet — ask if you want that added; today the suite assumes `localhost:5173`).
