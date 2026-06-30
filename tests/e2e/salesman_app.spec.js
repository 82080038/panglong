const { test, expect } = require('@playwright/test');
const FRONTEND_BASE = 'http://localhost/panglong/frontend';

test.describe('Panglong ERP - Salesman Mobile App', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', 'supervisor1');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });
  });

  test('salesman app page loads and registers service worker', async ({ page }) => {
    const consoleErrors = [], pageErrors = [];
    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => pageErrors.push(err.message));

    await page.goto(`${FRONTEND_BASE}/salesman_app.php?test_mode=true`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h4')).toContainText('Aplikasi Salesman Mobile');
    await expect(page.locator('a.nav-link:has-text("Pesanan Saya")')).toBeVisible();
    await expect(page.locator('a.nav-link:has-text("Pesanan Baru")')).toBeVisible();

    const swRegistered = await page.evaluate(() => {
      return new Promise((resolve) => {
        if (!('serviceWorker' in navigator)) resolve(false);
        navigator.serviceWorker.ready.then(() => resolve(true)).catch(() => resolve(false));
        setTimeout(() => resolve(false), 3000);
      });
    });
    expect(swRegistered).toBe(true);

    expect(consoleErrors).toEqual([]);
    expect(pageErrors).toEqual([]);
  });
});
