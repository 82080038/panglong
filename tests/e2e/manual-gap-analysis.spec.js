const { test, expect } = require('@playwright/test');
const FRONTEND_BASE = 'http://localhost/panglong/frontend';

const USERS = {
  admin: { username: 'admin', password: 'password123', role: 'Owner' },
  manager: { username: 'manager1', password: 'password123', role: 'Manager' },
  kasir: { username: 'kasir1', password: 'password123', role: 'Kasir' },
  gudang: { username: 'gudang1', password: 'password123', role: 'Gudang' },
  accounting: { username: 'accounting1', password: 'password123', role: 'Accounting' },
  supervisor: { username: 'supervisor1', password: 'password123', role: 'Supervisor' }
};

test.describe('Manual Gap Analysis - All Roles and Features', () => {
  let gaps = [];

  test.beforeEach(async ({ page }) => {
    page.on('console', msg => {
      if (msg.type() === 'error') {
        gaps.push({ type: 'console_error', message: msg.text(), url: page.url() });
      }
    });
    page.on('pageerror', err => {
      gaps.push({ type: 'page_error', message: err.message, url: page.url() });
    });
  });

  test('Check Theme Toggles Exist', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', USERS.admin.username);
    await page.fill('input[name="password"]', USERS.admin.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    // Theme toggle is a dropdown with palette icon
    const themeDropdown = page.locator('button .bi-palette').locator('..');

    if (!await themeDropdown.isVisible()) {
      gaps.push({ type: 'ui_issue', message: 'Theme dropdown not found on dashboard', url: page.url() });
    } else {
      // Click dropdown to show options
      await themeDropdown.click();
      await page.waitForTimeout(500);

      const lightOption = page.locator('a:has-text("Terang")');
      const darkOption = page.locator('a:has-text("Gelap")');
      const eyeCareOption = page.locator('a:has-text("Mode Mata")');

      if (!await lightOption.isVisible()) {
        gaps.push({ type: 'ui_issue', message: 'Light mode option not found', url: page.url() });
      }
      if (!await darkOption.isVisible()) {
        gaps.push({ type: 'ui_issue', message: 'Dark mode option not found', url: page.url() });
      }
      if (!await eyeCareOption.isVisible()) {
        gaps.push({ type: 'ui_issue', message: 'Eye-care mode option not found', url: page.url() });
      }
    }
  });

  test('Test All Pages for Errors', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', USERS.admin.username);
    await page.fill('input[name="password"]', USERS.admin.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    const pages = [
      'index.php', 'products.php', 'customers.php', 'suppliers.php',
      'sales.php', 'stock.php', 'reports.php', 'warehouses.php',
      'accounting.php', 'cashbook.php', 'cash_flow.php', 'quotations.php',
      'sales_orders.php', 'deliveries.php', 'returns.php', 'stock_transfers.php',
      'purchase-orders.php', 'pricing.php', 'whatsapp.php', 'e_faktur.php',
      'fixed_assets.php', 'closing.php', 'stock_opname.php', 'reorder.php',
      'marketplace.php', 'iot.php', 'ai_insights.php', 'batches.php',
      'fleet.php', 'landed_cost.php', 'tenants.php', 'users.php',
      'settings.php', 'saas.php'
    ];

    for (const pg of pages) {
      try {
        await page.goto(`${FRONTEND_BASE}/${pg}`, { timeout: 30000 });
        await page.waitForLoadState('networkidle', { timeout: 10000 });

        const hasError = await page.locator('.alert-danger').isVisible();
        if (hasError) {
          const errorText = await page.locator('.alert-danger').first().textContent();
          gaps.push({ type: 'page_error', message: `${pg}: ${errorText}`, url: page.url() });
        }
      } catch (error) {
        gaps.push({ type: 'navigation_error', message: `${pg}: ${error.message}`, url: page.url() });
      }
    }
  });

  test('Test Role-Based Access', async ({ page }) => {
    for (const [key, user] of Object.entries(USERS)) {
      await page.goto(`${FRONTEND_BASE}/login.php`);
      await page.fill('input[name="username"]', user.username);
      await page.fill('input[name="password"]', user.password);
      await page.click('button[type="submit"]');
      await page.waitForURL('**/index.php', { timeout: 10000 });

      // Check if user can access dashboard
      const dashboardTitle = await page.title();
      if (!dashboardTitle.includes('Beranda')) {
        gaps.push({ type: 'auth_issue', message: `${user.role}: Dashboard not accessible`, url: page.url() });
      }

      await page.goto(`${FRONTEND_BASE}/logout.php`);
      await page.waitForTimeout(1000);
    }
  });

  test('Test Responsive Design', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', USERS.admin.username);
    await page.fill('input[name="password"]', USERS.admin.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    // Test mobile view
    await page.setViewportSize({ width: 375, height: 667 });
    await page.waitForLoadState('networkidle');

    const navbar = page.locator('.navbar, nav');
    if (await navbar.isVisible()) {
      const navbarWidth = await navbar.boundingBox();
      if (navbarWidth && navbarWidth.width > 375) {
        gaps.push({ type: 'responsive_issue', message: 'Navbar not responsive on mobile', url: page.url() });
      }
    }

    // Test tablet view
    await page.setViewportSize({ width: 768, height: 1024 });
    await page.waitForLoadState('networkidle');

    // Test desktop view
    await page.setViewportSize({ width: 1920, height: 1080 });
    await page.waitForLoadState('networkidle');
  });

  test('Test Data Integrity - Check Empty States', async ({ page }) => {
    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', USERS.admin.username);
    await page.fill('input[name="password"]', USERS.admin.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    const dataPages = [
      { page: 'products.php', selector: 'tbody tr', name: 'Products' },
      { page: 'customers.php', selector: 'tbody tr', name: 'Customers' },
      { page: 'suppliers.php', selector: 'tbody tr', name: 'Suppliers' },
      { page: 'sales.php', selector: 'tbody tr', name: 'Sales' },
      { page: 'stock.php', selector: 'tbody tr', name: 'Stock' }
    ];

    for (const dp of dataPages) {
      await page.goto(`${FRONTEND_BASE}/${dp.page}`);
      await page.waitForLoadState('networkidle');

      const count = await page.locator(dp.selector).count();
      if (count === 0) {
        gaps.push({ type: 'data_issue', message: `${dp.name}: No data found`, url: page.url() });
      }
    }
  });

  test.afterAll(async () => {
    console.log('\n=== GAP ANALYSIS COMPLETE ===');
    console.log(`Total gaps found: ${gaps.length}`);
    if (gaps.length > 0) {
      console.log('\nGap Details:');
      gaps.forEach((gap, index) => {
        console.log(`${index + 1}. [${gap.type}] ${gap.message}`);
        if (gap.url) console.log(`   URL: ${gap.url}`);
      });
    }
  });
});
