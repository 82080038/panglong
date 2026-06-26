# Panglong ERP

Sistem ERP untuk distribusi material bangunan — POS, inventory, AR/AP, akuntansi, delivery, dan lebih banyak lagi.

> **Status:** Sprint 1-12 + Gap Features + UI/UX Enhancements — ALL COMPLETED
> **Architecture:** PHP Native + PDO SQLite + jQuery AJAX + Bootstrap 5
> **Tests:** 39 Playwright E2E tests (ALL PASSING)

---

## Quick Start (5 menit)

### Prasyarat
- **XAMPP** (Apache + PHP 8.2+ dengan `pdo_sqlite`) — [download](https://www.apachefriends.org/)
- Browser modern (Chrome/Firefox)
- Optional: Node.js + npm (untuk Playwright E2E tests)
- Optional: Composer (untuk Laravel backend tests)

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
http://localhost/panglong/frontend/login.php
```

5. **Login dengan default credentials**
| Username | Password | Role |
|----------|----------|------|
| admin | password123 | Owner (akses semua menu) |
| manager1 | password123 | Manager |
| kasir1 | password123 | Kasir |
| gudang1 | password123 | Gudang |

> **Database sudah included!** File `database/database.sqlite` (78 tables, seed data) sudah ada di repo. Tidak perlu menjalankan migration atau seeder.

---

## Arsitektur

```
[Browser]
  ↓
[PHP Server-Side Rendering] — frontend/*.php (45 pages)
  ├── Direct PDO SQLite queries untuk initial page load
  └── jQuery 3.6 $.ajax() → frontend/ajax.php (1802 lines, 34 endpoints) → PDO SQLite
  ↓
[database/database.sqlite] — 78 tables
```

### Yang berjalan saat ini
- **Frontend:** PHP Native procedural (45 pages) di `frontend/`
- **Database:** SQLite langsung via PDO (`frontend/db.php`)
- **AJAX:** Single endpoint `frontend/ajax.php` (34 endpoints)
- **Auth:** Session-based (`frontend/auth.php`) dengan `password_verify()`
- **UI:** Bootstrap 5.3 + Bootstrap Icons (CDN), dark mode, eye-care mode, fullscreen toggle, responsive

### Yang TIDAK digunakan frontend
- **Laravel backend API** (`app/`, `routes/api.php`) — scaffolded & tested dengan PHPUnit, tetapi frontend mengakses database langsung via PDO SQLite, bukan melalui Laravel API

---

## Struktur Project

```
panglong/
├── frontend/                 # ← YANG AKTIF BERJALAN (PHP Native)
│   ├── config.php            # Session, navbar (RBAC), dark mode, fullscreen, CDN
│   ├── db.php                # PDO SQLite connection singleton
│   ├── auth.php              # Session auth: login(), logout(), hasPermission()
│   ├── ajax.php              # Single AJAX endpoint (1802 lines, 34 endpoints)
│   ├── login.php             # Login page dengan quick login buttons
│   ├── index.php             # Dashboard
│   ├── products.php          # Product management
│   ├── sales.php             # POS / Sales
│   ├── deliveries.php        # Delivery orders
│   ├── stock.php             # Stock management
│   ├── accounting.php        # Accounting (journal, P&L, balance sheet)
│   ├── ... (40+ pages lainnya)
│   ├── manifest.json         # PWA manifest
│   └── sw.js                 # Service worker (offline-first)
│
├── database/
│   ├── database.sqlite       # ← DATABASE AKTIF (78 tables, seed data, COMMITTED to git)
│   ├── database_export.sql   # SQL dump untuk import manual
│   ├── export_sqlite.php     # Script export database ke SQL
│   ├── import_sqlite.php     # Script import database dari SQL
│   ├── mysql_to_sqlite.php   # Konversi MySQL → SQLite
│   ├── migrations/           # 37 Laravel migration files
│   ├── seeders/              # 16 Laravel seeder files
│   └── factories/            # 9 model factories
│
├── app/                      # Laravel backend (TIDAK digunakan frontend)
├── routes/api.php            # Laravel API routes (TIDAK digunakan frontend)
├── tests/e2e/                # Playwright E2E tests (18 specs, 39 tests)
├── docker/                   # Docker config (nginx.conf)
├── Dockerfile                # Docker build
├── docker-compose.yml        # Docker compose (app + db + nginx + frontend)
├── package.json              # npm scripts untuk Playwright
├── playwright.config.js      # Playwright configuration
├── composer.json             # Laravel dependencies
└── .env.example              # Template untuk Laravel .env
```

---

## Menjalankan Tests

### Playwright E2E (Frontend)
```bash
# Install dependencies
npm install
npx playwright install chromium

# Pastikan XAMPP Apache berjalan, lalu:
npx playwright test --headed --reporter=list --workers=1
```

### PHPUnit (Laravel Backend — Optional)
```bash
composer install
cp .env.example .env
/opt/lampp/bin/php artisan key:generate
./vendor/bin/phpunit
```

---

## Database

### File yang sudah ada di repo
- `database/database.sqlite` — SQLite database dengan 78 tables dan seed data
- `database/database_export.sql` — SQL dump untuk import manual

### Import database di komputer baru (jika perlu)
```bash
# Opsi 1: Gunakan file yang sudah ada (sudah ada di repo, tidak perlu apa-apa)
# Opsi 2: Import dari SQL dump
sqlite3 database/database.sqlite < database/database_export.sql
# Atau:
/opt/lampp/bin/php database/import_sqlite.php
```

### Export database (setelah perubahan)
```bash
/opt/lampp/bin/php database/export_sqlite.php
```

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

## UI/UX Features

- **RBAC Navigation** — Menu berbeda per role (owner, manager, kasir, gudang, accounting, supervisor)
- **Dark Mode** — Toggle `data-bs-theme="dark"`, session-based
- **Eye-Care Mode** — Sepia theme untuk pemakaian 24 jam
- **Fullscreen Toggle** — Fullscreen API untuk desktop/large screen
- **Responsive Design** — Mobile, tablet, desktop, ultra-wide
- **PWA** — Installable app dengan offline-first service worker

---

## Docker Deployment (Optional)

```bash
docker-compose up -d
# Frontend: http://localhost:8081
# Backend API: http://localhost:8080
```

---

## Dokumentasi Lengkap

| File | Isi |
|------|-----|
| `SETUP_GUIDE.md` | Panduan setup detail (XAMPP, database, testing) |
| `PROJECT_STATUS.md` | Status audit lengkap (stats, komponen, gap features) |
| `DEVELOPMENT_ROADMAP.md` | Roadmap sprint 1-12 + gap features |
| `DATABASE_SCHEMA.md` | Skema 78 tables + ERD + migration order |
| `TECHNICAL_DOCUMENTATION.md` | Arsitektur teknis + komponen utama + RBAC nav |
| `API_SPECIFICATION.md` | Laravel API docs (TIDAK digunakan frontend) |
| `TESTING_FRAMEWORK.md` | PHPUnit + Playwright E2E testing guide |
| `MVP_SCOPE.md` | Scope MVP + deliverables + success criteria |
| `PANGLONG_BUSINESS_ANALYSIS.md` | Analisis bisnis + gap analysis + rekomendasi |
| `MASTER_BLUEPRINT.md` | Blueprint arsitektur enterprise lengkap |
| `PROMPTING_GUIDE.md` | Guide untuk AI-assisted development |

---

## Penting untuk Developer

1. **Gunakan XAMPP PHP** (`/opt/lampp/bin/php` 8.2.12) — memiliki `pdo_sqlite`. System PHP mungkin tidak punya.
2. **Database file sudah di repo** — `database/database.sqlite` committed to git. Setelah clone, langsung jalan.
3. **Set permission setelah clone** (Linux/macOS): `chmod 666 database/database.sqlite && chmod 777 database/`
4. **Frontend tidak menggunakan Laravel API** — Frontend akses database langsung via PDO SQLite di `frontend/ajax.php`
5. **Jangan modify Laravel backend** — Hanya untuk testing (PHPUnit). Frontend 100% PHP Native.
6. **API_URL adalah JavaScript constant** — Didefinisikan di `config.php` `renderHead()` sebagai `"ajax.php"`. Bukan PHP constant.
7. **Pattern AJAX** — `$.ajax({ url: API_URL + '?endpoint=name' })` → `ajax.php` → PDO SQLite → JSON response
8. **Permission check** — `requireLogin()`, `requirePermission('slug')`, `hasPermission('slug')`
9. **DB helper** — `db()` returns PDO instance singleton
10. **Render pattern** — `renderHead('Title')`, `renderNav('page_key')`, `renderFoot()`
