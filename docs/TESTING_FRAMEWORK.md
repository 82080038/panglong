# TESTING FRAMEWORK

# PANGLONG ERP

## Version: 3.0 (Updated 2026-06-29)

---

## Testing Stack

- **Playwright E2E** — Browser automation testing untuk frontend PHP Native
- **Manual Testing** — Quick add, CRUD simulation, multi-tenant flow

---

## Playwright E2E Tests

### Setup
```bash
npm install @playwright/test
npx playwright install chromium
```

### Konfigurasi
File `playwright.config.js`:
- `testDir: './tests/e2e'`
- `baseURL: 'http://localhost/panglong/frontend'`
- `fullyParallel: false` (sequential untuk menghindari konflik data)
- `workers: 1`
- `retries: 1`
- `timeout: 60000`

### Menjalankan Tests
```bash
# Pastikan XAMPP Apache berjalan
sudo /opt/lampp/lampp start

# Run all tests
npx playwright test --headed --reporter=list --workers=1

# Run specific test file
npx playwright test tests/e2e/products.spec.js

# Run with filter
npx playwright test --grep "should create product"

# Generate HTML report
npx playwright test --reporter=html
npx playwright show-report
```

### Test Specs (26 files)

| Spec File | Coverage |
|-----------|----------|
| `login.spec.js` | Login flow, quick login buttons |
| `dashboard.spec.js` | Dashboard stats, charts, quick access |
| `navigation.spec.js` | RBAC navigation per role |
| `products.spec.js` | Product CRUD, search, similar check |
| `customers.spec.js` | Customer CRUD, quick add group |
| `suppliers.spec.js` | Supplier CRUD |
| `sales.spec.js` | POS flow, cart, checkout, payment |
| `stock.spec.js` | Stock view, adjustments |
| `stock_opname.spec.js` | Stock opname flow |
| `warehouses.spec.js` | Warehouse management |
| `purchase-orders.spec.js` | PO create, receive |
| `deliveries.spec.js` | Delivery orders |
| `returns.spec.js` | Sales/purchase returns |
| `quotations.spec.js` | Quotation flow |
| `reports.spec.js` | Report generation |
| `accounting.spec.js` | Journal, P&L, balance sheet |
| `reorder.spec.js` | Reorder logic |
| `quick-add.spec.js` | Quick add brand/category/unit |
| `multi-tenant.spec.js` | Tenant isolation |
| `saas.spec.js` | SaaS management |
| `super_admin.spec.js` | Super admin dashboard |
| `marketplace.spec.js` | Marketplace integration |
| `iot.spec.js` | IoT monitoring |
| `ai_insights.spec.js` | AI insights |
| `crud-simulation.spec.js` | Full CRUD simulation |
| `simulation.spec.js` | End-to-end simulation |

### Test Data Setup
File `tests/e2e/setup-test-data.php` — PHP script untuk setup test data:
```bash
/opt/lampp/bin/php tests/e2e/setup-test-data.php
```

---

## Manual Testing

### Quick Add Functionality
Test quick add untuk brand, category, unit, location:
1. Login sebagai owner/manager
2. Buka halaman Products
3. Klik "Tambah Produk"
4. Klik tombol quick add (icon +) di samping dropdown brand/category/unit
5. Masukkan nama, klik simpan
6. Verifikasi item baru muncul di dropdown

### Master Catalog Import
1. Login sebagai tenant (bukan super admin)
2. Buka halaman Products
3. Klik "Import dari Master Catalog"
4. Cari produk, klik "Import"
5. Verifikasi produk muncul di katalog tenant

### Multi-Tenant Isolation
1. Login sebagai tenant A
2. Buat produk "Test Product A"
3. Logout, login sebagai tenant B
4. Verifikasi "Test Product A" tidak muncul di katalog B
5. Verifikasi master catalog products muncul di kedua tenant

---

## Test Environment

- **URL:** `http://localhost/panglong/frontend`
- **PHP:** XAMPP 8.2.12 (`/opt/lampp/bin/php`)
- **Database:** `database/database.sqlite` (87 tables)
- **Browser:** Chromium (via Playwright)

### Default Test Users
| Username | Password | Role |
|----------|----------|------|
| admin | password123 | Owner |
| manager1 | password123 | Manager |
| kasir1 | password123 | Kasir |
| gudang1 | password123 | Gudang |
| accounting1 | password123 | Accounting |
| supervisor1 | password123 | Supervisor |
