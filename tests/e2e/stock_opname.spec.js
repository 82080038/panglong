const { test, expect } = require('@playwright/test');
const FRONTEND_BASE = 'http://localhost/panglong/frontend';

test.describe('Panglong ERP - Stock Opname Page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });
  });

  test('stock opname page loads without errors', async ({ page }) => {
    const consoleErrors = [], pageErrors = [];
    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => pageErrors.push(err.message));

    await page.goto(`${FRONTEND_BASE}/stock_opname.php`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h1')).toContainText('Opname');
    await expect(page.locator('#opnameTable')).toBeVisible();
    await expect(page.locator('#opnameTable th:has-text("Kode Produk")')).toBeVisible();
    await expect(page.locator('#opnameTable th:has-text("Jml Sistem")')).toBeVisible();
    expect(consoleErrors).toEqual([]);
    expect(pageErrors).toEqual([]);
  });

  test('opname form has date and submit button', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/stock_opname.php`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('input[name="opname_date"]')).toBeVisible();
    await expect(page.locator('button:has-text("Buat Opname")')).toBeVisible();
  });
});
