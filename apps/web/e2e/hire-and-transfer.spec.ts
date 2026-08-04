import { test, expect } from '@playwright/test';
import { DEMO_USERS, createOrgUnit, loginAs, uniqueSuffix } from './helpers';

test('hires an employee end-to-end and then transfers them', async ({ page }) => {
  await loginAs(page, DEMO_USERS.admin);
  const suffix = uniqueSuffix();
  const org = await createOrgUnit(page, suffix);

  await page.goto('/employees/new');
  await page.getByLabel('First name').fill('Taylor');
  await page.getByLabel('Last name').fill(`Reed-${suffix}`);
  await page.getByLabel('Employee #').fill(`E-HIRE-${suffix}`);
  await page.getByLabel('Department ID').fill(String(org.departmentId));
  await page.getByLabel('Location ID').fill(String(org.locationId));
  await page.getByLabel('Position ID').fill(String(org.positionId));
  await page.getByLabel('Rate amount').fill('20');
  await page.getByRole('button', { name: 'Hire employee' }).click();

  // Redirects to the new employee's detail page.
  await expect(page).toHaveURL(/\/employees\/\d+$/);
  await expect(page.getByRole('heading', { name: new RegExp(`Taylor Reed-${suffix}`) })).toBeVisible();

  // Shows up in the searchable employee list.
  await page.goto('/employees');
  await page.getByPlaceholder('Search name or employee #').fill(`E-HIRE-${suffix}`);
  await expect(page.getByRole('link', { name: new RegExp(`Taylor Reed-${suffix}`) })).toBeVisible();

  // Transfer to a second org unit.
  await page.getByRole('link', { name: new RegExp(`Taylor Reed-${suffix}`) }).click();
  const org2 = await createOrgUnit(page, suffix + 'b');
  await page.getByRole('button', { name: 'Transfer employee' }).click();
  await page.getByLabel('New department ID').fill(String(org2.departmentId));
  await page.getByLabel('New location ID').fill(String(org2.locationId));
  await page.getByLabel('New position ID').fill(String(org2.positionId));
  await page.getByRole('button', { name: 'Confirm transfer' }).click();

  await expect(page.getByText(`Test Dept ${suffix}b`)).toBeVisible();

  // Timeline records both the hire and the transfer.
  await page.getByRole('button', { name: 'Timeline' }).click();
  await expect(page.getByText('transferred', { exact: true })).toBeVisible();
});
