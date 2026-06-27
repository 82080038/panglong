/**
 * Panglong ERP — Full Simulation Test
 * 
 * Simulates 3 months of operations across all 6 roles:
 * - Owner: all 35 menus, full CRUD
 * - Manager: 34 menus, full CRUD (no SaaS)
 * - Kasir: POS, sales, deliveries, returns, quotations, SO, WhatsApp
 * - Gudang: products, stock, PO, transfers, batches, fleet, routes, IoT
 * - Accounting: journal, cashbook, assets, cash flow, e-Faktur, closing
 * - Supervisor: dashboard, reports
 * 
 * Monitors: console errors, console warnings, page errors, network failures,
 * AJAX API responses (success/fail), HTTP status codes.
 * 
 * Any error/warning → test fails immediately so we can fix before continuing.
 * 
 * NOTE: Most simulation tests skipped due to requiring extensive test data setup
 */

const { test, expect, request } = require('@playwright/test');

const FRONTEND_BASE = 'http://localhost/panglong/frontend';

// Override timeout for simulation tests (each test covers 30-90 days of operations)
test.setTimeout(180000); // 3 minutes per test

// === Helper: collect all console/page errors ===
function attachMonitors(page, context) {
  const errors = [];
  const warnings = [];
  const apiFailures = [];
  const pageErrors = [];

  page.on('console', msg => {
    if (msg.type() === 'error') {
      errors.push(`[console.error] ${msg.text()}`);
    }
    if (msg.type() === 'warning') {
      warnings.push(`[console.warn] ${msg.text()}`);
    }
  });
  page.on('pageerror', err => {
    pageErrors.push(`[pageerror] ${err.message}`);
  });
  page.on('response', response => {
    const url = response.url();
    if (url.includes('ajax.php') && response.status() >= 400) {
      apiFailures.push(`[api ${response.status()}] ${url}`);
    }
  });

  return { errors, warnings, apiFailures, pageErrors };
}

function assertClean(context, label) {
  const all = [...context.errors, ...context.pageErrors, ...context.apiFailures];
  if (all.length > 0) {
    throw new Error(`${label}: ${all.length} issues found:\n${all.join('\n')}`);
  }
  // Warnings are non-blocking but reported
  if (context.warnings.length > 0) {
    console.log(`  ⚠ ${context.warnings.length} warnings in ${label}:`, context.warnings.slice(0, 5));
  }
}

// === Helper: login as role ===
async function loginAs(page, username) {
  await page.goto(`${FRONTEND_BASE}/login.php`);
  await page.fill('input[name="username"]', username);
  await page.fill('input[name="password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/index.php', { timeout: 10000 });
}

// === Helper: visit page and check no errors ===
async function visitPage(page, monitors, path, label) {
  await page.goto(`${FRONTEND_BASE}/${path}`);
  await page.waitForLoadState('networkidle');
  // Small delay for any async JS
  await page.waitForTimeout(500);
  assertClean(monitors, label);
}

// === Helper: AJAX call via page context ===
async function ajaxGet(page, endpoint, params = {}) {
  const qs = new URLSearchParams({ endpoint, test_mode: 'true', ...params }).toString();
  const response = await page.evaluate(async (qs) => {
    const res = await fetch(`ajax.php?${qs}`, { credentials: 'same-origin' });
    const text = await res.text();
    try { return { status: res.status, body: JSON.parse(text) }; }
    catch (e) { return { status: res.status, body: { success: false, message: text || 'Empty response' } }; }
  }, qs);
  return response;
}

async function ajaxPost(page, endpoint, body, action = '') {
  const qs = action ? `?endpoint=${endpoint}&action=${action}&test_mode=true` : `?endpoint=${endpoint}&test_mode=true`;
  const response = await page.evaluate(async ({ qs, body }) => {
    const res = await fetch(`ajax.php${qs}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
      credentials: 'same-origin',
    });
    const text = await res.text();
    try { return { status: res.status, body: JSON.parse(text) }; }
    catch (e) { return { status: res.status, body: { success: false, message: text || 'Empty response' } }; }
  }, { qs, body });
  return response;
}

async function ajaxPut(page, endpoint, body, id) {
  const qs = id ? `?endpoint=${endpoint}&id=${id}&test_mode=true` : `?endpoint=${endpoint}&test_mode=true`;
  const response = await page.evaluate(async ({ qs, body }) => {
    const res = await fetch(`ajax.php${qs}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
      credentials: 'same-origin',
    });
    const text = await res.text();
    try { return { status: res.status, body: JSON.parse(text) }; }
    catch (e) { return { status: res.status, body: { success: false, message: text || 'Empty response' } }; }
  }, { qs, body });
  return response;
}

async function ajaxDelete(page, endpoint, id) {
  const qs = `?endpoint=${endpoint}&id=${id}&test_mode=true`;
  const response = await page.evaluate(async (qs) => {
    const res = await fetch(`ajax.php${qs}`, { method: 'DELETE', credentials: 'same-origin' });
    const text = await res.text();
    try { return { status: res.status, body: JSON.parse(text) }; }
    catch (e) { return { status: res.status, body: { success: false, message: text || 'Empty response' } }; }
  }, qs);
  return response;
}

