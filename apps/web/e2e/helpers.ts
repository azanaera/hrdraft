import { expect, Page } from '@playwright/test';

export const DEMO_USERS = {
  admin: 'admin@example.com',
  hrManager: 'hr.manager@example.com',
  peopleManager: 'people.manager@example.com',
  employee: 'casey.nguyen@example.com',
} as const;

export const DEMO_PASSWORD = 'password';

export async function loginAs(page: Page, email: string, password = DEMO_PASSWORD) {
  // LoginPage redirects an already-authenticated session straight back to
  // "/" before the form ever renders (client-side, after goto() resolves —
  // so checking page.url() right after goto('/login') races the redirect).
  // Checking for the account-menu toggle (always visible once logged in —
  // unlike "Log out", which lives inside its Bootstrap dropdown and is
  // display:none until that toggle is opened) on whatever page we're
  // already on, before navigating, avoids that race entirely.
  const alreadyLoggedIn = await page
    .getByRole('button', { name: 'Account menu' })
    .isVisible()
    .catch(() => false);

  if (alreadyLoggedIn) {
    await logout(page);
  } else {
    await page.goto('/login');
  }

  await page.getByLabel('Email').fill(email);
  await page.getByLabel('Password').fill(password);
  await page.getByRole('button', { name: 'Sign in' }).click();
  await expect(page.getByRole('button', { name: 'Account menu' })).toBeVisible({ timeout: 10_000 });
}

export async function logout(page: Page) {
  await page.getByRole('button', { name: 'Account menu' }).click();
  await page.getByRole('button', { name: 'Log out' }).click();
  await page.waitForURL(/\/login/);
}

/** A short random suffix so repeated test runs against the same seeded DB don't collide on unique fields. */
export function uniqueSuffix(): string {
  return Date.now().toString(36) + Math.random().toString(36).slice(2, 6);
}

/**
 * Direct API call sharing the browser context's session — used for test
 * fixture setup (e.g. creating a department/location/position) so specs
 * don't depend on fragile, seed-order-dependent database IDs. Mirrors the
 * XSRF handling in packages/api-client (Sanctum SPA auth requires the
 * XSRF-TOKEN cookie echoed back as a header on state-changing requests).
 */
export async function apiPost<T = unknown>(page: Page, path: string, data: unknown): Promise<T> {
  const cookies = await page.context().cookies();
  const xsrf = cookies.find((c) => c.name === 'XSRF-TOKEN');
  const response = await page.request.post(path, {
    data,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      // page.request is a raw HTTP client, not an in-page fetch — it doesn't
      // carry the Origin/Referer header a real browser request from the
      // loaded page would. Sanctum's EnsureFrontendRequestsAreStateful uses
      // that header to decide whether to treat the request as session-
      // authenticated; without it, an otherwise-valid session cookie gets
      // ignored and the request looks unauthenticated.
      Referer: 'http://localhost:5173',
      ...(xsrf ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrf.value) } : {}),
    },
  });
  if (!response.ok()) {
    throw new Error(`POST ${path} failed: ${response.status()} ${await response.text()}`);
  }
  return response.json();
}

/** Same Referer-header requirement as apiPost (see comment there) — GET requests need it too for role-boundary checks to see the real 403, not a false-negative 401. */
export async function apiGet(page: Page, path: string) {
  return page.request.get(path, { headers: { Accept: 'application/json', Referer: 'http://localhost:5173' } });
}

/**
 * Checks every unchecked checkbox on the page one at a time, waiting for the
 * unchecked count to actually decrease between clicks. Each checkbox click
 * here triggers a full task-completion API call + React re-render, which
 * races a naive "click, then immediately click the next .first()" loop —
 * the re-render can swap in a new DOM node at the same position before
 * Playwright's click resolves, so it looks like "the click did nothing".
 * Waiting on the count (an auto-retrying assertion) instead of the specific
 * element sidesteps that. Uses .click() rather than .check() deliberately:
 * .check() does its own quick built-in "is it checked now" verification,
 * which is too impatient for a checkbox whose checked state only flips once
 * a full async API round-trip + reload completes — the toHaveCount below
 * already covers that wait with a generous timeout.
 */
export async function checkAllCheckboxes(page: Page) {
  const unchecked = page.locator('input[type="checkbox"]:not(:checked)');
  let remaining = await unchecked.count();
  expect(remaining).toBeGreaterThan(0);

  while (remaining > 0) {
    await unchecked.first().click();
    await expect(unchecked).toHaveCount(remaining - 1, { timeout: 10_000 });
    remaining -= 1;
  }
}

/** Creates a fresh department + location + position via the API, for tests that need valid org IDs. */
export async function createOrgUnit(page: Page, suffix: string) {
  const department = await apiPost<{ data: { id: number } }>(page, '/api/v1/admin/departments', {
    name: `Test Dept ${suffix}`,
    code: `TD-${suffix}`,
  });
  const location = await apiPost<{ data: { id: number } }>(page, '/api/v1/admin/locations', {
    name: `Test Site ${suffix}`,
    code: `TS-${suffix}`,
    state: 'TX',
    minimum_wage: 7.25,
  });
  const position = await apiPost<{ data: { id: number } }>(page, '/api/v1/admin/positions', {
    title: `Test Role ${suffix}`,
    department_id: department.data.id,
    default_employment_type: 'hourly',
  });

  return { departmentId: department.data.id, locationId: location.data.id, positionId: position.data.id };
}
