# DEVELOPMENT ROADMAP

# PANGLONG ERP

## Version: 8.0 (Updated 2026-06-29)

---

## Tech Stack

- **Backend:** PHP Native Procedural (no framework)
- **Database:** SQLite via PDO (`frontend/db.php`)
- **Frontend:** Bootstrap 5.3 + jQuery 3.6 + Chart.js
- **AJAX:** Single endpoint `frontend/ajax.php` (60 endpoints)
- **Auth:** Session-based dengan CSRF + rate limiting
- **Testing:** Playwright E2E (26 specs)
- **PWA:** Service worker + manifest.json

---

## Completed Sprints

### Sprint 1-3: Core ERP
- Products, categories, brands, units
- Sales (POS), customers, customer groups
- Suppliers, purchase orders
- Stock management, stock movements
- Dashboard dengan charts

### Sprint 4-6: Operations
- Delivery orders, partial deliveries
- Sales returns, purchase returns
- Quotations, sales orders
- Warehouse locations

### Sprint 7-9: Financial
- Customer-specific pricing, tier pricing
- Batch/lot tracking
- Cash flow statement, bank reconciliation
- Cash book, fixed assets
- e-Faktur, period closing

### Sprint 10-12: Extended
- Fleet management, vehicle maintenance
- Delivery routes
- WhatsApp notifications, templates
- Salesman mobile app
- AI insights
- Marketplace integration
- IoT monitoring

### Gap Features
- Multi-tenant architecture (tenant isolation)
- SaaS management (subscriptions, invoices, revenue)
- Super admin dashboard
- Reorder logic
- Stock opname

### UI/UX Enhancements
- Dark mode, eye-care mode
- Fullscreen toggle
- Responsive design (mobile, tablet, desktop)
- PWA (installable, offline-first)
- CSRF protection, rate limiting
- Anti-double-click
- Session heartbeat

### Master Catalog (2026-06-29)
- 190 produk material bangunan (tenant_id = NULL)
- 19 kategori master, 23 satuan master
- Import dari master catalog ke tenant
- Auto-sync produk baru tenant ke master catalog
- Query produk: `(tenant_id = ? OR tenant_id IS NULL)`

---

## Architecture Decisions

1. **PHP Native, no framework** — Aplikasi 100% PHP procedural. Tidak ada Laravel, Composer, atau framework lain.
2. **Single AJAX endpoint** — Semua CRUD melalui `frontend/ajax.php` dengan parameter `endpoint`.
3. **SQLite database** — File-based, committed to git. Tidak perlu server database.
4. **Session-based auth** — Tidak menggunakan JWT atau token. CSRF + rate limiting untuk security.
5. **Master Catalog** — Produk dengan `tenant_id = NULL` dapat diakses semua tenant.
6. **jQuery + fetch()** — `API_URL` constant didefinisikan di `config.php`. Pattern: `fetch(API_URL + '?endpoint=...')`.
7. **RBAC** — 7 roles dengan permission map. Navigation berbeda per role.
