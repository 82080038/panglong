# TECHNICAL DOCUMENTATION

# PANGLONG ERP

## Version: 3.0 (Updated 2026-06-29)

---

## Architecture

```
[Browser]
  ↓
[PHP Server-Side Rendering] — frontend/*.php (49 files)
  ├── Direct PDO SQLite queries untuk initial page load
  └── fetch(API_URL + '?endpoint=...') → frontend/ajax.php (4044 lines, 60 endpoints) → PDO SQLite
  ↓
[database/database.sqlite] — 87 tables
```

### Tech Stack
- **PHP 8.2+** (XAMPP) — Native procedural, no framework
- **PDO SQLite** — Direct database access via `db()` singleton
- **jQuery 3.6** — DOM manipulation, AJAX
- **Bootstrap 5.3** — UI framework, dark mode, responsive
- **Bootstrap Icons** — Icon set
- **Chart.js** — Dashboard charts
- **Playwright** — E2E testing

---

## Core Files

### `frontend/config.php`
- Session management (timeout, secure cookies)
- `renderHead($title)` — HTML head, CSS, JS, `API_URL` constant, CSRF token
- `renderNav($page_key)` — RBAC navbar
- `renderFoot()` — Bootstrap JS, CSRF setup, anti-double-click, session heartbeat
- Theme: light, dark, eyecare (session-based)
- `formatRupiah()`, `formatTanggal()`, `formatTanggalWaktu()` helpers

### `frontend/db.php`
- `db()` — Returns PDO SQLite singleton
- Database path: `database/database.sqlite`
- WAL mode for better concurrency

### `frontend/auth.php`
- `login($username, $password)` — Session-based auth with `password_verify()`
- `logout()` — Destroy session
- `currentUser()` — Get current user from session
- `requireLogin()` — Redirect to login if not authenticated
- `requirePermission($slug)` — Check RBAC permission
- `hasPermission($slug)` — Boolean permission check
- `generateCsrfToken()` / `verifyCsrfToken($token)` — CSRF protection
- `checkRateLimit($key, $max, $window)` — Rate limiting
- Login attempt lockout (5x = 15 minutes)

### `frontend/ajax.php`
- Single endpoint untuk semua CRUD operations
- 60 endpoints via `?endpoint=name` parameter
- Role-based endpoint permission map (`$endpointRoles`)
- CSRF validation for POST/PUT/DELETE
- Rate limiting for write operations
- `ok($data, $meta)` / `fail($msg, $code)` / `created($data)` response helpers
- `logAudit($action, $table, $record_id, $before, $after)` — Audit logging
- `addTenantFilter($sql, $alias, $tenantId, $isSuperAdmin, &$params)` — Tenant isolation
- `addTenantFilterWithMaster($sql, $alias, $tenantId, $isSuperAdmin, &$params)` — Include master catalog

---

## AJAX Pattern

```javascript
// GET (list/detail)
fetch(API_URL + '?endpoint=products&search=' + query)
  .then(r => r.json())
  .then(res => { if (res.success) { /* res.data */ } });

// POST (create)
fetch(API_URL + '?endpoint=products', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ name, code, category_id, ... })
})
.then(r => r.json())
.then(res => { if (res.success) { /* res.data */ } });
```

### Response Format
```json
// Success
{ "success": true, "data": {...}, "meta": { "total": 100, "per_page": 20 } }

// Error
{ "success": false, "message": "Error description" }
```

---

## RBAC Roles

| Role | Slug | Access |
|------|------|--------|
| Owner | owner | Full access + SaaS + settings |
| Manager | manager | All except SaaS |
| Kasir | kasir | Sales, customers, deliveries, returns |
| Gudang | gudang | Products, stock, suppliers, PO |
| Accounting | accounting | Accounting, cash book, e-Faktur |
| Supervisor | supervisor | Dashboard, reports |
| Super Admin | super_admin | Platform management, tenants |

---

## Master Catalog

Produk dengan `tenant_id = NULL` adalah master catalog:
- 190 produk material bangunan
- 19 kategori, 23 satuan
- Tenant lihat master + own: `(tenant_id = ? OR tenant_id IS NULL)`
- Endpoint `master-products` (GET: list, POST: import)
- Auto-sync: produk baru tenant → copy ke master jika belum ada

---

## Database

- **Engine:** SQLite (file-based)
- **File:** `database/database.sqlite` (committed to git)
- **Tables:** 87
- **Connection:** PDO singleton via `db()` in `frontend/db.php`
- **WAL mode:** Better read/write concurrency

---

## Security

- CSRF token validation (POST/PUT/DELETE)
- Rate limiting (30 req/60s for writes)
- Login lockout (5 attempts = 15 min lock)
- Security headers (nosniff, DENY frame, XSS protection)
- Session regeneration on login
- Anti-double-click protection
- Session heartbeat (5 min)
