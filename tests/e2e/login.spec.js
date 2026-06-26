const { test, expect } = require('@playwright/test');

const FRONTEND_BASE = 'http://localhost/panglong/frontend';

test.describe('Panglong ERP - Login Flow', () => {
  test('login page loads without errors', async ({ page }) => {
    const consoleErrors = [];
    const pageErrors = [];

    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => pageErrors.push(err.message));

    await page.goto(`${FRONTEND_BASE}/login.php`);
    await expect(page).toHaveTitle(/Masuk - Panglong ERP/);
    await expect(page.locator('input[name="username"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Super Admin (Platform Owner)' })).toBeVisible();
    expect(consoleErrors).toEqual([]);
    expect(pageErrors).toEqual([]);
  });

  test('login with valid credentials redirects to dashboard', async ({ page }) => {
    const consoleErrors = [], pageErrors = [], networkFailures = [];
    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => pageErrors.push(err.message));
    page.on('requestfailed', req => networkFailures.push(`${req.url()} - ${req.failure()?.errorText}`));

    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });
    await expect(page).toHaveTitle(/Beranda/);
    expect(consoleErrors).toEqual([]);
    expect(pageErrors).toEqual([]);
    expect(networkFailures).toEqual([]);
  });

  test('login with invalid credentials shows error', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', 'wronguser');
    await page.fill('input[name="password"]', 'wrongpass');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);
    const alert = page.locator('.alert-danger');
    await expect(alert).toBeVisible();
  });

  test('quick login admin button works', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.click('button:has-text("Super Admin")');
    await page.waitForURL('**/index.php', { timeout: 10000 });
    await expect(page).toHaveTitle(/Beranda/);
  });
});
