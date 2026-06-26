# DEVELOPMENT ROADMAP

# PANGLONG ERP - EXECUTION PLAN

## Version: 7.0
## Last Updated: 2026-06-26
## Status: ALL SPRINTS (1-12) + GAP FEATURES + UI/UX COMPLETED

> **ARSITEKTUR AKTUAL:** Frontend menggunakan PHP Native + PDO SQLite + jQuery AJAX.
> `frontend/ajax.php` adalah single endpoint (1940 lines, 48 endpoints) untuk semua CRUD operations.
> Laravel backend API ada di repo tetapi TIDAK digunakan oleh frontend.
> Database: SQLite (`database/database.sqlite`, 78 tables, 1.3MB).
> **Sprint 7-12 (Juni 2026):** Retur, Quotation, Sales Order, Pricing, Stock Transfer, Cash Book, Fixed Assets, Fleet, Routes, WhatsApp, e-Faktur.
> **Gap Features (Juni 2026):** Landed cost, Batch/FIFO, Cash Flow, Closing, Salesman App — ALL DONE.
> **UI/UX (Juni 2026):** RBAC nav, dark mode, eye-care mode, fullscreen, responsive design.

---

# DAFTAR ISI

1. [Ringkasan Eksekutif](#1-ringkasan-eksekutif)
2. [Audit Status Saat Ini](#2-audit-status-saat-ini)
3. [Sprint History](#3-sprint-history)
4. [Gap Fix History](#4-gap-fix-history)
5. [Tech Stack Final](#5-tech-stack-final)
6. [Fase Development](#6-fase-development)
7. [Issue Tracker](#7-issue-tracker)
8. [Definition of Done](#8-definition-of-done)

---

# 1. RINGKASAN EKSEKUTIF

Panglong ERP adalah platform ERP distribksi material bangunan yang dikembangkan dalam 4 fase. SEMUA FASE (1-4) telah selesai dengan fokus: POS, Inventory, AR/AP, Delivery, Reporting, Accounting Engine, Multi-warehouse, AI, Multi-tenant SaaS, Marketplace Integration, dan IoT.

**Status kode saat ini:**
- Backend Laravel API: 100% functional (33 controllers, 20 services) — TIDAK digunakan frontend
- Frontend PHP Native: 100% functional (45 pages, 48 AJAX endpoints)
- Database migrations: 37 migration files, all executed to SQLite (78 tables)
- Testing: PHPUnit (14 files) + Playwright E2E (19 specs, 50 tests) - ALL PASSING
- Docker deployment ready (Dockerfile + docker-compose + nginx)
- PWA offline-first (manifest.json + service worker)
- RBAC navigation per role, dark mode, eye-care mode, fullscreen toggle
- Documentation: 100% updated

---

# 2. AUDIT STATUS SAAT INI

## 2.1 Yang SUDAH Ada (berfungsi penuh)

| Komponen | Status | Catatan |
|----------|--------|---------|
| composer.json | OK | Laravel 10, Sanctum, Spatie Permission |
| 37 Migration files | OK | All executed to SQLite (78 tables) |
| 63 Eloquent Models | OK | Relationships defined, casts set |
| 20 Service classes | OK | Sale, Stock, Product, Pricing, Report, Payment, Auth, Customer, Accounting, Notification, Bank, SaaSBilling, Sync, AI, Marketplace, IoT, Return, Quotation, CashManagement, FixedAsset |
| 33 API Controllers | OK | Full CRUD + custom endpoints |
| API Routes (api.php) | OK | All endpoints with permission + tenant middleware |
| 16 Seeder classes | OK | Roles, permissions, users, categories, app settings, chart of accounts, warehouses, subscription plans, dll |
| Frontend PHP Native | OK | 45 pages (login, dashboard, products, product_detail, customers, customer_detail, sales, sale_detail, deliveries, stock, stock_opname, suppliers, purchase-orders, reports, settings, users, print_nota, accounting, warehouses, reorder, saas, ai_insights, marketplace, iot, quotations, sales_orders, returns, pricing, stock_transfers, cashbook, fixed_assets, fleet, routes, whatsapp, e_faktur, landed_cost, batches, cash_flow, closing, salesman_app) |
| Docker Deployment | OK | Dockerfile, docker-compose.yml, nginx.conf |
| PWA Offline-First | OK | manifest.json, sw.js service worker |
| .gitignore | OK | .env, vendor/, storage/, test artifacts excluded |
| PHPUnit | OK | 14 test files |
| Playwright E2E | OK | 19 specs, 50 tests, all passing |
| Form Request classes | OK | 7 classes |
| API Resource classes | OK | 6 classes |
| Model Factories | OK | 9 factories |
| AuditLog Observer | OK | Auto-logs created/updated/deleted |
| Multi-tenant | OK | Tenant scoping, BelongsToTenant trait, TenantScope middleware |
| SaaS Billing | OK | 3 plans (Starter/Business/Enterprise), trial, subscription, invoices |
| Offline Sync | OK | SyncService push/pull/status, SyncLog model |

## 2.2 Yang TIDAK Ada (future enhancement)

| Komponen | Catatan |
|----------|---------|
| Mobile app | React Native/Flutter - native mobile app (skipped per user request) |
| Production SMS gateway | Siap untuk Zenziva/Twilio, currently log mode |
| Production bank API | Siap untuk Midtrans/Xendit, currently manual mode |
| Production marketplace API | Siap untuk Tokopedia/Shopee API, requires credentials |

---

# 3. SPRINT HISTORY

## Sprint 1: Foundation Fix - COMPLETED
- [x] Buat `.gitignore`
- [x] Remove `.env`, `storage/`, `database.sqlite` dari git tracking
- [x] Fix circular FK: hapus `base_unit_id` dari products table
- [x] Buat `phpunit.xml`
- [x] Buat direktori `tests/Unit/` dan `tests/Feature/`
- [x] Buat `tests/TestCase.php` base class
- [x] Register CheckPermission middleware di Kernel.php
- [x] Verifikasi `composer install` + `php artisan migrate` berjalan
- [x] Verifikasi `php artisan db:seed` berjalan
- [x] Test login API endpoint

## Sprint 2: Core API Stabilization - COMPLETED
- [x] Buat Form Request classes (StoreSaleRequest, StoreProductRequest, dll)
- [x] Buat API Resource classes (SaleResource, ProductResource, dll)
- [x] Fix missing imports di controllers
- [x] Standardisasi error response format
- [x] Buat model factories (User, Product, Customer, Sale, StockMovement, dll)
- [x] Tulis unit tests untuk SaleService
- [x] Tulis unit tests untuk StockService
- [x] Tulis feature tests untuk Auth API
- [x] Tulis feature tests untuk Sales API

## Sprint 3: Frontend Development - COMPLETED
- [x] Frontend login page - koneksi ke API
- [x] Frontend dashboard - tampilkan stats real dari API
- [x] Frontend POS - transaksi penjualan
- [x] Frontend products - CRUD products
- [x] Frontend customers - CRUD customers
- [x] Frontend stock - view stock, adjustment
- [x] Frontend sales - list, detail, payment
- [x] Print nota (thermal 80mm)

## Sprint 4: Integration & Polish - COMPLETED
- [x] End-to-end testing: login -> create sale -> stock update -> payment
- [x] Stock opname flow
- [x] Piutang management UI (AR aging)
- [x] Hutang supplier UI (AP aging, PO payment)
- [x] Reports (daily sales, monthly, low stock, AR aging, AP aging, sales by product/customer, profit/loss, stock movement, dead stock, stock valuation)
- [x] Audit log implementation (auto-trigger on model events)
- [x] Permission middleware applied to all API routes
- [x] Login attempt limit (5 attempts, 15 min lock)
- [x] 50 Playwright E2E tests (19 specs)

---

# 4. GAP FIX HISTORY

## Gap Fix Sprint (June 2025) - COMPLETED

### CRITICAL (5/5)
- [x] Pricing engine - PricingService with auto-fill price, customer group discount, margin check
- [x] Walk-in customer support - nullable customer_id, "Walk-in Customer" option
- [x] Credit limit check on credit sales - blocks if exceeding customer.credit_limit
- [x] Negative stock prevention - pre-checks getCurrentStock() before sale
- [x] Delivery/surat jalan system - full CRUD, status workflow, frontend page

### HIGH (6/6)
- [x] Per-item discount in frontend + customer group auto-apply
- [x] Configurable tax rate - AppSetting model, PricingService::getTaxRate()
- [x] AP auto-create on PO credit + payment endpoint + AP aging report
- [x] PO partial receive - received_quantity column, receive modal
- [x] Stock opname frontend page - server-side stock loading, form POST
- [x] Product edit frontend - edit modal with all fields

### MEDIUM (3/3)
- [x] Missing reports (sales by product/customer, P/L, stock movement, dead stock, AP aging, stock valuation)
- [x] Pagination + search/filter on customers, products, suppliers pages
- [x] Customer/product detail pages with purchase history and stock info
- [x] Export CSV + Print-to-PDF on reports page

### LOW (2/2)
- [x] Session timeout (30 min idle) + redirect to login
- [x] Stock valuation (average cost method)

---

# 5. TECH STACK FINAL

## Backend
- **Framework**: Laravel 10.x (PHP 8.3)
- **Database**: MySQL 8.0+ (XAMPP, socket: /opt/lampp/var/mysql/mysql.sock)
- **Auth**: Laravel Sanctum (token-based API)
- **Permission**: spatie/laravel-permission
- **Cache**: File cache

## Frontend (Yang Aktif Berjalan)
- **Approach**: PHP Native (procedural, session-based, PDO SQLite langsung)
- **Database Access**: PDO SQLite via `frontend/db.php` → `database/database.sqlite`
- **AJAX Endpoint**: `frontend/ajax.php` (1940 lines, 48 endpoints) — single endpoint untuk semua CRUD
- **Auth**: Session-based via `frontend/auth.php` dengan `password_verify()`
- **CSS**: Bootstrap 5.3.x (CDN)
- **JS**: jQuery 3.6.x (CDN) — `$.ajax()` calls to `ajax.php`
- **Icons**: Bootstrap Icons (CDN)
- **Charts**: Chart.js 4.4.0 (CDN, untuk dashboard)
- **API_URL**: `'ajax.php'` (local, NOT Laravel API URL)
- **Session Timeout**: 30 minutes idle → redirect to login

## Development Tools
- **Testing**: PHPUnit 10.x (Laravel backend, 14 files) + Playwright E2E (19 specs, 50 tests, frontend)
- **Version Control**: Git (GitHub: 82080038/panglong)
- **Package Manager**: Composer (PHP), npm (Playwright)

## Key Credentials
- MySQL: root/root (XAMPP)
- Default users: admin/password123, manager1/password123, kasir1/password123, gudang1/password123
- Role slugs: owner, manager, kasir, gudang, accounting, supervisor
- Laravel API URL: http://127.0.0.1:8000/api/v1 (TIDAK digunakan frontend)
- Frontend AJAX endpoint: `ajax.php` (relative path, PHP Native)
- Frontend: http://localhost/panglong/frontend/
- PHP: XAMPP `/opt/lampp/bin/php` (8.2.12) — has pdo_sqlite
- System PHP (8.3.6) does NOT have pdo_sqlite

---

# 6. FASE DEVELOPMENT

## Phase 1: MVP - COMPLETED

### Frontend Pages (45 total)
1. login.php - Login with quick login buttons
2. index.php - Dashboard with real API data + Chart.js
3. products.php - Product CRUD with multi-unit + search + edit
4. product_detail.php - Product detail with stock info
5. customers.php - Customer CRUD with search
6. customer_detail.php - Customer detail with purchase history
7. sales.php - POS with walk-in, per-item discount, delivery, auto-price
8. deliveries.php - Delivery/surat jalan management
9. stock.php - Stock list with status badges + adjustment
10. stock_opname.php - Stock opname with physical count
11. suppliers.php - Supplier CRUD with search
12. purchase-orders.php - PO with partial receive + payment
13. reports.php - 11 report tabs + export CSV/PDF
14. settings.php - Tax config, company info, session timeout
15. users.php - User management (owner/manager only)
16. print_nota.php - Thermal 80mm print
17. sale_detail.php - Sale detail view
18. quotations.php - Quotation with bonus qty, valid until, convert to SO
19. sales_orders.php - Sales Order with delivered_qty tracking
20. returns.php - Sales Return & Purchase Return with approval
21. pricing.php - Customer pricing, tier pricing, supplier price history
22. stock_transfers.php - Stock transfer between warehouses
23. cashbook.php - Cash Book (cash in/out, bank statements, reconciliation)
24. fixed_assets.php - Fixed assets with auto depreciation
25. fleet.php - Vehicle management & maintenance log
26. routes.php - Delivery route planning with multi-stop
27. whatsapp.php - WhatsApp notification templates & message log
28. e_faktur.php - e-Faktur (PPN Masukan/Keluaran, CSV export DJP)
29. accounting.php - Trial balance, balance sheet, income statement, GL
30. ai_insights.php - AI insights dashboard
31. marketplace.php - Marketplace integration
32. iot.php - IoT sensor readings
33. reorder.php - Reorder AI suggestions
34. saas.php - SaaS subscription management
35. warehouses.php - Warehouse management
36. customer_detail.php - Customer detail with purchase history
37. product_detail.php - Product detail with stock info
38. print_nota.php - Thermal 80mm print
39. manifest.json - PWA manifest
40. sw.js - Service worker for PWA

### API Endpoints
- Auth: login, logout, me
- Products: CRUD + search + units + barcodes
- Customers: CRUD + search
- Sales: CRUD + payment + void + price endpoint
- Deliveries: CRUD + status update
- Stock: list + adjustment + opname
- Suppliers: CRUD
- Purchase Orders: CRUD + partial receive + payment
- Reports: 11 report types
- Settings: get + update
- Users: list + roles

## Phase 2: Enhancement - COMPLETED
- [x] Accounting Engine (double-entry, trial balance, balance sheet, income statement, general ledger, manual journal)
- [x] AI Basic (reorder suggestion, demand forecasting)
- [x] Multi-warehouse support (warehouse CRUD, stock transfer)
- [x] Advanced reporting (custom report builder with group-by)
- [x] Barcode scanning for POS (lookup endpoint + scanner input)
- [x] Email notifications (invoice, payment receipt, AR/AP due reminders)
- [x] SMS notifications (payment due reminder via gateway)
- [x] Bank integration (payment verification, statements)

## Phase 3: SaaS - COMPLETED
- [x] Multi-tenant architecture (tenants table, tenant_id scoping, BelongsToTenant trait, TenantScope middleware)
- [x] Offline-first sync engine (SyncService push/pull/status, SyncLog, PWA manifest + service worker)
- [x] Cloud deployment (Dockerfile, docker-compose, nginx config)
- [x] SaaS billing system (3 plans: Starter/Business/Enterprise, trial, subscription, invoices, payment)
- [x] White label support (customizable branding per tenant: logo, colors, company info)

## Phase 4: Advanced - COMPLETED
- [x] AI advanced & predictive analytics (demand forecasting with moving average + trend + seasonality, price optimization with elasticity model)
- [x] Marketplace integration (Tokopedia, Shopee, Bukalapak, Lazada, Blibli - connect, sync stock, map products)
- [x] IoT integration (smart warehouse sensors: temperature, humidity, weight, proximity, door - registration, readings, alerts)
- [~] Mobile app (React Native) - SKIPPED per user request

---

# 7. ISSUE TRACKER

| ID | Severity | Title | Status |
|----|----------|-------|--------|
| ISSUE-001 | CRITICAL | .env ter-commit ke git | RESOLVED |
| ISSUE-002 | HIGH | Circular FK: products <-> product_units | RESOLVED |
| ISSUE-003 | HIGH | Inkonsistensi tech stack di dokumen | RESOLVED |
| ISSUE-004 | MEDIUM | Storage & database.sqlite di git | RESOLVED |
| ISSUE-005 | MEDIUM | Tidak ada tests/ directory | RESOLVED |
| ISSUE-006 | MEDIUM | stock_adjustments.status column missing | RESOLVED |
| ISSUE-007 | MEDIUM | stock_opnames.status column missing | RESOLVED |
| ISSUE-008 | LOW | Frontend hardcoded "Admin" di navbar | RESOLVED |
| ISSUE-009 | LOW | API_URL hardcoded ke 127.0.0.1:8000 | RESOLVED (config.php) |

---

# 8. DEFINITION OF DONE

## All Sprints (1-12) - ALL CHECKED
- [x] Semua sprint selesai (Sprint 1-12)
- [x] Playwright E2E tests pass (50 tests)
- [x] All 17 gap fixes implemented
- [x] Sprint 7: Retur (sales/purchase), Quotation, Sales Order
- [x] Sprint 8: Delivery cost, landed cost fields, bonus qty, weight/dimension
- [x] Sprint 9: Customer-specific pricing, tier pricing, supplier price history
- [x] Sprint 10: Stock adjustment, stock transfer, warehouse locations
- [x] Sprint 11: Cash book, bank reconciliation, fixed assets, depreciation
- [x] Sprint 12: Fleet management, delivery routes, WhatsApp, e-Faktur
- [x] Export CSV + Print-to-PDF on reports
- [x] Session timeout implemented
- [x] Permission middleware on all routes
- [x] Audit log auto-trigger on model events
- [x] Search/filter on all list pages
- [x] Customer/product detail pages
- [x] Settings page (tax, company, session)
- [x] Stock valuation report
