# SETUP GUIDE

# PANGLONG ERP - PHASE 1 MVP

## Version: 3.0 (Updated 2026-06-26)
## Status: Frontend 100% Functional (PHP Native + PDO SQLite) — Sprint 1-12 COMPLETED

> **ARSITEKTUR AKTUAL:** Frontend menggunakan PHP Native + PDO SQLite + jQuery AJAX.
> `frontend/ajax.php` adalah single endpoint (1802 lines) untuk semua CRUD operations.
> Laravel backend API ada di repo tetapi TIDAK digunakan oleh frontend.
> Database: SQLite (`database/database.sqlite`, 78 tables, 1.3MB).
>
> **PHP REQUIREMENT:** Gunakan XAMPP PHP (`/opt/lampp/bin/php` 8.2.12) yang
> memiliki `pdo_sqlite`. System PHP (8.3.6) TIDAK memiliki `pdo_sqlite`.

---

# WHAT HAS BEEN COMPLETED

## 1. Frontend (PHP Native + PDO SQLite) — 100% Functional
- 45 halaman PHP di `frontend/` directory
- `frontend/db.php` — PDO SQLite connection singleton
- `frontend/auth.php` — Session-based auth dengan `password_verify()`
- `frontend/config.php` — Session timeout, navbar, CDN loads (jQuery 3.6, Bootstrap 5.3)
- `frontend/ajax.php` — Single AJAX endpoint (1802 lines) untuk semua CRUD operations
- jQuery `$.ajax()` calls to `ajax.php` untuk dynamic operations
- Direct PDO queries untuk initial page load data
- Chart.js 4.4.0 untuk dashboard charts
- Bootstrap Icons untuk UI icons

## 2. Backend Laravel API (Scaffolded, Tested, TIDAK Digunakan Frontend)
- 37 migration files — all executed to SQLite database (78 tables active)
- 63 Eloquent models with relationships, casts, traits
- 33 API controllers in `app/Http/Controllers/Api/v1/`
- 20 service classes in `app/Services/`
- 16 seeders — all executed to SQLite database
- 9 model factories for testing
- 7 Form Request validation classes
- 6 API Resource transformer classes
- Routes with Sanctum + Spatie Permission middleware
- 14 PHPUnit test files
- 18 Playwright E2E test specs (39 tests, ALL PASSING)

## 3. Database
- SQLite: `database/database.sqlite` (1.3MB, 78 tables)
- All migrations executed successfully
- All seeders executed successfully
- Default users: admin, manager1, kasir1, gudang1 (password: password123)

## 4. Infrastructure
- `.gitignore` — .env, vendor/, storage/, test artifacts excluded
- `phpunit.xml` — PHPUnit configured with SQLite :memory: for tests
- `package.json` — Playwright E2E test configuration
- `Dockerfile` + `docker-compose.yml` + `docker/nginx.conf`
- PWA: `frontend/manifest.json` + `frontend/sw.js` service worker
- `.env.example` — Template available

---

# SETUP INSTRUCTIONS

## Prerequisites

1. XAMPP (Apache + PHP 8.2+ with `pdo_sqlite` extension)
2. Web browser (Chrome/Firefox for testing)
3. Optional: Composer (for Laravel backend tests), npm (for Playwright E2E tests)

> **PENTING:** Gunakan XAMPP PHP (`/opt/lampp/bin/php`) yang memiliki `pdo_sqlite`.
> System PHP mungkin tidak memiliki `pdo_sqlite` extension.

## Quick Start (Frontend Only — Yang Aktif Berjalan)

### 0. Clone repo
```bash
cd /opt/lampp/htdocs    # atau C:\xampp\htdocs di Windows
git clone <repo-url> panglong
cd panglong
```

### 1. Pastikan XAMPP berjalan
```bash
sudo /opt/lampp/lampp start
```

### 1b. Set database permission (Linux/macOS only)
```bash
chmod 666 database/database.sqlite
chmod 777 database/
```
> File `database/database.sqlite` sudah ada di repo (committed to git).
> Tidak perlu menjalankan migration atau seeder — database sudah siap dengan 78 tables dan seed data.

### 2. Akses Frontend
Buka browser: http://localhost/panglong/frontend/login.php

