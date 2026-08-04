import { test, expect } from '@playwright/test';
import { DEMO_USERS, apiPost, createOrgUnit, loginAs, uniqueSuffix } from './helpers';

test('applying a raise closes the old record, and a below-minimum-wage rate is rejected', async ({ page }) => {
  await loginAs(page, DEMO_USERS.admin);
  const suffix = uniqueSuffix();
  const org = await createOrgUnit(page, suffix); // seeded with minimum_wage: 7.25

  const hired = await apiPost<{ data: { id: number } }>(page, '/api/v1/employees', {
    first_name: 'Sam',
    last_name: `Ortiz-${suffix}`,
    employee_number: `E-COMP-${suffix}`,
    hire_date: '2023-01-01',
    employment_type: 'hourly',
    department_id: org.departmentId,
    location_id: org.locationId,
    position_id: org.positionId,
    pay_type: 'hourly',
    rate_amount: 15,
    pay_frequency: 'biweekly',
  });

  await page.goto(`/employees/${hired.data.id}`);
  await page.getByRole('button', { name: 'Compensation' }).click();

  // Apply a raise.
  await page.getByLabel('Rate amount').fill('18');
  await page.getByLabel('Reason').selectOption('raise');
  await page.getByRole('button', { name: 'Apply compensation change' }).click();

  await expect(page.getByRole('cell', { name: 'current' })).toBeVisible();
  const rows = page.locator('table.data-table tbody tr');
  await expect(rows).toHaveCount(2);

  // Attempt a rate below the location's minimum wage.
  await page.getByLabel('Rate amount').fill('5');
  await page.getByRole('button', { name: 'Apply compensation change' }).click();
  await expect(page.getByText(/minimum wage/i)).toBeVisible();
  await expect(rows).toHaveCount(2); // unchanged
});
