const { test, expect } = require('@playwright/test');
const FRONTEND_BASE = 'http://localhost/panglong/frontend';

test.describe('Multi-Tenant Isolation', () => {
  test('super admin can access all pages without errors', async ({ page }) => {
    const consoleErrors = [], pageErrors = [];
    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => pageErrors.push(err.message));

    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    // Test key pages with tenant_id filters
    await page.goto(`${FRONTEND_BASE}/products.php`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h1')).toContainText('Produk');

    await page.goto(`${FRONTEND_BASE}/customers.php`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h1')).toContainText('Pelanggan');

    await page.goto(`${FRONTEND_BASE}/sales.php`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h1')).toContainText('Penjualan');

    await page.goto(`${FRONTEND_BASE}/warehouses.php`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h1')).toContainText('Gudang');

    expect(consoleErrors).toEqual([]);
    expect(pageErrors).toEqual([]);
  });

  test('reports with tenant_id filters work without errors', async ({ page }) => {
    const consoleErrors = [], pageErrors = [];
    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => pageErrors.push(err.message));

    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    await page.goto(`${FRONTEND_BASE}/reports.php`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h1')).toContainText('Laporan');

    expect(consoleErrors).toEqual([]);
    expect(pageErrors).toEqual([]);
  });
});
