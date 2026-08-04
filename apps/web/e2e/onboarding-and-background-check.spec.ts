import { test, expect } from '@playwright/test';
import { DEMO_USERS, apiPost, checkAllCheckboxes, createOrgUnit, loginAs, uniqueSuffix } from './helpers';

test('onboarding auto-starts on hire, background checks resolve, and completing tasks finishes the workflow', async ({ page }) => {
  await loginAs(page, DEMO_USERS.admin);
  const suffix = uniqueSuffix();
  const org = await createOrgUnit(page, suffix);

  const hired = await apiPost<{ data: { id: number } }>(page, '/api/v1/employees', {
    first_name: 'Jordan', last_name: `Kim-${suffix}`, employee_number: `E-ONB-${suffix}`,
    hire_date: new Date().toISOString().slice(0, 10), employment_type: 'hourly',
    department_id: org.departmentId, location_id: org.locationId, position_id: org.positionId,
    pay_type: 'hourly', rate_amount: 20, pay_frequency: 'biweekly',
  });

  await page.goto(`/employees/${hired.data.id}`);
  await page.getByRole('button', { name: 'Onboarding' }).click();

  await expect(page.getByText('Standard Hourly Onboarding')).toBeVisible();
  await expect(page.getByText(/e-verify/i)).toBeVisible();

  const checkBadges = page.locator('.card', { hasText: 'Background & eligibility checks' }).locator('.badge');
  await expect(checkBadges).toHaveCount(2);
  for (const badge of await checkBadges.all()) {
    await expect(badge).toHaveText('clear');
  }

  await checkAllCheckboxes(page);

  await expect(page.getByText('completed')).toBeVisible();
});
