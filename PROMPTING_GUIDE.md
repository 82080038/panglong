# COMPREHENSIVE PROMPTING GUIDE

# PANGLONG ERP — Guide for AI-Assisted Development

## Version: 2.0 (Updated 2026-06-27)
## Status: 50 pages, 58 AJAX endpoints, 87 tables, 23 E2E specs (48 pass, 19 skipped)

> Panduan ini memberikan konteks lengkap dan prompt templates untuk melanjutkan
> pengembangan Panglong ERP menggunakan AI coding assistant (Cascade/Claude).

---

# 1. ARSITEKTUR AKTUAL

## Yang Berjalan Saat Ini

```
[Browser]
  ↓
[PHP Server-Side Rendering] — frontend/*.php (50 pages)
  ├── Direct PDO SQLite queries untuk initial page load
  └── jQuery 3.6 $.ajax() → frontend/ajax.php (3395 lines, 58 endpoints) → PDO SQLite
  ↓
[database/database.sqlite] — 87 tables
```

## Komponen Utama

| Komponen | File | Fungsi |
|----------|------|--------|
| DB Connection | `frontend/db.php` | PDO SQLite singleton, `PRAGMA foreign_keys = ON` |
| Auth | `frontend/auth.php` | Session-based, `password_verify()`, `hasPermission()` |
| Config | `frontend/config.php` | Session timeout 30min, `renderNav()`, `renderHead()`, `renderFoot()` |
| AJAX Endpoint | `frontend/ajax.php` | Single endpoint (3395 lines, 58 endpoints), parameter `?endpoint=X` |
| Database | `database/database.sqlite` | SQLite, 87 tables, hasil 37 migrations + 16 seeders |

## Yang Ada Tapi TIDAK Digunakan

| Komponen | Lokasi | Status |
|----------|--------|--------|
| Laravel Backend | `app/`, `routes/api.php` | Scaffolded, PHPUnit tested, TIDAK dipanggil frontend |
| Laravel Migrations | `database/migrations/` | 37 files, sudah dijalankan ke SQLite |
| Laravel Models | `app/Models/` | 63 Eloquent models |
| Laravel Controllers | `app/Http/Controllers/Api/v1/` | 33 controllers |
| Laravel Services | `app/Services/` | 20 service classes |

## Tech Stack Frontend

- PHP Native procedural (no framework)
- PDO SQLite (no ORM)
- jQuery 3.6.0 (CDN)
- Bootstrap 5.3.0 (CDN)
- Bootstrap Icons 1.10.0 (CDN)
- Chart.js 4.4.0 (CDN)
- Session-based auth (`$_SESSION['user']`)
- `API_URL = 'ajax.php'`, `API_TOKEN = ''`

## PHP Environment

- **XAMPP PHP** (`/opt/lampp/bin/php` 8.2.12): has `pdo_sqlite` — USE THIS
- **System PHP** (8.3.6): does NOT have `pdo_sqlite`
- Frontend diakses via: `http://localhost/panglong/frontend/login.php`

## Default Users

| Username | Password | Role |
|----------|----------|------|
| admin | password123 | Owner |
| manager1 | password123 | Manager |
| kasir1 | password123 | Kasir |
| gudang1 | password123 | Gudang |
| accounting1 | password123 | Accounting |
| supervisor1 | password123 | Supervisor |

---

# 2. STRUKTUR FILE FRONTEND

## Core Files (4 files)

```
frontend/
├── db.php          # PDO SQLite connection
├── auth.php        # login(), logout(), hasPermission(), requireLogin()
├── config.php      # renderNav(), renderHead(), renderFoot(), session timeout
└── ajax.php        # Single AJAX endpoint — all CRUD operations
```

## Page Files (26+ pages)

