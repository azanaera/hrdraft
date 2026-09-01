import { test, expect } from '@playwright/test';
import { DEMO_USERS, loginAs, logout } from './helpers';

async function submitQuickRequest(page: import('@playwright/test').Page) {
  await page.goto('/time-off');
  // The policy <select> starts with just its "Select…" placeholder option —
  // real policies load asynchronously after this navigation. On a slower
  // runner (CI) selecting index 1 before that fetch resolves either throws
  // (option doesn't exist yet) or silently no-ops the whole submission, so
  // the row count assertion after this helper returns stays flaky without
  // this wait. Wait for a second real option to actually exist first.
  const policySelect = page.locator('select').first();
  await expect(async () => {
    expect(await policySelect.locator('option').count()).toBeGreaterThan(1);
  }).toPass({ timeout: 10_000 });
  await policySelect.selectOption({ index: 1 });
  await page.getByRole('button', { name: 'Request time off' }).click();
}

test('employee submits a time off request and their manager approves it', async ({ page }) => {
  await loginAs(page, DEMO_USERS.employee);
  // loginAs lands on the dashboard, which has its own table.data-table
  // (recent hires) — navigate to /time-off first so `before` counts the
  // right table, not whatever happened to be on the dashboard.
  await page.goto('/time-off');

  const rows = page.locator('table.data-table tbody tr');
  const before = await rows.count();

  await submitQuickRequest(page);
  await expect(rows).toHaveCount(before + 1);
  await expect(rows.first().getByText('pending')).toBeVisible();

  // Wait for the logout redirect to actually land on /login before calling
  // loginAs() again — loginAs() itself checks "am I already logged in" by
  // looking for a visible Log-out button, and on a slower runner (CI) that
  // check can race the still-in-flight logout redirect, targeting a button
  // that's mid-navigation and gets detached from the DOM out from under it.
  await logout(page);

  await loginAs(page, DEMO_USERS.peopleManager);
  await page.goto('/time-off');
  await expect(page.getByRole('button', { name: 'Approve' }).first()).toBeVisible();
  await page.getByRole('button', { name: 'Approve' }).first().click();
  await expect(page.getByText('approved').first()).toBeVisible();
});

test('an employee never sees approve/deny controls on the time off list', async ({ page }) => {
  await loginAs(page, DEMO_USERS.employee);
  await submitQuickRequest(page);

  await expect(page.getByRole('button', { name: 'Approve' })).toHaveCount(0);
  await expect(page.getByRole('button', { name: 'Deny' })).toHaveCount(0);
});
