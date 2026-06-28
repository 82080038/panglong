# Panglong ERP - Progress & Status Report

**Date:** 2026-06-29

---

## Completed

### Laravel Cleanup
- Removed all Laravel framework files: `app/`, `bootstrap/`, `config/`, `routes/`, `resources/`, `storage/`, `vendor/`
- Removed: `artisan`, `composer.json`, `composer.lock`, `phpunit.xml`, `.env.example`, `index.php` (Laravel), `package.json`, `package-lock.json`, `node_modules/`
- Removed: `database/migrations/`, `database/factories/`, `database/seeders/`
- Removed: `tests/Feature/`, `tests/CreatesApplication.php`, `tests/TestCase.php`
- Removed: `Dockerfile`, `docker-compose.yml`, `docker/`, `public/` (Laravel entry point)
- Application is now 100% PHP Native — no framework dependencies

### Master Catalog
- 190 produk material bangunan seeded (tenant_id = NULL)
- 19 kategori master, 23 satuan master
- Import dari master catalog feature (modal + AJAX endpoint)
- Auto-sync produk baru tenant ke master catalog
- All product queries updated: `(tenant_id = ? OR tenant_id IS NULL)`
- Updated 8 frontend PHP files: products, sales, quotations, stock, warehouses, stock_opname, sales_orders

### Frontend Fixes
- Fixed hardcoded `'ajax.php'` → `API_URL` in products.php (4 locations) and customers.php (1 location)
- Fixed `qr_generator.php` — replaced Composer/Endroid QR with Google Chart API + GD fallback
- Fixed `getDefaultTaxRate()` — convert percentage to fraction (11 → 0.11)
- Fixed simulation script: stock opname format, sale payment endpoint, warehouse creation, stock adjustment action

### Root Index
- Created `index.php` as gerbang utama (redirect to login/dashboard)

### Documentation
- All 18 MD files updated to reflect pure PHP architecture
- Removed all Laravel references from documentation

---

## Current Stats

| Metric | Value |
|--------|-------|
| Frontend PHP files | 49 |
| AJAX endpoints | 60 |
| ajax.php lines | 4044 |
| Database tables | 87 |
| Playwright E2E specs | 26 |
| Scripts | 8 |
| Master catalog products | 190 |
| Master categories | 19 |
| Master units | 23 |
| Roles | 7 |

---

## Architecture

**100% PHP Native Procedural + PDO SQLite + jQuery AJAX + Bootstrap 5.3**