### 3. Login
| Username | Password | Role |
|----------|----------|------|
| admin | password123 | Owner |
| manager1 | password123 | Manager |
| kasir1 | password123 | Kasir |
| gudang1 | password123 | Gudang |

### 4. Database sudah siap
File `database/database.sqlite` (1.3MB, 78 tables) sudah berisi seed data.
Tidak perlu menjalankan migration atau seeder manual.

## Optional: Laravel Backend Setup (Untuk Testing API Only)

### 1. Install Laravel Dependencies
```bash
cd /opt/lampp/htdocs/panglong
composer install
```

### 2. Configure Environment
```bash
cp .env.example .env
/opt/lampp/bin/php artisan key:generate
```

### 3. Configure Database (SQLite untuk development)
Edit `.env` file:
```env
DB_CONNECTION=sqlite
DB_DATABASE=/opt/lampp/htdocs/panglong/database/database.sqlite
```

### 4. Run Migrations (jika diperlukan)
```bash
/opt/lampp/bin/php artisan migrate
```

### 5. Run Seeders (jika diperlukan)
```bash
/opt/lampp/bin/php artisan db:seed
```

### 6. Start Laravel API Server (optional, tidak digunakan frontend)
```bash
/opt/lampp/bin/php artisan serve
```
Laravel API akan tersedia di: http://localhost:8000/api/v1

---

# DEFAULT USERS

Database SQLite sudah berisi data user hasil seeder:

| Username | Password | Role | Description |
|----------|----------|------|-------------|
| admin | password123 | Owner | Full access |
| manager1 | password123 | Manager | Manager access |
| kasir1 | password123 | Kasir | Cashier access |
| gudang1 | password123 | Gudang | Warehouse access |

---

# FRONTEND AJAX ENDPOINTS (ajax.php)

Frontend menggunakan `frontend/ajax.php` sebagai single endpoint untuk semua
CRUD operations via jQuery `$.ajax()`. Endpoint dipanggil dengan parameter
`endpoint` untuk menentukan operasi yang dijalankan.

## Contoh Penggunaan

### Login (via auth.php, bukan ajax.php)
```php
// frontend/auth.php — direct PDO query
login($username, $password); // Sets $_SESSION['user']
```

### Product List (via ajax.php)
```javascript
$.ajax({
  url: 'ajax.php?endpoint=products',
  method: 'GET',
  success: function(data) { /* render products */ }
});
```

### Create Sale (via ajax.php)
```javascript
$.ajax({
  url: 'ajax.php?endpoint=sales',
  method: 'POST',
  data: { customer_id, items, payment_method, ... },
  success: function(data) { /* show receipt */ }
});
```

---

# LARAVEL API ENDPOINTS (TIDAK Digunakan Frontend)

> Laravel API berikut ada di repo dan tested via PHPUnit, tetapi frontend
> TIDAK memanggil endpoint ini. Frontend menggunakan `ajax.php` + PDO SQLite.

## Authentication

- `POST /api/v1/auth/login` - Login (token-based)
- `POST /api/v1/auth/logout` - Logout
- `GET /api/v1/auth/me` - Get current user

## Sales

- `GET /api/v1/sales` - List sales
- `POST /api/v1/sales` - Create sale
- `GET /api/v1/sales/{id}` - Get sale details
- `PUT /api/v1/sales/{id}` - Update sale
- `DELETE /api/v1/sales/{id}` - Void sale
- `POST /api/v1/sales/{id}/payment` - Record payment

## Products

- `GET /api/v1/products` - List products
- `POST /api/v1/products` - Create product
- `GET /api/v1/products/{id}` - Get product details
- `PUT /api/v1/products/{id}` - Update product
- `DELETE /api/v1/products/{id}` - Delete product
- `GET /api/v1/products/search` - Search products

## Customers

- `GET /api/v1/customers` - List customers
- `POST /api/v1/customers` - Create customer
- `GET /api/v1/customers/{id}` - Get customer details
- `PUT /api/v1/customers/{id}` - Update customer
- `DELETE /api/v1/customers/{id}` - Delete customer

## Inventory

- `GET /api/v1/stock` - Get stock report
- `GET /api/v1/stock/{product_id}` - Get product stock history
- `POST /api/v1/stock/adjustments` - Create stock adjustment
- `POST /api/v1/stock/adjustments/{id}/approve` - Approve adjustment
- `POST /api/v1/stock/opnames` - Create stock opname
- `POST /api/v1/stock/opnames/{id}/approve` - Approve opname

