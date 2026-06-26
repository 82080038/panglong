const { test, expect } = require('@playwright/test');
const FRONTEND_BASE = 'http://localhost/panglong/frontend';

test.describe('Super Admin - Screenshot Review', () => {
  test('1. Login page', async ({ page }) => {
    await page.goto(FRONTEND_BASE + '/login.php');
    await page.screenshot({ path: 'tests/screenshots/01-login-page.png', fullPage: true });
  });

  test('2. Login as Super Admin', async ({ page }) => {
    await page.goto(FRONTEND_BASE + '/login.php');
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });
    await page.screenshot({ path: 'tests/screenshots/02-dashboard.png', fullPage: true });
  });

  test('3. Users page', async ({ page }) => {
    await page.goto(FRONTEND_BASE + '/login.php');
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });
    
    await page.goto(FRONTEND_BASE + '/users.php');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: 'tests/screenshots/03-users-page.png', fullPage: true });
  });

  test('4. Registration page', async ({ page }) => {
    await page.goto(FRONTEND_BASE + '/register.php');
    await page.screenshot({ path: 'tests/screenshots/04-register-page.png', fullPage: true });
  });

  test('5. Register new tenant', async ({ page }) => {
    await page.goto(FRONTEND_BASE + '/register.php');
    
    await page.fill('input[name="company_name"]', 'Toko Test ABC');
    await page.fill('textarea[name="address"]', 'Jl. Test No. 123');
    await page.fill('input[name="phone"]', '08123456789');
    await page.fill('input[name="email"]', 'test@tokotest.com');
    await page.fill('input[name="subdomain"]', 'tokotest2');
    await page.fill('input[name="username"]', 'ownertest2');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password123');
    await page.fill('input[name="full_name"]', 'Owner Test 2');
    
    await page.screenshot({ path: 'tests/screenshots/05-register-filled.png', fullPage: true });
    
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);
    await page.screenshot({ path: 'tests/screenshots/06-register-result.png', fullPage: true });
  });

  test('6. Login as tenant owner', async ({ page }) => {
    await page.goto(FRONTEND_BASE + '/login.php');
    await page.fill('input[name="username"]', 'ownertest2');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);
    await page.screenshot({ path: 'tests/screenshots/07-tenant-dashboard.png', fullPage: true });
  });

  test('7. Tenant users page', async ({ page }) => {
    await page.goto(FRONTEND_BASE + '/login.php');
    await page.fill('input[name="username"]', 'ownertest2');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);
    
    await page.goto(FRONTEND_BASE + '/users.php');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: 'tests/screenshots/08-tenant-users.png', fullPage: true });
  });
});
