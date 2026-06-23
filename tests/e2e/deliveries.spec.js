const { test, expect } = require('@playwright/test');
const FRONTEND_BASE = 'http://localhost/panglong/frontend';

test.describe('Panglong ERP - Deliveries Page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php');
  });

  test('deliveries page loads without errors', async ({ page }) => {
    const consoleErrors = [], pageErrors = [];
    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => pageErrors.push(err.message));

    await page.goto(`${FRONTEND_BASE}/deliveries.php`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h1')).toHaveText('Deliveries (Surat Jalan)');
    await expect(page.locator('table.table-striped')).toBeVisible();
    expect(consoleErrors).toEqual([]);
    expect(pageErrors).toEqual([]);
  });

  test('new delivery modal opens with form fields', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/deliveries.php`);
    await page.waitForLoadState('networkidle');
    await page.click('button:has-text("New Delivery")');
    await expect(page.locator('#deliveryModal')).toBeVisible();
    await expect(page.locator('#delCustomerName')).toBeVisible();
    await expect(page.locator('#delDate')).toBeVisible();
  });
});
