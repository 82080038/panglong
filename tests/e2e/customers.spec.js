const { test, expect } = require('@playwright/test');
const FRONTEND_BASE = 'http://localhost/panglong/frontend';

test.describe('Panglong ERP - Customers Page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });
  });

  test('customers page loads without errors', async ({ page }) => {
    const consoleErrors = [], pageErrors = [];
    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => pageErrors.push(err.message));

    await page.goto(`${FRONTEND_BASE}/customers.php`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h1')).toHaveText('Pelanggan');
    await expect(page.locator('table.table')).toBeVisible();
    expect(consoleErrors).toEqual([]);
    expect(pageErrors).toEqual([]);
  });

  test('add customer modal opens with form fields', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/customers.php`);
    await page.waitForLoadState('networkidle');
    await page.click('button:has-text("Tambah Pelanggan")');
    await expect(page.locator('#addModal')).toBeVisible();
    await expect(page.locator('#addModal input[name="name"]')).toBeVisible();
    await expect(page.locator('#addModal input[name="phone"]')).toBeVisible();
  });
});