## Suppliers

- `GET /api/v1/suppliers` - List suppliers
- `POST /api/v1/suppliers` - Create supplier
- `GET /api/v1/suppliers/{id}` - Get supplier details
- `PUT /api/v1/suppliers/{id}` - Update supplier
- `DELETE /api/v1/suppliers/{id}` - Delete supplier

## Purchase Orders

- `GET /api/v1/purchase-orders` - List purchase orders
- `POST /api/v1/purchase-orders` - Create purchase order
- `GET /api/v1/purchase-orders/{id}` - Get PO details
- `POST /api/v1/purchase-orders/{id}/receive` - Receive PO
- `DELETE /api/v1/purchase-orders/{id}` - Delete PO

## Categories

- `GET /api/v1/categories` - List categories
- `POST /api/v1/categories` - Create category
- `GET /api/v1/categories/{id}` - Get category details
- `PUT /api/v1/categories/{id}` - Update category
- `DELETE /api/v1/categories/{id}` - Delete category

## Customer Groups

- `GET /api/v1/customer-groups` - List customer groups
- `POST /api/v1/customer-groups` - Create customer group
- `GET /api/v1/customer-groups/{id}` - Get group details
- `PUT /api/v1/customer-groups/{id}` - Update customer group
- `DELETE /api/v1/customer-groups/{id}` - Delete customer group

## Reports

- `GET /api/v1/reports/sales/daily` - Daily sales report
- `GET /api/v1/reports/sales/monthly` - Monthly sales report
- `GET /api/v1/reports/inventory/low-stock` - Low stock report
- `GET /api/v1/reports/accounts/receivable/aging` - AR aging report

---

# TESTING THE LARAVEL API (Optional)

## Login Example

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password123"}'
```

Response:
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
        "user": {
            "id": 1,
            "username": "admin",
            "full_name": "Administrator"
        }
    }
}
```

## Using the Token

```bash
curl -X GET http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer {token}"
```

---

# NEXT STEPS FOR DEVELOPMENT

## Pending Features — ALL COMPLETED

1. ~~Retur Penjualan & Pembelian~~ — ✅ `returns.php`
2. ~~Quotation & Sales Order~~ — ✅ `quotations.php`, `sales_orders.php`
3. ~~Ongkos Angkut & Landed Cost~~ — ✅ `landed_cost.php`
4. ~~Bonus Barang~~ — ✅ bonus_qty field di SaleItem & PurchaseItem
5. ~~Partial Delivery~~ — ✅ multiple DO per invoice
6. ~~Customer-Specific Pricing~~ — ✅ `pricing.php`
7. ~~Volume-Based Pricing~~ — ✅ tier pricing di `pricing.php`
8. ~~Batch/Lot Tracking~~ — ✅ `batches.php`
9. ~~Cash Flow Statement & Bank Reconciliation~~ — ✅ `cash_flow.php`, `cashbook.php`
10. ~~SPT PPN Report & Closing Periode~~ — ✅ `e_faktur.php`, `closing.php`
11. ~~WhatsApp Notification & Salesman Mobile App~~ — ✅ `whatsapp.php`, `salesman_app.php`

## Remaining (Non-kritis untuk go-live)
1. Login attempt limit (5x = lock 15 menit)
2. Audit log di frontend (ajax.php)
3. QR Code auto-generate produk
4. SPT PPN Report terpisah (e-Faktur sudah ada)
5. Multi-unit dropdown di POS (pilih satuan saat transaksi)

## Documentation Sync — ALL COMPLETED
1. ✅ API_SPECIFICATION.md updated dengan note bahwa frontend tidak menggunakan API ini
2. ✅ DATABASE_SCHEMA.md updated dengan note SQLite sebagai database development
3. ✅ TECHNICAL_DOCUMENTATION.md updated dengan arsitektur aktual
4. ✅ TESTING_FRAMEWORK.md updated dengan Playwright E2E documentation
5. ✅ All stats accurate across all MD files

---

# TROUBLESHOOTING

## Common Issues

