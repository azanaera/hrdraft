import { test, expect } from '@playwright/test';
import { DEMO_USERS, apiGet, apiPost, createOrgUnit, loginAs, uniqueSuffix } from './helpers';

test('an employee cannot see Admin/Reports/Onboarding/Hiring nav links', async ({ page }) => {
  await loginAs(page, DEMO_USERS.employee);

  for (const label of ['Admin', 'Reports', 'Onboarding', 'Hiring (ATS)']) {
    await expect(page.getByRole('link', { name: label })).toHaveCount(0);
  }
});

test('an employee is blocked by the API even if they navigate directly to a back-office page', async ({ page }) => {
  await loginAs(page, DEMO_USERS.employee);

  // Direct navigation bypasses the nav-link gating — the page should still
  // fail closed via the API's role checks (403s), not silently leak data.
  await page.goto('/admin');
  const response = await apiGet(page, '/api/v1/admin/locations');
  expect(response.status()).toBe(403);
});

test('an employee only sees their own record in the employee list', async ({ page }) => {
  await loginAs(page, DEMO_USERS.employee);
  await page.goto('/employees');

  const rows = page.locator('table.data-table tbody tr');
  await expect(rows).toHaveCount(1);
  await expect(page.locator('table.data-table').getByRole('link', { name: 'Casey Nguyen' })).toBeVisible();
});

test("an employee viewing another employee's record directly via the API is forbidden", async ({ page }) => {
  await loginAs(page, DEMO_USERS.admin);
  const suffix = uniqueSuffix();
  const org = await createOrgUnit(page, suffix);
  const created = await apiPost<{ data: { id: number } }>(page, '/api/v1/employees', {
    first_name: 'Other', last_name: `Person-${suffix}`, employee_number: `E-RB-${suffix}`,
    hire_date: '2023-01-01', employment_type: 'hourly',
    department_id: org.departmentId, location_id: org.locationId, position_id: org.positionId,
    pay_type: 'hourly', rate_amount: 20, pay_frequency: 'biweekly',
  });

  await loginAs(page, DEMO_USERS.employee);
  const response = await apiGet(page, `/api/v1/employees/${created.data.id}`);
  expect(response.status()).toBe(403);
});