```
frontend/
├── login.php              # Login page with quick login buttons
├── logout.php             # Session destroy
├── index.php              # Dashboard (Chart.js, real DB stats)
├── products.php           # Product CRUD + multi-unit + search
├── product_detail.php     # Product detail view
├── customers.php          # Customer CRUD + search
├── customer_detail.php    # Customer detail + purchase history
├── sales.php              # POS (walk-in, discount, delivery)
├── sale_detail.php        # Sale detail view
├── deliveries.php         # Surat jalan management
├── stock.php              # Stock list + adjustment
├── stock_opname.php       # Stock opname (physical count)
├── suppliers.php          # Supplier CRUD + search
├── purchase-orders.php    # PO + partial receive + payment
├── reports.php            # 11 report tabs + CSV/PDF export
├── settings.php           # Tax config, company info
├── users.php              # User management (owner/manager only)
├── print_nota.php         # Thermal 80mm print
├── accounting.php         # Journal, trial balance, P&L, balance sheet
├── warehouses.php         # Warehouse CRUD + stock transfer
├── reorder.php            # Reorder suggestions (AI basic)
├── ai_insights.php        # Demand forecasting, price optimization
├── saas.php               # SaaS billing
├── marketplace.php        # Tokopedia, Shopee integration
├── iot.php                # IoT sensors
├── manifest.json          # PWA manifest
└── sw.js                  # Service worker (offline-first)
```

---

# 3. AJAX ENDPOINT PATTERN

## Struktur `ajax.php`

```php
// 1. Session check
session_start();
if (!isset($_SESSION['user'])) { http_response_code(401); ... }

// 2. Get parameters
$endpoint = $_GET['endpoint'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// 3. Helper functions
function ok($data = null, $meta = null) { ... }
function fail($msg, $code = 400) { ... }
function created($data = null) { ... }

// 4. Endpoint routing (if/if pattern, NOT switch)
if ($endpoint === 'products') {
    if ($method === 'GET') { ... }
    if ($method === 'POST') { ... }
    if ($method === 'PUT') { ... }
    if ($method === 'DELETE') { ... }
}

// 5. Fallback
fail('Endpoint not found: ' . $endpoint, 404);
```

## Endpoints yang Ada di `ajax.php`

| Endpoint | Methods | Fungsi |
|----------|---------|--------|
| `products` | GET, POST, PUT, DELETE | Product CRUD + search + pagination |
| `categories` | GET | List categories |
| `customers` | GET, POST, PUT, DELETE | Customer CRUD + search |
| `customer-groups` | GET | List customer groups |
| `suppliers` | GET, POST, DELETE | Supplier CRUD + search |
| `sales` | GET, POST, DELETE | Sales CRUD + void |
| `sale-payment` | POST | Record sale payment |
| `stock` | GET, POST | Stock list + adjustment |
| `barcode-lookup` | GET | Find product by barcode/code |
| `sales-price` | GET | Get sell_price for product |
| `deliveries` | POST | Create delivery/surat jalan |
| `reports` | GET | 11 report types (daily, monthly, low-stock, etc.) |
| `branches` | GET | List branches |
| `settings` | GET | Get app settings |
| `warehouses` | GET | List warehouses |
| `users` | GET | List users |

## Frontend AJAX Call Pattern

```javascript
// Standard pattern used in frontend pages
$.ajax({
    url: API_URL + '?endpoint=products&search=' + search,
    method: 'GET',
    success: function(res) {
        if (res.success) {
            // render res.data
        }
    },
    error: function(xhr) {
        console.error(xhr.responseText);
    }
});
```

---

# 4. DATABASE SCHEMA

## Key Tables (65 total in SQLite)

### Core Tables
- `users`, `roles`, `permissions`, `role_permission`
- `tenants`, `branches`, `warehouses`
- `categories`, `customer_groups`
- `products`, `product_units`, `barcodes`
- `customers`, `suppliers`

### Transaction Tables
- `sales`, `sale_items`, `sale_payments`
- `purchase_orders`, `purchase_items`, `purchase_payments`
- `stock_movements`, `stock_adjustments`, `stock_opnames`, `opname_items`
- `deliveries`, `delivery_items`

### Accounting Tables
- `chart_of_accounts`, `journal_entries`, `journal_entry_lines`
- `accounts_receivable`, `accounts_payable`, `payments`

