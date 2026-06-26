const { test, expect } = require('@playwright/test');
const FRONTEND_BASE = 'http://localhost/panglong/frontend';

test.describe('Panglong ERP - AI Insights Page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });
  });

  test('ai insights page loads without errors', async ({ page }) => {
    const consoleErrors = [], pageErrors = [];
    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => pageErrors.push(err.message));

    await page.goto(`${FRONTEND_BASE}/ai_insights.php`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h1')).toContainText('AI');
    expect(consoleErrors).toEqual([]);
    expect(pageErrors).toEqual([]);
  });

  test('ai pricing tab works', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/ai_insights.php?tab=pricing`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('.nav-tabs .nav-link.active')).toContainText('Harga');
  });
});
