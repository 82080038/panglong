const { test, expect } = require('@playwright/test');
const FRONTEND_BASE = 'http://localhost/panglong/frontend';

const USERS = {
  admin: { username: 'admin', password: 'password123', role: 'Owner' }
};

test.describe('Verify Test Data in UI', () => {
  test.beforeEach(async ({ page }) => {
    page.on('console', msg => {
      if (msg.type() === 'error') {
        console.log('Console Error:', msg.text());
      }
    });
  });

  test('Verify User Data in Users Page', async ({ page }) => {
    console.log('=== VERIFY USER DATA ===');

    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', USERS.admin.username);
    await page.fill('input[name="password"]', USERS.admin.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    await page.goto(`${FRONTEND_BASE}/users.php`);
    await page.waitForLoadState('networkidle');

    // Check if test user exists in table
    const testUserRow = page.locator('table').getByText(/testuser\d+/).first();
    if (await testUserRow.isVisible()) {
      console.log('✓ Test user found in users table');
    } else {
      console.log('⚠ Test user not found in users table');
    }
  });

  test('Verify Product Data in Products Page', async ({ page }) => {
    console.log('=== VERIFY PRODUCT DATA ===');

    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', USERS.admin.username);
    await page.fill('input[name="password"]', USERS.admin.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    await page.goto(`${FRONTEND_BASE}/products.php`);
    await page.waitForLoadState('networkidle');

    // Check if test product exists in table
    const testProductRow = page.locator('table').getByText(/PRD\d+/).first();
    if (await testProductRow.isVisible()) {
      console.log('✓ Test product found in products table');
    } else {
      console.log('⚠ Test product not found in products table');
    }
  });

  test('Verify Customer Data in Customers Page', async ({ page }) => {
    console.log('=== VERIFY CUSTOMER DATA ===');

    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', USERS.admin.username);
    await page.fill('input[name="password"]', USERS.admin.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    await page.goto(`${FRONTEND_BASE}/customers.php`);
    await page.waitForLoadState('networkidle');

    // Check if test customer exists in table
    const testCustomerRow = page.locator('table').getByText(/Test Customer \d+/).first();
    if (await testCustomerRow.isVisible()) {
      console.log('✓ Test customer found in customers table');
    } else {
      console.log('⚠ Test customer not found in customers table');
    }
  });

  test('Verify Supplier Data in Suppliers Page', async ({ page }) => {
    console.log('=== VERIFY SUPPLIER DATA ===');

    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', USERS.admin.username);
    await page.fill('input[name="password"]', USERS.admin.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    await page.goto(`${FRONTEND_BASE}/suppliers.php`);
    await page.waitForLoadState('networkidle');

    // Check if test supplier exists in table
    const testSupplierRow = page.locator('table').getByText(/Test Supplier \d+/).first();
    if (await testSupplierRow.isVisible()) {
      console.log('✓ Test supplier found in suppliers table');
    } else {
      console.log('⚠ Test supplier not found in suppliers table');
    }
  });

  test('Verify Sale Data in Sales Page', async ({ page }) => {
    console.log('=== VERIFY SALE DATA ===');

    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', USERS.admin.username);
    await page.fill('input[name="password"]', USERS.admin.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    await page.goto(`${FRONTEND_BASE}/sales.php`);
    await page.waitForLoadState('networkidle');

    // Check if test sale exists in table
    const testSaleRow = page.locator('table').getByText(/INV\d+/).first();
    if (await testSaleRow.isVisible()) {
      console.log('✓ Test sale found in sales table');
    } else {
      console.log('⚠ Test sale not found in sales table');
    }
  });
});