### System Tables
- `app_settings`, `audit_logs`
- `subscriptions`, `subscription_plans`
- `iot_sensors`, `marketplace_integrations`
- `demand_forecasts`, `price_optimizations`

## SQLite-Specific Notes

- Boolean values stored as 0/1 (INTEGER)
- Dates stored as TEXT (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)
- Decimal values stored as REAL
- `julianday()` for date calculations
- `date('now')`, `date('now','start of month')`, `date('now','-6 days')` for date ranges
- `COALESCE(SUM(...), 0)` for nullable aggregates
- `CAST(x AS REAL)` for numeric comparisons on TEXT columns

---

# 5. PENDING FEATURES (Sprint 7-12)

## Sprint 7: Retur & Quotation
- Sales Return (retur penjualan)
- Purchase Return (retur pembelian)
- Quotation (penawaran harga)
- Sales Order (SO dari lapangan)

## Sprint 8: Ongkos Angkut & Landed Cost
- Ongkos angkut per delivery
- Landed cost calculation (HPP = buy_price + ongkos_angkut + biaya_lain)
- Distribusi ongkos ke item (proporsional berat/volume)

## Sprint 9: Bonus & Partial Delivery
- Bonus barang (free item per qty beli)
- Partial delivery (multiple DO per invoice)
- Delivery scheduling

## Sprint 10: Advanced Pricing
- Customer-specific pricing
- Volume-based pricing (tier pricing)
- Promo/campaign pricing
- Margin analysis

## Sprint 11: Batch Tracking & Accounting
- Batch/Lot tracking
- FIFO/FEFO stock valuation
- Cash Flow Statement
- Bank Reconciliation

## Sprint 12: Tax & Mobile
- SPT PPN Report (Indonesian tax)
- Closing periode (monthly/yearly)
- WhatsApp notification
- Salesman mobile app (PWA)

---

# 6. PROMPT TEMPLATES

## Template: Add New Feature to Frontend

```
Tambahkan fitur [NAMA FITUR] ke frontend Panglong ERP.

Konteks:
- Frontend: PHP Native + PDO SQLite + jQuery AJAX
- AJAX endpoint: frontend/ajax.php (tambah endpoint baru di sini)
- Database: database/database.sqlite (lihat schema di DATABASE_SCHEMA.md)
- Pattern: ikuti pattern yang ada di ajax.php (if $endpoint === 'xxx')
- UI: Bootstrap 5.3, ikuti pattern di pages lain (renderHead, renderNav, renderFoot)

Yang dibutuhkan:
1. Tambah endpoint '[endpoint-name]' di frontend/ajax.php
2. Buat frontend/[page_name].php
3. Tambah nav link di frontend/config.php (renderNav function)
4. Tambah permission check di auth.php jika diperlukan

Default users: admin/password123 (Owner), kasir1/password123 (Kasir)
```

## Template: Add New Database Table

```
Buat migration dan table baru untuk [NAMA TABLE].

Konteks:
- Database: SQLite (database/database.sqlite)
- Migration: Laravel migration di database/migrations/
- Frontend akses DB langsung via PDO, bukan via Laravel API
- Jalankan migration dengan: /opt/lampp/bin/php artisan migrate

Yang dibutuhkan:
1. Buat migration file: database/migrations/YYYY_MM_DD_HHMMSS_create_[table]_table.php
2. Define columns dengan tipe yang SQLite-compatible
3. Jalankan migration
4. Tambah seeder jika diperlukan
5. Tambah endpoint di frontend/ajax.php untuk CRUD table baru
```

## Template: Fix Bug in Frontend

```
Fix bug [DESKRIPSI BUG] di frontend Panglong ERP.

Konteks:
- File yang relevan: frontend/[file].php
- AJAX endpoint: frontend/ajax.php
- Database: PDO SQLite via frontend/db.php
- PHP: Gunakan XAMPP PHP (/opt/lampp/bin/php) yang punya pdo_sqlite
- Error reporting: cek error di browser console + PHP error log

Langkah:
1. Baca file yang bermasalah
2. Identifikasi root cause
3. Fix dengan minimal changes
4. Test dengan login ke http://localhost/panglong/frontend/login.php
```

