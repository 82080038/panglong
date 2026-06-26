const { test, expect } = require('@playwright/test');
const FRONTEND_BASE = 'http://localhost/panglong/frontend';

test.describe('Panglong ERP - Stock Page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });
  });

  test('stock page loads without errors', async ({ page }) => {
    const consoleErrors = [], pageErrors = [];
    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => pageErrors.push(err.message));

    await page.goto(`${FRONTEND_BASE}/stock.php`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('table.table')).toBeVisible();
    expect(consoleErrors).toEqual([]);
    expect(pageErrors).toEqual([]);
  });

  test('stock adjustment modal opens with form', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/stock.php`);
    await page.waitForLoadState('networkidle');
    await page.click('button:has-text("Penyesuaian Stok")');
    await expect(page.locator('#adjustModal')).toBeVisible();
    await expect(page.locator('#adjustModal select[name="product_id"]')).toBeVisible();
    await expect(page.locator('#adjustModal select[name="adjustment_type"]')).toBeVisible();
    await expect(page.locator('#adjustModal textarea[name="reason"]')).toBeVisible();
  });
});
