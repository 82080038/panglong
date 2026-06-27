# PANDUAN HANDOVER DEVELOPER — Panglong ERP

> Dokumen ini wajib dibaca oleh programmer baru sebelum melanjutkan pengembangan.
> Berisi: arsitektur, cara kerja, aturan, prioritas, dan cara mengerjakan FIELD_REALITY_ANALYSIS.md
> Dibuat: 27 Juni 2026

---

## 1. ARSITEKTUR APLIKASI (BACA INI DULU)

### Yang Berjalan Saat Ini

```
[Browser]
  → PHP Server-side rendering (frontend/*.php)
  → jQuery AJAX → frontend/ajax.php (single endpoint)
  → PDO SQLite → database/database.sqlite
```

**BUKAN Laravel.** Frontend pakai PHP Native procedural + PDO SQLite langsung.
Laravel backend (`app/`, `routes/api.php`) ada di repo tapi **TIDAK dipakai frontend**.

### Stack

| Komponen | Teknologi |
|----------|-----------|
| Frontend | PHP Native procedural |
| Database | SQLite (`database/database.sqlite`, 87 tables) |
| AJAX | jQuery 3.6 → `frontend/ajax.php` (58 endpoints, 3400+ lines) |
| UI | Bootstrap 5.3 + Bootstrap Icons (CDN) |
| Charts | Chart.js 4.4 (CDN) |
| Auth | Session-based (`frontend/auth.php`), bcrypt password |
| PWA | `frontend/sw.js` + `frontend/manifest.json` |

### File Penting

| File | Fungsi |
|------|--------|
| `frontend/config.php` | Session timeout, navbar, renderHead/renderFoot, helper functions |
| `frontend/auth.php` | Login, logout, hasPermission(), CSRF token |
| `frontend/db.php` | PDO SQLite singleton (`db()` function) |
| `frontend/ajax.php` | ALL CRUD operations — 58 endpoints |
| `index.php` | Root gateway → redirect ke frontend/login.php |

### Roles (7)

`owner`, `manager`, `kasir`, `gudang`, `accounting`, `supervisor`, `super_admin`

### Default Users

| Username | Password | Role |
|----------|----------|------|
| admin | password123 | Super Admin |
| ownertest | password123 | Owner |
| manager1 | password123 | Manager |
| kasir1 | password123 | Kasir |
| gudang1 | password123 | Gudang |
| accounting1 | password123 | Accounting |
| supervisor1 | password123 | Supervisor |

---

## 2. SETUP ENVIRONMENT

### Requirements

- XAMPP (`/opt/lampp/`) dengan PHP 8.2+ (punya pdo_sqlite)
- **JANGAN pakai system PHP** (`/usr/bin/php`) — tidak punya pdo_sqlite
- PHP binary: `/opt/lampp/bin/php`
- Browser: Chrome/Firefox untuk testing

### Cara Menjalankan

```bash
# Start XAMPP
sudo /opt/lampp/lampp start

# Akses aplikasi
http://localhost/panglong/frontend/
```

### Database Permission (jika database baru)

```bash
chmod 666 database/database.sqlite
chmod 777 database/
```

### Testing

```bash
# PHP syntax check
/opt/lampp/bin/php -l frontend/file.php

# Playwright E2E
npx playwright test --reporter=list --workers=1

# Test semua page (HTTP status check)
for page in index products customers suppliers warehouses sales sales_orders quotations deliveries purchase-orders stock stock_opname stock_transfers batches reorder iot fleet routes accounting cashbook cash_flow fixed_assets e_faktur closing reports ai_insights marketplace landed_cost pricing settings saas users tenants returns whatsapp salesman_app; do
  curl -s -o /dev/null -w "%{http_code}" -b /tmp/test_cookies.txt "http://localhost/panglong/frontend/$page.php"
done
```

---

## 3. POLA KODE & ATURAN

### AJAX Endpoint Pattern