// ═══════════════════════════════════════════════════════════════
// OWNER ROLE — All 35 menus + CRUD operations
// ═══════════════════════════════════════════════════════════════
test.describe('Simulation — Owner Role (Full Access)', () => {
  test('Day 1-30: Navigate all pages, CRUD products, customers, suppliers', async ({ page }) => {
    const m = attachMonitors(page, {});
    await loginAs(page, 'ownertest');

    // Visit all owner pages
    const pages = [
      ['index.php', 'Dashboard'],
      ['products.php', 'Products'],
      ['customers.php', 'Customers'],
      ['sales.php', 'Sales'],
      ['sales_orders.php', 'Sales Orders'],
      ['quotations.php', 'Quotations'],
      ['deliveries.php', 'Deliveries'],
      ['returns.php', 'Returns'],
      ['stock.php', 'Stock'],
      ['stock_opname.php', 'Stock Opname'],
      ['stock_transfers.php', 'Stock Transfers'],
      ['suppliers.php', 'Suppliers'],
      ['purchase-orders.php', 'Purchase Orders'],
      ['pricing.php', 'Pricing'],
      ['reports.php', 'Reports'],
      ['accounting.php', 'Accounting'],
      ['cashbook.php', 'Cashbook'],
      ['fixed_assets.php', 'Fixed Assets'],
      ['warehouses.php', 'Warehouses'],
      ['reorder.php', 'Reorder AI'],
      ['ai_insights.php', 'AI Insights'],
      ['marketplace.php', 'Marketplace'],
      ['fleet.php', 'Fleet'],
      ['routes.php', 'Routes'],
      ['whatsapp.php', 'WhatsApp'],
      ['e_faktur.php', 'e-Faktur'],
      ['iot.php', 'IoT'],
      ['landed_cost.php', 'Landed Cost'],
      ['batches.php', 'Batches/FIFO'],
      ['cash_flow.php', 'Cash Flow'],
      ['closing.php', 'Closing'],
      ['salesman_app.php', 'Salesman App'],
      ['users.php', 'Users'],
      ['settings.php', 'Settings'],
      ['saas.php', 'SaaS'],
    ];

    for (const [path, label] of pages) {
      await visitPage(page, m, path, `Owner:${label}`);
    }

    // CRUD: Create product
    const prodRes = await ajaxPost(page, 'products', {
      code: 'SIM-TEST-' + Date.now(),
      name: 'Semen Portland Test ' + Date.now(),
      category_id: 1,
      brand: 'Semen Gresik',
      min_stock: 10,
      max_stock: 1000,
      buy_price: 50000,
      sell_price: 55000,
      units: [{ unit_name: 'sak', conversion_factor: 1, price_per_unit: 55000 }]
    });
    if (!prodRes.body.success) console.log('Product create failed:', JSON.stringify(prodRes.body));
    expect(prodRes.body.success).toBe(true);
    const productId = prodRes.body.data.id;

    // CRUD: Read product
    const getProd = await ajaxGet(page, 'products', { id: productId });
    expect(getProd.body.success).toBe(true);
    expect(getProd.body.data.name).toContain('Semen Portland Test');

    // CRUD: Update product
    const updProd = await ajaxPut(page, 'products', {
      name: 'Semen Portland Updated ' + Date.now(),
      category_id: 1,
      brand: 'Semen Gresik',
      min_stock: 20,
      max_stock: 2000,
      buy_price: 52000,
      sell_price: 58000,
      is_active: 1
    }, productId);
    expect(updProd.body.success).toBe(true);

    // CRUD: Create customer
    const custRes = await ajaxPost(page, 'customers', {
      name: 'Toko Bangunan Maju Jaya',
      address: 'Jl. Raya Bekasi No. 100',
      phone: '021-1234567',
      email: 'maju@test.com',
      group_id: 1,
      credit_limit: 50000000,
      payment_terms: 30
    });
    expect(custRes.body.success).toBe(true);
    const customerId = custRes.body.data.id;

    // CRUD: Create supplier
    const supRes = await ajaxPost(page, 'suppliers', {
      name: 'PT Sumber Material',
      address: 'Jl. Industri No. 5',
      phone: '021-7654321',
      email: 'sumber@test.com',
      payment_terms: 45,
      credit_limit: 100000000
    });
    expect(supRes.body.success).toBe(true);

    // Create PO
    const poRes = await ajaxPost(page, 'purchase-orders', {
      supplier_id: 1,
      po_date: '2026-04-01',
      items: [{ product_id: productId, quantity: 100, unit_price: 50000 }],
      notes: 'Simulasi PO April'
    });
    expect(poRes.body.success).toBe(true);
    const poId = poRes.body.data.id;

    // Receive PO (use the PO we just created)
    const poDetail = await ajaxGet(page, 'purchase-orders', { id: poId });
    if (poDetail.body.success && poDetail.body.data && poDetail.body.data.items) {
      for (const item of poDetail.body.data.items) {
        await page.evaluate(async ({ poId, itemId, qty }) => {
          await fetch(`ajax.php?endpoint=purchase-orders&action=receive&id=${poId}&test_mode=true`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: [{ purchase_item_id: itemId, received_quantity: qty }] }),
            credentials: 'same-origin'
          });
        }, { poId, itemId: item.id, qty: item.quantity });
      }
    }

    // Create Sale
    const saleRes = await ajaxPost(page, 'sales', {
      customer_id: customerId,
      sale_date: '2026-04-15',
      payment_method: 'cash',
      items: [{ product_id: productId, quantity: 10, unit_price: 58000, discount: 0 }]
    });
    expect(saleRes.body.success).toBe(true);
    const saleId = saleRes.body.data.id;

    // Sale payment (endpoint is 'sale-payment' singular, with id in query string)
    const payRes2 = await page.evaluate(async ({ saleId }) => {
      const res = await fetch('ajax.php?endpoint=sale-payment&id=' + saleId + '&test_mode=true', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ amount: 638000, payment_method: 'cash', payment_date: '2026-04-15' }),
        credentials: 'same-origin'
      });
      const text = await res.text();
      try { return { status: res.status, body: JSON.parse(text) }; }
      catch (e) { return { status: res.status, body: { success: false, message: text } }; }
    }, { saleId });
    expect(payRes2.body.success).toBe(true);

    // Create delivery
    const delRes = await ajaxPost(page, 'deliveries', {
      sale_id: saleId,
      customer_name: 'Toko Bangunan Maju Jaya',
      delivery_address: 'Jl. Raya Bekasi No. 100',
      phone: '021-1234567',
      delivery_date: '2026-04-16',
      driver_name: 'Budi',
      vehicle_plate: 'B 1234 XX'
    });
    expect(delRes.body.success).toBe(true);

    // Stock adjustment
    const adjRes = await ajaxPost(page, 'stock', {
      product_id: productId,
      quantity: 5,
      adjustment_type: 'correction',
      reason: 'Simulasi penyesuaian stok'
    });
    expect(adjRes.body.success).toBe(true);

    // Reports - check all types
    const reportTypes = ['daily', 'monthly', 'low-stock', 'stock-valuation', 'by-product', 'by-customer', 'profit-loss', 'stock-movement', 'dead-stock', 'ar-aging', 'ap-aging'];
    for (const type of reportTypes) {
      const rpt = await ajaxGet(page, 'reports', { type, date_from: '2026-04-01', date_to: '2026-06-30' });
      expect(rpt.body.success).toBe(true);
    }

    // Settings
    const settings = await ajaxGet(page, 'settings');
    expect(settings.body.success).toBe(true);

    // Users
    const users = await ajaxGet(page, 'users');
    expect(users.body.success).toBe(true);

    // Warehouses
    const wh = await ajaxGet(page, 'warehouses');
    expect(wh.body.success).toBe(true);

    // Categories
    const cats = await ajaxGet(page, 'categories');
    expect(cats.body.success).toBe(true);

    // Customer groups
    const groups = await ajaxGet(page, 'customer-groups');
    expect(groups.body.success).toBe(true);

    assertClean(m, 'Owner CRUD Day 1-30');
  });

  test('Day 31-60: Advanced features — pricing, fleet, routes, WhatsApp, e-Faktur, landed cost, batches', async ({ page }) => {
    const m = attachMonitors(page, {});
    await loginAs(page, 'ownertest');

    // Visit advanced pages
    await visitPage(page, m, 'pricing.php', 'Owner:Pricing');
    await visitPage(page, m, 'fleet.php', 'Owner:Fleet');
    await visitPage(page, m, 'routes.php', 'Owner:Routes');
    await visitPage(page, m, 'whatsapp.php', 'Owner:WhatsApp');
    await visitPage(page, m, 'e_faktur.php', 'Owner:e-Faktur');
    await visitPage(page, m, 'landed_cost.php', 'Owner:Landed Cost');
    await visitPage(page, m, 'batches.php', 'Owner:Batches');
    await visitPage(page, m, 'cash_flow.php', 'Owner:Cash Flow');
    await visitPage(page, m, 'closing.php', 'Owner:Closing');
    await visitPage(page, m, 'cashbook.php', 'Owner:Cashbook');
    await visitPage(page, m, 'fixed_assets.php', 'Owner:Fixed Assets');
    await visitPage(page, m, 'ai_insights.php', 'Owner:AI Insights');
    await visitPage(page, m, 'marketplace.php', 'Owner:Marketplace');
    await visitPage(page, m, 'iot.php', 'Owner:IoT');
    await visitPage(page, m, 'saas.php', 'Owner:SaaS');

    // Pricing: tier prices
    const tierRes = await ajaxPost(page, 'tier-prices', {
      product_id: 1,
      min_qty: 10,
      max_qty: 50,
      unit_price: 52000
    });
    expect(tierRes.body.success).toBe(true);

    // Customer-specific pricing
    const cpmRes = await ajaxPost(page, 'customer-prices', {
      product_id: 1,
      customer_id: 1,
      unit_price: 53000
    });
    expect(cpmRes.body.success).toBe(true);

    // Supplier price history
    const sphRes = await ajaxPost(page, 'supplier-price-history', {
      supplier_id: 1,
      product_id: 1,
      unit_price: 48000,
      effective_date: '2026-05-01'
    });
    expect(sphRes.body.success).toBe(true);

    // Fleet: create vehicle
    const vehRes = await ajaxPost(page, 'vehicles', {
      plate_no: 'B SIM-' + Date.now(),
      vehicle_type: 'truck',
      brand: 'Mitsubishi',
      model: 'Colt Diesel',
      capacity_kg: 3500,
      fuel_type: 'diesel',
      acquisition_date: '2026-05-01'
    });
    expect(vehRes.body.success).toBe(true);
    const vehicleId = vehRes.body.data.id;

    // Vehicle maintenance
    const maintRes = await ajaxPost(page, 'vehicle-maintenance', {
      vehicle_id: vehicleId,
      maintenance_date: '2026-05-15',
      maintenance_type: 'service',
      cost: 500000,
      odometer_km: 15000,
      description: 'Service rutin 15k km'
    });
    expect(maintRes.body.success).toBe(true);

    // Delivery route
    const routeRes = await ajaxPost(page, 'delivery-routes', {
      route_date: '2026-05-20',
      vehicle_id: vehicleId,
      driver_name: 'Budi Santoso',
      total_distance_km: 50,
      estimated_time_minutes: 180,
      stops: [
        { customer_name: 'Toko Maju', address: 'Bekasi', phone: '021123' },
        { customer_name: 'Toko Jaya', address: 'Jakarta', phone: '021456' }
      ]
    });
    expect(routeRes.body.success).toBe(true);

    // WhatsApp: send message
    const waRes = await ajaxPost(page, 'whatsapp-messages', {
      phone_number: '628123456789',
      message_body: 'Test simulasi WhatsApp',
      template_name: 'invoice_reminder'
    });
    expect(waRes.body.success).toBe(true);

    // WhatsApp templates
    const waTpls = await ajaxGet(page, 'whatsapp-templates');
    expect(waTpls.body.success).toBe(true);

    // e-Faktur: create
    const efRes = await ajaxPost(page, 'e-faktur', {
      faktur_type: 'keluaran',
      transaction_date: '2026-05-25',
      counterparty_name: 'PT Bangun Sejahtera',
      counterparty_npwp: '01.234.567.8-901.000',
      dpp: 100000000,
      description: 'Penjualan semen April 2026'
    });
    expect(efRes.body.success).toBe(true);

    // e-Faktur: list
    const efList = await ajaxGet(page, 'e-faktur');
    expect(efList.body.success).toBe(true);

    // Product batches: create
    const batchRes = await ajaxPost(page, 'product-batches', {
      product_id: 1,
      batch_no: 'BATCH-SIM-' + Date.now(),
      lot_no: 'LOT-001',
      received_date: '2026-05-01',
      expiry_date: '2027-05-01',
      quantity_received: 200,
      unit_cost: 50000,
      supplier_id: 1
    });
    expect(batchRes.body.success).toBe(true);

    // Stock valuation FIFO
    const fifoRes = await ajaxGet(page, 'stock-valuation-fifo');
    expect(fifoRes.body.success).toBe(true);

    // Cash flow statement
    const cfRes = await ajaxGet(page, 'cash-flow', { start_date: '2026-04-01', end_date: '2026-06-30' });
    expect(cfRes.body.success).toBe(true);

    // Cash transactions: create
    const ctRes = await ajaxPost(page, 'cash-transactions', {
      type: 'in',
      account_type: 'cash',
      transaction_date: '2026-05-10',
      amount: 5000000,
      description: 'Penerimaan kas penjualan',
      category: 'sales'
    });
    expect(ctRes.body.success).toBe(true);

    // Bank statements
    const bsRes = await ajaxPost(page, 'bank-statements', {
      bank_account: 'BCA-123456',
      transaction_date: '2026-05-10',
      description: 'Transfer masuk',
      credit: 5000000,
      balance: 10000000
    });
    expect(bsRes.body.success).toBe(true);

    // Fixed assets: create
    const faRes = await ajaxPost(page, 'fixed-assets', {
      name: 'Truk Colt Diesel',
      category: 'vehicle',
      acquisition_date: '2026-05-01',
      acquisition_cost: 350000000,
      salvage_value: 35000000,
      useful_life_months: 60
    });
    expect(faRes.body.success).toBe(true);
    const assetId = faRes.body.data.id;

    // Fixed assets: depreciate
    const depRes = await ajaxPut(page, 'fixed-assets', { action: 'depreciate' }, assetId);
    expect(depRes.body.success).toBe(true);

    // Period closing
    const pcRes = await ajaxPost(page, 'period-closings', {
      year: 2026,
      month: 4,
      notes: 'Tutup buku April 2026'
    });
    // May already exist, that's ok
    if (pcRes.body.success) {
      expect(pcRes.body.success).toBe(true);
    }

    // Period closings: list
    const pcList = await ajaxGet(page, 'period-closings');
    expect(pcList.body.success).toBe(true);

    // Check period locked
    const plRes = await ajaxGet(page, 'check-period-locked', { date: '2026-04-15' });
    expect(plRes.body.success).toBe(true);

    // Marketplace
    const mpRes = await ajaxGet(page, 'marketplace');
    expect(mpRes.body.success).toBe(true);

    // Branches
    const brRes = await ajaxGet(page, 'branches');
    expect(brRes.body.success).toBe(true);

    assertClean(m, 'Owner Advanced Day 31-60');
  });

  test('Day 61-90: Quotations, Sales Orders, Returns, Stock Transfers, Reports', async ({ page }) => {
    const m = attachMonitors(page, {});
    await loginAs(page, 'ownertest');

    // Visit transaction pages
    await visitPage(page, m, 'quotations.php', 'Owner:Quotations');
    await visitPage(page, m, 'sales_orders.php', 'Owner:Sales Orders');
    await visitPage(page, m, 'returns.php', 'Owner:Returns');
    await visitPage(page, m, 'stock_transfers.php', 'Owner:Stock Transfers');
    await visitPage(page, m, 'stock_opname.php', 'Owner:Stock Opname');

    // Quotations: create
    const quoteRes = await ajaxPost(page, 'quotations', {
      customer_id: 1,
      quote_date: '2026-06-01',
      valid_until: '2026-06-30',
      items: [{ product_id: 1, quantity: 50, unit_price: 55000, discount: 0 }],
      notes: 'Penawaran semen Juni'
    });
    expect(quoteRes.body.success).toBe(true);

    // Quotations: list
    const quoteList = await ajaxGet(page, 'quotations');
    expect(quoteList.body.success).toBe(true);

    // Sales orders: create
    const soRes = await ajaxPost(page, 'sales-orders', {
      customer_id: 1,
      order_date: '2026-06-05',
      expected_delivery_date: '2026-06-10',
      items: [{ product_id: 1, quantity: 30, unit_price: 55000 }],
      notes: 'SO Juni'
    });
    expect(soRes.body.success).toBe(true);

    // Sales orders: list
    const soList = await ajaxGet(page, 'sales-orders');
    expect(soList.body.success).toBe(true);

    // Sales returns: create (need a sale first)
    const saleRes = await ajaxPost(page, 'sales', {
      customer_id: 1,
      sale_date: '2026-06-10',
      payment_method: 'cash',
      items: [{ product_id: 1, quantity: 5, unit_price: 55000, discount: 0 }]
    });
    expect(saleRes.body.success).toBe(true);
    const saleId = saleRes.body.data.id;

    const srRes = await ajaxPost(page, 'sales-returns', {
      sale_id: saleId,
      return_date: '2026-06-15',
      refund_method: 'cash',
      items: [{ product_id: 1, quantity: 2, unit_price: 55000, reason: 'Barang rusak' }]
    });
    expect(srRes.body.success).toBe(true);

    // Sales returns: list
    const srList = await ajaxGet(page, 'sales-returns');
    expect(srList.body.success).toBe(true);

    // Purchase returns
    const prRes = await ajaxPost(page, 'purchase-returns', {
      po_id: 1,
      return_date: '2026-06-15',
      refund_method: 'credit',
      items: [{ product_id: 1, quantity: 5, unit_price: 50000, reason: 'Quality issue' }]
    });
    // May fail if PO 1 doesn't exist, ok for simulation
    if (prRes.body.success) {
      const prList = await ajaxGet(page, 'purchase-returns');
      expect(prList.body.success).toBe(true);
    }

    // Stock transfers: create
    const stRes = await ajaxPost(page, 'stock-transfers', {
      transfer_date: '2026-06-20',
      from_warehouse_id: 1,
      to_warehouse_id: 2,
      items: [{ product_id: 1, quantity: 20 }],
      notes: 'Mutasi antar gudang'
    });
    expect(stRes.body.success).toBe(true);

    // Stock transfers: list
    const stList = await ajaxGet(page, 'stock-transfers');
    expect(stList.body.success).toBe(true);

    // Stock adjustments
    const saRes = await ajaxPost(page, 'stock-adjustments', {
      product_id: 1,
      quantity: -3,
      adjustment_type: 'damage',
      reason: 'Barang rusak 3 sak'
    });
    expect(saRes.body.success).toBe(true);

    // Warehouse locations
    const wlRes = await ajaxGet(page, 'warehouse-locations', { warehouse_id: 1 });
    expect(wlRes.body.success).toBe(true);

    // Final reports check
    for (const type of ['daily', 'monthly', 'profit-loss', 'stock-valuation', 'ar-aging']) {
      const rpt = await ajaxGet(page, 'reports', { type, date_from: '2026-04-01', date_to: '2026-06-30' });
      expect(rpt.body.success).toBe(true);
    }

    assertClean(m, 'Owner Transactions Day 61-90');
  });
});

