# PROJECT STATUS

# PANGLONG ERP - ACCURATE AUDIT

## Version: 8.0 (Updated 2026-06-29)

---

## Current Architecture

**100% PHP Native Procedural + PDO SQLite + jQuery AJAX + Bootstrap 5.3**

Tidak ada Laravel, Composer, atau framework lain. Aplikasi mengakses database langsung via PDO SQLite.

---

## Stats (Accurate as of 2026-06-29)

| Metric | Value |
|--------|-------|
| Frontend PHP files | 49 |
| AJAX endpoints | 60 |
| ajax.php lines | 4044 |
| Database tables | 87 |
| Playwright E2E specs | 26 |
| Scripts | 8 |
| Docs | 18 MD files |
| Master catalog products | 190 |
| Master categories | 19 |
| Master units | 23 |
| Roles | 7 (owner, manager, kasir, gudang, accounting, supervisor, super_admin) |

---

## Components

### Frontend (`frontend/`)
- `config.php` — Session, navbar (RBAC), dark mode, fullscreen, CSRF, CDN
- `db.php` — PDO SQLite connection singleton
- `auth.php` — Session auth: login(), logout(), hasPermission(), CSRF, rate limiting
- `ajax.php` — Single AJAX endpoint (4044 lines, 60 endpoints)
- `index.php` — Dashboard (super admin & tenant views)
- `login.php` / `register.php` — Auth pages
- `products.php` — Product management + master catalog import
- `sales.php` — POS / Sales
- `stock.php` — Stock management
- `purchase-orders.php` — Purchase orders
- `deliveries.php` — Delivery orders
- `accounting.php` — Accounting
- `reports.php` — Reports
- `settings.php` — Settings
- `users.php` — User management
- `tenants.php` — Tenant management (super admin)
- `qr_generator.php` — QR code via Google Chart API
- 30+ other pages

### Scripts (`scripts/`)
- `seed_master_catalog.php` — Seed master catalog (190 produk bangunan)
- `simulate_one_month.php` — Simulasi 1 bulan operasional
- `backup_database.sh` — Backup database
- `clean_data.php` — Bersihkan data tenant
- `export_sqlite.php` — Export database ke SQL
- `import_sqlite.php` — Import database dari SQL
- `add_performance_indexes.php` — Add DB indexes
- `setup_cron.sh` — Setup cron jobs

### Tests (`tests/e2e/`)
- 26 Playwright E2E spec files
- `setup-test-data.php` — Test data setup

---

## Master Catalog Architecture

- Produk dengan `tenant_id = NULL` adalah master catalog milik super admin
- Tenant dapat melihat master catalog + produk sendiri: `(tenant_id = ? OR tenant_id IS NULL)`
- Tenant dapat import produk dari master catalog via endpoint `master-products`
- Produk baru tenant auto-sync ke master catalog jika belum ada (by name)
- 190 produk material bangunan: semen, besi, cat, pipa, sanitary, listrik, dll.

---

## Security Features

- Session-based auth dengan `password_verify()`
- Login attempt lockout (5x gagal = lock 15 menit)
- CSRF token validation untuk POST/PUT/DELETE
- Rate limiting (30 requests/60 seconds untuk write operations)
- Role-based endpoint permission map
- Security headers (X-Content-Type-Options, X-Frame-Options, X-XSS-Protection)
- Anti-double-click protection
- Session heartbeat (5 minutes)

---

## UI/UX Features

- RBAC Navigation — Menu berbeda per role
- Dark Mode — `data-bs-theme="dark"`, session-based
- Eye-Care Mode — Sepia theme
- Fullscreen Toggle — Fullscreen API
- Responsive Design — Mobile, tablet, desktop
- PWA — Installable app dengan offline-first service worker
- Chart.js — Dashboard charts

---

## Completed Sprints

1. Sprint 1-3: Core ERP (products, sales, customers, suppliers, stock)
2. Sprint 4-6: Purchase orders, deliveries, returns, quotations, sales orders
3. Sprint 7-9: Pricing, batch tracking, cash flow, bank reconciliation, e-Faktur
4. Sprint 10-12: Fleet, delivery routes, WhatsApp, salesman app, AI insights
5. Gap Features: Multi-tenant, SaaS, marketplace, IoT, reorder
6. UI/UX: Dark mode, eye-care, fullscreen, PWA, responsive
7. Master Catalog: 190 produk bangunan, import/sync feature
