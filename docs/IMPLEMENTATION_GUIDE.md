# Implementation Guide - Panglong ERP

## Flow Onboarding Tenant

### Overview
Tenant baru dapat register → login → import dari master catalog → mulai berjualan dalam 5 menit.

### Step 1: Registration
1. Super admin atau tenant owner buka `register.php`
2. Isi: company name, subdomain, owner name, username, password
3. System membuat: tenant record, user owner, default branches, default settings
4. Master catalog (190 produk) langsung tersedia

### Step 2: Import dari Master Catalog
1. Login sebagai tenant owner
2. Buka halaman Products
3. Klik "Import dari Master Catalog"
4. Cari produk, klik "Import"
5. Produk di-copy ke katalog tenant

### Step 3: Setup
1. Tambah customer
2. Tambah supplier (atau gunakan existing)
3. Set warehouse location
4. Set payment methods
5. Configure tax rate

### Step 4: Mulai Berjualan
1. Buat sales order / quotation (optional)
2. Buat sale (POS) — pilih customer, tambah produk ke cart
3. Pilih payment method
4. Generate invoice
5. Create delivery order (jika perlu)

---

## Master Catalog Implementation

### Database
- Produk dengan `tenant_id = NULL` = master catalog
- Query: `(tenant_id = ? OR tenant_id IS NULL)` untuk tenant access
- 190 produk, 19 kategori, 23 satuan

### AJAX Endpoints
- `GET master-products` — List master catalog (paginated, search, filter)
- `POST master-products` — Import master product to tenant (copy with new tenant_id)

### Auto-Sync
Saat tenant membuat produk baru di `ajax.php`:
1. Cek apakah nama produk sudah ada di master (`tenant_id IS NULL`)
2. Jika belum, insert copy dengan `tenant_id = NULL`
3. Produk tersedia untuk semua tenant

### Files Modified
- `frontend/ajax.php` — Endpoints + auto-sync logic + tenant filter with master
- `frontend/products.php` — Master catalog modal + import button
- `frontend/sales.php` — Product query include master
- `frontend/quotations.php` — Product query include master
- `frontend/stock.php` — Product query include master
- `frontend/warehouses.php` — Product query include master
- `frontend/stock_opname.php` — Product query include master
- `frontend/sales_orders.php` — Product query include master

### Seed Script
```bash
/opt/lampp/bin/php scripts/seed_master_catalog.php
```