## Template: Add Report

```
Tambahkan report [NAMA REPORT] ke frontend.

Konteks:
- Reports page: frontend/reports.php (11 tabs sudah ada)
- Report endpoint: frontend/ajax.php?endpoint=reports&type=[type]
- Pattern: ikuti report type yang sudah ada (daily, monthly, low-stock, dll)
- Export: CSV dan PDF print

Yang dibutuhkan:
1. Tambah `if ($type === '[type]')` di ajax.php reports section
2. Tambah tab baru di reports.php
3. Tambah JavaScript untuk load dan render data
4. Tambah export CSV/PDF button
```

## Template: Playwright E2E Test

```
Buat Playwright E2E test untuk [NAMA FITUR].

Konteks:
- Test dir: tests/e2e/
- Config: playwright.config.js (baseURL: http://localhost/panglong/frontend)
- Pattern: lihat tests/e2e/login.spec.js atau dashboard.spec.js
- Browser: Chromium only
- Login: admin/password123 (Owner), kasir1/password123 (Kasir)

Yang dibutuhkan:
1. Buat tests/e2e/[feature].spec.js
2. Test scenario: login → navigate → interact → verify
3. Run: npx playwright test tests/e2e/[feature].spec.js --headed
```

---

# 7. BEST PRACTICES UNTUK PROMPTING

## DO

- **Sebutkan file spesifik** yang ingin diubah (e.g., "edit frontend/ajax.php")
- **Sebutkan endpoint name** yang ingin ditambah (e.g., "tambah endpoint 'returns'")
- **Ikuti pattern yang ada** — ajax.php menggunakan if/if, bukan switch
- **Gunakan SQLite syntax** — `julianday()`, `date('now')`, `COALESCE()`
- **Bootstrap 5.3 classes** — ikuti UI yang sudah ada di pages lain
- **Sebutkan role** yang bisa akses fitur (owner, manager, kasir, gudang)
- **Test dengan XAMPP PHP** — `/opt/lampp/bin/php` untuk CLI, Apache untuk web

## DON'T

- **Jangan buat Laravel API endpoint** untuk fitur frontend baru — frontend tidak pakai Laravel API
- **Jangan gunakan Eloquent** di frontend — frontend pakai PDO langsung
- **Jangan gunakan MySQL syntax** — database adalah SQLite
- **Jangan tambah npm/webpack build** — frontend pakai CDN, no build step
- **Jangan ubah auth pattern** — session-based, bukan token-based
- **Jangan buat file baru** tanpa alasan jelas — edit file yang ada

## DEBUGGING TIPS

```bash
# Cek PHP errors (XAMPP)
tail -f /opt/lampp/logs/php_error_log

# Cek SQLite database
/opt/lampp/bin/php -r "
\$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');
\$tables = \$db->query(\"SELECT name FROM sqlite_master WHERE type='table' ORDER BY name\")->fetchAll(PDO::FETCH_COLUMN);
echo implode(\"\n\", \$tables);
"

# Run specific Playwright test
npx playwright test tests/e2e/login.spec.js --headed

# Run all Playwright tests
npx playwright test --headed

# Run PHPUnit tests
/opt/lampp/bin/php vendor/bin/phpunit
```

---

# 8. QUICK REFERENCE

## File Locations

| What | Where |
|------|-------|
| Frontend pages | `frontend/*.php` |
| AJAX endpoint | `frontend/ajax.php` |
| DB connection | `frontend/db.php` |
| Auth | `frontend/auth.php` |
| Config/Nav | `frontend/config.php` |
| Database | `database/database.sqlite` |
| Migrations | `database/migrations/` |
| Seeders | `database/seeders/` |
| E2E tests | `tests/e2e/*.spec.js` |
| PHPUnit tests | `tests/Feature/`, `tests/Unit/` |
| Playwright config | `playwright.config.js` |
| PHPUnit config | `phpunit.xml` |

## Key Functions