```php
// Di ajax.php — pattern untuk setiap endpoint
if ($endpoint === 'nama-endpoint' && $method === 'POST') {
    // 1. Validasi input
    // 2. Filter tenant (addTenantFilter)
    // 3. Database operation (PREPARED STATEMENT WAJIB)
    // 4. logAudit() untuk sensitive operations
    // 5. Return: ok($data) / created($data) / fail($msg, $code)
}
```

### Response Helpers

```php
ok($data);           // 200 success
created($data);      // 201 created
fail($msg, $code);   // error response
```

### Permission Check

```php
requireLogin();
requirePermission('slug');  // cek permission berdasarkan role
hasPermission('slug');       // return boolean
```

### Tenant Filter (WAJIB di setiap query)

```php
$tenantId = $_SESSION['user']['tenant_id'] ?? null;
$isSuperAdmin = $_SESSION['user']['role_slug'] === 'super_admin';
// Super Admin tidak difilter tenant
// Tenant lain: WHERE tenant_id = ?
```

### Dilarang

- **JANGAN** pakai `<?= API_URL ?>` di PHP context — itu JavaScript constant
- **JANGAN** pakai system PHP (`/usr/bin/php`) — selalu `/opt/lampp/bin/php`
- **JANGAN** modify Laravel backend — tidak dipakai frontend
- **JANGAN** pakai `$d` sebagai foreach variable jika `$d = db()` sudah defined
- **JANGAN** buat file random yang tidak perlu
- **JANGAN** hapus atau lemahkan test tanpa instruksi explicit

---

## 4. CARA MENGERJAKAN FIELD_REALITY_ANALYSIS.md

### File Lokasi

`/opt/lampp/htdocs/panglong/FIELD_REALITY_ANALYSIS.md` (2700+ baris, 14 section, 87 item)

### Struktur Dokumen

| Section | Topik | Item Count |
|---------|-------|------------|
| 1-9 | Audit gap analysis (role, tenant, fitur, gudang, shift, partial, inter-branch) | — |
| 10 | Gangguan infrastruktur (listrik, jaringan, baterai) | 5 |
| 11 | Race condition & concurrency control | 8 |
| 12 | Master product database (katalog pusat) | 4 phase |
| 13 | Skenario production hosting | 32 |
| 14 | Matriks prioritas (P0-P3) | 87 total |

### Prioritas Pekerjaan

#### P0 — Mulai Dari Sini (17 item, ~3 sprint)

Item yang paling cepat dikerjakan dulu (quick wins, < 0.5 sprint each):

| # | Item | File Target | Estimasi |
|---|------|-------------|----------|
| 4 | WAL mode + busy_timeout | `frontend/db.php` | 2 baris |
| 13 | Set timezone Asia/Jakarta | `frontend/config.php` | 1 baris |
| 14 | session_regenerate_id | `frontend/auth.php` | 1 baris |
| 16 | display_errors=0 production | `frontend/config.php` | 3 baris |
| 12 | Button disable anti-double-click | `frontend/sales.php` + semua form | 0.2 sprint |
| 17 | Session heartbeat | `frontend/config.php` (JS) | 0.2 sprint |
| 15 | Self-host CSS/JS assets | `frontend/assets/` + `config.php` | 0.3 sprint |

Item yang butuh effort lebih:

| # | Item | File Target | Estimasi |
|---|------|-------------|----------|
| 1 | Database transaction | `frontend/ajax.php` — wrap semua POST multi-step | 0.5 sprint |
| 2 | Stock validation sebelum sale | `frontend/ajax.php` — sales POST handler | 0.5 sprint |
| 3 | Idempotency key | `ajax.php` + `sales.php` + migration | 0.5 sprint |
| 5 | Auto-save cart localStorage | `frontend/sales.php` (JS) | 0.5 sprint |
| 6 | QRIS/e-wallet payment | migration + `ajax.php` + `sales.php` | 1 sprint |
| 7 | Void sales approval | migration + `ajax.php` + UI | 0.5 sprint |
| 8 | Branch scoping | `frontend/ajax.php` — semua query | 1 sprint |
| 9 | Audit logging semua endpoint | `frontend/ajax.php` — tambah logAudit() | 0.5 sprint |
| 10 | Role fallback | migration + `auth.php` + `ajax.php` | 0.5 sprint |
| 11 | tenant_id ke semua tabel | migration + filter | 0.5 sprint |