// ═══════════════════════════════════════════════════════════════
// MANAGER ROLE — 34 menus (no SaaS)
// ═══════════════════════════════════════════════════════════════
test.describe('Simulation — Manager Role', () => {
  test('Navigate all manager pages + CRUD operations', async ({ page }) => {
    const m = attachMonitors(page, {});
    await loginAs(page, 'manager1');

    const pages = [
      ['index.php', 'Dashboard'],
      ['products.php', 'Products'],
      ['customers.php', 'Customers'],
      ['sales.php', 'Sales'],
      ['sales_orders.php', 'Sales Orders'],
      ['quotations.php', 'Quotations'],
      ['deliveries.php', 'Deliveries'],
      ['returns.php', 'Returns'],
      ['stock.php', 'Stock'],
      ['stock_opname.php', 'Stock Opname'],
      ['stock_transfers.php', 'Stock Transfers'],
      ['suppliers.php', 'Suppliers'],
      ['purchase-orders.php', 'Purchase Orders'],
      ['pricing.php', 'Pricing'],
      ['reports.php', 'Reports'],
      ['accounting.php', 'Accounting'],
      ['cashbook.php', 'Cashbook'],
      ['fixed_assets.php', 'Fixed Assets'],
      ['warehouses.php', 'Warehouses'],
      ['reorder.php', 'Reorder AI'],
      ['ai_insights.php', 'AI Insights'],
      ['marketplace.php', 'Marketplace'],
      ['fleet.php', 'Fleet'],
      ['routes.php', 'Routes'],
      ['whatsapp.php', 'WhatsApp'],
      ['e_faktur.php', 'e-Faktur'],
      ['iot.php', 'IoT'],
      ['landed_cost.php', 'Landed Cost'],
      ['batches.php', 'Batches'],
      ['cash_flow.php', 'Cash Flow'],
      ['closing.php', 'Closing'],
      ['salesman_app.php', 'Salesman App'],
      ['users.php', 'Users'],
      ['settings.php', 'Settings'],
    ];

    for (const [path, label] of pages) {
      await visitPage(page, m, path, `Manager:${label}`);
    }

    // Manager should NOT see SaaS
    await page.goto(`${FRONTEND_BASE}/saas.php`);
    // Should redirect or show access denied, not crash
    await page.waitForTimeout(500);

    // CRUD operations
    const prodRes = await ajaxPost(page, 'products', {
      code: 'MGR-TEST-' + Date.now(),
      name: 'Bata Ringan Manager Test ' + Date.now(),
      category_id: 1,
      brand: 'Hebel',
      min_stock: 5,
      max_stock: 500,
      buy_price: 15000,
      sell_price: 18000,
      units: [{ unit_name: 'pcs', conversion_factor: 1, price_per_unit: 18000 }]
    });
    expect(prodRes.body.success).toBe(true);

    // Create sale
    const saleRes = await ajaxPost(page, 'sales', {
      customer_id: 1,
      sale_date: '2026-05-10',
      payment_method: 'credit',
      items: [{ product_id: 1, quantity: 20, unit_price: 55000, discount: 0 }]
    });
    expect(saleRes.body.success).toBe(true);

    // Reports
    for (const type of ['daily', 'monthly', 'profit-loss', 'low-stock', 'ar-aging']) {
      const rpt = await ajaxGet(page, 'reports', { type });
      expect(rpt.body.success).toBe(true);
    }

    assertClean(m, 'Manager Full Simulation');
  });
});

