import { test, expect } from '@playwright/test';
import { DEMO_USERS, apiPost, createOrgUnit, loginAs, uniqueSuffix } from './helpers';

test('dashboard reflects a new hire, and the turnover report reflects a termination', async ({ page }) => {
  await loginAs(page, DEMO_USERS.admin);

  await page.goto('/');
  await expect(page.getByText('Active employees')).toBeVisible();
  const headcountBefore = Number(await page.locator('.card', { hasText: 'Active employees' }).locator('h3').textContent());

  const suffix = uniqueSuffix();
  const org = await createOrgUnit(page, suffix);
  const hired = await apiPost<{ data: { id: number; person: { id: number } } }>(page, '/api/v1/employees', {
    first_name: 'Dash', last_name: `Board-${suffix}`, employee_number: `E-DASH-${suffix}`,
    hire_date: new Date().toISOString().slice(0, 10), employment_type: 'hourly',
    department_id: org.departmentId, location_id: org.locationId, position_id: org.positionId,
    pay_type: 'hourly', rate_amount: 20, pay_frequency: 'biweekly',
  });

  await page.reload();
  const headcountAfter = Number(await page.locator('.card', { hasText: 'Active employees' }).locator('h3').textContent());
  expect(headcountAfter).toBe(headcountBefore + 1);
  await expect(page.getByRole('link', { name: new RegExp(`Dash Board-${suffix}`) })).toBeVisible();

  // Terminate them and check the turnover report picks it up.
  await apiPost(page, `/api/v1/employees/${hired.data.id}/terminate`, {
    termination_date: new Date().toISOString().slice(0, 10),
    reason: `E2E dashboard test ${suffix}`,
  });

  await page.goto('/reports/turnover');
  await expect(page.getByText('Terminations in period')).toBeVisible();
  await expect(page.getByText(`E2E dashboard test ${suffix}`)).toBeVisible();
});
