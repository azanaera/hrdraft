import { test, expect } from '@playwright/test';
import { DEMO_USERS, apiPost, checkAllCheckboxes, createOrgUnit, loginAs, uniqueSuffix } from './helpers';

test('terminating starts offboarding, tasks complete it, and the final payout appears on the timeline', async ({ page }) => {
  await loginAs(page, DEMO_USERS.admin);
  const suffix = uniqueSuffix();
  const org = await createOrgUnit(page, suffix);

  const hired = await apiPost<{ data: { id: number } }>(page, '/api/v1/employees', {
    first_name: 'Casey', last_name: `Offboard-${suffix}`, employee_number: `E-OFF-${suffix}`,
    hire_date: '2023-01-01', employment_type: 'hourly',
    department_id: org.departmentId, location_id: org.locationId, position_id: org.positionId,
    pay_type: 'hourly', rate_amount: 20, pay_frequency: 'biweekly',
  });

  await page.goto(`/employees/${hired.data.id}`);
  // TerminateForm's window.confirm() fires only on the actual submit click below.
  await page.getByRole('button', { name: 'Terminate employee' }).click();
  await page.getByLabel('Termination date').fill(new Date().toISOString().slice(0, 10));
  await page.getByLabel('Reason').fill('Resignation');
  page.once('dialog', (dialog) => dialog.accept());
  await page.getByRole('button', { name: 'Confirm termination' }).click();

  await page.getByRole('button', { name: 'Offboarding' }).click();
  await expect(page.getByText('Standard Offboarding')).toBeVisible();

  await checkAllCheckboxes(page);
  await expect(page.getByText('completed')).toBeVisible();

  await page.getByRole('button', { name: 'Timeline' }).click();
  await expect(page.getByText(/final payout calculated/i).first()).toBeVisible();
});