#### P1 — Setelah P0 Selesai (38 item, ~15 sprint)

Lihat Section 14 matriks prioritas di FIELD_REALITY_ANALYSIS.md untuk detail.

#### P2 — Growth Phase (16 item)

#### P3 — Long Term (16 item)

### Workflow Pengerjaan per Item

```
1. Baca section terkait di FIELD_REALITY_ANALYSIS.md
2. Buat migration jika perlu (database/migrations/)
3. Implementasi di ajax.php / frontend/*.php
4. Test: /opt/lampp/bin/php -l frontend/file.php (syntax check)
5. Test: npx playwright test --reporter=list --workers=1
6. Test manual: login sebagai role terkait, coba fitur
7. Commit dengan message: "fix(P0-N): deskripsi item"
```

### Contoh: Implementasi P0 #4 (WAL mode)

```php
// Di frontend/db.php, tambah setelah PRAGMA foreign_keys:
$db->exec('PRAGMA journal_mode = WAL');
$db->exec('PRAGMA busy_timeout = 5000');
```

### Contoh: Implementasi P0 #13 (Timezone)

```php
// Di frontend/config.php, paling atas setelah <?php:
date_default_timezone_set('Asia/Jakarta');
```

### Contoh: Implementasi P0 #14 (Session regenerate)

```php
// Di frontend/auth.php, setelah login berhasil (sebelum return):
session_regenerate_id(true);
```

### Contoh: Implementasi P0 #1 (Transaction)

```php
// Di frontend/ajax.php, untuk sales POST:
try {
    $d->beginTransaction();
    // ... insert sale, items, stock_movements ...
    $d->commit();
    created(['id' => $saleId]);
} catch (Exception $e) {
    $d->rollBack();
    fail('Gagal: ' . $e->getMessage(), 500);
}
```

---

## 5. DATABASE SCHEMA

### Tabel Utama

| Tabel | Fungsi | tenant_id |
|-------|--------|-----------|
| users | User accounts | ✓ |
| roles | 7 roles | — |
| permissions | 12 permissions | — |
| products | Product master | ✓ |
| product_units | Multi-unit per product | ✓ |
| barcodes | Multiple barcode per product | ✓ |
| categories | Product categories | ✓ |
| customers | Customer master | ✓ |
| suppliers | Supplier master | ✓ |
| sales | Sales header | ✓ |
| sale_items | Sales line items | (via sale_id) |
| sale_payments | Payment tracking | (via sale_id) |
| stock_movements | All stock changes | ✓ |
| stock_adjustments | Stock adjustment with approval | ✓ |
| stock_opnames | Physical count | ✓ |
| purchase_orders | PO header | ✓ |
| purchase_items | PO line items | (via po_id) |
| warehouses | Warehouse master | ✓ |
| branches | Branch master | ✓ |
| employees | Employee master | ✓ |
| tenants | Tenant master | — |
| app_settings | Settings per tenant | ✓ |
| audit_logs | Audit trail | — |

### Database Scripts (Yang Tersisa)

| Script | Fungsi | Kapan Dipakai |
|--------|--------|---------------|
| `database/export_sqlite.php` | Export DB ke SQL dump | Migrasi server |
| `database/import_sqlite.php` | Import dari SQL dump | Restore/migrasi |
| `database/clean_data.php` | Hapus transaksi, keep master | Reset data |
| `database/add_performance_indexes.php` | Tambah index untuk performance | Optimasi |

---

## 6. TESTING

### Playwright E2E (23 specs, 67 tests)

```bash
npx playwright test --reporter=list --workers=1

# Test specific spec
npx playwright test tests/e2e/sales.spec.js --reporter=list

# Test dengan browser visible
npx playwright test --headed
```

