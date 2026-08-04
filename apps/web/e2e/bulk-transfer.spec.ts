import { test, expect } from '@playwright/test';
import { DEMO_USERS, apiPost, createOrgUnit, loginAs, uniqueSuffix } from './helpers';

test('selects multiple employees and bulk-transfers them to a new org unit', async ({ page }) => {
  await loginAs(page, DEMO_USERS.admin);
  const suffix = uniqueSuffix();
  const orgA = await createOrgUnit(page, suffix + 'a');
  const orgB = await createOrgUnit(page, suffix + 'b');

  for (const n of [1, 2]) {
    await apiPost(page, '/api/v1/employees', {
      first_name: 'Bulk', last_name: `Test${n}-${suffix}`, employee_number: `E-BULK${n}-${suffix}`,
      hire_date: '2023-01-01', employment_type: 'hourly',
      department_id: orgA.departmentId, location_id: orgA.locationId, position_id: orgA.positionId,
      pay_type: 'hourly', rate_amount: 20, pay_frequency: 'biweekly',
    });
  }

  await page.goto('/employees');
  await page.getByPlaceholder('Search name or employee #').fill(`-${suffix}`);

  const checkboxes = page.locator('table.data-table tbody tr input[type="checkbox"]');
  await expect(checkboxes).toHaveCount(2);
  await checkboxes.nth(0).check();
  await checkboxes.nth(1).check();

  await expect(page.getByText('2 selected')).toBeVisible();
  await page.getByPlaceholder('Department ID').fill(String(orgB.departmentId));
  await page.getByPlaceholder('Location ID').fill(String(orgB.locationId));
  await page.getByPlaceholder('Position ID').fill(String(orgB.positionId));
  await page.locator('.bulk-action-bar input[type="date"]').fill(new Date().toISOString().slice(0, 10));
  await page.getByRole('button', { name: 'Bulk transfer' }).click();

  // The bulk-action bar clears once the transfer succeeds and the list reloads.
  await expect(page.locator('.bulk-action-bar')).toHaveCount(0, { timeout: 10_000 });

  await page.getByRole('link', { name: /Bulk Test1/ }).click();
  await expect(page.getByText(`Test Dept ${suffix}b`)).toBeVisible();
});