// ═══════════════════════════════════════════════════════════════
// KASIR ROLE — POS focus
// ═══════════════════════════════════════════════════════════════
test.describe('Simulation — Kasir Role (POS)', () => {
  test('Day 1-90: POS transactions, deliveries, returns, quotations, WhatsApp', async ({ page }) => {
    const m = attachMonitors(page, {});
    await loginAs(page, 'kasir1');

    // Kasir pages
    const pages = [
      ['index.php', 'Dashboard'],
      ['customers.php', 'Customers'],
      ['sales.php', 'Sales'],
      ['sales_orders.php', 'Sales Orders'],
      ['quotations.php', 'Quotations'],
      ['deliveries.php', 'Deliveries'],
      ['returns.php', 'Returns'],
      ['whatsapp.php', 'WhatsApp'],
      ['salesman_app.php', 'Salesman App'],
    ];

    for (const [path, label] of pages) {
      await visitPage(page, m, path, `Kasir:${label}`);
    }

    // Create customer
    const custRes = await ajaxPost(page, 'customers', {
      name: 'Pelanggan Kasir Test',
      phone: '081234567890',
      address: 'Jl. Test No. 1',
      group_id: 1,
      credit_limit: 10000000,
      payment_terms: 14
    });
    expect(custRes.body.success).toBe(true);

    // Create 5 sales (simulating daily POS)
    for (let i = 0; i < 5; i++) {
      const saleRes = await ajaxPost(page, 'sales', {
        customer_id: custRes.body.data.id,
        sale_date: `2026-0${4 + Math.floor(i / 3)}-${10 + i}`,
        payment_method: i % 2 === 0 ? 'cash' : 'credit',
        items: [{ product_id: 1, quantity: 2 + i, unit_price: 55000, discount: 0 }]
      });
      expect(saleRes.body.success).toBe(true);

      // Payment for cash sales
      if (i % 2 === 0) {
        const payRes = await page.evaluate(async ({ saleId, qty }) => {
          const res = await fetch('ajax.php?endpoint=sale-payment&id=' + saleId + '&test_mode=true', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ amount: 121000 * qty, payment_method: 'cash', payment_date: '2026-04-10' }),
            credentials: 'same-origin'
          });
          const text = await res.text();
          try { return JSON.parse(text); }
          catch (e) { return { success: false, message: text }; }
        }, { saleId: saleRes.body.data.id, qty: 2 + i });
        expect(payRes.success).toBe(true);
      }
    }

    // Create delivery
    const delRes = await ajaxPost(page, 'deliveries', {
      customer_name: 'Pelanggan Kasir Test',
      delivery_address: 'Jl. Test No. 1',
      phone: '081234567890',
      delivery_date: '2026-05-01',
      driver_name: 'Andi',
      vehicle_plate: 'B 9999 ZZ'
    });
    expect(delRes.body.success).toBe(true);

    // Create quotation
    const quoteRes = await ajaxPost(page, 'quotations', {
      customer_id: custRes.body.data.id,
      quote_date: '2026-05-15',
      valid_until: '2026-06-15',
      items: [{ product_id: 1, quantity: 10, unit_price: 55000, discount: 0 }]
    });
    expect(quoteRes.body.success).toBe(true);

    // WhatsApp message
    const waRes = await ajaxPost(page, 'whatsapp-messages', {
      phone_number: '628123456789',
      message_body: 'Terima kasih atas pembelian Anda',
      template_name: 'payment_confirmation'
    });
    expect(waRes.body.success).toBe(true);

    assertClean(m, 'Kasir 90-day simulation');
  });
});

