# Panglong ERP

Sistem ERP untuk distribusi material bangunan — POS, inventory, AR/AP, akuntansi, delivery, dan lebih banyak lagi.

> **Status:** Sprint 1-12 + Gap Features + UI/UX Enhancements + Master Catalog — ALL COMPLETED
> **Architecture:** PHP Native Procedural + PDO SQLite + jQuery AJAX + Bootstrap 5.3
> **Tests:** 26 Playwright E2E test specs

---

## Quick Start (5 menit)

### Prasyarat
- **XAMPP** (Apache + PHP 8.2+ dengan `pdo_sqlite`) — [download](https://www.apachefriends.org/)
- Browser modern (Chrome/Firefox)
- Optional: Node.js + npm (untuk Playwright E2E tests)

### Langkah-langkah

1. **Clone repo ke XAMPP htdocs**
```bash
cd /opt/lampp/htdocs    # atau C:\xampp\htdocs di Windows
git clone <repo-url> panglong
cd panglong
```

2. **Set permission database (Linux/macOS only)**
```bash
chmod 666 database/database.sqlite
chmod 777 database/
```

3. **Start XAMPP**
```bash
sudo /opt/lampp/lampp start    # Linux
# atau buka XAMPP Control Panel di Windows, klik "Start" Apache
```

4. **Buka aplikasi di browser**
```
http://localhost/panglong/
```
Akan otomatis diarahkan ke halaman login atau dashboard.

5. **Login dengan default credentials**
| Username | Password | Role |
|----------|----------|------|
| admin | password123 | Owner (akses semua menu) |
| manager1 | password123 | Manager |
| kasir1 | password123 | Kasir |
| gudang1 | password123 | Gudang |

> **Database sudah included!** File `database/database.sqlite` (87 tables, seed data + master catalog) sudah ada di repo. Tidak perlu menjalankan migration atau seeder.

---

## Arsitektur

```
[Browser]
  ↓
[PHP Server-Side Rendering] — frontend/*.php (49 files)
  ├── Direct PDO SQLite queries untuk initial page load
  └── fetch(API_URL + '?endpoint=...') → frontend/ajax.php (4044 lines, 60 endpoints) → PDO SQLite
  ↓
[database/database.sqlite] — 87 tables
```

### Yang berjalan saat ini
- **Frontend:** PHP Native procedural (49 files) di `frontend/`
- **Database:** SQLite langsung via PDO (`frontend/db.php`)
- **AJAX:** Single endpoint `frontend/ajax.php` (60 endpoints)
- **Auth:** Session-based (`frontend/auth.php`) dengan `password_verify()`
- **UI:** Bootstrap 5.3 + Bootstrap Icons, dark mode, eye-care mode, fullscreen toggle, responsive
- **Master Catalog:** 190 produk bangunan dengan `tenant_id = NULL` dapat diakses semua tenant

---

## Struktur Project

```
panglong/
├── index.php                 # Gerbang utama → redirect ke login/dashboard
├── frontend/                 # Aplikasi utama (PHP Native)
│   ├── config.php            # Session, navbar (RBAC), dark mode, fullscreen, CDN
│   ├── db.php                # PDO SQLite connection singleton
│   ├── auth.php              # Session auth: login(), logout(), hasPermission()
│   ├── ajax.php              # Single AJAX endpoint (4044 lines, 60 endpoints)
│   ├── login.php             # Login page dengan quick login buttons
│   ├── index.php             # Dashboard
│   ├── products.php          # Product management + import dari master catalog
│   ├── sales.php             # POS / Sales
│   ├── deliveries.php        # Delivery orders
│   ├── stock.php             # Stock management
│   ├── accounting.php        # Accounting (journal, P&L, balance sheet)
│   ├── qr_generator.php      # QR code generator (Google Chart API, no Composer)
│   ├── ... (40+ pages lainnya)
│   ├── manifest.json         # PWA manifest
│   └── sw.js                 # Service worker (offline-first)
│
├── database/
│   └── database.sqlite       # DATABASE AKTIF (87 tables, COMMITTED to git)
│
├── scripts/                  # Utility scripts
│   ├── seed_master_catalog.php   # Seed master catalog (190 produk bangunan)
│   ├── simulate_one_month.php    # Simulasi 1 bulan operasional
│   ├── backup_database.sh        # Backup database
│   ├── clean_data.php            # Bersihkan data
│   ├── export_sqlite.php         # Export database ke SQL
│   ├── import_sqlite.php         # Import database dari SQL
│   ├── add_performance_indexes.php
│   └── setup_cron.sh
│
├── tests/e2e/                # Playwright E2E tests (26 specs)
├── docs/                     # Dokumentasi (18 MD files)
├── playwright.config.js      # Playwright configuration
└── .gitignore
```

---

## Menjalankan Tests

### Playwright E2E (Frontend)
```bash
# Install dependencies
npm install @playwright/test
npx playwright install chromium

# Pastikan XAMPP Apache berjalan, lalu:
npx playwright test --headed --reporter=list --workers=1
```

---

## Database

### File yang sudah ada di repo
- `database/database.sqlite` — SQLite database dengan 87 tables, seed data, dan master catalog (190 produk)

### Default users
| Username | Password | Role | Akses |
|----------|----------|------|-------|
| admin | password123 | owner | Semua menu + Pengguna + Pengaturan + SaaS |
| manager1 | password123 | manager | Semua menu kecuali SaaS |
| kasir1 | password123 | kasir | Penjualan, Pelanggan, Pengiriman, Retur |
| gudang1 | password123 | gudang | Produk, Stok, Opname, Mutasi, Supplier, PO |
| accounting1 | password123 | accounting | Akuntansi, Kas Buku, Aset Tetap, Arus Kas, e-Faktur |
| supervisor1 | password123 | supervisor | Beranda, Laporan |

---

## Master Catalog

Master catalog adalah katalog produk global milik super admin (`tenant_id = NULL`) yang dapat diakses semua tenant:

- **190 produk** material bangunan (semen, besi, cat, pipa, sanitary, listrik, dll.)
- **19 kategori** master
- **23 satuan** master
- Tenant dapat **import produk dari master catalog** ke katalog sendiri
- Produk baru yang ditambahkan tenant **otomatis sync ke master catalog** jika belum ada

---

## UI/UX Features

- **RBAC Navigation** — Menu berbeda per role (owner, manager, kasir, gudang, accounting, supervisor)
- **Dark Mode** — Toggle `data-bs-theme="dark"`, session-based
- **Eye-Care Mode** — Sepia theme untuk pemakaian 24 jam
- **Fullscreen Toggle** — Fullscreen API untuk desktop/large screen
- **Responsive Design** — Mobile, tablet, desktop, ultra-wide
- **PWA** — Installable app dengan offline-first service worker

---

## Dokumentasi Lengkap

| File | Isi |
|------|-----|
| `SETUP_GUIDE.md` | Panduan setup detail (XAMPP, database, testing) |
| `PROJECT_STATUS.md` | Status audit lengkap (stats, komponen, gap features) |
| `DEVELOPMENT_ROADMAP.md` | Roadmap sprint 1-12 + gap features |
| `DATABASE_SCHEMA.md` | Skema 87 tables + ERD |
| `TECHNICAL_DOCUMENTATION.md` | Arsitektur teknis + komponen utama + RBAC nav |
| `API_SPECIFICATION.md` | AJAX endpoint docs (60 endpoints) |
| `TESTING_FRAMEWORK.md` | Playwright E2E testing guide |
| `MVP_SCOPE.md` | Scope MVP + deliverables + success criteria |
| `PANGLONG_BUSINESS_ANALYSIS.md` | Analisis bisnis + gap analysis + rekomendasi |
| `MASTER_BLUEPRINT.md` | Blueprint arsitektur enterprise lengkap |
| `MULTI_TENANT_GUIDE.md` | Panduan multi-tenant + master catalog |
| `PROMPTING_GUIDE.md` | Guide untuk AI-assisted development |
| `DEVELOPMENT_HANDOFF.md` | Panduan handover developer baru |
| `FIELD_REALITY_ANALYSIS.md` | Analisis realita lapangan |
| `IMPLEMENTATION_GUIDE.md` | Guide implementasi fitur |

---

## Penting untuk Developer

1. **Gunakan XAMPP PHP** (`/opt/lampp/bin/php` 8.2.12) — memiliki `pdo_sqlite`. System PHP mungkin tidak punya.
2. **Database file sudah di repo** — `database/database.sqlite` committed to git. Setelah clone, langsung jalan.
3. **Set permission setelah clone** (Linux/macOS): `chmod 666 database/database.sqlite && chmod 777 database/`
4. **100% PHP Native** — Tidak ada Laravel, Composer, atau framework lain. Frontend akses database langsung via PDO SQLite.
5. **API_URL adalah JavaScript constant** — Didefinisikan di `config.php` `renderHead()` sebagai `"ajax.php"`. Gunakan `fetch(API_URL + '?endpoint=...')` untuk semua AJAX calls.
6. **Pattern AJAX** — `fetch(API_URL + '?endpoint=name', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) })` → `ajax.php` → PDO SQLite → JSON response
7. **Permission check** — `requireLogin()`, `requirePermission('slug')`, `hasPermission('slug')`
8. **DB helper** — `db()` returns PDO instance singleton
9. **Render pattern** — `renderHead('Title')`, `renderNav('page_key')`, `renderFoot()`
10. **Master Catalog** — Produk dengan `tenant_id = NULL` adalah master catalog. Query produk harus menggunakan `(tenant_id = ? OR tenant_id IS NULL)` untuk tenant.
