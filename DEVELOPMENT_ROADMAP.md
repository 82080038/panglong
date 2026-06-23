# DEVELOPMENT ROADMAP

# PANGLONG ERP - EXECUTION PLAN

## Version: 2.0
## Last Updated: 2025-06-23
## Status: Active Development

---

# DAFTAR ISI

1. [Ringkasan Eksekutif](#1-ringkasan-eksekutif)
2. [Audit Status Saat Ini](#2-audit-status-saat-ini)
3. [Resolusi Inkonsistensi Dokumen](#3-resolusi-inkonsistensi-dokumen)
4. [Tech Stack Final](#4-tech-stack-final)
5. [Fase Development](#5-fase-development)
6. [Sprint Plan](#6-sprint-plan)
7. [Issue Tracker](#7-issue-tracker)
8. [Definition of Done](#8-definition-of-done)

---

# 1. RINGKASAN EKSEKUTIF

Panglong ERP adalah platform ERP distribksi material bangunan yang dikembangkan dalam 4 fase. Saat ini berada di **Phase 1 MVP** dengan fokus: POS, Inventory, dan Hutang.

**Status kode saat ini:**
- Backend Laravel API: ~70% scaffolded, ~30% functional
- Frontend PHP Native: ~20% scaffolded (dashboard, login, basic CRUD pages)
- Database migrations: 100% created, belum di-test dengan MySQL
- Testing: 0% (tidak ada direktori tests/)
- Documentation: 100% written, namun ada inkonsistensi

---

# 2. AUDIT STATUS SAAT INI

## 2.1 Yang SUDAH Ada (berfungsi)

| Komponen | Status | Catatan |
|----------|--------|---------|
| composer.json | OK | Laravel 10, Sanctum, Spatie Permission |
| 25 Migration files | OK | Belum di-test dengan MySQL live |
| 25 Eloquent Models | OK | Relationships defined |
| 7 Service classes | Partial | Logic ada, belum verified |
| 10 API Controllers | Partial | CRUD methods ada, belum semua di-test |
| API Routes (api.php) | OK | Semua endpoint terdaftar |
| 8 Seeder classes | OK | Roles, permissions, users, categories, dll |
| Frontend PHP Native | Partial | 8 file PHP (login, dashboard, products, customers, sales, stock) |
| .env.example | OK | Template konfigurasi |

## 2.2 Yang BELUM Ada / Missing

| Komponen | Prioritas | Catatan |
|----------|-----------|---------|
| `.gitignore` | CRITICAL | .env, vendor/, storage/, database.sqlite ter-commit |
| `tests/` directory | HIGH | Tidak ada test file sama sekali |
| `phpunit.xml` | HIGH | Tidak ada konfigurasi testing |
| `database/factories/` | HIGH | Tidak ada model factories |
| `package.json` | MEDIUM | Untuk frontend assets (Bootstrap, jQuery, Chart.js) |
| `resources/views/` | LOW | Frontend menggunakan PHP Native, bukan Blade |
| Form Request classes | HIGH | Validasi masih inline di controller |
| API Resource classes | MEDIUM | Response masih return model langsung |
| Custom Exception handlers | MEDIUM | Error handling belum standardized |
| Middleware aliases | HIGH | CheckPermission belum terdaftar di Kernel |
| `config/permissions.php` | LOW | Konfigurasi permission Spatie |
| AuditLog trait/observer | MEDIUM | Audit logging belum auto-trigger |
| Stock locking mechanism | MEDIUM | Race condition prevention |

## 2.3 Issue Kritis

### ISSUE-001: Security - .env ter-commit ke Git
- **Severity**: CRITICAL
- **File**: `.env`
- **Dampak**: Credentials terbuka
- **Solusi**: Tambah `.gitignore`, `git rm --cached .env`

### ISSUE-002: Database - Circular FK Dependency
- **Severity**: HIGH
- **File**: `DATABASE_SCHEMA.md`, migration products & product_units
- **Dampak**: `products.base_unit_id` → `product_units.id` dan `product_units.product_id` → `products.id` = circular
- **Solusi**: Hapus FK `base_unit_id` dari `products`, ambil via query `WHERE is_base_unit = 1`

### ISSUE-003: Inkonsistensi Tech Stack
- **Severity**: HIGH
- **File**: `MASTER_BLUEPRINT.md` vs `MVP_SCOPE.md`
- **Dampak**: Blueprint menyebut "PHP Native", implementasi menggunakan Laravel
- **Solusi**: Update blueprint, dokumentasikan bahwa Laravel adalah backend API + PHP Native sebagai frontend

### ISSUE-004: Storage & Database di Git
- **Severity**: MEDIUM
- **Files**: `storage/framework/sessions/*`, `storage/logs/laravel.log`, `database/database.sqlite`
- **Dampak**: Repo bloat, potential data leak
- **Solusi**: `.gitignore` + `git rm --cached`

### ISSUE-005: Tidak Ada Autoloader Test
- **Severity**: MEDIUM
- **Dampak**: `tests/` namespace terdaftar di composer.json tapi direktori tidak ada
- **Solusi**: Buat direktori `tests/Unit/` dan `tests/Feature/` + `phpunit.xml`

---

# 3. RESOLUSI INKONSISTENSI DOKUMEN

## 3.1 MASTER_BLUEPRINT vs Implementasi

| Blueprint | Implementasi | Resolusi |
|-----------|-------------|----------|
| PHP Native | Laravel 10 API + PHP Native frontend | Blueprint adalah vision document. Implementasi: Laravel API backend, PHP Native frontend |
| MySQL/MariaDB | SQLite (default) + MySQL config | Production: MySQL. Dev: SQLite OK |
| jQuery + Bootstrap | Bootstrap 5 + jQuery 3.6 (CDN) | Sesuai |
| Offline First | Tidak ada | Phase 3, bukan MVP |
| Multi Tenant | Tidak ada | Phase 3, bukan MVP |

## 3.2 MVP_SCOPE vs Implementasi

| MVP Scope | Implementasi | Status |
|-----------|-------------|--------|
| Laravel 10.x | composer.json: ^10.10 | Sesuai |
| MySQL 8.0+ | .env: SQLite default | Perlu config MySQL |
| Bootstrap 5.x | CDN 5.3.0 | Sesuai |
| jQuery 3.x | CDN 3.6.0 | Sesuai |
| PHPUnit | Tidak ada tests/ | MISSING |
| Sanctum auth | Terdaftar di routes | Partial - belum verified |

## 3.3 DATABASE_SCHEMA vs Migrations

| Schema Doc | Migration Files | Status |
|-----------|----------------|--------|
| 25 tables | 25 migrations | Sesuai |
| products.base_unit_id FK | Migration ada | CIRCULAR - perlu fix |
| stock_adjustments.status | Tidak ada kolom status di migration | MISSING |
| stock_opnames.status | Tidak ada kolom status di migration | MISSING |

---

# 4. TECH STACK FINAL

## Backend
- **Framework**: Laravel 10.x (PHP 8.1+)
- **Database**: MySQL 8.0+ / MariaDB 10.6+ (production), SQLite (dev/testing)
- **Auth**: Laravel Sanctum (token-based API)
- **Permission**: spatie/laravel-permission
- **Queue**: Database queue (Laravel built-in)
- **Cache**: File cache (Redis optional)

## Frontend
- **Approach**: PHP Native (procedural, session-based, calls Laravel API via cURL)
- **CSS**: Bootstrap 5.3.x (CDN)
- **JS**: jQuery 3.6.x (CDN)
- **Icons**: Bootstrap Icons (CDN)
- **Charts**: Chart.js (CDN, untuk dashboard)

## Development Tools
- **Testing**: PHPUnit 10.x
- **Code Quality**: Laravel Pint (PSR-12)
- **Version Control**: Git
- **Package Manager**: Composer (PHP), npm (optional untuk asset compilation)

---

# 5. FASE DEVELOPMENT

## Phase 1: MVP (Saat Ini) - 3-4 Bulan

### Sprint 1: Foundation Fix (Minggu 1-2)
- [ ] Buat `.gitignore`
- [ ] Remove `.env`, `storage/`, `database.sqlite` dari git tracking
- [ ] Fix circular FK: hapus `base_unit_id` dari products table
- [ ] Buat `phpunit.xml`
- [ ] Buat direktori `tests/Unit/` dan `tests/Feature/`
- [ ] Buat `tests/TestCase.php` base class
- [ ] Register CheckPermission middleware di Kernel.php
- [ ] Verifikasi `composer install` + `php artisan migrate` berjalan
- [ ] Verifikasi `php artisan db:seed` berjalan
- [ ] Test login API endpoint

### Sprint 2: Core API Stabilization (Minggu 3-4)
- [ ] Buat Form Request classes (CreateSaleRequest, CreateProductRequest, dll)
- [ ] Buat API Resource classes (SaleResource, ProductResource, dll)
- [ ] Fix missing imports di controllers
- [ ] Standardisasi error response format
- [ ] Buat model factories (User, Product, Customer, Sale, StockMovement)
- [ ] Tulis unit tests untuk SaleService
- [ ] Tulis unit tests untuk StockService
- [ ] Tulis feature tests untuk Auth API
- [ ] Tulis feature tests untuk Sales API

### Sprint 3: Frontend Development (Minggu 5-8)
- [ ] Frontend login page - koneksi ke API
- [ ] Frontend dashboard - tampilkan stats real dari API
- [ ] Frontend POS - transaksi penjualan
- [ ] Frontend products - CRUD products
- [ ] Frontend customers - CRUD customers
- [ ] Frontend stock - view stock, adjustment
- [ ] Frontend sales - list, detail, payment
- [ ] Print nota (thermal/A4)

### Sprint 4: Integration & Polish (Minggu 9-12)
- [ ] End-to-end testing: login → create sale → stock update → payment
- [ ] Stock opname flow
- [ ] Piutang management UI
- [ ] Hutang supplier UI
- [ ] Reports (daily sales, low stock, AR aging)
- [ ] Audit log implementation (auto-trigger on model events)
- [ ] Performance testing
- [ ] Bug fixes
- [ ] Deployment documentation

## Phase 2: Enhancement - 3-4 Bulan
- Accounting Engine (jurnal otomatis)
- Delivery System (surat jalan, tracking basic)
- AI Basic (reorder suggestion)
- Multi-warehouse support
- Advanced reporting

## Phase 3: SaaS - 3-4 Bulan
- Multi-tenant architecture
- Offline-first sync engine
- Cloud deployment
- SaaS billing system

## Phase 4: Advanced - Ongoing
- AI advanced & predictive analytics
- Marketplace integration
- Mobile app
- White label system

---

# 6. SPRINT PLAN

## Sprint 1 Aktif: Foundation Fix

| Task | Estimasi | Status |
|------|----------|--------|
| Buat .gitignore | 5 menit | TODO |
| Git rm cached (.env, storage, sqlite) | 5 menit | TODO |
| Fix circular FK di migration | 30 menit | TODO |
| Buat phpunit.xml | 10 menit | TODO |
| Buat tests/ struktur | 15 menit | TODO |
| Fix middleware Kernel.php | 10 menit | TODO |
| composer install + migrate test | 30 menit | TODO |
| db:seed test | 15 menit | TODO |
| API login test (curl) | 15 menit | TODO |

---

# 7. ISSUE TRACKER

| ID | Severity | Title | Status |
|----|----------|-------|--------|
| ISSUE-001 | CRITICAL | .env ter-commit ke git | OPEN |
| ISSUE-002 | HIGH | Circular FK: products ↔ product_units | OPEN |
| ISSUE-003 | HIGH | Inkonsistensi tech stack di dokumen | OPEN |
| ISSUE-004 | MEDIUM | Storage & database.sqlite di git | OPEN |
| ISSUE-005 | MEDIUM | Tidak ada tests/ directory | OPEN |
| ISSUE-006 | MEDIUM | stock_adjustments.status column missing di migration | OPEN |
| ISSUE-007 | MEDIUM | stock_opnames.status column missing di migration | OPEN |
| ISSUE-008 | LOW | Frontend menggunakan hardcoded "Admin" di navbar | OPEN |
| ISSUE-009 | LOW | API_URL hardcoded ke 127.0.0.1:8000 di frontend | OPEN |

---

# 8. DEFINITION OF DONE

## Per Sprint
- [ ] Semua task sprint selesai
- [ ] `php artisan test` pass (setelah tests dibuat)
- [ ] Tidak ada error di `php artisan migrate:fresh --seed`
- [ ] API endpoint dapat diakses via cURL
- [ ] Frontend dapat login dan menampilkan data dari API
- [ ] Code committed dengan pesan yang jelas

## Per Feature
- [ ] Migration dibuat dan tested
- [ ] Model dengan relationships lengkap
- [ ] Service layer dengan business logic
- [ ] Controller dengan validasi
- [ ] API endpoint terdaftar di routes
- [ ] Unit test untuk service
- [ ] Feature test untuk API endpoint
- [ ] Frontend page functional
- [ ] Dokumentasi API diperbarui

## Per Release (Phase)
- [ ] Semua sprint selesai
- [ ] Test coverage >= 70%
- [ ] Critical path 100% tested
- [ ] Performance target tercapai
- [ ] Deployment guide lengkap
- [ ] User manual tersedia