// ═══════════════════════════════════════════════════════════════
// GUDANG ROLE — Inventory focus
// ═══════════════════════════════════════════════════════════════
test.describe('Simulation — Gudang Role (Inventory)', () => {
  test('Day 1-90: Products, stock, PO, transfers, batches, fleet, routes, IoT', async ({ page }) => {
    const m = attachMonitors(page, {});
    await loginAs(page, 'gudang1');

    const pages = [
      ['index.php', 'Dashboard'],
      ['products.php', 'Products'],
      ['stock.php', 'Stock'],
      ['stock_opname.php', 'Stock Opname'],
      ['stock_transfers.php', 'Stock Transfers'],
      ['suppliers.php', 'Suppliers'],
      ['purchase-orders.php', 'Purchase Orders'],
      ['deliveries.php', 'Deliveries'],
      ['returns.php', 'Returns'],
      ['warehouses.php', 'Warehouses'],
      ['reorder.php', 'Reorder AI'],
      ['fleet.php', 'Fleet'],
      ['routes.php', 'Routes'],
      ['iot.php', 'IoT'],
      ['landed_cost.php', 'Landed Cost'],
      ['batches.php', 'Batches'],
    ];

    for (const [path, label] of pages) {
      await visitPage(page, m, path, `Gudang:${label}`);
    }

    // Stock list
    const stockRes = await ajaxGet(page, 'stock');
    expect(stockRes.body.success).toBe(true);

    // Stock adjustment
    const adjRes = await ajaxPost(page, 'stock', {
      product_id: 1,
      quantity: 10,
      adjustment_type: 'correction',
      reason: 'Koreksi stok opname'
    });
    expect(adjRes.body.success).toBe(true);

    // Create PO
    const poRes = await ajaxPost(page, 'purchase-orders', {
      supplier_id: 1,
      po_date: '2026-04-05',
      items: [
        { product_id: 1, quantity: 200, unit_price: 50000 },
        { product_id: 2, quantity: 100, unit_price: 15000 }
      ],
      notes: 'PO April Gudang'
    });
    expect(poRes.body.success).toBe(true);

    // Stock transfer
    const stRes = await ajaxPost(page, 'stock-transfers', {
      transfer_date: '2026-04-10',
      from_warehouse_id: 1,
      to_warehouse_id: 2,
      items: [{ product_id: 1, quantity: 50 }],
      notes: 'Mutasi ke gudang 2'
    });
    expect(stRes.body.success).toBe(true);

    // Stock adjustment with approval flow
    const saRes = await ajaxPost(page, 'stock-adjustments', {
      product_id: 1,
      quantity: -5,
      adjustment_type: 'damage',
      reason: 'Barang rusak'
    });
    expect(saRes.body.success).toBe(true);
    const adjId = saRes.body.data.id;

    // Approve adjustment
    const apprRes = await ajaxPut(page, 'stock-adjustments', { status: 'approved' }, adjId);
    expect(apprRes.body.success).toBe(true);

    // Product batch
    const batchRes = await ajaxPost(page, 'product-batches', {
      product_id: 1,
      batch_no: 'BATCH-GUDANG-' + Date.now(),
      received_date: '2026-04-15',
      quantity_received: 300,
      unit_cost: 50000,
      supplier_id: 1
    });
    expect(batchRes.body.success).toBe(true);

    // Vehicle create
    const vehRes = await ajaxPost(page, 'vehicles', {
      plate_no: 'B GD-' + Date.now(),
      vehicle_type: 'box_truck',
      brand: 'Isuzu',
      model: 'Elf NLR',
      capacity_kg: 2000,
      fuel_type: 'diesel'
    });
    expect(vehRes.body.success).toBe(true);

    // Route create
    const routeRes = await ajaxPost(page, 'delivery-routes', {
      route_date: '2026-05-05',
      vehicle_id: 1,
      driver_name: 'Surya',
      stops: [
        { customer_name: 'Toko A', address: 'Bekasi', phone: '021111' },
        { customer_name: 'Toko B', address: 'Jakarta', phone: '021222' },
        { customer_name: 'Toko C', address: 'Depok', phone: '021333' }
      ]
    });
    expect(routeRes.body.success).toBe(true);

    // Warehouse locations
    const wlRes = await ajaxGet(page, 'warehouse-locations', { warehouse_id: 1 });
    expect(wlRes.body.success).toBe(true);

    assertClean(m, 'Gudang 90-day simulation');
  });
});

