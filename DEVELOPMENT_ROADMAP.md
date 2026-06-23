# DEVELOPMENT ROADMAP

# PANGLONG ERP - EXECUTION PLAN

## Version: 2.1
## Last Updated: 2025-06-24
## Status: Phase 1 MVP - COMPLETED

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

Panglong ERP adalah platform ERP distribksi material bangunan yang dikembangkan dalam 4 fase. Phase 1 MVP telah selesai dengan fokus: POS, Inventory, Accounts Receivable/Payable, Delivery System, dan Reporting.

**Status kode saat ini:**
- Backend Laravel API: 100% functional
- Frontend PHP Native: 100% functional (17 pages)
- Database migrations: 100% created dan tested dengan MySQL
- Testing: PHPUnit (19 tests, 47 assertions) + Playwright E2E (29 tests) - ALL PASSING
- Documentation: 100% updated

---

# 2. AUDIT STATUS SAAT INI

## 2.1 Yang SUDAH Ada (berfungsi penuh)

| Komponen | Status | Catatan |
|----------|--------|---------|
| composer.json | OK | Laravel 10, Sanctum, Spatie Permission |
| 26 Migration files | OK | Tested dengan MySQL live |
| 27 Eloquent Models | OK | Relationships defined, casts set |
| 8 Service classes | OK | SaleService, StockService, ProductService, PricingService, ReportService, PaymentService, AuthService, CustomerService |
| 12 API Controllers | OK | Full CRUD + custom endpoints |
| API Routes (api.php) | OK | All endpoints with permission middleware |
| 10 Seeder classes | OK | Roles, permissions, users, categories, app settings, dll |
| Frontend PHP Native | OK | 17 pages (login, dashboard, products, customers, sales, deliveries, stock, stock_opname, suppliers, purchase-orders, reports, settings, users, print_nota, customer_detail, product_detail, sale_detail) |
| .gitignore | OK | .env, vendor/, storage/, test artifacts excluded |
| PHPUnit | OK | 19 tests, 47 assertions |
| Playwright E2E | OK | 29 tests, all passing |
| Form Request classes | OK | 7 classes (StoreSale, StoreProduct, UpdateProduct, dll) |
| API Resource classes | OK | 6 classes (SaleResource, ProductResource, dll) |
| Model Factories | OK | 9 factories (User, Product, Customer, Sale, dll) |
| AuditLog Observer | OK | Auto-logs created/updated/deleted |

## 2.2 Yang TIDAK Ada (by design - Phase 2+)

| Komponen | Fase | Catatan |
|----------|------|---------|
| Multi-tenant architecture | Phase 3 | SaaS feature |
| Offline-first sync | Phase 3 | Mobile/PWA |
| Accounting engine (jurnal otomatis) | Phase 2 | Double-entry bookkeeping |
| AI reorder suggestion | Phase 2 | Predictive analytics |
| Multi-warehouse | Phase 2 | Branch support |
| Mobile app | Phase 4 | React Native |
| Cloud deployment | Phase 3 | SaaS hosting |

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
- [x] 25 Playwright E2E tests

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

## Frontend
- **Approach**: PHP Native (procedural, session-based, calls Laravel API via cURL)
- **CSS**: Bootstrap 5.3.x (CDN)
- **JS**: jQuery 3.6.x (CDN)
- **Icons**: Bootstrap Icons (CDN)
- **Charts**: Chart.js (CDN, untuk dashboard)

## Development Tools
- **Testing**: PHPUnit 10.x (19 tests) + Playwright E2E (29 tests)
- **Version Control**: Git (GitHub: 82080038/panglong)
- **Package Manager**: Composer (PHP), npm (Playwright)

## Key Credentials
- MySQL: root/root (XAMPP)
- Default users: admin/password123, manager1/password123, kasir1/password123, gudang1/password123
- Role slugs: owner, manager, kasir, gudang, accounting, supervisor
- API_URL: http://127.0.0.1:8000/api/v1
- Frontend: http://localhost/panglong/frontend/

---

# 6. FASE DEVELOPMENT

## Phase 1: MVP - COMPLETED

### Frontend Pages (17 total)
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

## Phase 2: Enhancement - 3-4 Bulan (NEXT)
- [ ] Accounting Engine (jurnal otomatis, double-entry)
- [ ] AI Basic (reorder suggestion, demand forecasting)
- [ ] Multi-warehouse support
- [ ] Advanced reporting (custom report builder)
- [ ] Barcode scanning for POS
- [ ] Email notifications (invoice, payment receipt)
- [ ] SMS notifications (payment due reminder)
- [ ] Bank integration (payment verification)

## Phase 3: SaaS - 3-4 Bulan
- [ ] Multi-tenant architecture
- [ ] Offline-first sync engine
- [ ] Cloud deployment (Docker + Kubernetes)
- [ ] SaaS billing system
- [ ] White label support

## Phase 4: Advanced - Ongoing
- [ ] AI advanced & predictive analytics
- [ ] Marketplace integration (Tokopedia, Shopee)
- [ ] Mobile app (React Native)
- [ ] IoT integration (smart warehouse)

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

## Phase 1 MVP - ALL CHECKED
- [x] Semua sprint selesai (Sprint 1-4 + Gap Fix)
- [x] `php artisan test` pass (19 tests, 47 assertions)
- [x] Tidak ada error di `php artisan migrate:fresh --seed`
- [x] API endpoint dapat diakses via cURL
- [x] Frontend dapat login dan menampilkan data dari API
- [x] Code committed dengan pesan yang jelas
- [x] Playwright E2E tests pass (29 tests)
- [x] All 17 gap fixes implemented
- [x] Export CSV + Print-to-PDF on reports
- [x] Session timeout implemented
- [x] Permission middleware on all routes
- [x] Audit log auto-trigger on model events
- [x] Search/filter on all list pages
- [x] Customer/product detail pages
- [x] Settings page (tax, company, session)
- [x] Stock valuation report
