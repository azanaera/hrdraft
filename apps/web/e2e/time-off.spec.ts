import { test, expect } from '@playwright/test';
import { DEMO_USERS, loginAs } from './helpers';

async function submitQuickRequest(page: import('@playwright/test').Page) {
  await page.goto('/time-off');
  await page.locator('select').first().selectOption({ index: 1 });
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

  await page.getByRole('button', { name: 'Log out' }).click();

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
