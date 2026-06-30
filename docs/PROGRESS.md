# Panglong ERP - Progress & Status Report

**Date:** 2026-06-30 (Cycle 3)
**Test Status:** 88/88 PASSED

---

## Cycle 3 — Autonomous AI Development Cycle

### Completed
- Executed AI Development Cycle from `.devin/workflows/ai-development-cycle.md`
- Baseline analysis: 88/88 tests passing, all pages HTTP 200, no PHP syntax errors
- Identified potential `branch_id` filter bug in `cash_flow.php`
- Fixed `runCashFlowSum()` to only apply `branch_id` filter when caller explicitly enables it
- Implemented proper stock opname workflow using existing `stock_opnames` and `opname_items` tables
  - Create opname with pending status
  - Save opname items (system qty, physical qty, difference)
  - Approval workflow (owner/manager/super_admin)
  - Stock adjustment only created on approval
  - Delete pending opname
  - History view and detail view
- Updated `tests/e2e/stock_opname.spec.js` to target specific `#opnameTable` (avoid ambiguity with new history table)
- Verified all 88 tests still pass

---

## Cycle 1 — Laravel Cleanup & Master Catalog

### Completed
- Removed all Laravel framework files (app/, bootstrap/, config/, routes/, etc.)
- Created root `index.php` as gerbang utama
- Fixed `qr_generator.php` — replaced Composer/Endroid with Google Chart API
- Fixed hardcoded `'ajax.php'` → `API_URL` in products.php, customers.php
- Added master catalog (190 produk, 19 kategori, 23 satuan)
- Added import from master catalog feature
- Added auto-sync new tenant products to master catalog
- Updated all 19 MD documentation files
- Database: Dropped migrations & personal_access_tokens tables
- Database: Fixed admin tenant_id → NULL
- Database: Fixed tax_rates (tenant_id → NULL, rate → percentage)
- Database: Seeded 4 subscription plans
- Database: Added subscription_plan_id to tenants table

---

## Cycle 2 — Bug Fixes & Test Pass

### Bugs Found & Fixed

1. **`sales.php:47` — Undefined `$branchId`**
   - Added `$branchId = $user['branch_id'] ?? null;`

2. **`users.php:128` — `renderHead()` undefined**
   - Replaced `new PDO()` with `db()` singleton
   - Replaced all `$db->` with `$d->`

3. **`tenants.php:40` — `renderHead()` undefined**
   - Replaced `$db` with `$d` for consistency

4. **`register.php:169` — Redirect to login (config.php enforces login)**
   - Changed `require_once 'config.php'` back to `require_once 'auth.php'`
   - Register page is public, should not require login

5. **Database readonly — `auth.php:74` write failed**
   - Fixed: `chmod 666 database/database.sqlite && chmod 777 database/`

6. **`products.name UNIQUE` — Multi-tenant conflict**
   - Recreated table with `UNIQUE(name, tenant_id)` composite index
   - Two tenants can now have products with the same name

7. **`payment_methods.code UNIQUE` — Multi-tenant conflict**
   - Recreated table with `UNIQUE(code, tenant_id)` composite index
   - Fixed register.php to use standard codes (cash, transfer, credit, qris, ewallet)
   - Inserted standard payment methods for tenant 2 and global (NULL)

8. **12 more tables with UNIQUE constraints fixed:**
   - unit_measurements, tax_rates, adjustment_types, delivery_methods
   - status_codes, e_faktur_types, whatsapp_template_types, whatsapp_templates
   - vehicles, delivery_routes, e_faktur, period_closings

9. **Master catalog sync fatal error**
   - Wrapped auto-sync in try-catch (best-effort, don't fail product creation)

10. **Products test — strict mode violation**
    - Fixed: `table.table-striped` → `#productsTable`
    - Fixed: `th:has-text("Nama")` → `#productsTable th:has-text("Nama")`

---

## Test Results

| Cycle | Passed | Failed | Total |
|-------|--------|--------|-------|
| Cycle 1 | 82 | 6 | 88 |
| Cycle 2 | 88 | 0 | 88 |

---

## Current Stats

| Metric | Value |
|--------|-------|
| Frontend PHP files | 49 |
| AJAX endpoints | 60 |
| ajax.php lines | 4049 |
| Database tables | 84 |
| Playwright E2E specs | 26 |
| Test cases | 88 |
| Master catalog products | 190 |
| Subscription plans | 4 |
| Roles | 7 |
| Tenants | 7 |
| Users | 19 |