### Frontend: Blank page / Database error
- Pastikan XAMPP berjalan: `sudo /opt/lampp/lampp start`
- Pastikan `database/database.sqlite` exists dan readable
- **Linux/macOS:** Jalankan `chmod 666 database/database.sqlite && chmod 777 database/`
- **Windows:** Pastikan file tidak read-only (klik kanan → Properties → uncheck Read-only)
- Jika database corrupt, re-import: `sqlite3 database/database.sqlite < database/database_export.sql`
- Atau gunakan: `/opt/lampp/bin/php database/import_sqlite.php`

### Frontend: "could not find driver" (pdo_sqlite)
- Gunakan XAMPP PHP (`/opt/lampp/bin/php`), bukan system PHP
- Cek: `/opt/lampp/bin/php -m | grep pdo_sqlite` — harus muncul `pdo_sqlite`
- Jika tidak ada, edit `php.ini` di XAMPP, uncomment `extension=pdo_sqlite`

### Playwright: Tests gagal / connection refused
- Pastikan Apache berjalan: `sudo /opt/lampp/lampp start`
- Pastikan URL benar: `http://localhost/panglong/frontend`
- Pastikan `database/database.sqlite` ada dan readable
- Run: `npx playwright test --headed --reporter=list --workers=1`

### Setup di komputer lain (new developer)
1. Install XAMPP (PHP 8.2+ dengan `pdo_sqlite`)
2. `git clone <repo-url> /opt/lampp/htdocs/panglong` (atau ke `C:\xampp\htdocs\panglong`)
3. `chmod 666 database/database.sqlite && chmod 777 database/` (Linux/macOS only)
4. Start XAMPP Apache
5. Buka `http://localhost/panglong/frontend/login.php`
6. Login dengan `admin` / `password123`
7. Database sudah siap — tidak perlu migration atau seeder
- Gunakan XAMPP PHP (`/opt/lampp/bin/php`) yang memiliki `pdo_sqlite`
- Cek: `php -m | grep pdo_sqlite` — jika kosong, gunakan XAMPP PHP

### Laravel: Migration Error: Table Already Exists
```bash
/opt/lampp/bin/php artisan migrate:fresh
```

### Composer Install Fails
```bash
composer install --no-interaction --prefer-dist
```

### Permission Denied on Storage
```bash
chmod -R 775 storage bootstrap/cache
```

### Laravel API Returns 401 Unauthorized
- Ensure you have the correct token
- Check that the token hasn't expired
- Verify the user is active
- Note: Frontend tidak menggunakan Laravel API, jadi ini hanya untuk testing API langsung

---

# LEARNING RESOURCES

- Read `PROJECT_STATUS.md` for accurate audit of current state
- Read `DEVELOPMENT_ROADMAP.md` for sprint plan and tech stack
- Read `PANGLONG_BUSINESS_ANALYSIS.md` for business requirements
- Read `API_SPECIFICATION.md` for Laravel API details (unused by frontend)
- Read `DATABASE_SCHEMA.md` for database structure
- Read `TESTING_FRAMEWORK.md` for testing guidelines

---

# SUPPORT

For questions or issues:
1. Check the documentation files
2. Review PROJECT_STATUS.md for current state
3. Review DEVELOPMENT_ROADMAP.md for execution plan

---

# SUMMARY

Frontend 100% functional dengan PHP Native + PDO SQLite + jQuery AJAX.
Laravel backend API scaffolded dan tested tetapi TIDAK digunakan oleh frontend.

**Yang sudah berjalan:**
1. Frontend: 45 halaman PHP dengan PDO SQLite + jQuery AJAX
2. Database: SQLite dengan 78 tables dan seed data
3. Auth: Session-based dengan permission checks
4. Testing: Playwright E2E (39 tests) + PHPUnit (14 files)
5. Deployment: Docker + PWA offline-first
6. Sprint 7-12: Retur, Quotation, Sales Order, Pricing, Stock Transfer, Cash Book, Fixed Assets, Fleet, Routes, WhatsApp, e-Faktur

**Sisa gap yang belum diimplementasi (non-kritis untuk go-live):**
1. Login attempt limit (5x = lock 15 menit)
2. Audit log di frontend (ajax.php)
3. QR Code auto-generate produk
4. SPT PPN Report terpisah (e-Faktur sudah ada)
5. Multi-unit dropdown di POS (pilih satuan saat transaksi)

> Lihat DEVELOPMENT_ROADMAP.md dan PANGLONG_BUSINESS_ANALYSIS.md untuk detail.
