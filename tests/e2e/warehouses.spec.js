const { test, expect } = require('@playwright/test');
const FRONTEND_BASE = 'http://localhost/panglong/frontend';

test.describe('Panglong ERP - Warehouses Page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });
  });

  test('warehouses page loads without errors', async ({ page }) => {
    const consoleErrors = [], pageErrors = [];
    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => pageErrors.push(err.message));

    await page.goto(`${FRONTEND_BASE}/warehouses.php`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h1')).toContainText('Gudang');
    expect(consoleErrors).toEqual([]);
    expect(pageErrors).toEqual([]);
  });

  test('warehouse location modal opens and creates location', async ({ page }) => {
    const consoleErrors = [], pageErrors = [];
    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => pageErrors.push(err.message));

    await page.goto(`${FRONTEND_BASE}/warehouses.php?test_mode=true`);
    await page.waitForLoadState('networkidle');

    await page.locator('button:has-text("Lokasi")').first().click();
    await expect(page.locator('.modal.show .modal-title:has-text("Lokasi Gudang")')).toBeVisible();
    await page.fill('.modal.show input[name="code"]', 'LOC-TEST-001');
    await page.fill('.modal.show input[name="name"]', 'Rack Test 1');
    await page.fill('.modal.show input[name="aisle"]', 'A');
    await page.fill('.modal.show input[name="level"]', '1');
    await page.locator('.modal.show button:has-text("Simpan Lokasi")').click();
    await page.waitForLoadState('networkidle');
    await expect(page.locator('.alert')).toContainText('Lokasi gudang dibuat');

    expect(consoleErrors).toEqual([]);
    expect(pageErrors).toEqual([]);
  });
});