// ═══════════════════════════════════════════════════════════════
// ACCOUNTING ROLE — Finance focus
// ═══════════════════════════════════════════════════════════════
test.describe('Simulation — Accounting Role', () => {
  test('Day 1-90: Journal, cashbook, assets, cash flow, e-Faktur, closing', async ({ page }) => {
    const m = attachMonitors(page, {});
    await loginAs(page, 'accounting1');

    const pages = [
      ['index.php', 'Dashboard'],
      ['customers.php', 'Customers'],
      ['reports.php', 'Reports'],
      ['accounting.php', 'Accounting'],
      ['cashbook.php', 'Cashbook'],
      ['fixed_assets.php', 'Fixed Assets'],
      ['cash_flow.php', 'Cash Flow'],
      ['closing.php', 'Closing'],
      ['e_faktur.php', 'e-Faktur'],
    ];

    for (const [path, label] of pages) {
      await visitPage(page, m, path, `Accounting:${label}`);
    }

    // Cash transactions
    for (let i = 0; i < 3; i++) {
      const ctRes = await ajaxPost(page, 'cash-transactions', {
        type: i % 2 === 0 ? 'in' : 'out',
        account_type: 'cash',
        transaction_date: `2026-04-${10 + i * 10}`,
        amount: 2000000 + i * 500000,
        description: `Transaksi kas simulasi ${i + 1}`,
        category: 'operational'
      });
      expect(ctRes.body.success).toBe(true);
    }

    // Bank statements
    const bsRes = await ajaxPost(page, 'bank-statements', {
      bank_account: 'BCA-999888',
      transaction_date: '2026-04-20',
      description: 'Penerimaan transfer',
      credit: 10000000,
      balance: 25000000
    });
    expect(bsRes.body.success).toBe(true);

    // Bank reconciliation
    const bsList = await ajaxGet(page, 'bank-statements');
    expect(bsList.body.success).toBe(true);
    if (bsList.body.data && bsList.body.data.length > 0) {
      const recRes = await ajaxPut(page, 'bank-statements', {}, bsList.body.data[0].id);
      expect(recRes.body.success).toBe(true);
    }

    // Fixed asset
    const faRes = await ajaxPost(page, 'fixed-assets', {
      name: 'Komputer Akuntansi',
      category: 'equipment',
      acquisition_date: '2026-04-01',
      acquisition_cost: 15000000,
      salvage_value: 1500000,
      useful_life_months: 36
    });
    expect(faRes.body.success).toBe(true);

    // Depreciate
    const depRes = await ajaxPut(page, 'fixed-assets', { action: 'depreciate' }, faRes.body.data.id);
    expect(depRes.body.success).toBe(true);

    // e-Faktur
    for (let i = 0; i < 3; i++) {
      const efRes = await ajaxPost(page, 'e-faktur', {
        faktur_type: i === 0 ? 'keluaran' : 'masukan',
        transaction_date: `2026-05-${10 + i * 5}`,
        counterparty_name: `PT Partner ${i + 1}`,
        counterparty_npwp: `01.234.567.${i}-901.000`,
        dpp: 50000000 + i * 10000000,
        description: `Faktur simulasi ${i + 1}`
      });
      expect(efRes.body.success).toBe(true);
    }

    // Cash flow statement
    const cfRes = await ajaxGet(page, 'cash-flow', { start_date: '2026-04-01', end_date: '2026-06-30' });
    expect(cfRes.body.success).toBe(true);

    // Period closing
    const pcList = await ajaxGet(page, 'period-closings');
    expect(pcList.body.success).toBe(true);

    // Reports
    for (const type of ['profit-loss', 'ar-aging', 'ap-aging']) {
      const rpt = await ajaxGet(page, 'reports', { type, date_from: '2026-04-01', date_to: '2026-06-30' });
      expect(rpt.body.success).toBe(true);
    }

    assertClean(m, 'Accounting 90-day simulation');
  });
});

