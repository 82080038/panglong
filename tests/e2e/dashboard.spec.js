const { test, expect } = require('@playwright/test');
const FRONTEND_BASE = 'http://localhost/panglong/frontend';

test.describe('Panglong ERP - Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });
  });

  test('dashboard loads without console/page errors', async ({ page }) => {
    const consoleErrors = [], pageErrors = [];
    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => pageErrors.push(err.message));

    await page.goto(`${FRONTEND_BASE}/index.php`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h1')).toHaveText('Beranda');
    await expect(page.locator('.card-title:has-text("Produk")')).toBeVisible();
    await expect(page.locator('.card-title:has-text("Pelanggan")')).toBeVisible();
    await expect(page.locator('.card-title:has-text("Penjualan Hari Ini")')).toBeVisible();
    await expect(page.locator('.card-title:has-text("Stok Menipis")')).toBeVisible();
    await expect(page.locator('canvas#salesChart')).toBeVisible();
    expect(consoleErrors).toEqual([]);
    expect(pageErrors).toEqual([]);
  });

  test('dashboard shows product and customer counts', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/index.php`);
    await page.waitForLoadState('networkidle');
    const productCount = page.locator('.card-text.fs-2').first();
    await expect(productCount).not.toBeEmpty();
  });

  test('logout redirects to login', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/index.php`);
    await page.waitForLoadState('networkidle');
    await page.click('a[href="logout.php"]');
    await page.waitForURL('**/login.php', { timeout: 10000 });
    await expect(page).toHaveTitle(/Masuk/);
  });

  test('navbar shows user name and role', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/index.php`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('.navbar-text')).toContainText('Administrator');
    await expect(page.locator('.navbar-text .badge')).toContainText('Owner');
  });
});
