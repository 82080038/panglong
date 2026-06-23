const { test, expect } = require('@playwright/test');
const FRONTEND_BASE = 'http://localhost/panglong/frontend';

test.describe('Panglong ERP - Sales Page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });
  });

  test('sales page loads without errors', async ({ page }) => {
    const consoleErrors = [], pageErrors = [], networkFailures = [];
    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => pageErrors.push(err.message));
    page.on('requestfailed', req => networkFailures.push(`${req.url()} - ${req.failure()?.errorText}`));

    await page.goto(`${FRONTEND_BASE}/sales.php`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h1')).toHaveText('Sales');
    await expect(page.locator('table.table-striped')).toBeVisible();
    await expect(page.locator('th:has-text("Invoice")')).toBeVisible();
    await expect(page.locator('th', { hasText: /^Total$/ })).toBeVisible();
    expect(consoleErrors).toEqual([]);
    expect(pageErrors).toEqual([]);
    expect(networkFailures).toEqual([]);
  });

  test('new sale modal opens with customer and product dropdowns', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/sales.php`);
    await page.waitForLoadState('networkidle');
    await page.click('button:has-text("New Sale")');
    await expect(page.locator('#saleModal')).toBeVisible();
    const customerSelect = page.locator('#customerSelect');
    await expect(customerSelect).toBeVisible();
    const customerOptions = await customerSelect.locator('option').count();
    expect(customerOptions).toBeGreaterThan(1);
    await page.waitForTimeout(500);
    const productSelect = page.locator('.item-row .productSelect').first();
    await expect(productSelect).toBeVisible();
    const productOptions = await productSelect.locator('option').count();
    expect(productOptions).toBeGreaterThan(1);
  });

  test('add item button creates new row in sale form', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/sales.php`);
    await page.waitForLoadState('networkidle');
    await page.click('button:has-text("New Sale")');
    await expect(page.locator('#saleModal')).toBeVisible();
    const initialRows = await page.locator('.item-row').count();
    expect(initialRows).toBe(1);
    await page.click('#addItemBtn');
    await page.waitForTimeout(500);
    const newRows = await page.locator('.item-row').count();
    expect(newRows).toBe(2);
  });
});