| Function | File | Purpose |
|----------|------|---------|
| `db()` | `frontend/db.php` | Get PDO SQLite singleton |
| `login($u, $p)` | `frontend/auth.php` | Authenticate user, set session |
| `logout()` | `frontend/auth.php` | Destroy session |
| `hasPermission($perm)` | `frontend/auth.php` | Check user permission |
| `requireLogin()` | `frontend/auth.php` | Redirect to login if not authenticated |
| `userRole()` | `frontend/auth.php` | Get current user's role slug |
| `userFullName()` | `frontend/auth.php` | Get current user's full name |
| `renderNav($active)` | `frontend/config.php` | Render navbar with role-based links |
| `renderHead($title)` | `frontend/config.php` | Render HTML head + CDN includes |
| `renderFoot()` | `frontend/config.php` | Render footer + Bootstrap JS |
| `ok($data, $meta)` | `frontend/ajax.php` | JSON success response |
| `fail($msg, $code)` | `frontend/ajax.php` | JSON error response |
| `created($data)` | `frontend/ajax.php` | JSON 201 response |

## Database Quick Stats

- 87 tables
- 37 migrations (all executed)
- 16 seeders (all executed)
- 9 factories
- Default data: 6 users, 7 roles, sample products/customers/suppliers
- Size: ~1.5MB

---

# 9. DEVELOPMENT CYCLE PROMPT (v2.0 — Jun 2026)

## Current State Analysis (27 Jun 2026)

| Metric | Value |
|--------|-------|
| Frontend PHP files | 50 |
| AJAX endpoints | 58 (ajax.php: 3395 lines) |
| Database tables | 87 (SQLite) |
| E2E test specs | 23 (48 pass, 19 skipped) |
| All pages HTTP 200 | 36/36 ✓ |
| PHP syntax check | 50/50 ✓ |
| Nav items | 34 (7 dropdown groups) |
| User roles | 7 (owner, manager, kasir, gudang, accounting, supervisor, super_admin) |

## Bugs Fixed This Session
1. **index.php** — `require_once auth.php` → `require_once config.php` (renderNav undefined)
2. **routes.php** — `number_format()` on non-numeric `total_distance_km` → added `is_numeric()` guard

## Remaining Work: Enable 19 Skipped Simulation Tests

The simulation tests in `tests/e2e/simulation.spec.js` are all `test.describe.skip()`.
They cover 7 role-based scenarios + 3 UI/UX tests = 11 test cases (19 with sub-tests).

### Strategy: Progressive Enablement
1. Enable UI/UX tests first (simplest — theme, responsive, navbar)
2. Enable Owner tests (3 tests: Day 1-30, 31-60, 61-90)
3. Enable Manager/Kasir/Gudang/Accounting/Supervisor tests
4. Fix failures as they appear — minimal changes, root cause fixes

### Execution Prompt

```
ENABLE SIMULATION TESTS PROGRESSIVELY:

Step 1: Enable UI/UX simulation tests
- Remove .skip from 'Simulation — UI/UX Features' describe block
- Run: npx playwright test tests/e2e/simulation.spec.js --grep "UI/UX" --reporter=list --workers=1
- Fix any failures (theme switching, responsive layout, navbar role badges)

Step 2: Enable Owner simulation tests
- Remove .skip from 'Simulation — Owner Role' describe block
- Run: npx playwright test tests/e2e/simulation.spec.js --grep "Owner" --reporter=list --workers=1
- Fix AJAX endpoint failures, page errors, console warnings

Step 3: Enable remaining role tests
- Remove .skip from Manager, Kasir, Gudang, Accounting, Supervisor blocks
- Run each role's test individually, fix failures

Step 4: Full test suite
- Run: npx playwright test --reporter=list --workers=1
- All 67 tests must pass (48 existing + 19 simulation)

Rules:
- Use /opt/lampp/bin/php for PHP CLI
- Use SQLite syntax (julianday, date('now'), COALESCE)
- Fix root causes, not symptoms
- Minimal changes — don't refactor working code
- Test after each fix
```
