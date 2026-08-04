import { defineConfig, devices } from '@playwright/test';

/**
 * Assumes the backend (php artisan serve --port=8000) is already running
 * against a freshly migrated + seeded database — see docs/TEST_PLAN.md.
 * Playwright only starts the Vite dev server itself; it does not manage
 * the PHP/Postgres stack, since that's native (not containerized) here.
 */
export default defineConfig({
  testDir: './e2e',
  fullyParallel: false,
  workers: 1,
  retries: 0,
  reporter: [['list']],
  timeout: 30_000,
  use: {
    baseURL: 'http://localhost:5173',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
  webServer: {
    command: 'npm run dev',
    url: 'http://localhost:5173',
    reuseExistingServer: true,
    timeout: 60_000,
  },
});
