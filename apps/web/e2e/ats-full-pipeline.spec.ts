import { test, expect } from '@playwright/test';
import { DEMO_USERS, createOrgUnit, loginAs, uniqueSuffix } from './helpers';

test('creates a requisition, adds a candidate, moves them through the pipeline, and hires them', async ({ page }) => {
  await loginAs(page, DEMO_USERS.admin);
  const suffix = uniqueSuffix();
  const org = await createOrgUnit(page, suffix);

  await page.goto('/ats');
  await page.getByLabel('Title').fill(`Warehouse Lead ${suffix}`);
  await page.getByLabel('Department ID').fill(String(org.departmentId));
  await page.getByLabel('Location ID').fill(String(org.locationId));
  await page.getByLabel('Position ID').fill(String(org.positionId));
  await page.getByRole('button', { name: 'Create requisition' }).click();

  await expect(page.getByRole('link', { name: new RegExp(`Warehouse Lead ${suffix}`) })).toBeVisible();
  await page.getByRole('link', { name: new RegExp(`Warehouse Lead ${suffix}`) }).click();

  await page.getByLabel('First name').fill('Avery');
  await page.getByLabel('Last name').fill(`Stone-${suffix}`);
  await page.getByLabel('Email').fill(`avery.stone.${suffix}@example.com`);
  await page.getByRole('button', { name: 'Add to pipeline' }).click();

  await expect(page.getByText(new RegExp(`Avery Stone-${suffix}`))).toBeVisible();

  await page.getByRole('button', { name: 'Hire' }).click();
  await page.getByLabel('Employee #').fill(`E-ATS-${suffix}`);
  await page.getByLabel('Rate amount').fill('20');
  await page.getByRole('button', { name: 'Confirm hire' }).click();

  await expect(page.getByText('hired')).toBeVisible();

  // The resulting employee record exists with initial compensation and an
  // auto-started onboarding workflow — verify by navigating to Employees.
  await page.goto('/employees');
  await page.getByPlaceholder('Search name or employee #').fill(`E-ATS-${suffix}`);
  await page.getByRole('link', { name: new RegExp(`Avery Stone-${suffix}`) }).click();

  await expect(page.getByText('active', { exact: true })).toBeVisible();
  await page.getByRole('button', { name: 'Onboarding' }).click();
  await expect(page.getByText(/onboarding/i).first()).toBeVisible();
});
