const { test, expect } = require('@playwright/test');
const FRONTEND_BASE = 'http://localhost/panglong/frontend';

const pages = [
  { name: 'batches', url: 'batches.php' },
  { name: 'cashbook', url: 'cashbook.php' },
  { name: 'cash_flow', url: 'cash_flow.php' },
  { name: 'closing', url: 'closing.php' },
  { name: 'e_faktur', url: 'e_faktur.php' },
  { name: 'fixed_assets', url: 'fixed_assets.php' },
  { name: 'fleet', url: 'fleet.php' },
  { name: 'landed_cost', url: 'landed_cost.php' },
  { name: 'pricing', url: 'pricing.php' },
  { name: 'quotations', url: 'quotations.php' },
  { name: 'returns', url: 'returns.php' },
  { name: 'routes', url: 'routes.php' },
  { name: 'settings', url: 'settings.php' },
  { name: 'stock_transfers', url: 'stock_transfers.php' },
  { name: 'tenants', url: 'tenants.php' },
  { name: 'users', url: 'users.php' },
  { name: 'whatsapp', url: 'whatsapp.php' },
];

test.describe('Panglong ERP - Uncovered Pages', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });
  });

  for (const p of pages) {
    test(`${p.name} page loads without errors`, async ({ page }) => {
      const pageErrors = [];
      page.on('pageerror', err => pageErrors.push(err.message));

      const response = await page.goto(`${FRONTEND_BASE}/${p.url}?test_mode=true`);
      expect(response.status()).toBe(200);

      await page.waitForLoadState('networkidle');

      const bodyHTML = await page.locator('body').innerHTML();
      expect(bodyHTML).not.toMatch(/<b>(Fatal error|Parse error|Notice|Warning)<\/b>/i);
      expect(bodyHTML).not.toMatch(/^Akses ditolak/);

      const hasCard = await page.locator('.card, .container, .table, h1, h2, h3, h4, h5').first().isVisible().catch(() => false);
      expect(hasCard).toBe(true);

      expect(pageErrors).toEqual([]);
    });
  }
});
