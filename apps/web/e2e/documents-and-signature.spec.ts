import { test, expect } from '@playwright/test';
import { DEMO_USERS, apiPost, createOrgUnit, loginAs, uniqueSuffix } from './helpers';

test('uploads a document requiring signature and signs it via the fake e-signature provider', async ({ page }) => {
  await loginAs(page, DEMO_USERS.admin);
  const suffix = uniqueSuffix();
  const org = await createOrgUnit(page, suffix);

  const hired = await apiPost<{ data: { id: number } }>(page, '/api/v1/employees', {
    first_name: 'Morgan', last_name: `Diaz-${suffix}`, employee_number: `E-DOC-${suffix}`,
    hire_date: '2023-01-01', employment_type: 'hourly',
    department_id: org.departmentId, location_id: org.locationId, position_id: org.positionId,
    pay_type: 'hourly', rate_amount: 20, pay_frequency: 'biweekly',
  });

  await page.goto(`/employees/${hired.data.id}`);
  await page.getByRole('button', { name: 'Documents' }).click();

  await page.locator('select').selectOption({ label: 'Handbook Acknowledgment' });
  await page.getByPlaceholder('e.g. Signed I-9').fill('Employee Handbook');
  await page.setInputFiles('input[type="file"]', {
    name: 'handbook.pdf',
    mimeType: 'application/pdf',
    buffer: Buffer.from('%PDF-1.4 fake handbook content'),
  });

  await expect(page.getByText('Employee Handbook')).toBeVisible();
  await expect(page.getByRole('button', { name: 'Sign' })).toBeVisible();
  await page.getByRole('button', { name: 'Sign' }).click();

  await expect(page.getByText('signed')).toBeVisible();
});
