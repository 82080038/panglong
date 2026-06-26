const { test, expect } = require('@playwright/test');
const FRONTEND_BASE = 'http://localhost/panglong/frontend';

test.describe('Panglong ERP - Purchase Orders Page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });
  });

  test('purchase orders page loads without errors', async ({ page }) => {
    const consoleErrors = [], pageErrors = [];
    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => pageErrors.push(err.message));

    await page.goto(`${FRONTEND_BASE}/purchase-orders.php`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h1')).toContainText('Pesanan');
    await expect(page.locator('table.table-striped')).toBeVisible();
    expect(consoleErrors).toEqual([]);
    expect(pageErrors).toEqual([]);
  });

  test('new PO modal opens with supplier dropdown', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/purchase-orders.php`);
    await page.waitForLoadState('networkidle');
    await page.click('button:has-text("PO Baru")');
    await expect(page.locator('#poModal')).toBeVisible();
    await expect(page.locator('#poSupplier')).toBeVisible();
  });
});