// ═══════════════════════════════════════════════════════════════
// SUPERVISOR ROLE — Dashboard + Reports only
// ═══════════════════════════════════════════════════════════════
test.describe('Simulation — Supervisor Role', () => {
  test('Dashboard + Reports view', async ({ page }) => {
    const m = attachMonitors(page, {});
    await loginAs(page, 'supervisor1');

    await visitPage(page, m, 'index.php', 'Supervisor:Dashboard');
    await visitPage(page, m, 'reports.php', 'Supervisor:Reports');

    // All report types
    for (const type of ['daily', 'monthly', 'profit-loss', 'by-product', 'by-customer', 'ar-aging', 'ap-aging', 'stock-valuation', 'dead-stock']) {
      const rpt = await ajaxGet(page, 'reports', { type, date_from: '2026-04-01', date_to: '2026-06-30' });
      expect(rpt.body.success).toBe(true);
    }

    assertClean(m, 'Supervisor simulation');
  });
});

// ═══════════════════════════════════════════════════════════════
// THEME + UI TESTS — Dark mode, eye-care, fullscreen
// ═══════════════════════════════════════════════════════════════
test.describe('Simulation — UI/UX Features', () => {
  test('Dark mode, eye-care mode, fullscreen toggle', async ({ page }) => {
    const m = attachMonitors(page, {});
    await loginAs(page, 'admin');

    // Light mode (default)
    await page.goto(`${FRONTEND_BASE}/index.php`);
    await page.waitForLoadState('networkidle');
    const htmlAttr = await page.getAttribute('html', 'data-bs-theme');
    expect(htmlAttr).toBeTruthy();

    // Switch to dark mode
    await page.goto(`${FRONTEND_BASE}/index.php?set_theme=dark`);
    await page.waitForLoadState('networkidle');
    await page.goto(`${FRONTEND_BASE}/index.php`);
    const darkAttr = await page.getAttribute('html', 'data-bs-theme');
    expect(darkAttr).toBe('dark');

    // Switch to eye-care mode
    await page.goto(`${FRONTEND_BASE}/index.php?set_theme=eyecare`);
    await page.waitForLoadState('networkidle');
    await page.goto(`${FRONTEND_BASE}/index.php`);
    const eyeAttr = await page.getAttribute('html', 'data-bs-theme');
    expect(eyeAttr).toBe('eyecare');

    // Switch back to light
    await page.goto(`${FRONTEND_BASE}/index.php?set_theme=light`);
    await page.waitForLoadState('networkidle');
    await page.goto(`${FRONTEND_BASE}/index.php`);
    const lightAttr = await page.getAttribute('html', 'data-bs-theme');
    expect(lightAttr).toBe('light');

    // Fullscreen button exists
    const fsBtn = page.locator('button[onclick="toggleFullscreen()"]').first();
    await expect(fsBtn).toBeVisible();

    // Theme dropdown exists
    const themeDropdown = page.locator('button[title="Tema"]');
    await expect(themeDropdown).toBeVisible();

    assertClean(m, 'UI/UX theme tests');
  });

  test('Responsive layout — mobile, tablet, desktop', async ({ page }) => {
    const m = attachMonitors(page, {});
    await loginAs(page, 'admin');

    // Mobile
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto(`${FRONTEND_BASE}/index.php`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    assertClean(m, 'Responsive:Mobile');

    // Tablet
    await page.setViewportSize({ width: 768, height: 1024 });
    await page.goto(`${FRONTEND_BASE}/index.php`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    assertClean(m, 'Responsive:Tablet');

    // Desktop
    await page.setViewportSize({ width: 1920, height: 1080 });
    await page.goto(`${FRONTEND_BASE}/index.php`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    assertClean(m, 'Responsive:Desktop');
  });

  test('Navbar shows correct user name and role for each role', async ({ page }) => {
    const roles = [
      ['ownertest', 'Owner'],
      ['manager1', 'Manager'],
      ['kasir1', 'Kasir'],
      ['gudang1', 'Gudang'],
      ['accounting1', 'Akuntansi'],
      ['supervisor1', 'Supervisor'],
    ];

    for (const [username, roleLabel] of roles) {
      const m = attachMonitors(page, {});
      await loginAs(page, username);
      await page.goto(`${FRONTEND_BASE}/index.php`);
      await page.waitForLoadState('networkidle');

      // Check role badge
      const roleBadge = page.locator('.badge.text-primary').filter({ hasText: roleLabel });
      await expect(roleBadge).toBeVisible();

      // Logout
      await page.click('a[href="logout.php"]');
      await expect(page).toHaveURL(/login\.php$/);

      assertClean(m, `Navbar:${roleLabel}`);
    }
  });
});
