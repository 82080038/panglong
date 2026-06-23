const { test, expect } = require('@playwright/test');
const FRONTEND_BASE = 'http://localhost/panglong/frontend';

test.describe('Panglong ERP - Products Page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });
  });

  test('products page loads without errors', async ({ page }) => {
    const consoleErrors = [], pageErrors = [], networkFailures = [];
    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => pageErrors.push(err.message));
    page.on('requestfailed', req => networkFailures.push(`${req.url()} - ${req.failure()?.errorText}`));

    await page.goto(`${FRONTEND_BASE}/products.php`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h1')).toHaveText('Products');
    await expect(page.locator('table.table-striped')).toBeVisible();
    await expect(page.locator('th:has-text("Name")')).toBeVisible();
    await expect(page.locator('button:has-text("Add Product")')).toBeVisible();
    expect(consoleErrors).toEqual([]);
    expect(pageErrors).toEqual([]);
    expect(networkFailures).toEqual([]);
  });

  test('products table shows product data', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/products.php`);
    await page.waitForLoadState('networkidle');
    const rows = page.locator('table tbody tr');
    const count = await rows.count();
    expect(count).toBeGreaterThan(0);
  });

  test('add product modal opens with form fields', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/products.php`);
    await page.waitForLoadState('networkidle');
    await page.click('button:has-text("Add Product")');
    const modal = page.locator('#addModal');
    await expect(modal).toBeVisible();
    await expect(page.locator('#addModal input[name="name"]')).toBeVisible();
    await expect(page.locator('#addModal input[name="code"]')).toBeVisible();
    await expect(page.locator('#addModal input[name="sell_price"]')).toBeVisible();
  });
});
