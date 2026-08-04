import { test, expect } from '@playwright/test';
import { DEMO_USERS, apiGet, loginAs, uniqueSuffix } from './helpers';

test('creates a location, department, position, and time off policy through the admin UI', async ({ page }) => {
  await loginAs(page, DEMO_USERS.admin);
  const suffix = uniqueSuffix();

  await page.goto('/admin');

  // Locations (default tab)
  await page.getByLabel('Name').fill(`Admin UI Site ${suffix}`);
  await page.getByLabel('Code').fill(`AUI-${suffix}`);
  await page.getByLabel('State').fill('CA');
  await page.getByLabel('Minimum wage').fill('16.50');
  await page.getByRole('button', { name: 'Add location' }).click();
  await expect(page.getByRole('cell', { name: `AUI-${suffix}` })).toBeVisible();

  // Departments
  await page.getByRole('button', { name: 'Departments' }).click();
  await page.getByLabel('Name').fill(`Admin UI Dept ${suffix}`);
  await page.getByLabel('Code').fill(`AUID-${suffix}`);
  await page.getByRole('button', { name: 'Add department' }).click();
  await expect(page.getByRole('cell', { name: `AUID-${suffix}` })).toBeVisible();

  // Positions — usable elsewhere means it appears in the department dropdown.
  await page.getByRole('button', { name: 'Positions' }).click();
  await page.getByLabel('Title').fill(`Admin UI Role ${suffix}`);
  await page.getByLabel('Department').selectOption({ label: `Admin UI Dept ${suffix}` });
  await page.getByRole('button', { name: 'Add position' }).click();
  await expect(page.getByRole('cell', { name: `Admin UI Role ${suffix}` })).toBeVisible();

  // Time off policy
  await page.getByRole('button', { name: 'Time Off Policies' }).click();
  await page.getByLabel('Name').fill(`Admin UI Leave ${suffix}`);
  await page.getByRole('button', { name: 'Add policy' }).click();
  await expect(page.getByRole('cell', { name: `Admin UI Leave ${suffix}` })).toBeVisible();

  // Confirm the new location is usable on the hire form (has a real, queryable ID).
  const response = await apiGet(page, '/api/v1/admin/locations');
  const locations = (await response.json()).data as Array<{ code: string }>;
  expect(locations.some((l) => l.code === `AUI-${suffix}`)).toBeTruthy();
});

test('blocks a non-admin role from reaching the admin section in the UI', async ({ page }) => {
  await loginAs(page, DEMO_USERS.employee);
  await expect(page.getByRole('link', { name: 'Admin' })).toHaveCount(0);
});
