# MASTER BLUEPRINT

# PANGLONG ERP + DISTRIBUTION + SaaS PLATFORM

## Version: 3.0 (Updated 2026-06-29)

---

## Architecture

**100% PHP Native Procedural + PDO SQLite + jQuery AJAX + Bootstrap 5.3**

```
panglong/
├── index.php                 # Gerbang utama → login/dashboard
├── frontend/                 # Aplikasi utama (49 PHP files)
│   ├── config.php            # Session, navbar, theme, CSRF
│   ├── db.php                # PDO SQLite singleton
│   ├── auth.php              # Auth, RBAC, CSRF, rate limiting
│   ├── ajax.php              # 60 AJAX endpoints (4044 lines)
│   ├── index.php             # Dashboard
│   ├── login.php             # Login page
│   ├── register.php          # Tenant registration
│   ├── products.php          # Products + master catalog import
│   ├── sales.php             # POS
│   └── ... (40+ pages)
├── database/
│   └── database.sqlite       # 87 tables, committed to git
├── scripts/                  # 8 utility scripts
├── tests/e2e/                # 26 Playwright specs
├── docs/                     # 18 MD files
└── playwright.config.js
```

---

## Core Modules

### 1. Product Management
- CRUD products with categories, brands, units
- Multi-unit support (base unit + conversions)
- Barcode lookup
- Master catalog (190 produk, tenant_id = NULL)
- Import from master catalog
- Auto-sync new products to master

### 2. Sales (POS)
- Cart-based checkout
- Customer selection
- Discount (per item + global)
- Tax calculation
- Multiple payment methods
- Invoice generation
- Stock validation

### 3. Inventory
- Stock movements (in/out/adjustment/transfer)
- Stock opname (physical count)
- Min/max stock alerts
- Batch/lot tracking
- FIFO stock valuation
- Landed cost calculation

### 4. Purchasing
- Purchase orders (create, receive, partial)
- Supplier management
- Supplier price history

### 5. Delivery
- Delivery orders
- Partial deliveries
- Fleet management
- Delivery routes
- Tracking

### 6. Financial
- Cash book
- Bank reconciliation
- Cash flow statement
- Fixed assets
- e-Faktur (PPN)
- Period closing
- Accounting (journal, P&L, balance sheet)

### 7. CRM
- Customer management
- Customer groups
- Customer-specific pricing
- Quotations
- Sales orders

### 8. Multi-Tenant & SaaS
- Tenant registration
- Data isolation (tenant_id)
- Master catalog (global products)
- Subscription plans
- SaaS revenue dashboard
- Super admin management

### 9. Communication
- WhatsApp templates & messages
- Salesman mobile app

### 10. Analytics
- Reports (sales, inventory, AR aging)
- AI insights
- IoT monitoring
- Marketplace integration

---

## Security Architecture

- Session-based auth (password_verify)
- RBAC (7 roles, permission map)
- CSRF token validation
- Rate limiting (30 req/60s writes)
- Login lockout (5 attempts = 15 min)
- Audit logging
- Security headers
- Anti-double-click
- Session heartbeat
