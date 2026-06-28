# COMPREHENSIVE PROMPTING GUIDE

# PANGLONG ERP — Guide for AI-Assisted Development

## Version: 4.0 (Updated 2026-06-29, Cycle 2)

---

## Architecture Rules (MANDATORY)

1. **100% PHP Native Procedural** — No Laravel, no Composer, no framework
2. **No `artisan`, `composer`, `namespace App\`, `use Illuminate\`**
3. **Database: PDO SQLite** — `db()` singleton in `db.php`
4. **AJAX: Single endpoint** — `frontend/ajax.php` with `?endpoint=xxx`
5. **Frontend: jQuery 3.6 + Bootstrap 5.3** — No React, no Vue
6. **Testing: Playwright E2E only** — 88 test cases, 26 specs
7. **Multi-tenant: `tenant_id` column** — NULL = master catalog/global
8. **UNIQUE constraints: composite (column, tenant_id)** — Never single-column
9. **Payment methods: standard codes** — cash, transfer, credit, qris, ewallet
10. **Session: `auth.php` handles session_start + db.php** — `config.php` enforces login

---

## Prompting Patterns

### Bug Fix Prompt
```
Perbaiki bug di [file]: [deskripsi error]
- Cek error log: sudo tail -20 /opt/lampp/logs/error_log
- Reproduce dengan curl
- Fix root cause, bukan symptom
- Cek pattern serupa di file lain
- Run: /opt/lampp/bin/php -l [file]
- Test: npx playwright test [spec] --reporter=list --workers=1
```

### Feature Add Prompt
```
Tambahkan fitur [nama] di [halaman]:
- Endpoint baru di ajax.php (ikuti pattern existing)
- UI di [file].php (ikuti pattern Bootstrap 5.3)
- Tambahkan ke endpointRoles map
- Test dengan curl sebelum Playwright
- Update API_SPECIFICATION.md
```

### Database Change Prompt
```
Ubah schema [table]:
- Backup database dulu: cp database/database.sqlite database/database.sqlite.bak
- Gunakan ALTER TABLE atau recreate table untuk SQLite
- Jangan gunakan UNIQUE pada single column jika table punya tenant_id
- Update DATABASE_SCHEMA.md
- Test semua endpoint yang terkait
```

---

## Common Pitfalls to Avoid

1. **Never use `new PDO()` directly** — use `db()` singleton
2. **Never hardcode `'ajax.php'`** — use `API_URL` constant
3. **Never use `config.php` in public pages** (register.php) — use `auth.php` only
4. **Never use single-column UNIQUE** with tenant tables — use composite `(col, tenant_id)`
5. **Never forget `$branchId`** — always define from `$user['branch_id'] ?? null`
6. **Never use `fn()` arrow functions** — PHP 8.2 compatibility issues
7. **Never use `DATE()` in SQL with JOINs** — use direct comparison
8. **Always wrap master catalog sync in try-catch** — best-effort, don't fail creation
9. **Always use `test_mode=true`** in Playwright tests to bypass CSRF
10. **Always set `chmod 666 database/database.sqlite`** after git operations

---

## Test Commands

```bash
# Syntax check
/opt/lampp/bin/php -l frontend/[file].php

# Run specific test
npx playwright test tests/e2e/[spec].js --reporter=list --workers=1

# Run all tests
timeout 600 npx playwright test --reporter=list --workers=1

# Check error log
echo "8208" | sudo -S tail -20 /opt/lampp/logs/error_log

# Test AJAX endpoint
curl -s -c /tmp/c.txt -L -X POST http://localhost/panglong/frontend/login.php -d "username=admin&password=password123" -o /dev/null
curl -s -b /tmp/c.txt -X POST "http://localhost/panglong/frontend/ajax.php?endpoint=XXX&test_mode=true" -H "Content-Type: application/json" -d '{...}'
```

---

## Development Cycle

1. **Analyse** — Check error logs, run tests, identify failures
2. **Fix** — Fix root cause, check for similar patterns in other files
3. **Test** — Run syntax check, then Playwright tests
4. **Prompt** — Update this guide with new learnings
5. **Repeat** — Until all tests pass

---

## Current Stats (Cycle 2)

- 88/88 Playwright E2E tests passing
- 84 database tables (Laravel tables removed)
- 60 AJAX endpoints in ajax.php
- 49 frontend PHP pages
- 190 master catalog products with prices
- 4 subscription plans (Free, Basic, Pro, Enterprise)
- 7 tenants, 19 users, 7 roles
- Multi-tenant UNIQUE constraints (column, tenant_id) on all tenant tables
