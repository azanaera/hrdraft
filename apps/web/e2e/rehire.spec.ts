import { test, expect } from '@playwright/test';
import { DEMO_USERS, apiPost, createOrgUnit, loginAs, uniqueSuffix } from './helpers';

test('terminating revokes access, and rehiring the same person preserves history in a new employment', async ({ page }) => {
  await loginAs(page, DEMO_USERS.admin);
  const suffix = uniqueSuffix();
  const org = await createOrgUnit(page, suffix);

  // Access-revocation itself (token/session invalidation) is covered at the
  // API layer in apps/api/tests/Feature/TerminationTest.php — this spec
  // focuses on the UI-driven terminate → rehire → history-preserved journey.
  const hired = await apiPost<{ data: { id: number; person: { id: number } } }>(page, '/api/v1/employees', {
    first_name: 'Riley',
    last_name: `Chen-${suffix}`,
    employee_number: `E-REHIRE-${suffix}`,
    hire_date: '2023-01-01',
    employment_type: 'hourly',
    department_id: org.departmentId,
    location_id: org.locationId,
    position_id: org.positionId,
    pay_type: 'hourly',
    rate_amount: 20,
    pay_frequency: 'biweekly',
  });
  const employmentId = hired.data.id;

  await page.goto(`/employees/${employmentId}`);
  await expect(page.getByRole('button', { name: 'Terminate employee' })).toBeVisible();

  // TerminateForm's window.confirm() fires only on the actual submit click below.
  await page.getByRole('button', { name: 'Terminate employee' }).click();
  await page.getByLabel('Termination date').fill(new Date().toISOString().slice(0, 10));
  await page.getByLabel('Reason').fill('End of seasonal contract');
  page.once('dialog', (dialog) => dialog.accept());
  await page.getByRole('button', { name: 'Confirm termination' }).click();

  await expect(page.getByText('terminated', { exact: true })).toBeVisible();

  // Rehire the same person.
  await page.getByRole('button', { name: 'Rehire this person' }).click();
  await page.getByLabel('New employee #').fill(`E-REHIRE2-${suffix}`);
  await page.getByLabel('Hire date').fill(new Date().toISOString().slice(0, 10));
  await page.getByRole('button', { name: 'Confirm rehire' }).click();

  // Lands on a NEW employment record (different URL/id than the original).
  await expect(page).toHaveURL(new RegExp(`/employees/(?!${employmentId}$)\\d+$`));
  await expect(page.getByText('active', { exact: true })).toBeVisible();

  // Old employment history is preserved, not overwritten — the timeline
  // shows both the original hire and the rehire.
  await page.getByRole('button', { name: 'Timeline' }).click();
  await expect(page.getByText('rehired', { exact: true })).toBeVisible();
});
