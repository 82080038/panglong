const { test, expect } = require('@playwright/test');

const FRONTEND_BASE = 'http://localhost/panglong/frontend';

// Navigation test for all user roles
const roles = [
  { username: 'admin', password: 'password123', role: 'Super Admin' },
  { username: 'manager1', password: 'password123', role: 'Manager' },
  { username: 'kasir1', password: 'password123', role: 'Kasir' },
  { username: 'gudang1', password: 'password123', role: 'Gudang' },
  { username: 'accounting1', password: 'password123', role: 'Akuntansi' },
  { username: 'supervisor1', password: 'password123', role: 'Supervisor' },
];

test.describe('Navigation Tests', () => {
  for (const r of roles) {
    test(`Navigation for ${r.role} (${r.username})`, async ({ page }) => {
      await page.goto(`${FRONTEND_BASE}/login.php`);
      await page.fill('input[name="username"]', r.username);
      await page.fill('input[name="password"]', r.password);
      await page.click('button[type="submit"]');
      await page.waitForURL('**/index.php');
      const currentUrl = page.url();
      expect(currentUrl).toContain('panglong/frontend');
      await expect(page.locator('h1')).toBeVisible();
    });
  }

  test('Responsive navigation test', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php');

    // Desktop view
    await page.setViewportSize({ width: 1280, height: 720 });
    await expect(page.locator('.navbar')).toBeVisible();

    // Tablet view
    await page.setViewportSize({ width: 768, height: 1024 });
    await page.waitForTimeout(500);
    await expect(page.locator('.navbar')).toBeVisible();

    // Mobile view
    await page.setViewportSize({ width: 375, height: 667 });
    await page.waitForTimeout(500);
    await expect(page.locator('.navbar')).toBeVisible();
  });

  test('Dropdown navigation test', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php');

    // Find dropdown toggle buttons in nav
    const dropdowns = page.locator('.navbar .dropdown-toggle');
    const count = await dropdowns.count();
    expect(count).toBeGreaterThan(0);

    // Click first dropdown and verify menu appears
    await dropdowns.first().click();
    await page.waitForTimeout(500);
    const menu = page.locator('.navbar .dropdown-menu').first();
    await expect(menu).toBeVisible();
  });
});
