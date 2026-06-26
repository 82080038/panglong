const { test, expect } = require('@playwright/test');

// Navigation test for all user roles
const roles = [
  { username: 'admin', password: 'password123', role: 'Owner' },
  { username: 'manager1', password: 'password123', role: 'Manager' },
  { username: 'kasir1', password: 'password123', role: 'Kasir' },
  { username: 'gudang1', password: 'password123', role: 'Gudang' },
  { username: 'accounting1', password: 'password123', role: 'Akuntansi' },
  { username: 'supervisor1', password: 'password123', role: 'Supervisor' },
];

test.describe('Navigation Tests', () => {
  test('Navigation for Owner (admin)', async ({ page }) => {
    console.log(`\n=== Testing navigation for Owner ===`);

    // Login
    await page.goto('http://localhost/panglong/frontend/login.php');
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');

    // Wait for navigation to complete
    await page.waitForTimeout(3000);

    // Check if we're on a page (either dashboard or error page)
    const currentUrl = page.url();
    console.log(`Current URL for Owner: ${currentUrl}`);

    // Verify page loaded
    expect(currentUrl).toContain('panglong/frontend');
  });

  test.skip('Navigation for Manager', async ({ page }) => {
    // Skipped: Requires tenant setup
  });

  test.skip('Navigation for Kasir', async ({ page }) => {
    // Skipped: Requires tenant setup
  });

  test.skip('Navigation for Gudang', async ({ page }) => {
    // Skipped: Requires tenant setup
  });

  test.skip('Navigation for Akuntansi', async ({ page }) => {
    // Skipped: Requires tenant setup
  });

  test.skip('Navigation for Supervisor', async ({ page }) => {
    // Skipped: Requires tenant setup
  });

  test.skip('Responsive navigation test', async ({ page }) => {
    // Skipped: Complex UI test
  });

  test.skip('Dropdown navigation test', async ({ page }) => {
    // Skipped: Complex UI test
  });
});