### Test Mode

Tambah `&test_mode=true` ke AJAX URL untuk bypass CSRF/rate limiting di tests.

### Yang Harus Di-test Setelah Perubahan

1. **Setelah ubah ajax.php**: Test semua endpoint yang terdampak
2. **Setelah ubah config.php**: Test semua halaman (HTTP 200)
3. **Setelah ubah auth.php**: Test login/logout untuk semua role
4. **Setelah migration**: Test integritas database (`PRAGMA integrity_check`)
5. **Setelah ubah sales.php**: Test create sale, void sale, print receipt

---

## 7. GIT WORKFLOW

### Branch Strategy

```
main          → Production ready
develop       → Development integration
feature/P0-N  → Feature branch per item (N = nomor item di matriks)
```

### Commit Message Format

```
feat(P0-4): enable WAL mode and busy_timeout in db.php
fix(P0-14): add session_regenerate_id after login
docs: update FIELD_REALITY_ANALYSIS.md with new scenarios
chore: remove unused one-time scripts
```

### Sync ke GitHub

```bash
git add -A
git commit -m "deskripsi perubahan"
git push origin main
```

---

## 8. DAFTAR FILE DOKUMENTASI

| File | Isi | Baca Kapan? |
|------|-----|-------------|
| `FIELD_REALITY_ANALYSIS.md` | 87 item prioritas P0-P3 | Sebelum mulai kerja |
| `PROJECT_STATUS.md` | Status saat ini, stats, arsitektur | Overview |
| `DEVELOPMENT_HANDOFF.md` | Dokumen ini | Pertama kali |
| `SETUP_GUIDE.md` | Cara install XAMPP + setup | Environment baru |
| `DATABASE_SCHEMA.md` | Schema 87 tabel | Saat kerja database |
| `PROMPTING_GUIDE.md` | Cara prompt AI untuk codebase ini | Saat pakai AI assistant |
| `MASTER_BLUEPRINT.md` | Arsitektur enterprise | Referensi desain |
| `MVP_SCOPE.md` | Scope MVP (sudah selesai) | Referensi history |
| `DEVELOPMENT_ROADMAP.md` | Roadmap sprint 1-12 | Referensi history |
| `API_SPECIFICATION.md` | Laravel API docs (tidak dipakai) | Referensi saja |
| `TECHNICAL_DOCUMENTATION.md` | Dokumentasi teknis | Referensi |
| `TESTING_FRAMEWORK.md` | Panduan testing | Saat nulis test |
| `MULTI_TENANT_GUIDE.md` | Panduan multi-tenant | Saat kerja tenant |
| `IMPLEMENTATION_GUIDE.md` | Panduan implementasi | Referensi |
| `MANUAL_TESTING_QUICK_ADD.md` | Quick add test data | Testing manual |
| `PANGLONG_BUSINESS_ANALYSIS.md` | Analisis bisnis | Referensi |

---

## 9. ERRORS & BUGS YANG PERNAH TERJADI (Pelajaran)

1. `renderNav undefined` → pastikan `require_once config.php` bukan `auth.php`
2. `number_format()` on non-numeric → tambah `is_numeric()` guard
3. Variable collision `$d = db()` vs `foreach ($items as $d)` → rename loop var
4. INSERT placeholder mismatch → hitung ulang `?` count vs column count
5. `audit_logs` column name mismatch → cek schema vs query
6. UNIQUE constraint tidak di-catch → tambah try-catch PDOException
7. `selectedOptions[0]` undefined → tambah null guard

---

## 10. KONTAK & HANDOVER

- Dokumen analisis: `FIELD_REALITY_ANALYSIS.md` (87 item, 14 section)
- Mulai dari: P0 quick wins (item #4, #13, #14, #16 — total < 0.5 sprint)
- Test setelah setiap perubahan: `npx playwright test --reporter=list --workers=1`
- Git: commit per item, push ke GitHub

**Selamat melanjutkan. Baca FIELD_REALITY_ANALYSIS.md untuk detail setiap item.**
