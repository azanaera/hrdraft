import { test, expect } from '@playwright/test';
import { DEMO_USERS, apiGet, createOrgUnit, loginAs, uniqueSuffix } from './helpers';

test('previews a CSV import, flags a broken row, and commits only the valid one', async ({ page }) => {
  await loginAs(page, DEMO_USERS.admin);
  const suffix = uniqueSuffix();
  const org = await createOrgUnit(page, suffix);

  const response = await apiGet(page, '/api/v1/admin/departments');
  const departments = (await response.json()).data as Array<{ id: number; code: string }>;
  const departmentCode = departments.find((d) => d.id === org.departmentId)!.code;

  const locResponse = await apiGet(page, '/api/v1/admin/locations');
  const locations = (await locResponse.json()).data as Array<{ id: number; code: string }>;
  const locationCode = locations.find((l) => l.id === org.locationId)!.code;

  const csv = [
    'first_name,last_name,personal_email,employee_number,hire_date,employment_type,department_code,location_code,position_title,pay_type,rate_amount,pay_frequency',
    `Import,Good-${suffix},import.${suffix}@example.com,E-IMPORT-${suffix},2024-01-01,hourly,${departmentCode},${locationCode},Test Role ${suffix},hourly,20,biweekly`,
    'Import,Bad,,,,,ZZZ,ZZZ,Nonexistent,cash,abc,weekly',
  ].join('\n');

  await page.goto('/admin/import');
  await page.setInputFiles('input[type="file"]', {
    name: 'import.csv',
    mimeType: 'text/csv',
    buffer: Buffer.from(csv),
  });

  await expect(page.getByText('1 valid, 1 with errors.')).toBeVisible();
  await expect(page.getByText('valid', { exact: true })).toBeVisible();

  // The UI only ever submits the pre-filtered valid rows — the broken row
  // never gets sent, so it shows as 0 failed here (server-side rejection of
  // a bad row that slips through is covered separately in
  // apps/api/tests/Feature/DataImportTest.php).
  await page.getByRole('button', { name: /Import 1 valid row/ }).click();
  await expect(page.getByText('1 employee(s) created. 0 failed.')).toBeVisible();

  await page.goto('/employees');
  await page.getByPlaceholder('Search name or employee #').fill(`E-IMPORT-${suffix}`);
  await expect(page.getByText(new RegExp(`Good-${suffix}`))).toBeVisible();
});
