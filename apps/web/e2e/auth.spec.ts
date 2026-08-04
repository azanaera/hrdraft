import { test, expect } from '@playwright/test';
import { DEMO_PASSWORD, DEMO_USERS, loginAs, logout } from './helpers';

test.describe('Authentication', () => {
  for (const [role, email] of Object.entries(DEMO_USERS)) {
    test(`${role} can log in and out`, async ({ page }) => {
      await loginAs(page, email);
      await expect(page).toHaveURL('/');
      await logout(page);
    });
  }

  test('shows an error on invalid credentials', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email').fill(DEMO_USERS.admin);
    await page.getByLabel('Password').fill('wrong-password');
    await page.getByRole('button', { name: 'Sign in' }).click();
    await expect(page.getByText(/invalid credentials/i)).toBeVisible();
  });

  test('forgot password flow reaches the confirmation screen', async ({ page }) => {
    await page.goto('/login');
    await page.getByRole('link', { name: /forgot password/i }).click();
    await expect(page).toHaveURL(/\/forgot-password/);

    await page.getByLabel('Email').fill(DEMO_USERS.admin);
    await page.getByRole('button', { name: /send reset link/i }).click();

    await expect(page.getByText(/reset link has been sent/i)).toBeVisible();
  });

  test('locks out login after repeated failed attempts', async ({ page }) => {
    await page.goto('/login');
    for (let i = 0; i < 5; i++) {
      await page.getByLabel('Email').fill(DEMO_USERS.hrManager);
      await page.getByLabel('Password').fill('wrong-password');
      await page.getByRole('button', { name: 'Sign in' }).click();
      await expect(page.getByText(/invalid credentials/i)).toBeVisible();
    }

    await page.getByLabel('Email').fill(DEMO_USERS.hrManager);
    await page.getByLabel('Password').fill(DEMO_PASSWORD);
    await page.getByRole('button', { name: 'Sign in' }).click();

    // Even the correct password is now rate-limited for a minute.
    await expect(page.getByText(/too many login attempts/i)).toBeVisible();
  });
});
