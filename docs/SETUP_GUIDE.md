# SETUP GUIDE

# PANGLONG ERP

## Version: 5.0 (Updated 2026-06-29)
## Status: 100% PHP Native + PDO SQLite — Master Catalog Added

> **ARSITEKTUR:** 100% PHP Native Procedural + PDO SQLite + jQuery AJAX + Bootstrap 5.3
> `frontend/ajax.php` adalah single endpoint (4044 lines, 60 endpoints) untuk semua CRUD operations.
> Tidak ada Laravel, Composer, atau framework lain.
> Database: SQLite (`database/database.sqlite`, 87 tables, master catalog 190 produk).
>
> **PHP REQUIREMENT:** Gunakan XAMPP PHP (`/opt/lampp/bin/php` 8.2.12) yang
> memiliki `pdo_sqlite`. System PHP mungkin tidak memiliki `pdo_sqlite`.

---

# WHAT HAS BEEN COMPLETED

## 1. Frontend (PHP Native + PDO SQLite) — 100% Functional
- 49 file PHP di `frontend/` directory
- `frontend/db.php` — PDO SQLite connection singleton
- `frontend/auth.php` — Session-based auth dengan `password_verify()`, login attempt lockout
- `frontend/config.php` — Session, navbar (RBAC), dark mode, fullscreen, CSRF protection
- `frontend/ajax.php` — Single AJAX endpoint (4044 lines, 60 endpoints) untuk semua CRUD
- `fetch(API_URL + '?endpoint=...')` calls untuk dynamic operations
- Direct PDO queries untuk initial page load data
- Chart.js untuk dashboard charts
- Bootstrap Icons untuk UI icons
- Audit logging di `ajax.php`

## 2. Master Catalog
- 190 produk material bangunan dengan `tenant_id = NULL`
- 19 kategori master, 23 satuan master
- Tenant dapat import produk dari master catalog
- Produk baru tenant auto-sync ke master catalog jika belum ada
- Query produk menggunakan `(tenant_id = ? OR tenant_id IS NULL)`

## 3. Database
- SQLite: `database/database.sqlite` (87 tables)
- Default users: admin, manager1, kasir1, gudang1, accounting1, supervisor1 (password: password123)
- Master catalog data (190 produk, 19 kategori, 23 satuan)

## 4. Infrastructure
- `index.php` — Gerbang utama aplikasi (redirect ke login/dashboard)
- `.gitignore` — node_modules, test artifacts, backups excluded
- `playwright.config.js` — Playwright E2E test configuration
- PWA: `frontend/manifest.json` + `frontend/sw.js` service worker
- `frontend/qr_generator.php` — QR code via Google Chart API (no Composer)

---

# SETUP INSTRUCTIONS

## Prerequisites

1. XAMPP (Apache + PHP 8.2+ with `pdo_sqlite` extension)
2. Web browser (Chrome/Firefox for testing)
3. Optional: npm (for Playwright E2E tests)

> **PENTING:** Gunakan XAMPP PHP (`/opt/lampp/bin/php`) yang memiliki `pdo_sqlite`.
> System PHP mungkin tidak memiliki `pdo_sqlite` extension.

## Quick Start

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
> Tidak perlu menjalankan migration atau seeder — database sudah siap dengan 87 tables dan seed data.

### 2. Akses Aplikasi
Buka browser: `http://localhost/panglong/`
Akan otomatis diarahkan ke halaman login atau dashboard.

### 3. Login
| Username | Password | Role |
|----------|----------|------|
| admin | password123 | Owner |
| manager1 | password123 | Manager |
| kasir1 | password123 | Kasir |
| gudang1 | password123 | Gudang |
| accounting1 | password123 | Accounting |
| supervisor1 | password123 | Supervisor |

### 4. Database sudah siap
File `database/database.sqlite` (87 tables) sudah berisi seed data dan master catalog.
Tidak perlu menjalankan migration atau seeder manual.

---

# DEFAULT USERS

| Username | Password | Role | Description |
|----------|----------|------|-------------|
| admin | password123 | Owner | Full access |
| manager1 | password123 | Manager | Manager access |
| kasir1 | password123 | Kasir | Cashier access |
| gudang1 | password123 | Gudang | Warehouse access |
| accounting1 | password123 | Accounting | Accounting access |
| supervisor1 | password123 | Supervisor | Dashboard & reports |

---

# FRONTEND AJAX ENDPOINTS (ajax.php)

Frontend menggunakan `frontend/ajax.php` sebagai single endpoint untuk semua
CRUD operations via `fetch()`. Endpoint dipanggil dengan parameter
`endpoint` untuk menentukan operasi yang dijalankan.

## Contoh Penggunaan

### Login (via auth.php, bukan ajax.php)
```php
// frontend/auth.php — direct PDO query
login($username, $password); // Sets $_SESSION['user']
```

### Product List (via ajax.php)
```javascript
fetch(API_URL + '?endpoint=products')
  .then(r => r.json())
  .then(res => { /* render products */ });
```

### Create Sale (via ajax.php)
```javascript
fetch(API_URL + '?endpoint=sales', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ customer_id, items, payment_method, ... })
})
.then(r => r.json())
.then(res => { /* show receipt */ });
```

### Import from Master Catalog
```javascript
fetch(API_URL + '?endpoint=master-products', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ master_product_id: id })
})
.then(r => r.json())
.then(res => { /* product imported */ });
```

---

# TESTING

## Playwright E2E Tests
```bash
npm install @playwright/test
npx playwright install chromium

# Pastikan XAMPP Apache berjalan
npx playwright test --headed --reporter=list --workers=1
```

---

# TROUBLESHOOTING

## Common Issues

### Frontend: Blank page / Database error
- Pastikan XAMPP berjalan: `sudo /opt/lampp/lampp start`
- Pastikan `database/database.sqlite` exists dan readable
- **Linux/macOS:** Jalankan `chmod 666 database/database.sqlite && chmod 777 database/`
- **Windows:** Pastikan file tidak read-only

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
5. Buka `http://localhost/panglong/`
6. Login dengan `admin` / `password123`
7. Database sudah siap — tidak perlu migration atau seeder

---

# LEARNING RESOURCES

- Read `PROJECT_STATUS.md` for accurate audit of current state
- Read `DEVELOPMENT_ROADMAP.md` for sprint plan and tech stack
- Read `PANGLONG_BUSINESS_ANALYSIS.md` for business requirements
- Read `DATABASE_SCHEMA.md` for database structure
- Read `TESTING_FRAMEWORK.md` for testing guidelines
- Read `MULTI_TENANT_GUIDE.md` for master catalog architecture

---

# SUMMARY

Aplikasi 100% PHP Native + PDO SQLite + jQuery AJAX + Bootstrap 5.3.
Tidak ada Laravel, Composer, atau framework lain.

**Yang sudah berjalan:**
1. Frontend: 49 file PHP dengan PDO SQLite + AJAX
2. Database: SQLite dengan 87 tables, seed data, dan master catalog (190 produk)
3. Auth: Session-based dengan permission checks, CSRF protection, rate limiting
4. Testing: 26 Playwright E2E test specs
5. Master Catalog: 190 produk bangunan dapat diakses semua tenant
6. Sprint 1-12 + Gap Features: Retur, Quotation, Sales Order, Pricing, Stock Transfer, Cash Book, Fixed Assets, Fleet, Routes, WhatsApp, e-Faktur
