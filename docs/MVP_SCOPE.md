# MVP SCOPE DOCUMENT

# PANGLONG ERP

## Version: 3.0 (Updated 2026-06-29)

---

## Overview

Panglong ERP adalah sistem ERP untuk distribusi material bangunan dengan arsitektur multi-tenant. Aplikasi 100% PHP Native + PDO SQLite + jQuery AJAX + Bootstrap 5.3.

---

## MVP Deliverables

### Core ERP
- Product management (CRUD, categories, brands, units, barcodes)
- Sales (POS, cart, checkout, payment, invoice)
- Customer management (CRUD, groups, pricing)
- Supplier management (CRUD, price history)
- Purchase orders (create, receive, partial receive)
- Stock management (movements, adjustments, transfers, opname)
- Dashboard (stats, charts, quick access)

### Operations
- Delivery orders (partial delivery, tracking)
- Returns (sales returns, purchase returns)
- Quotations & sales orders
- Warehouse management (locations, transfers)

### Financial
- Customer-specific pricing & tier pricing
- Batch/lot tracking
- Cash flow & bank reconciliation
- Cash book & fixed assets
- e-Faktur & period closing
- Accounting (journal entries, P&L, balance sheet)

### Extended
- Fleet management & vehicle maintenance
- Delivery routes
- WhatsApp notifications & templates
- Salesman mobile app
- AI insights
- Marketplace integration
- IoT monitoring

### Multi-Tenant & SaaS
- Tenant registration & isolation
- SaaS management (subscriptions, invoices, revenue)
- Super admin dashboard
- Master catalog (190 produk bangunan, import/sync)

### UI/UX
- RBAC navigation per role
- Dark mode, eye-care mode
- Fullscreen toggle
- Responsive (mobile, tablet, desktop)
- PWA (installable, offline-first)

### Security
- Session-based auth with password_verify()
- CSRF protection
- Rate limiting
- Login attempt lockout
- Audit logging

---

## Success Criteria

1. Tenant dapat register → login → manage products → sell → report
2. Master catalog tersedia untuk semua tenant (190 produk)
3. Tenant dapat import produk dari master catalog
4. Produk baru tenant auto-sync ke master catalog
5. Data isolation antar tenant terjaga
6. Semua role dapat mengakses menu sesuai RBAC
7. UI responsive di mobile, tablet, desktop
8. PWA installable dan works offline

---

## Tech Stack

- PHP 8.2+ (Native procedural, no framework)
- PDO SQLite (file-based, 87 tables)
- jQuery 3.6 + Bootstrap 5.3
- Chart.js for dashboard
- Playwright for E2E testing
- 49 frontend PHP files, 60 AJAX endpoints
