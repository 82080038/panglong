# COMPREHENSIVE PROMPTING GUIDE

# PANGLONG ERP — Guide for AI-Assisted Development

## Version: 3.0 (Updated 2026-06-29)

---

## Architecture Summary

- **100% PHP Native Procedural** — No Laravel, no Composer, no framework
- **PDO SQLite** — Direct database access via `db()` singleton
- **jQuery 3.6 + Bootstrap 5.3** — Frontend
- **Single AJAX endpoint** — `frontend/ajax.php` (60 endpoints, 4044 lines)
- **Session-based auth** — CSRF + rate limiting + RBAC

---

## Key Patterns

### AJAX Call Pattern
```javascript
fetch(API_URL + '?endpoint=products', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(data)
})
.then(r => r.json())
.then(res => { if (res.success) { /* res.data */ } });
```

### Page Render Pattern
```php
requireLogin();
requirePermission('view_products');
// ... PDO queries for initial data ...
renderHead('Page Title - Panglong ERP');
renderNav('page_key');
// ... HTML content ...
renderFoot();
```

### Tenant Filter Pattern
```php
// Tenant sees own + master catalog
if (!$isSuperAdmin && $tenantId) {
    $sql .= " AND (tenant_id = ? OR tenant_id IS NULL)";
    $params[] = $tenantId;
}
```

### AJAX Endpoint Pattern (ajax.php)
```php
if ($endpoint === 'my-endpoint') {
    if ($method === 'GET') {
        // ... query ...
        ok($data, $meta);
    }
    if ($method === 'POST') {
        // ... validate & insert ...
        created($newRecord);
    }
}
```

---

## Rules for AI Development

1. **Never use Laravel** — No `artisan`, `composer`, `namespace App\`, `use Illuminate\`
2. **Never use `$.ajax` with hardcoded `'ajax.php'`** — Use `API_URL` constant
3. **Always use `fetch(API_URL + '?endpoint=...')`** — Not `fetch('ajax.php?...')`
4. **Always filter by tenant** — Use `(tenant_id = ? OR tenant_id IS NULL)` for products
5. **Always add CSRF token** — Automatically handled by `renderFoot()` for `fetch()`
6. **Always use prepared statements** — `$stmt = $d->prepare($sql); $stmt->execute($params);`
7. **Always use `db()` singleton** — Not `new PDO(...)` directly
8. **Always check permissions** — `requireLogin()`, `requirePermission('slug')`
9. **Always use `renderHead()` / `renderNav()` / `renderFoot()`** — For consistent UI
10. **Never create Composer packages** — Pure PHP only, no external dependencies

---

## Database

- **File:** `database/database.sqlite` (87 tables, committed to git)
- **Connection:** `$d = db();` returns PDO singleton
- **WAL mode** for better concurrency

### Query Example
```php
$d = db();
$stmt = $d->prepare("SELECT * FROM products WHERE (tenant_id = ? OR tenant_id IS NULL) AND is_active = 1");
$stmt->execute([$tenantId]);
$products = $stmt->fetchAll();
```

---

## File Structure

```
frontend/          # 49 PHP files (app)
scripts/           # 8 utility scripts
tests/e2e/         # 26 Playwright specs
docs/              # 18 MD files
database/          # SQLite database
```

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

---

## PHP Path

Always use XAMPP PHP: `/opt/lampp/bin/php` (8.2.12 with pdo_sqlite)
