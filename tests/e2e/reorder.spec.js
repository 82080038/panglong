const { test, expect } = require('@playwright/test');
const FRONTEND_BASE = 'http://localhost/panglong/frontend';

test.describe('Panglong ERP - Reorder AI Page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });
  });

  test('reorder page loads without errors', async ({ page }) => {
    const consoleErrors = [], pageErrors = [];
    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => pageErrors.push(err.message));

    await page.goto(`${FRONTEND_BASE}/reorder.php`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h1')).toContainText('Reorder');
    expect(consoleErrors).toEqual([]);
    expect(pageErrors).toEqual([]);
  });

  test('reorder page has PO integration controls', async ({ page }) => {
    const consoleErrors = [], pageErrors = [];
    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => pageErrors.push(err.message));

    await page.goto(`${FRONTEND_BASE}/reorder.php`);
    await page.waitForLoadState('networkidle');

    await expect(page.locator('#poSupplier')).toBeVisible();
    await expect(page.locator('button[onclick*="createPOFromReorder"]')).toBeVisible();
    await expect(page.locator('#reorderTable th:has-text("Suggested Order Qty")')).toBeVisible();

    expect(consoleErrors).toEqual([]);
    expect(pageErrors).toEqual([]);
  });

  test('purchase-orders endpoint creates PO from reorder payload', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    const response = await page.evaluate(async () => {
      const res = await fetch('ajax.php?endpoint=purchase-orders&test_mode=true', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          supplier_id: 63,
          po_date: '2026-06-30',
          payment_method: 'credit',
          items: [{ product_id: 1, quantity: 5, unit_id: 1, unit_price: 10000 }]
        })
      });
      return res.json();
    });

    if (!response.success) {
      throw new Error('PO create failed: ' + JSON.stringify(response));
    }
    expect(response.data.po_number).toBeTruthy();

    await page.goto(`${FRONTEND_BASE}/purchase-orders.php`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h1')).toContainText('Pesanan Pembelian');
    const poRows = await page.locator('table tbody tr').count();
    expect(poRows).toBeGreaterThan(0);
  });
});
