# Multi-Tenant Guide - Panglong ERP

## Struktur Hierarki Multi-Tenant

### Konsep Penting

- **Super Admin** (`role_slug = 'super_admin'`) — Mengelola platform, tidak terikat tenant
- **Tenant Owner** (`role_slug = 'owner'`) — Owner dari sebuah tenant
- **Tenant Users** — User lain dalam tenant (manager, kasir, gudang, accounting, supervisor)
- **Master Catalog** — Produk dengan `tenant_id = NULL`, dapat diakses semua tenant

### Data Isolation

Setiap tabel yang mendukung multi-tenant memiliki kolom `tenant_id`:
- Data tenant hanya terlihat oleh user tenant tersebut
- Master catalog (`tenant_id = NULL`) terlihat oleh semua tenant
- Super admin dapat melihat semua data

### Query Pattern

```php
// Tenant melihat produk sendiri + master catalog
if (!$isSuperAdmin && $tenantId) {
    $sql .= " AND ({$alias}.tenant_id = ? OR {$alias}.tenant_id IS NULL)";
    $params[] = $tenantId;
}

// Super admin melihat semua
// (no filter)
```

### Helper Functions (ajax.php)

- `addTenantFilter($sql, $alias, $tenantId, $isSuperAdmin, &$params)` — Filter tenant only
- `addTenantFilterWithMaster($sql, $alias, $tenantId, $isSuperAdmin, &$params)` — Filter tenant + master catalog

---

## Master Catalog

### Overview
Master catalog adalah katalog produk global milik super admin yang dapat diakses semua tenant.

### Stats
- **190 produk** material bangunan (semen, besi, cat, pipa, sanitary, listrik, dll.)
- **19 kategori** master
- **23 satuan** master

### Import dari Master Catalog
1. Tenant buka halaman Products
2. Klik "Import dari Master Catalog"
3. Cari produk di modal
4. Klik "Import" → endpoint `master-products` (POST)
5. Produk di-copy ke tenant dengan `tenant_id = {tenant_id}`

### Auto-Sync ke Master Catalog
Saat tenant membuat produk baru:
1. Cek apakah nama produk sudah ada di master catalog (`tenant_id IS NULL`)
2. Jika belum ada, insert copy dengan `tenant_id = NULL`
3. Produk tersedia untuk tenant lain

### Seed Script
```bash
/opt/lampp/bin/php scripts/seed_master_catalog.php
```

---

## Tenant Registration

1. Super admin atau tenant owner register via `register.php`
2. Buat tenant record (company_name, subdomain, status='trial')
3. Buat user owner untuk tenant
4. Tenant dapat langsung login dan mulai berjualan
5. Produk master catalog langsung tersedia

---

## SaaS Management

- **Subscriptions** — Plan (free, basic, pro, enterprise), billing cycle
- **Subscription Invoices** — Auto-generated invoices
- **SaaS Revenue Dashboard** — Super admin melihat total revenue, active tenants, trial tenants
- **Tenant Status** — active, trial, suspended

---

## Roles & Permissions

| Role | Scope | Access |
|------|-------|--------|
| super_admin | Platform | All tenants, SaaS, settings |
| owner | Tenant | Full tenant access + settings + users |
| manager | Tenant | All except settings & SaaS |
| kasir | Tenant | Sales, customers, deliveries, returns |
| gudang | Tenant | Products, stock, suppliers, PO |
| accounting | Tenant | Accounting, cash, e-Faktur |
| supervisor | Tenant | Dashboard, reports |
