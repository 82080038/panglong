# PANDUAN HANDOVER DEVELOPER — Panglong ERP

> Dokumen ini wajib dibaca oleh programmer baru sebelum melanjutkan pengembangan.
> Dibuat: 29 Juni 2026

---

## Arsitektur

**100% PHP Native Procedural + PDO SQLite + jQuery AJAX + Bootstrap 5.3**

Tidak ada Laravel, Composer, atau framework lain. Aplikasi mengakses database langsung via PDO SQLite.

### Alur Data
```
Browser → frontend/*.php (PHP rendering) → PDO SQLite (database/database.sqlite)
         ↘ fetch(API_URL + '?endpoint=...') → ajax.php → PDO SQLite → JSON
```

### File Penting
| File | Fungsi |
|------|--------|
| `frontend/config.php` | Session, navbar RBAC, theme, CSRF, `renderHead/Nav/Foot()` |
| `frontend/db.php` | PDO SQLite singleton (`db()`) |
| `frontend/auth.php` | Auth, RBAC, CSRF, rate limiting |
| `frontend/ajax.php` | 60 AJAX endpoints (4044 lines) |
| `index.php` | Gerbang utama → redirect login/dashboard |

---

## Aturan Pengembangan

1. **Gunakan `API_URL` constant** — Jangan hardcode `'ajax.php'`
2. **Gunakan `fetch(API_URL + '?endpoint=...')`** — Pattern AJAX yang benar
3. **Gunakan prepared statements** — `$d->prepare($sql)->execute($params)`
4. **Gunakan `db()` singleton** — Jangan `new PDO()` langsung
5. **Filter tenant** — `(tenant_id = ? OR tenant_id IS NULL)` untuk produk
6. **Check permission** — `requireLogin()`, `requirePermission('slug')`
7. **Render pattern** — `renderHead()`, `renderNav()`, `renderFoot()`
8. **No Laravel** — Tidak ada `artisan`, `composer`, `namespace App\`
9. **No external dependencies** — Pure PHP, no Composer packages
10. **XAMPP PHP** — Gunakan `/opt/lampp/bin/php` (8.2.12 dengan pdo_sqlite)

---

## Master Catalog

- Produk `tenant_id = NULL` = master catalog (190 produk bangunan)
- Tenant lihat master + own: `(tenant_id = ? OR tenant_id IS NULL)`
- Endpoint `master-products`: GET (list), POST (import ke tenant)
- Auto-sync: produk baru tenant → copy ke master jika belum ada

---

## Roles (7)

| Role | Akses |
|------|-------|
| owner | Full + SaaS + settings |
| manager | All except SaaS |
| kasir | Sales, customers, deliveries |
| gudang | Products, stock, suppliers, PO |
| accounting | Accounting, cash, e-Faktur |
| supervisor | Dashboard, reports |
| super_admin | Platform, tenants |

---

## Testing

```bash
npx playwright test --headed --reporter=list --workers=1
```

26 Playwright E2E specs di `tests/e2e/`.

---

## Database

- File: `database/database.sqlite` (87 tables, committed to git)
- Permission: `chmod 666 database/database.sqlite && chmod 777 database/`

---

## Default Users

| Username | Password | Role |
|----------|----------|------|
| admin | password123 | Owner |
| manager1 | password123 | Manager |
| kasir1 | password123 | Kasir |
| gudang1 | password123 | Gudang |
| accounting1 | password123 | Accounting |
| supervisor1 | password123 | Supervisor |
