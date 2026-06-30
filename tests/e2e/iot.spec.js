const { test, expect } = require('@playwright/test');
const FRONTEND_BASE = 'http://localhost/panglong/frontend';

test.describe('Panglong ERP - IoT Page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });
  });

  test('iot page loads without errors', async ({ page }) => {
    const consoleErrors = [], pageErrors = [];
    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => pageErrors.push(err.message));

    await page.goto(`${FRONTEND_BASE}/iot.php`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h1')).toContainText('IoT');
    expect(consoleErrors).toEqual([]);
    expect(pageErrors).toEqual([]);
  });

  test('register sensor and record reading', async ({ page }) => {
    const consoleErrors = [], pageErrors = [];
    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => pageErrors.push(err.message));

    await page.goto(`${FRONTEND_BASE}/iot.php?test_mode=true`);
    await page.waitForLoadState('networkidle');

    await page.locator('button:has-text("Register Sensor")').click();
    await expect(page.locator('.modal.show .modal-title:has-text("Register IoT Sensor")')).toBeVisible();
    await page.fill('.modal.show input[name="sensor_id"]', 'TEMP-TEST-001');
    await page.fill('.modal.show input[name="name"]', 'Test Temperature Sensor');
    await page.fill('.modal.show input[name="location"]', 'Warehouse A');
    await page.locator('.modal.show button:has-text("Daftar")').click();
    await page.waitForLoadState('networkidle');
    await expect(page.locator('.alert')).toContainText('Sensor berhasil terdaftar');

    await page.locator('button:has-text("Reading")').first().click();
    await expect(page.locator('.modal.show .modal-title:has-text("Record Reading")')).toBeVisible();
    await page.fill('.modal.show input[name="value"]', '25.5');
    await page.locator('.modal.show button:has-text("Simpan")').click();
    await page.waitForLoadState('networkidle');
    await expect(page.locator('.alert')).toContainText('Data pembacaan tersimpan');

    expect(consoleErrors).toEqual([]);
    expect(pageErrors).toEqual([]);
  });
});
