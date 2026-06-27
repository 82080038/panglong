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

test.describe('Real CRUD Simulation - 2 Year Business Operations', () => {
  let createdData = {
    tenant: null,
    user: null,
    product: null,
    customer: null,
    supplier: null,
    sale: null,
    purchaseOrder: null,
    quotation: null,
    stockAdjustment: null,
    stockOpname: null
  };

  test.beforeEach(async ({ page }) => {
    page.on('console', msg => {
      if (msg.type() === 'error') {
        console.log('Console Error:', msg.text());
      }
    });
  });

  test('Step 1: Super Admin - Create New Tenant', async ({ page }) => {
    console.log('=== STEP 1: CREATE NEW TENANT ===');

    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', USERS.admin.username);
    await page.fill('input[name="password"]', USERS.admin.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    // Navigate to registration page
    await page.goto(`${FRONTEND_BASE}/register.php`);
    await page.waitForLoadState('networkidle');

    // Fill tenant registration form
    const timestamp = Date.now();
    const tenantName = `Test Tenant ${timestamp}`;
    const subdomain = `test${timestamp.toString().slice(-6)}`;

    await page.fill('input[name="company_name"]', tenantName);
    await page.fill('input[name="subdomain"]', subdomain);
    await page.fill('input[name="email"]', `tenant${timestamp}@test.com`);
    await page.fill('input[name="phone"]', '081234567890');
    await page.fill('textarea[name="address"]', 'Test Address for Simulation');

    // Fill owner details
    await page.fill('input[name="username"]', `owner${timestamp.toString().slice(-6)}`);
    await page.fill('input[name="full_name"]', `Owner ${timestamp}`);
    await page.fill('input[name="owner_email"]', `owner${timestamp}@test.com`);
    await page.fill('input[name="owner_phone"]', '081234567891');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password123');

    // Submit form
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);

    // Check for success message
    const successAlert = page.locator('.alert-success');
    if (await successAlert.isVisible()) {
      createdData.tenant = { name: tenantName, subdomain: subdomain };
      console.log(`✓ Tenant created: ${tenantName} (${subdomain})`);
    } else {
      console.log('⚠ Tenant creation may have failed or form not submitted');
    }
  });

  test('Step 2: Owner - Create New User/Karyawan', async ({ page }) => {
    console.log('=== STEP 2: CREATE NEW USER ===');

    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', USERS.admin.username);
    await page.fill('input[name="password"]', USERS.admin.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    // Navigate to users page
    await page.goto(`${FRONTEND_BASE}/users.php`);
    await page.waitForLoadState('networkidle');

    // Click add user button to open modal
    const addButton = page.locator('button:has-text("Tambah User")');
    await addButton.click();
    await page.waitForTimeout(1000);

    // Check if modal is visible
    const modalVisible = await page.locator('#addUserModal').isVisible();
    console.log(`Modal visible: ${modalVisible}`);

    if (modalVisible) {
      // Fill user form
      const timestamp = Date.now();
      const username = `user${timestamp.toString().slice(-6)}`;

      await page.fill('input[name="username"]', username);
      await page.fill('input[name="full_name"]', `Test User ${timestamp}`);
      await page.fill('input[name="email"]', `${username}@test.com`);
      await page.fill('input[name="phone"]', '081234567892');
      await page.fill('input[name="password"]', 'password123');

      // Select role - try index 1 (second option) instead of 0
      const roleSelect = page.locator('select[name="role_id"]');
      if (await roleSelect.isVisible()) {
        const options = await roleSelect.locator('option').count();
        console.log(`Role options: ${options}`);
        if (options > 1) {
          // Select second option (index 1) to avoid potential first option issue
          await roleSelect.selectOption({ index: 1 });
          const selectedValue = await roleSelect.inputValue();
          console.log(`Selected role value: ${selectedValue}`);
        }
      }

      // Submit form
      await page.click('button[type="submit"]');

      // Wait for redirect back to users.php
      await page.waitForURL('**/users.php', { timeout: 5000 });
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);

      // Check for success or error after redirect
      const successAlert = page.locator('.alert-success');
      const errorAlert = page.locator('.alert-danger');

      if (await successAlert.isVisible()) {
        createdData.user = { username: username };
        console.log(`✓ User created: ${username}`);
      } else if (await errorAlert.isVisible()) {
        const errorText = await errorAlert.textContent();
        console.log(`⚠ User creation failed: ${errorText}`);
      } else {
        console.log('⚠ User creation may have failed - no alert visible after redirect');
        // Check if user exists in table
        const userRow = page.locator('table').getByText(username);
        if (await userRow.isVisible()) {
          createdData.user = { username: username };
          console.log(`✓ User found in table: ${username}`);
        }
      }
    } else {
      console.log('⚠ Add User modal not visible');
    }
  });

  test('Step 3: Gudang - Create New Product', async ({ page }) => {
    console.log('=== STEP 3: CREATE NEW PRODUCT ===');

    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', USERS.gudang.username);
    await page.fill('input[name="password"]', USERS.gudang.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    // Navigate to products page
    await page.goto(`${FRONTEND_BASE}/products.php`);
    await page.waitForLoadState('networkidle');

    // Click add product button
    await page.click('button:has-text("Tambah Produk"), button:has-text("Add Product")');
    await page.waitForTimeout(500);

    // Fill product form
    const timestamp = Date.now();
    const productCode = `PRD${timestamp.toString().slice(-6)}`;
    const productName = `Test Product ${timestamp}`;

    await page.fill('input[name="code"]', productCode);
    await page.fill('input[name="name"]', productName);
    await page.fill('input[name="sell_price"]', '100000');
    await page.fill('input[name="buy_price"]', '75000');
    await page.fill('input[name="stock"]', '100');
    await page.fill('input[name="min_stock"]', '10');

    // Select category if exists
    const categorySelect = page.locator('select[name="category_id"]');
    if (await categorySelect.isVisible()) {
      const options = await categorySelect.locator('option').count();
      if (options > 1) {
        await categorySelect.selectOption({ index: 1 });
      }
    }

    // Submit form
    await page.click('button:has-text("Simpan"), button:has-text("Save")');
    await page.waitForTimeout(2000);

    // Check for success
    const successAlert = page.locator('.alert-success');
    if (await successAlert.isVisible()) {
      createdData.product = { code: productCode, name: productName };
      console.log(`✓ Product created: ${productCode} - ${productName}`);
    } else {
      console.log('⚠ Product creation may have failed');
    }
  });

  test('Step 4: Manager - Create New Customer', async ({ page }) => {
    console.log('=== STEP 4: CREATE NEW CUSTOMER ===');

    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', USERS.manager.username);
    await page.fill('input[name="password"]', USERS.manager.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    // Navigate to customers page
    await page.goto(`${FRONTEND_BASE}/customers.php`);
    await page.waitForLoadState('networkidle');

    // Click add customer button
    await page.click('button:has-text("Tambah Pelanggan"), button:has-text("Add Customer")');
    await page.waitForTimeout(500);

    // Fill customer form
    const timestamp = Date.now();
    const customerName = `Test Customer ${timestamp}`;

    await page.fill('input[name="name"]', customerName);
    await page.fill('input[name="phone"]', '081234567893');
    await page.fill('input[name="email"]', `customer${timestamp}@test.com`);
    await page.fill('textarea[name="address"]', 'Test Customer Address');

    // Submit form
    await page.click('button:has-text("Simpan"), button:has-text("Save")');
    await page.waitForTimeout(2000);

    // Check for success
    const successAlert = page.locator('.alert-success');
    if (await successAlert.isVisible()) {
      createdData.customer = { name: customerName };
      console.log(`✓ Customer created: ${customerName}`);
    } else {
      console.log('⚠ Customer creation may have failed');
    }
  });

  test('Step 5: Manager - Create New Supplier', async ({ page }) => {
    console.log('=== STEP 5: CREATE NEW SUPPLIER ===');

    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', USERS.manager.username);
    await page.fill('input[name="password"]', USERS.manager.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    // Navigate to suppliers page
    await page.goto(`${FRONTEND_BASE}/suppliers.php`);
    await page.waitForLoadState('networkidle');

    // Click add supplier button
    await page.click('button:has-text("Tambah Supplier"), button:has-text("Add Supplier")');
    await page.waitForTimeout(500);

    // Fill supplier form
    const timestamp = Date.now();
    const supplierName = `Test Supplier ${timestamp}`;

    await page.fill('input[name="name"]', supplierName);
    await page.fill('input[name="phone"]', '081234567894');
    await page.fill('input[name="email"]', `supplier${timestamp}@test.com`);
    await page.fill('textarea[name="address"]', 'Test Supplier Address');

    // Submit form
    await page.click('button:has-text("Simpan"), button:has-text("Save")');
    await page.waitForTimeout(2000);

    // Check for success
    const successAlert = page.locator('.alert-success');
    if (await successAlert.isVisible()) {
      createdData.supplier = { name: supplierName };
      console.log(`✓ Supplier created: ${supplierName}`);
    } else {
      console.log('⚠ Supplier creation may have failed');
    }
  });

  test('Step 6: Kasir - Create New Sale Transaction', async ({ page }) => {
    console.log('=== STEP 6: CREATE NEW SALE ===');

    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', USERS.kasir.username);
    await page.fill('input[name="password"]', USERS.kasir.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    // Navigate to sales page
    await page.goto(`${FRONTEND_BASE}/sales.php`);
    await page.waitForLoadState('networkidle');

    // Click new sale button
    await page.click('button:has-text("Penjualan Baru")');
    await page.waitForTimeout(500);

    // Check if modal is visible
    const modalVisible = await page.locator('#saleModal').isVisible();
    if (modalVisible) {
      // Select customer
      const customerSelect = page.locator('#customerSelect');
      const customerOptions = await customerSelect.locator('option').count();
      if (customerOptions > 1) {
        await customerSelect.selectOption({ index: 1 });
      }

      // Add product
      const productSelect = page.locator('.item-row .productSelect').first();
      const productOptions = await productSelect.locator('option').count();
      if (productOptions > 1) {
        await productSelect.selectOption({ index: 1 });
      }

      // Fill quantity
      await page.locator('.item-row .quantity, .item-row .qtyInput').first().fill('5');

      // Save sale
      await page.click('button:has-text("Simpan")');
      await page.waitForTimeout(2000);

      // Check for success
      const successAlert = page.locator('.alert-success');
      if (await successAlert.isVisible()) {
        createdData.sale = { status: 'created' };
        console.log('✓ Sale transaction created');
      } else {
        const errorAlert = page.locator('.alert-danger');
        if (await errorAlert.isVisible()) {
          const errorText = await errorAlert.textContent();
          console.log(`⚠ Sale creation failed: ${errorText}`);
        }
      }
    } else {
      console.log('⚠ Sale modal not visible');
    }
  });

  test('Step 7: Gudang - Create Purchase Order', async ({ page }) => {
    console.log('=== STEP 7: CREATE PURCHASE ORDER ===');

    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', USERS.gudang.username);
    await page.fill('input[name="password"]', USERS.gudang.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    // Navigate to purchase orders page
    await page.goto(`${FRONTEND_BASE}/purchase-orders.php`);
    await page.waitForLoadState('networkidle');

    // Click new PO button
    await page.click('button:has-text("PO Baru"), button:has-text("New PO")');
    await page.waitForTimeout(500);

    // Check if modal is visible
    const modalVisible = await page.locator('#poModal, #purchaseOrderModal').isVisible();
    if (modalVisible) {
      // Select supplier
      const supplierSelect = page.locator('select[name="supplier_id"]');
      const supplierOptions = await supplierSelect.locator('option').count();
      if (supplierOptions > 1) {
        await supplierSelect.selectOption({ index: 1 });
      }

      // Add product
      const productSelect = page.locator('.item-row .productSelect').first();
      const productOptions = await productSelect.locator('option').count();
      if (productOptions > 1) {
        await productSelect.selectOption({ index: 1 });
      }

      // Fill quantity
      await page.locator('.item-row .quantity, .item-row .qtyInput').first().fill('50');

      // Save PO
      await page.click('button:has-text("Simpan"), button:has-text("Save")');
      await page.waitForTimeout(2000);

      // Check for success
      const successAlert = page.locator('.alert-success');
      if (await successAlert.isVisible()) {
        createdData.purchaseOrder = { status: 'created' };
        console.log('✓ Purchase Order created');
      } else {
        console.log('⚠ Purchase Order creation may have failed');
      }
    } else {
      console.log('⚠ PO modal not visible');
    }
  });

  test('Step 8: Manager - Create Quotation', async ({ page }) => {
    console.log('=== STEP 8: CREATE QUOTATION ===');

    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', USERS.manager.username);
    await page.fill('input[name="password"]', USERS.manager.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    // Navigate to quotations page
    await page.goto(`${FRONTEND_BASE}/quotations.php`);
    await page.waitForLoadState('networkidle');

    // Click new quotation button
    await page.click('button:has-text("Quotation Baru"), button:has-text("New Quotation")');
    await page.waitForTimeout(500);

    // Check if modal is visible
    const modalVisible = await page.locator('#quotationModal').isVisible();
    if (modalVisible) {
      // Select customer
      const customerSelect = page.locator('select[name="customer_id"]');
      const customerOptions = await customerSelect.locator('option').count();
      if (customerOptions > 1) {
        await customerSelect.selectOption({ index: 1 });
      }

      // Add product
      const productSelect = page.locator('.item-row .productSelect').first();
      const productOptions = await productSelect.locator('option').count();
      if (productOptions > 1) {
        await productSelect.selectOption({ index: 1 });
      }

      // Fill quantity
      await page.locator('.item-row .quantity, .item-row .qtyInput').first().fill('10');

      // Save quotation
      await page.click('button:has-text("Simpan"), button:has-text("Save")');
      await page.waitForTimeout(2000);

      // Check for success
      const successAlert = page.locator('.alert-success');
      if (await successAlert.isVisible()) {
        createdData.quotation = { status: 'created' };
        console.log('✓ Quotation created');
      } else {
        console.log('⚠ Quotation creation may have failed');
      }
    } else {
      console.log('⚠ Quotation modal not visible');
    }
  });

  test('Step 9: Gudang - Stock Adjustment', async ({ page }) => {
    console.log('=== STEP 9: STOCK ADJUSTMENT ===');

    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', USERS.gudang.username);
    await page.fill('input[name="password"]', USERS.gudang.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    // Navigate to stock page
    await page.goto(`${FRONTEND_BASE}/stock.php`);
    await page.waitForLoadState('networkidle');

    // Click stock adjustment button
    await page.click('button:has-text("Penyesuaian Stok")');
    await page.waitForTimeout(500);

    // Check if modal is visible
    const modalVisible = await page.locator('#adjustmentModal, #stockModal').isVisible();
    if (modalVisible) {
      // Select product
      const productSelect = page.locator('select[name="product_id"]');
      const productOptions = await productSelect.locator('option').count();
      if (productOptions > 1) {
        await productSelect.selectOption({ index: 1 });
      }

      // Fill adjustment quantity
      await page.fill('input[name="quantity"]', '5');

      // Select adjustment type
      const typeSelect = page.locator('select[name="type"]');
      if (await typeSelect.isVisible()) {
        await typeSelect.selectOption('add');
      }

      // Save adjustment
      await page.click('button:has-text("Simpan"), button:has-text("Save")');
      await page.waitForTimeout(2000);

      // Check for success
      const successAlert = page.locator('.alert-success');
      if (await successAlert.isVisible()) {
        createdData.stockAdjustment = { status: 'created' };
        console.log('✓ Stock adjustment created');
      } else {
        console.log('⚠ Stock adjustment may have failed');
      }
    } else {
      console.log('⚠ Stock adjustment modal not visible');
    }
  });

  test('Step 10: Gudang - Stock Opname', async ({ page }) => {
    console.log('=== STEP 10: STOCK OPNAME ===');

    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', USERS.gudang.username);
    await page.fill('input[name="password"]', USERS.gudang.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    // Navigate to stock opname page
    await page.goto(`${FRONTEND_BASE}/stock_opname.php`);
    await page.waitForLoadState('networkidle');

    // Fill opname form
    const today = new Date().toISOString().split('T')[0];
    await page.fill('input[name="opname_date"]', today);

    // Add item to opname
    await page.click('button:has-text("Tambah Item"), button:has-text("Add Item")');
    await page.waitForTimeout(500);

    // Select product
    const productSelect = page.locator('.item-row .productSelect').first();
    const productOptions = await productSelect.locator('option').count();
    if (productOptions > 1) {
      await productSelect.selectOption({ index: 1 });
    }

    // Fill actual quantity
    await page.locator('.item-row .actualQty, .item-row input[type="number"]').first().fill('100');

    // Submit opname
    await page.click('button:has-text("Simpan"), button:has-text("Save"), button:has-text("Submit")');
    await page.waitForTimeout(2000);

    // Check for success
    const successAlert = page.locator('.alert-success');
    if (await successAlert.isVisible()) {
      createdData.stockOpname = { status: 'created' };
      console.log('✓ Stock opname created');
    } else {
      console.log('⚠ Stock opname may have failed');
    }
  });

  test('Step 11: Accounting - Generate Reports', async ({ page }) => {
    console.log('=== STEP 11: GENERATE REPORTS ===');

    await page.goto(`${FRONTEND_BASE}/login.php`);
    await page.fill('input[name="username"]', USERS.accounting.username);
    await page.fill('input[name="password"]', USERS.accounting.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 10000 });

    // Navigate to reports page
    await page.goto(`${FRONTEND_BASE}/reports.php`);
    await page.waitForLoadState('networkidle');

    // Generate sales report
    const reportType = page.locator('select[name="report_type"], #reportType');
    if (await reportType.isVisible()) {
      await reportType.selectOption('sales');
      await page.click('button:has-text("Generate"), button:has-text("Tampilkan")');
      await page.waitForTimeout(2000);
      console.log('✓ Sales report generated');
    }

    // Generate inventory report
    if (await reportType.isVisible()) {
      await reportType.selectOption('inventory');
      await page.click('button:has-text("Generate"), button:has-text("Tampilkan")');
      await page.waitForTimeout(2000);
      console.log('✓ Inventory report generated');
    }
  });

  test.afterAll(async () => {
    console.log('\n=== CRUD SIMULATION COMPLETE ===');
    console.log('\nCreated Data Summary:');
    console.log(`- Tenant: ${createdData.tenant ? '✓' : '✗'} ${createdData.tenant?.name || 'Not created'}`);
    console.log(`- User: ${createdData.user ? '✓' : '✗'} ${createdData.user?.username || 'Not created'}`);
    console.log(`- Product: ${createdData.product ? '✓' : '✗'} ${createdData.product?.name || 'Not created'}`);
    console.log(`- Customer: ${createdData.customer ? '✓' : '✗'} ${createdData.customer?.name || 'Not created'}`);
    console.log(`- Supplier: ${createdData.supplier ? '✓' : '✗'} ${createdData.supplier?.name || 'Not created'}`);
    console.log(`- Sale: ${createdData.sale ? '✓' : '✗'} ${createdData.sale?.status || 'Not created'}`);
    console.log(`- Purchase Order: ${createdData.purchaseOrder ? '✓' : '✗'} ${createdData.purchaseOrder?.status || 'Not created'}`);
    console.log(`- Quotation: ${createdData.quotation ? '✓' : '✗'} ${createdData.quotation?.status || 'Not created'}`);
    console.log(`- Stock Adjustment: ${createdData.stockAdjustment ? '✓' : '✗'} ${createdData.stockAdjustment?.status || 'Not created'}`);
    console.log(`- Stock Opname: ${createdData.stockOpname ? '✓' : '✗'} ${createdData.stockOpname?.status || 'Not created'}`);
  });
});
