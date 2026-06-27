# Panglong ERP — Analisis Realita Lapangan & Antisipasi

> Dokumen ini adalah hasil analisis mendalam aplikasi Panglong ERP terhadap realita operasional toko retail di Indonesia.
> Dibuat: 27 Juni 2026

---

## DAFTAR ISI

1. [Audit Sistem Role & Permission](#1-audit-sistem-role--permission)
2. [Antisipasi Role yang Tidak Ada di Toko](#2-antisipasi-role-yang-tidak-ada-di-toko)
3. [Pemisahan Data Antar Tenant](#3-pemisahan-data-antar-tenant)
4. [Fitur yang Tidak Relevan untuk Toko Kecil](#4-fitur-yang-tidak-relevan-untuk-toko-kecil)
5. [Skenario Gudang: Same-Site, Off-Site, atau Tanpa Gudang](#5-skenario-gudang-same-site-off-site-atau-tanpa-gudang)
6. [Shift Karyawan, Absensi, dan Sanksi](#6-shift-karyawan-absensi-dan-sanksi)
7. [Pesanan Sebagian Diambil/Diantar](#7-pesanan-sebagian-diambil-diantar)
8. [Pembelian Antar Cabang (Bukan dari Supplier)](#8-pembelian-antar-cabang-bukan-dari-supplier)
9. [Gap Kritis Lainnya](#9-gap-kritis-lainnya)
10. [Gangguan Infrastruktur: Listrik Padam, Jaringan, Baterai](#10-gangguan-infrastruktur-listrik-padam-jaringan-baterai)
11. [Race Condition & Concurrency Control](#11-race-condition--concurrency-control)
12. [Master Product Database (Katalog Produk Pusat)](#12-master-product-database-katalog-produk-pusat)
13. [Skenario Production Hosting & Praktek Nyata](#13-skenario-production-hosting--praktek-nyata)
14. [Matriks Prioritas](#14-matriks-prioritas)

---

## 1. AUDIT SISTEM ROLE & PERMISSION

### Kondisi Saat Ini

| Role | Slug | Nav Items | Permission Seeder |
|------|------|-----------|-------------------|
| Owner | `owner` | 34 | ALL (bypass) |
| Manager | `manager` | 32 | All except `manage_users` |
| Kasir | `kasir` | 12 | 4 perms: create_sales, edit_sales, manage_customers, record_payment |
| Gudang | `gudang` | 18 | 3 perms: manage_products, stock_adjustment, manage_suppliers |
| Accounting | `accounting` | 10 | 3 perms: view_reports, record_payment, manage_customers |
| Supervisor | `supervisor` | 2 | 2 perms: view_reports, view_profit |
| Super Admin | `super_admin` | 5 | Platform-level (tenant management) |

### Enforcement

- **ajax.php**: Role-based endpoint mapping (58 endpoints, lines 70-129) — per-role, bukan per-permission
- **Page-level**: Hanya 4 halaman pakai `requirePermission()`: batches.php, landed_cost.php, closing.php, cash_flow.php
- **Owner bypass**: `hasPermission()` return `true` untuk owner tanpa cek actual permission
- **Permission seeder** (12 permissions) sebagian besar tidak dipakai di frontend

---

## 2. ANTISIPASI ROLE YANG TIDAK ADA DI TOKO

### Realita Lapangan

Toko kecil (warung, minimarket kecil, toko sembako) sering hanya punya 1-2 orang:
- Owner yang sering kali juga jadi kasir, gudang, accounting sekaligus
- Tidak ada orang khusus untuk role Accounting, Supervisor, atau Gudang
- Toko menengah mungkin punya kasir + gudang tapi tidak ada accounting khusus

### Skenario & Antisipasi

#### Skenario A: Toko hanya 1 orang (Owner-Operator)

**Keputusan**: Owner sudah punya akses penuh (bypass semua permission). Tidak perlu perubahan. Owner login dan melakukan semua fungsi sendiri.

#### Skenario B: Toko punya owner + 1 kasir, tidak ada gudang/accounting

**Keputusan**: 
- **Role fallback otomatis**: Sistem harus mendeteksi jika tidak ada user dengan role tertentu, tanggung jawab role itu otomatis jatuh ke owner/manager.
- **Tidak perlu buat user dummy** untuk role yang tidak ada.
- **Kasir dengan akses diperluas**: Tambah konsep "role inheritance" — jika toko hanya punya owner + kasir, kasir bisa diberi permission tambahan (misal: manage_products) tanpa harus ganti role.
- **Implementasi**: Tambah field `extra_permissions` (JSON) di tabel `users` untuk permission tambahan di luar role default. Owner bisa centang permission tambahan per user di halaman users.php.

#### Skenario C: Toko tidak punya orang Accounting

**Keputusan**:
- Owner atau Manager yang menjalankan modul akuntansi.
- Tidak perlu user khusus — owner sudah bypass semua permission.
- Jika manager yang handle, permission `view_reports` dan `record_payment` sudah cukup untuk operasi dasar.
- **Modul akuntansi tetap berjalan** — jurnal otomatis dari transaksi penjualan/pembelian tetap terbentuk tanpa perlu user accounting.

#### Skenario D: Toko tidak punya Supervisor

**Keputusan**:
- Supervisor saat ini hanya bisa lihat reports (2 permission). Jika tidak ada supervisor, owner/manager yang lihat reports.
- **Yang lebih penting**: Jika supervisor tidak ada, approval workflow (void, refund, adjustment) butuh fallback ke manager atau owner.
- **Implementasi approval fallback**: Buat setting `approval_fallback_role` di app_settings. Jika tidak ada user dengan role supervisor, approval request otomatis dirutekan ke manager, jika manager tidak ada, ke owner.

### Keputusan Final: Role Fallback System

```
Jika role X tidak ada user aktif:
  - Approval request → fallback ke role di atasnya (supervisor → manager → owner)
  - Modul tetap bisa diakses oleh owner (bypass) atau user dengan extra_permissions
  - Tidak ada error "role not found" — sistem graceful degrade
```

**Implementasi teknis**:
1. Tambah kolom `extra_permissions` (TEXT/JSON) di `users` table
2. Modifikasi `hasPermission()` di auth.php untuk cek `extra_permissions` selain role permissions
3. Tambah setting `approval_fallback_chain` (JSON array: `["supervisor","manager","owner"]`) di app_settings
4. Di halaman users.php, tambahkan UI untuk centang permission tambahan per user

---

## 3. PEMISAHAN DATA ANTAR TENANT

### Kondisi Saat Ini

- `tenant_id` ada di 15+ tabel: products, categories, customers, customer_groups, suppliers, sales, purchase_orders, stock_movements, stock_adjustments, stock_opnames, deliveries, chart_of_accounts, journal_entries, warehouses, app_settings
- `addTenantFilter()` function ada di ajax.php
- Super Admin tidak difilter (bisa lihat semua tenant)
- User non-super-admin difilter berdasarkan `tenant_id` session

### Realita Lapangan

- SaaS: Setiap tenant adalah perusahaan terpisah. Data produk, transaksi, customer, supplier TIDAK boleh bocor antar tenant.
- Multi-cabang dalam 1 tenant: Data antar cabang (branch) masih dalam tenant yang sama, tapi perlu isolasi untuk role tertentu (kasir hanya lihat cabangnya).

### Yang Sudah Benar

- Tenant filtering di query sudah berjalan untuk mayoritas endpoint
- Insert sudah set `tenant_id` dari session user

### Yang Perlu Diperbaiki

1. **Tabel yang belum punya tenant_id**:
   - `sale_items` — tidak ada tenant_id (mengakses via join sales, tapi tetap perlu untuk direct query)
   - `sale_payments` — tidak ada tenant_id
   - `delivery_items` — tidak ada tenant_id
   - `stock_transfer_items` — tidak ada tenant_id
   - `opname_items` — tidak ada tenant_id
   - `journal_entry_lines` — tidak ada tenant_id (via join journal_entries, tapi risk)
   - `product_units` — sudah ada tenant_id di insert tapi tidak di schema migration
   - `barcodes` — sudah ada tenant_id di insert tapi tidak di schema migration
   - `product_batches` — sudah ada tenant_id di insert tapi tidak di schema migration
   - `employees` — TIDAK ada tenant_id
   - `branches` — TIDAK ada tenant_id
   - `fixed_assets` — TIDAK ada tenant_id
   - `asset_depreciations` — TIDAK ada tenant_id
   - `cash_transactions` — TIDAK ada tenant_id
   - `bank_statements` — TIDAK ada tenant_id
   - `sales_returns` — TIDAK ada tenant_id
   - `sales_return_items` — TIDAK ada tenant_id
   - `purchase_returns` — TIDAK ada tenant_id
   - `purchase_return_items` — TIDAK ada tenant_id
   - `quotations` — TIDAK ada tenant_id
   - `quotation_items` — TIDAK ada tenant_id
   - `sales_orders` — TIDAK ada tenant_id
   - `sales_order_items` — TIDAK ada tenant_id
   - `customer_product_prices` — TIDAK ada tenant_id
   - `product_tier_prices` — TIDAK ada tenant_id
   - `supplier_price_history` — TIDAK ada tenant_id
   - `demand_forecasts` — sudah ada tenant_id
   - `price_optimizations` — sudah ada tenant_id
   - `marketplace_integrations` — sudah ada tenant_id
   - `marketplace_product_mappings` — TIDAK ada tenant_id
   - `iot_sensors` — sudah ada tenant_id
   - `iot_sensor_readings` — TIDAK ada tenant_id (via join sensor, tapi risk)
   - `reorder_suggestions` — TIDAK ada tenant_id
   - `sync_logs` — sudah ada tenant_id
   - `subscription_plans` — global (tidak perlu tenant_id)
   - `subscriptions` — sudah ada tenant_id
   - `subscription_invoices` — sudah ada tenant_id

### Keputusan Final

**Tambah `tenant_id` ke semua tabel bisnis yang belum punya.** Eksekusi via migration:

```sql
-- Tabel yang butuh tenant_id ditambahkan:
ALTER TABLE employees ADD COLUMN tenant_id INTEGER;
ALTER TABLE branches ADD COLUMN tenant_id INTEGER;
ALTER TABLE fixed_assets ADD COLUMN tenant_id INTEGER;
ALTER TABLE cash_transactions ADD COLUMN tenant_id INTEGER;
ALTER TABLE bank_statements ADD COLUMN tenant_id INTEGER;
ALTER TABLE sales_returns ADD COLUMN tenant_id INTEGER;
ALTER TABLE purchase_returns ADD COLUMN tenant_id INTEGER;
ALTER TABLE quotations ADD COLUMN tenant_id INTEGER;
ALTER TABLE sales_orders ADD COLUMN tenant_id INTEGER;
ALTER TABLE customer_product_prices ADD COLUMN tenant_id INTEGER;
ALTER TABLE product_tier_prices ADD COLUMN tenant_id INTEGER;
ALTER TABLE supplier_price_history ADD COLUMN tenant_id INTEGER;
ALTER TABLE reorder_suggestions ADD COLUMN tenant_id INTEGER;
-- (item tables tidak perlu tenant_id jika selalu di-join dengan parent)
```

**Aturan**: 
- Tabel header (master, transaksi header) WAJIB punya `tenant_id`
- Tabel item (line items) tidak perlu `tenant_id` jika selalu diakses via join parent
- Setiap query yang langsung SELECT dari tabel item tanpa join parent harus include join parent untuk filter tenant

---

## 4. FITUR YANG TIDAK RELEVAN UNTUK TOKO KECIL

### Realita Lapangan

Toko kecil tidak punya:
- Kendaraan pengiriman (fleet) — toko kecil tidak antar, customer ambil sendiri
- IoT sensors — terlalu mahal untuk toko kecil
- Marketplace integration — toko kecil mungkin belum jual online
- Fixed assets — toko kecil mungkin tidak punya aset tetap yang perlu depresiasi
- e-Faktur — toko kecil mungkin tidak PKP
- Landed cost — toko kecil beli langsung tanpa biaya import/logistik kompleks

### Keputusan: Modular Feature Toggle

**Implementasi**: Tambah tabel `feature_toggles` atau gunakan `app_settings` dengan key `enabled_modules` (JSON).

```php
// Cek apakah modul aktif untuk tenant ini
function isModuleEnabled($module) {
    $settings = db()->query("SELECT value FROM app_settings WHERE key = 'enabled_modules'")->fetch();
    $enabled = $settings ? json_decode($settings['value'], true) : [];
    return in_array($module, $enabled);
}

// Daftar modul yang bisa di-toggle
$modules = [
    'fleet',           // default: off
    'iot',             // default: off
    'marketplace',     // default: off
    'fixed_assets',    // default: off
    'e_faktur',        // default: off
    'landed_cost',     // default: off
    'accounting',      // default: on
    'deliveries',      // default: on
    'quotations',      // default: on
    'sales_orders',    // default: on
    'stock_transfers', // default: on
    'batches',         // default: off (toko kecil tidak butuh batch tracking)
    'ai_insights',     // default: off
    'whatsapp',        // default: off
    'salesman_app',    // default: off
    'pricing',         // default: on
    'saas',            // default: off (hanya untuk super_admin)
];
```

**Nav rendering**: Di `getNavLinks()`, tambahkan cek `isModuleEnabled()` sebelum menampilkan menu.

**ajax.php**: Endpoint untuk modul yang disabled return 403 dengan message "Modul tidak aktif".

**Subscription plan integration**: 
- Plan "Basic": fleet=off, iot=off, marketplace=off, accounting=on
- Plan "Pro": semua on kecuali iot
- Plan "Enterprise": semua on

### Skenario Khusus: Toko Tanpa Kendaraan (Fleet)

**Keputusan**: 
- Modul fleet default OFF
- Jika toko punya kendaraan, owner enable di settings
- Jika fleet OFF, deliveries tetap bisa berjalan tanpa assign kendaraan — field `vehicle_plate` dan `driver_name` di deliveries tetap manual text input (tidak wajib pilih dari master fleet)
- Delivery routes juga tidak wajib — bisa kosong

### Skenario Khusus: Toko Tanpa Gudang Terpisah

**Keputusan**:
- Default: 1 warehouse dengan type='display' (gudang = toko)
- Jika toko punya gudang terpisah, owner tambah warehouse dengan type='utama' atau 'cabang'
- Jika tidak ada gudang sama sekali, sistem tetap berjalan dengan warehouse default "Toko" — semua stok masuk ke warehouse ini
- **Implementasi**: Saat registrasi tenant baru, auto-create 1 warehouse default dengan nama = nama toko, type='display'

---

## 5. SKENARIO GUDANG: SAME-SITE, OFF-SITE, ATAU TANPA GUDANG

### Skenario A: Gudang Satu Tempat dengan Toko (Same-Site)

**Realita**: Toko sembako kecil — stok disimpan di belakang toko atau di rak toko langsung. Tidak ada gudang terpisah.

**Keputusan**: 
- 1 warehouse record dengan `type='display'` dan `branch_id` = branch toko
- `warehouse_locations` optional — bisa dibuat zona "Rak Depan", "Rak Belakang", "Gudang Mini" atau tidak dibuat sama sekali
- Stok movement tetap tercatat — masuk dari PO, keluar dari sale
- **Tidak perlu stock transfer** karena hanya 1 warehouse

### Skenario B: Gudang Berbeda Tempat (Off-Site)

**Realita**: Toko menengah punya gudang terpisah untuk stok overflow. Barang dipindahkan dari gudang ke toko saat stok di toko menipis.

**Keputusan**:
- 2 warehouse records: 
  - Warehouse 1: type='display', branch_id = branch toko (toko/front store)
  - Warehouse 2: type='utama' atau 'cabang', branch_id = branch toko atau null (gudang terpisah)
- **Stock transfer** wajib saat pindah barang dari gudang ke toko
- **Receiving**: PO diterima di warehouse 2 (gudang utama), lalu transfer ke warehouse 1 (display)
- **Selling**: Sale mengurangi stok warehouse 1 (display). Saat stok display menipis, lakukan transfer dari gudang.
- **Reporting**: Laporan stok per warehouse — owner bisa lihat stok total dan stok per lokasi

### Skenario C: Tidak Ada Gudang Sama Sekali

**Realita**: Dropship-only atau toko yang stoknya langsung dari supplier per order.

**Keputusan**:
- Sistem tetap membuat 1 warehouse default "Toko" saat registrasi
- Stok bisa di-set 0 untuk semua produk (pre-order/dropship model)
- Saat create sale, sistem cek stok:
  - Jika stok 0 dan produk ditandai `allow_backorder=true`, sale tetap bisa dibuat
  - Jika stok 0 dan `allow_backorder=false`, sale diblok dengan pesan "Stok habis"
- **Implementasi**: Tambah kolom `allow_backorder` (BOOLEAN) di products table

### Skenario D: Multi-Gudang dengan Cabang Berbeda

**Realita**: Toko punya 3 cabang, masing-masing punya gudang sendiri. Plus 1 gudang pusat.

**Keputusan**:
- Warehouse records:
  - Gudang Pusat: type='utama', branch_id=null atau branch pusat
  - Gudang Cabang A: type='cabang', branch_id=branch A
  - Gudang Cabang B: type='cabang', branch_id=branch B
  - Gudang Cabang C: type='cabang', branch_id=branch C
- **Stock transfer** antar gudang dengan approval workflow
- **Branch scoping**: Kasir cabang A hanya lihat stok gudang cabang A
- **PO receiving**: Bisa diterima di gudang pusat atau gudang cabang (field `receiving_warehouse_id` di PO)

---

## 6. SHIFT KARYAWAN, ABSENSI, DAN SANKSI

### Realita Lapangan Indonesia

Berdasarkan studi Alfamart, Indomaret, dan toko retail Indonesia:

- **Shift**: Pagi (07:00-15:00), Siang (15:00-23:00), Malam (23:00-07:00, hanya toko 24 jam)
- **Rotasi shift**: Karyawan rotasi setiap minggu/2 minggu
- **Tidak masuk**: Sakit, izin, alpha (tanpa keterangan)
- **Sanksi**: Potongan gaji untuk alpha, telat, atau tidak hadir tanpa izin
- **Lembur**: Dihitung per jam dengan rate berbeda
- **Kasir EOD**: Wajib rekonsiliasi kas sebelum tutup shift

### Kondisi Saat Ini di Aplikasi

- Tabel `employees` ada dengan position, base_salary, commission_pct
- **Tidak ada**: tabel attendance, shift, leave, payroll, salary deduction
- **Tidak ada**: sistem buka/tutup shift untuk kasir

### Keputusan: Implementasi Shift & Attendance System

#### 6.1 Tabel yang Perlu Dibuat

```sql
-- Master shift
CREATE TABLE shift_schedules (
    id INTEGER PRIMARY KEY,
    tenant_id INTEGER,
    name VARCHAR(50),           -- 'Pagi', 'Siang', 'Malam'
    start_time TIME,            -- '07:00:00'
    end_time TIME,              -- '15:00:00'
    grace_late_minutes INT DEFAULT 15,  -- toleransi terlambat
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- Assign karyawan ke shift per hari
CREATE TABLE employee_shifts (
    id INTEGER PRIMARY KEY,
    tenant_id INTEGER,
    employee_id INTEGER NOT NULL,
    shift_schedule_id INTEGER NOT NULL,
    shift_date DATE NOT NULL,
    branch_id INTEGER,
    status ENUM('scheduled', 'attended', 'absent', 'sick', 'leave', 'late'),
    check_in_at DATETIME,
    check_out_at DATETIME,
    late_minutes INT DEFAULT 0,
    overtime_minutes INT DEFAULT 0,
    notes TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (shift_schedule_id) REFERENCES shift_schedules(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

-- Kasir shift session (buka/tutup kasir)
CREATE TABLE cashier_sessions (
    id INTEGER PRIMARY KEY,
    tenant_id INTEGER,
    user_id INTEGER NOT NULL,
    branch_id INTEGER,
    shift_schedule_id INTEGER,
    opened_at DATETIME NOT NULL,
    closed_at DATETIME,
    opening_cash DECIMAL(15,2) NOT NULL DEFAULT 0,
    expected_cash DECIMAL(15,2) DEFAULT 0,
    closing_cash DECIMAL(15,2) DEFAULT 0,
    cash_difference DECIMAL(15,2) DEFAULT 0,
    total_sales INTEGER DEFAULT 0,
    total_revenue DECIMAL(15,2) DEFAULT 0,
    status ENUM('open', 'closed', 'force_closed') DEFAULT 'open',
    closed_by INTEGER,
    notes TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (shift_schedule_id) REFERENCES shift_schedules(id),
    FOREIGN KEY (closed_by) REFERENCES users(id)
);

-- Absensi/leave requests
CREATE TABLE leave_requests (
    id INTEGER PRIMARY KEY,
    tenant_id INTEGER,
    employee_id INTEGER NOT NULL,
    leave_type ENUM('sick', 'personal', 'annual', 'unpaid', 'emergency'),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INTEGER,
    approved_at DATETIME,
    created_at DATETIME,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Payroll runs
CREATE TABLE payroll_runs (
    id INTEGER PRIMARY KEY,
    tenant_id INTEGER,
    period_month INT NOT NULL,    -- 1-12
    period_year INT NOT NULL,
    branch_id INTEGER,
    status ENUM('draft', 'approved', 'paid') DEFAULT 'draft',
    approved_by INTEGER,
    approved_at DATETIME,
    paid_at DATETIME,
    total_gross DECIMAL(15,2) DEFAULT 0,
    total_deductions DECIMAL(15,2) DEFAULT 0,
    total_net DECIMAL(15,2) DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);

-- Payroll items (per karyawan per periode)
CREATE TABLE payroll_items (
    id INTEGER PRIMARY KEY,
    payroll_run_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    base_salary DECIMAL(15,2) DEFAULT 0,
    overtime_pay DECIMAL(15,2) DEFAULT 0,
    commission_amount DECIMAL(15,2) DEFAULT 0,
    bonus DECIMAL(15,2) DEFAULT 0,
    -- Potongan
    late_deduction DECIMAL(15,2) DEFAULT 0,
    absent_deduction DECIMAL(15,2) DEFAULT 0,
    bpjs_tk DECIMAL(15,2) DEFAULT 0,      -- BPJS Ketenagakerjaan
    bpjs_ks DECIMAL(15,2) DEFAULT 0,      -- BPJS Kesehatan
    pph21 DECIMAL(15,2) DEFAULT 0,        -- PPh 21
    other_deduction DECIMAL(15,2) DEFAULT 0,
    -- Total
    gross_pay DECIMAL(15,2) DEFAULT 0,
    total_deductions DECIMAL(15,2) DEFAULT 0,
    net_pay DECIMAL(15,2) DEFAULT 0,
    working_days INT DEFAULT 0,
    absent_days INT DEFAULT 0,
    late_count INT DEFAULT 0,
    overtime_hours DECIMAL(5,2) DEFAULT 0,
    notes TEXT,
    FOREIGN KEY (payroll_run_id) REFERENCES payroll_runs(id),
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- Salary deduction rules (configurable per tenant)
CREATE TABLE deduction_rules (
    id INTEGER PRIMARY KEY,
    tenant_id INTEGER,
    rule_type ENUM('late', 'absent', 'early_leave', 'no_show'),
    deduction_amount DECIMAL(15,2) DEFAULT 0,
    deduction_type ENUM('fixed', 'per_day', 'per_hour', 'percentage'),
    threshold_minutes INT DEFAULT 0,  -- untuk late: berapa menit dianggap telat
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);
```

#### 6.2 Alur Shift Kasir

```
1. Kasir login → sistem cek apakah ada shift aktif untuk hari ini
2. Jika belum ada shift → kasir buka shift (cashier_session)
   - Input nominal kas awal (opening_cash)
   - Sistem catat opened_at = now
3. Selama shift berjalan:
   - Setiap sale otomatis link ke cashier_session_id
   - Sistem update total_sales dan total_revenue real-time
4. Kasir tutup shift (EOD):
   - Input nominal kas fisik (closing_cash)
   - Sistem hitung expected_cash = opening_cash + total_cash_sales
   - Sistem hitung cash_difference = closing_cash - expected_cash
   - Jika difference != 0 → wajib input notes penjelasan
   - Jika |difference| > threshold → butuh approval supervisor/manager
5. Supervisor approve/reject EOD
   - Jika reject → kasir wajib recheck dan submit ulang
```

#### 6.3 Antisipasi Karyawan Tidak Masuk

**Skenario**: Kasir A sakit, tidak masuk shift pagi.

**Keputusan**:
1. **Auto-detect**: Sistem cek `employee_shifts` untuk hari ini. Jika status='scheduled' tapi tidak ada `check_in_at` setelah `start_time + grace_late_minutes`, otomatis set status='absent'.
2. **Notifikasi**: Owner/manager dapat notifikasi "Kasir A tidak masuk shift pagi".
3. **Fallback**: Owner/manager bisa:
   - Assign karyawan lain ke shift kosong (re-assign di employee_shifts)
   - Jalankan toko sendiri (owner login sebagai kasir)
   - Tutup shift (toko buka tanpa kasir formal — owner handle)
4. **Deduction**: Saat payroll run, sistem hitung:
   - Jika status='sick' dengan surat dokter → tidak ada potongan
   - Jika status='sick' tanpa surat → potongan sesuai rule
   - Jika status='absent' (alpha) → potongan full hari + denda (sesuai deduction_rules)
   - Jika status='leave' dan approved → tidak ada potongan (cuti tahunan)
   - Jika status='late' → potongan per menit kelipatan (sesuai rule)

#### 6.4 Sanksi dan Potongan Gaji

**Configurable via `deduction_rules` table:**

| Rule Type | Default | Contoh |
|-----------|---------|--------|
| `late` | Rp 5.000 per 30 menit | Telat 1 jam = Rp 10.000 |
| `absent` | 1 hari gaji + Rp 50.000 denda | Alpha 1 hari = potong gaji harian + denda |
| `early_leave` | Rp 5.000 per 30 menit | Pulang cepat 1 jam = Rp 10.000 |
| `no_show` | 2 hari gaji + Rp 100.000 denda | No-show tanpa kabar = sanksi berat |

**Owner bisa customize** di halaman settings → deduction rules.

**Perhitungan otomatis di payroll_run:**
```
gross_pay = base_salary + overtime_pay + commission + bonus
total_deductions = late_deduction + absent_deduction + bpjs_tk + bpjs_ks + pph21 + other
net_pay = gross_pay - total_deductions
```

---

## 7. PESANAN SEBAGIAN DIAMBIL/DIANTAR

### Realita Lapangan

- Customer pesan 10 karung beras, tapi hanya ambil 6 hari ini, sisanya diambil besok
- Customer pesan 50 dus minuman, 20 dus diantar hari ini, 30 dus besok
- Toko bangunan: customer pesan 100 sak semen, 50 diantar pagi, 50 sore

### Kondisi Saat Ini

- `deliveries` table ada dengan `delivery_items` — bisa create multiple deliveries per sale
- `purchase_items` punya `received_quantity` untuk partial receiving
- **Tidak ada partial delivery tracking di sales** — sale status hanya draft/completed/voided/returned
- **Tidak ada partial pickup tracking** — tidak ada field "quantity_taken" vs "quantity_ordered"

### Keputusan: Partial Fulfillment System

#### 7.1 Tambah Field di Sales

```sql
ALTER TABLE sales ADD COLUMN fulfillment_status VARCHAR(20) DEFAULT 'pending';
-- Values: 'pending', 'partially_fulfilled', 'fulfilled', 'cancelled'
ALTER TABLE sales ADD COLUMN total_quantity DECIMAL(10,3) DEFAULT 0;
ALTER TABLE sales ADD COLUMN fulfilled_quantity DECIMAL(10,3) DEFAULT 0;
```

#### 7.2 Tambah Field di Sale Items

```sql
ALTER TABLE sale_items ADD COLUMN fulfilled_quantity DECIMAL(10,3) DEFAULT 0;
ALTER TABLE sale_items ADD COLUMN pending_quantity DECIMAL(10,3) DEFAULT 0;
-- pending_quantity = quantity - fulfilled_quantity
```

#### 7.3 Alur Partial Fulfillment

```
1. Customer pesan 10 karung beras (sale created, status='completed', fulfillment_status='pending')
   - sale_items: quantity=10, fulfilled_quantity=0, pending_quantity=10
   - Stock TIDAK langsung berkurang — stok di-reserve (reserved_quantity)

2. Customer ambil 6 karung (create delivery/pickup)
   - delivery_items: quantity=6
   - Update sale_items: fulfilled_quantity=6, pending_quantity=4
   - Stock berkurang 6 (stock_movement: 'sale', quantity=-6)
   - sales.fulfillment_status = 'partially_fulfilled'

3. Customer ambil sisa 4 karung besok
   - delivery_items: quantity=4
   - Update sale_items: fulfilled_quantity=10, pending_quantity=0
   - Stock berkurang 4
   - sales.fulfillment_status = 'fulfilled'
```

#### 7.4 Stock Reservation

**Keputusan**: Saat sale dibuat, stok di-reserve (tidak langsung dikurangi dari available stock).

```sql
ALTER TABLE products ADD COLUMN reserved_quantity DECIMAL(10,3) DEFAULT 0;
-- available_stock = current_stock - reserved_quantity
```

Atau lebih baik: gunakan tabel `stock_reservations`:
```sql
CREATE TABLE stock_reservations (
    id INTEGER PRIMARY KEY,
    tenant_id INTEGER,
    sale_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    unit_id INTEGER,
    reserved_quantity DECIMAL(10,3) NOT NULL,
    fulfilled_quantity DECIMAL(10,3) DEFAULT 0,
    status ENUM('active', 'fulfilled', 'released', 'cancelled') DEFAULT 'active',
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

**Tampilan stok menjadi**:
- **Stok Tersedia** = current_stock - reserved (available for new sales)
- **Stok Reserved** = total reserved (sudah dipesan, belum diambil)
- **Stok Total** = current_stock (fisik di gudang)

#### 7.5 Antisipasi: Customer Tidak Ambil Sisa Pesanan

**Skenario**: Customer pesan 10, ambil 6, sisanya tidak diambil selama 30 hari.

**Keputusan**:
- Tambah setting `reservation_expiry_days` (default: 30) di app_settings
- Cron job / manual check: Jika reservation melebihi expiry_days, auto-release:
  - `stock_reservations.status = 'released'`
  - `sale_items.pending_quantity = 0` (atau tetap record tapi mark as 'expired')
  - `sales.fulfillment_status = 'fulfilled'` (anggap selesai dengan yang sudah diambil)
  - Atau `sales.fulfillment_status = 'cancelled_partial'` dengan notes
- Owner bisa extend reservation expiry per sale (untuk customer VIP)

---

## 8. PEMBELIAN ANTAR CABANG (BUKAN DARI SUPPLIER)

### Realita Lapangan

- Toko punya 3 cabang. Cabang A kehabisan stok beras, butuh restock dari cabang B (bukan dari supplier).
- Cabang pusat beli grosir dari supplier, lalu distribusi ke cabang.
- Ant cabang = transfer stok, BUKAN purchase order dari supplier.

### Kondisi Saat Ini

- `stock_transfers` table ada dengan from_warehouse_id dan to_warehouse_id
- Tapi: Tidak ada "purchase" antar cabang — hanya transfer stok
- Tidak ada perhitungan biaya/harga antar cabang (transfer stok tidak ada "harga beli")
- Tidak ada journal entry untuk transfer antar cabang

### Keputusan: Dua Model Inter-Cabang

#### Model 1: Stock Transfer (Tanpa Transaksi Keuangan)

**Gunakan ketika**: Cabang adalah milik sendiri (1 tenant), transfer = pemindahan internal.

**Alur**:
1. Cabang A request stok dari cabang B (create stock_transfer)
2. Cabang B approve dan keluarkan stok (status: in_transit)
3. Cabang A terima stok (status: received)
4. Stok di cabang B berkurang, stok di cabang A bertambah
5. **Tidak ada journal entry** — ini pemindahan internal, bukan transaksi jual-beli
6. **Bisa ada biaya transport** — catat sebagai cash_transaction (operasional)

**Yang perlu diperbaiki**:
- Tambah `branch_id` (from_branch, to_branch) di stock_transfers — saat ini hanya from_warehouse/to_warehouse
- Tambah approval workflow: pending → approved → in_transit → received → cancelled
- Tambah `approved_by` dan `approved_at`
- Tambah `transport_cost` (decimal, optional) dan `transport_cost_paid_by` (enum: sender, receiver, shared)

#### Model 2: Inter-Branch Purchase Order (Dengan Transaksi Keuangan)

**Gunakan ketika**: Cabang adalah franchise atau entity terpisah yang butuh pencatatan jual-beli.

**Alur**:
1. Cabang A buat PO ke "Supplier Internal" (cabang B)
2. Cabang B terima PO, proses sebagai sale (dengan customer = cabang A)
3. Cabang B keluarkan stok, buat delivery ke cabang A
4. Cabang A terima barang, update PO receiving
5. Journal entry:
   - Cabang B: Debit AR-CabangA, Credit Sales Revenue
   - Cabang A: Debit Inventory, Credit AP-CabangB

**Implementasi**:
- Tambah tipe supplier: `internal` (supplier yang sebenarnya adalah cabang sendiri)
- Atau: Tambah field `is_inter_branch` di purchase_orders dan sales
- Tambah field `inter_branch_id` di PO (menunjuk ke cabang sumber)
- Tambah field `inter_branch_po_id` di sales (menunjuk ke PO dari cabang lain)

#### Keputusan Final

**Default: Model 1 (Stock Transfer)** untuk semua cabang dalam 1 tenant.
**Opsi: Model 2** untuk franchise atau entity terpisah — enable via setting `inter_branch_billing = true`.

**Yang perlu diimplementasi segera (Model 1)**:
1. Tambah `from_branch_id`, `to_branch_id`, `approved_by`, `approved_at`, `transport_cost` di stock_transfers
2. Approval workflow di ajax.php untuk stock-transfers endpoint
3. UI di stock_transfers.php untuk approval button
4. Tambah `branch_id` filter untuk kasir/gudang (hanya lihat transfer untuk cabangnya)

---

## 9. GAP KRITIS LAINNYA

### 9.1 Payment Methods — Tidak Sesuai Realita Indonesia

**Current**: enum ['cash', 'credit', 'transfer']
**Realita**: QRIS wajib (regulasi BI), e-wallet (GoPay/DANA/OVO/ShopeePay), kartu debit/kredit

**Keputusan**: Ubah payment_method dari enum ke VARCHAR + reference table `payment_methods` (sudah ada).

```sql
-- Sudah ada payment_methods reference table
-- Ubah sales.payment_method dari ENUM ke VARCHAR
ALTER TABLE sales RENAME COLUMN payment_method TO payment_method_old;
ALTER TABLE sales ADD COLUMN payment_method VARCHAR(50) DEFAULT 'cash';
UPDATE sales SET payment_method = payment_method_old;
```

Tambah default payment methods di seeder:
- cash, transfer, qris, ewallet_gopay, ewallet_dana, ewallet_ovo, ewallet_shopeepay, debit_bca, debit_mandiri, credit_card, credit (piutang)

### 9.2 Void Sales — Tidak Ada Approval

**Keputusan**: 
- Tambah kolom `void_reason`, `void_requested_by`, `void_approved_by`, `void_approved_at` di sales
- Kasir hanya bisa request void (status = 'void_requested')
- Supervisor/manager approve (status = 'voided') atau reject (status kembali ke 'completed')
- Jika tidak ada supervisor, fallback ke manager/owner (lihat section 2)

### 9.3 Discount Control — Tidak Ada Guard

**Keputusan**:
- Tambah setting `max_discount_pct` (default: 10) di app_settings
- Tambah setting `max_discount_amount` (default: 100000) di app_settings
- Jika diskon > threshold, butuh approval supervisor
- Tambah kolom `discount_approved_by` di sales

### 9.4 FEFO — Tidak Otomatis

**Keputusan**:
- Saat create sale item, sistem otomatis pilih batch dengan `expiry_date` terdekat yang masih ada `quantity_remaining > 0`
- Tambah field `batch_id` di `sale_items` untuk tracking batch mana yang terjual
- Tambah endpoint `auto-pick-batch` yang return batch recommendation

### 9.5 Customer Credit Limit — Tidak Enforced

**Keputusan**:
- Saat create sale dengan payment_method='credit', cek:
  ```sql
  SELECT COALESCE(SUM(s.total), 0) - COALESCE((SELECT SUM(sp.amount) FROM sale_payments sp WHERE sp.sale_id IN (SELECT id FROM sales WHERE customer_id = ?)), 0) as outstanding
  FROM sales s WHERE s.customer_id = ? AND s.payment_status != 'paid' AND s.status != 'voided'
  ```
- Jika outstanding + new_sale_total > credit_limit → block dengan pesan "Melebihi limit kredit"
- Owner bisa override (bypass) dengan input reason

### 9.6 Audit Logging — Minimal

**Keputusan**: Tambah `logAudit()` di setiap POST/PUT/DELETE endpoint:
- sales (create, void, edit)
- purchase_orders (create, receive, cancel)
- stock_adjustments (create, approve)
- stock_transfers (create, approve, receive)
- deliveries (create, update status)
- returns (create, approve)
- users (create, update, delete, activate/deactivate)
- settings (update)
- cash_transactions (create, update, delete)
- customers/suppliers (create, update, delete)
- quotations, sales_orders (create, update, convert)

### 9.7 PPN 11% — Tidak Auto-Calculate

**Keputusan**:
- Tambah setting `tax_rate` (default: 11) dan `tax_enabled` (default: false) di app_settings
- Saat create sale, jika tax_enabled=true:
  - DPP = subtotal - discount
  - PPN = DPP * (tax_rate / 100)
  - Total = DPP + PPN
- Auto-generate e-Faktur record dari sales yang PPN-nya dipungut

### 9.8 Offline POS Mode

**Keputusan** (P2 — jangka menengah):
- Service worker cache untuk halaman POS (sales.php)
- IndexedDB untuk offline transaction queue
- Saat online kembali, sync queue ke server via ajax.php
- `sync_logs` table sudah ada — gunakan untuk tracking sync status

### 9.9 Komisi Salesman

**Keputusan**:
- Tambah field `salesman_id` (employee_id) di sales table
- Saat create sale, jika ada salesman, link ke employee
- Endpoint `commission-report`: hitung komisi per salesman per periode
- Formula: `commission = total_sales * (commission_pct / 100)`
- Integrasikan ke payroll_items.commission_amount

### 9.10 Thermal Printer

**Keputusan** (P2):
- Tambah format thermal 58mm dan 80mm di print_nota.php
- Tambah QRIS QR code jika payment_method = 'qris'
- Tambah logo toko (dari tenant settings)
- Tambah barcode produk di receipt

---

## 10. GANGGUAN INFRASTRUKTUR: LISTRIK PADAM, JARINGAN, BATERAI

### Realita Lapangan Indonesia

- **Listrik padam** sering terjadi di Indonesia (PLN outage, gedung tua, area rural). Toko 24 jam paling terdampak.
- **Jaringan internet putus** — WiFi mati, ISP down, sinyal lemah di area remote. Toko dengan internet tidak stabil.
- **Baterai ponsel habis** — kasir atau salesman menggunakan tablet/HP, baterai habis di tengah transaksi.
- **Server lokal mati** — jika XAMPP/Apapse crash atau PC restart paksa.

### Kondisi Saat Ini di Aplikasi

- **sw.js** ada (PWA basic) — cache static assets (Bootstrap, jQuery, Chart.js) untuk GET requests saja
- **manifest.json** ada — installable sebagai PWA
- **sync_logs** table ada di DB — tapi tidak ada implementasi sync mechanism
- **Tidak ada**: offline transaction queue, IndexedDB storage, auto-retry, connection status indicator
- **Tidak ada**: auto-save draft transaksi, session recovery, graceful degradation

### Skenario & Antisipasi

#### 10.1 Listrik Padam Saat Transaksi

**Skenario**: Kasir sedang input 15 item ke keranjang, tiba-tiba listrik padam. PC mati. Browser tertutup.

**Keputusan**: 
- **Auto-save cart ke localStorage** setiap kali item ditambah/dihapus
- Saat kasir kembali login, sistem deteksi: "Ada transaksi draft yang belum disimpan. Lanjutkan?"
- Cart state disimpan: daftar produk, qty, harga, customer, discount
- **Implementasi**:

```javascript
// Di sales.php — auto-save cart ke localStorage
function saveCartToLocalStorage() {
    const cartData = {
        items: selectedItems,
        customerId: selectedCustomerId,
        discount: globalDiscount,
        timestamp: Date.now()
    };
    localStorage.setItem('panglong_draft_sale', JSON.stringify(cartData));
}

// Panggil setiap kali cart berubah
$(document).on('cartUpdated', saveCartToLocalStorage);

// Saat halaman load — cek draft
function loadDraftSale() {
    const draft = localStorage.getItem('panglong_draft_sale');
    if (draft) {
        const data = JSON.parse(draft);
        // Hanya load jika < 24 jam
        if (Date.now() - data.timestamp < 24 * 60 * 60 * 1000) {
            if (confirm('Ada transaksi draft yang belum disimpan. Lanjutkan?')) {
                selectedItems = data.items;
                selectedCustomerId = data.customerId;
                globalDiscount = data.discount;
                renderCart();
            } else {
                localStorage.removeItem('panglong_draft_sale');
            }
        }
    }
}
```

#### 10.2 Jaringan Internet Putus

**Skenario**: Kasir sudah selesai input semua item, klik "Simpan Penjualan", tapi internet putus saat AJAX request berjalan. Request gagal. Kasir tidak tahu apakah transaksi tersimpan atau tidak.

**Keputusan**: Multi-layer defense:

**Layer 1: Connection Status Indicator**
```javascript
// Tambah indicator di navbar
window.addEventListener('online', () => showToast('Koneksi internet kembali', 'success'));
window.addEventListener('offline', () => showToast('Koneksi internet terputus', 'warning'));

// Cek sebelum submit
function checkConnection() {
    return navigator.onLine;
}
```

**Layer 2: Retry Mechanism**
```javascript
function submitSaleWithRetry(saleData, maxRetries = 3) {
    let attempts = 0;
    function attempt() {
        $.ajax({
            url: API_URL + '?endpoint=sales',
            method: 'POST',
            data: JSON.stringify(saleData),
            contentType: 'application/json',
            timeout: 15000
        })
        .done(function(resp) { handleSaleSuccess(resp); })
        .fail(function(xhr, status) {
            attempts++;
            if (attempts < maxRetries && (status === 'timeout' || xhr.status === 0)) {
                showToast('Koneksi gagal, mencoba lagi... (' + attempts + '/' + maxRetries + ')', 'warning');
                setTimeout(attempt, 3000 * attempts); // exponential backoff
            } else {
                // Simpan ke offline queue
                saveToOfflineQueue('sales', saleData);
                showToast('Transaksi disimpan offline. Akan dikirim saat koneksi kembali.', 'info');
            }
        });
    }
    attempt();
}
```

**Layer 3: Offline Transaction Queue (IndexedDB)**
```javascript
// Buat IndexedDB store untuk offline transactions
function initOfflineDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('PanglongOffline', 1);
        request.onupgradeneeded = (e) => {
            const db = e.target.result;
            if (!db.objectStoreNames.contains('pending_transactions')) {
                db.createObjectStore('pending_transactions', { keyPath: 'id', autoIncrement: true });
            }
        };
        request.onsuccess = (e) => resolve(e.target.result);
        request.onerror = (e) => reject(e.target.error);
    });
}

async function saveToOfflineQueue(endpoint, data) {
    const db = await initOfflineDB();
    const tx = db.transaction('pending_transactions', 'readwrite');
    tx.objectStore('pending_transactions').add({
        endpoint: endpoint,
        data: data,
        timestamp: Date.now(),
        status: 'pending',
        retries: 0
    });
    showOfflineBadge();
}

async function syncOfflineQueue() {
    const db = await initOfflineDB();
    const tx = db.transaction('pending_transactions', 'readonly');
    const all = tx.objectStore('pending_transactions').getAll();
    all.onsuccess = () => {
        all.result.forEach(item => {
            $.ajax({
                url: API_URL + '?endpoint=' + item.endpoint,
                method: 'POST',
                data: JSON.stringify(item.data),
                contentType: 'application/json'
            })
            .done(() => {
                // Hapus dari queue
                const delTx = db.transaction('pending_transactions', 'readwrite');
                delTx.objectStore('pending_transactions').delete(item.id);
            })
            .fail(() => {
                // Tetap di queue, retry nanti
            });
        });
    };
}

// Auto-sync saat online kembali
window.addEventListener('online', syncOfflineQueue);
```

**Layer 4: Idempotency Key**
```javascript
// Generate unique key per transaksi
function generateIdempotencyKey() {
    return Date.now() + '-' + Math.random().toString(36).substr(2, 9) + '-' + currentUser.id;
}

// Kirim bersama sale data
saleData.idempotency_key = generateIdempotencyKey();
```

**Backend (ajax.php)**:
```php
// Cek idempotency key sebelum process
$idempotencyKey = $input['idempotency_key'] ?? null;
if ($idempotencyKey) {
    $stmt = $d->prepare("SELECT id FROM sales WHERE notes LIKE ? OR idempotency_key = ?");
    // Cek apakah sudah pernah diproses
    $stmt->execute(['%' . $idempotencyKey . '%', $idempotencyKey]);
    $existing = $stmt->fetch();
    if ($existing) {
        ok(['id' => $existing['id'], 'message' => 'Transaction already processed']);
        // Return data existing, jangan insert duplikat
    }
}
```

#### 10.3 Baterai Ponsel Habis

**Skenario**: Salesman di lapangan buka salesman_app.php di tablet, baterai 5%, sedang input order customer.

**Keputusan**:
- **Auto-save ke localStorage** setiap 5 detik jika ada perubahan (debounced)
- **Battery API** — deteksi low battery, tampilkan warning "Baterai lemah, transaksi disimpan otomatis"
- **Quick save button** — tombol "Simpan Draft" yang langsung simpan ke localStorage tanpa perlu submit ke server
- **Resume di device lain** — salesman bisa login di device lain, draft tersimpan di localStorage device tersebut (limitation: tidak sync cross-device tanpa server)

```javascript
// Battery API
if ('getBattery' in navigator) {
    navigator.getBattery().then(battery => {
        if (battery.level < 0.15) {
            showToast('Baterai lemah! Transaksi auto-simpan ke draft.', 'warning');
            saveCartToLocalStorage();
        }
        battery.addEventListener('levelchange', () => {
            if (battery.level < 0.15) {
                saveCartToLocalStorage();
            }
        });
    });
}
```

#### 10.4 Server Lokal Mati (XAMPP/Apache Crash)

**Skenario**: Server PC di toko crash, Apache berhenti, database tidak accessible.

**Keputusan**:
- **Database backup otomatis** — cron job backup database.sqlite setiap jam (sudah ada `scripts/backup_database.sh`)
- **Auto-restart script** — systemd service atau cron check untuk restart Apache jika mati
- **Recovery mode** — saat server kembali, sistem cek integritas database:
  ```php
  $d->exec('PRAGMA integrity_check');
  ```
- **WAL mode** — aktifkan Write-Ahead Logging untuk SQLite agar lebih tahan crash:
  ```php
  $d->exec('PRAGMA journal_mode = WAL');
  ```
- **UPS recommendation** — di dokumentasi setup, rekomendasikan UPS untuk server toko

#### 10.5 Mode Kasir Offline (Offline POS)

**Keputusan**: Implementasi offline POS untuk sales.php dengan prioritas P1.

**Arsitektur**:
```
[Online Mode]
  Kasir → sales.php → ajax.php → SQLite (normal flow)

[Offline Mode]  
  Kasir → sales.php → IndexedDB (local queue)
  Saat online → sync queue → ajax.php → SQLite
  Konflik → alert owner untuk resolve manual
```

**Yang disimpan offline**:
- Product catalog (cache di IndexedDB, sync saat online)
- Customer list (cache)
- Pending sales (queue)
- Pending customer create (queue)

**Yang TIDAK bisa offline**:
- Stock validation (stok real-time tidak bisa di-cache — risk oversell)
- Payment processing (QRIS/e-wallet butuh koneksi)
- New product creation
- Reports

**Kompromi stock untuk offline**:
- Saat offline, kasir input sale tanpa stock validation
- Saat sync, sistem cek stok. Jika stok tidak cukup → flag sale sebagai 'stock_conflict'
- Owner resolve: approve (allow negative stock) atau cancel sale

#### 10.6 Ringkasan Antisipasi Gangguan Infrastruktur

| Gangguan | Antisipasi | Priority |
|----------|------------|----------|
| Listrik padam | Auto-save cart ke localStorage, resume saat kembali | P0 |
| Internet putus | Retry mechanism + offline queue (IndexedDB) + idempotency key | P1 |
| Baterai habis | Auto-save + Battery API warning + quick save draft | P1 |
| Server crash | WAL mode + auto-backup + integrity check on restart | P0 |
| Offline POS | IndexedDB queue + sync mechanism + conflict resolution | P1 |

---

## 11. RACE CONDITION & CONCURRENCY CONTROL

### Realita Lapangan

- **Dua kasir jualan produk yang sama bersamaan** — stok tinggal 5, kasir A jual 3, kasir B jual 3. Total 6 > 5. Oversell!
- **PO receiving bersamaan** — dua staff gudang terima PO yang sama, stok masuk dobel.
- **Double submit** — kasir klik "Simpan" dua kali karena internet lambat. Transaksi dobel.
- **Stock adjustment + sale bersamaan** — adjustment sedang dilakukan, sale masuk, stok tidak sinkron.

### Kondisi Saat Ini di Aplikasi — KRITIS

**Tidak ada satupun dari berikut ini:**
- ❌ Database transaction (`beginTransaction`/`commit`/`rollBack`) — 0 occurrence di ajax.php
- ❌ Stock validation sebelum sale — tidak cek `current_stock >= quantity`
- ❌ Row locking — tidak ada `SELECT ... FOR UPDATE` atau `BEGIN EXCLUSIVE`
- ❌ Idempotency key — tidak ada cek duplikat submit
- ❌ Optimistic locking — tidak ada version/timestamp check
- ❌ Mutex/semaphore — tidak ada anti-double-submit

**Dampak nyata**:
1. **Oversell**: Dua kasir jual stok terakhir → stok negatif → customer marah karena barang tidak ada
2. **Duplicate transaction**: Double klik "Simpan" → 2 invoice untuk 1 transaksi → masalah akuntansi
3. **Data inconsistency**: Sale insert berhasil, stock_movement gagal → stok tidak berkurang tapi sale tercatat
4. **PO double-receive**: Dua staff terima PO yang sama → stok masuk 2x → rugi

### Keputusan: Multi-Layer Concurrency Protection

#### 11.1 Layer 1: Database Transaction (Atomic Operations)

**Semua multi-step operasi WAJIB dibungkus transaction.**

```php
// Contoh: Create sale dengan items dan stock movements
if ($endpoint === 'sales' && $method === 'POST') {
    try {
        $d->beginTransaction();
        
        // 1. Insert sale header
        $stmt = $d->prepare("INSERT INTO sales (...) VALUES (...)");
        $stmt->execute([...]);
        $saleId = $d->lastInsertId();
        
        // 2. Insert sale items + stock movements
        foreach ($input['items'] as $item) {
            // Cek stok DULU (dengan lock)
            $stockStmt = $d->prepare("SELECT COALESCE(SUM(quantity),0) as current_stock FROM stock_movements WHERE product_id = ? FOR UPDATE");
            // SQLite tidak support FOR UPDATE, gunakan BEGIN EXCLUSIVE (sudah di-handle oleh transaction)
            $stockStmt->execute([$item['product_id']]);
            $stock = (float)$stockStmt->fetch()['current_stock'];
            
            if ($stock < (float)$item['quantity']) {
                throw new Exception("Stok tidak cukup untuk produk ID {$item['product_id']}. Stok: $stock, Diminta: {$item['quantity']}");
            }
            
            // Insert sale item
            $d->prepare("INSERT INTO sale_items (...) VALUES (...)")->execute([...]);
            
            // Insert stock movement (negative)
            $d->prepare("INSERT INTO stock_movements (product_id, quantity, ...) VALUES (?, ?, ...)")->execute([
                $item['product_id'], -abs((float)$item['quantity']), ...
            ]);
        }
        
        // 3. Insert audit log
        logAudit('create', 'sales', $saleId, null, [...]);
        
        $d->commit();
        created(['id' => $saleId, 'invoice_no' => $invoiceNo]);
        
    } catch (Exception $e) {
        $d->rollBack();
        fail('Gagal menyimpan penjualan: ' . $e->getMessage(), 500);
    }
}
```

**SQLite-specific note**: SQLite menggunakan file-level locking. `beginTransaction()` di SQLite PDO otomatis menggunakan `BEGIN IMMEDIATE` jika configured, atau `BEGIN DEFERRED` (default). Untuk write-heavy concurrent access, set:

```php
// Di db.php — aktifkan WAL mode untuk better concurrency
$db->exec('PRAGMA journal_mode = WAL');
$db->exec('PRAGMA busy_timeout = 5000'); // Wait 5 seconds if locked
```

WAL mode allows concurrent readers + 1 writer. Writer tidak block readers, readers tidak block writer. Ini cukup untuk toko dengan 2-5 kasir.

#### 11.2 Layer 2: Stock Validation Sebelum Sale

**Tambah cek stok di setiap sale create:**

```php
// Di ajax.php — sales POST, sebelum insert
foreach ($input['items'] as $item) {
    if (empty($item['product_id'])) continue;
    
    $stockStmt = $d->prepare("
        SELECT COALESCE(SUM(quantity), 0) as current_stock 
        FROM stock_movements 
        WHERE product_id = ? AND tenant_id = ?
    ");
    $stockStmt->execute([$item['product_id'], $tenantId]);
    $currentStock = (float)$stockStmt->fetch()['current_stock'];
    
    $requestedQty = abs((float)$item['quantity']);
    
    if ($currentStock < $requestedQty) {
        // Cek allow_backorder
        $prodStmt = $d->prepare("SELECT name, code, allow_backorder FROM products WHERE id = ?");
        $prodStmt->execute([$item['product_id']]);
        $prod = $prodStmt->fetch();
        
        if (!$prod || !$prod['allow_backorder']) {
            fail("Stok tidak cukup untuk {$prod['name']} ({$prod['code']}). Stok: $currentStock, Diminta: $requestedQty", 422);
        }
        // Jika allow_backorder = true, lanjut tapi catat sebagai backorder
    }
}
```

#### 11.3 Layer 3: Idempotency Key (Anti-Double-Submit)

**Backend (ajax.php)**:

```php
// Tambah kolom idempotency_key di sales
// ALTER TABLE sales ADD COLUMN idempotency_key VARCHAR(100) UNIQUE;

// Di sales POST handler
$idempotencyKey = $input['idempotency_key'] ?? null;
if ($idempotencyKey) {
    $check = $d->prepare("SELECT id, invoice_no FROM sales WHERE idempotency_key = ?");
    $check->execute([$idempotencyKey]);
    $existing = $check->fetch();
    if ($existing) {
        // Return existing transaction, jangan insert duplikat
        ok(['id' => $existing['id'], 'invoice_no' => $existing['invoice_no'], 'message' => 'Transaction already processed']);
    }
}
```

**Frontend (sales.php)**:

```javascript
// Generate idempotency key saat mulai transaksi
let idempotencyKey = Date.now() + '-' + Math.random().toString(36).substr(2, 9);

// Disable submit button setelah klik (anti double-click)
$('#btnSaveSale').on('click', function() {
    const $btn = $(this);
    if ($btn.prop('disabled')) return; // sudah di-click
    $btn.prop('disabled', true).html('<span class="spinner"></span> Menyimpan...');
    
    const saleData = { ...collectSaleData(), idempotency_key: idempotencyKey };
    
    $.ajax({ ... })
    .done(function(resp) {
        if (resp.success) {
            localStorage.removeItem('panglong_draft_sale');
            window.location.href = 'sale_detail.php?id=' + resp.data.id;
        }
    })
    .fail(function() {
        $btn.prop('disabled', false).html('Simpan Penjualan');
        // Tetap pakai idempotencyKey yang sama untuk retry
    });
});
```

#### 11.4 Layer 4: Optimistic Locking untuk Update

**Untuk update operations (edit product, edit customer, dll):**

```php
// Tambah updated_at check
// Client kirim updated_at dari data yang di-load
$clientUpdatedAt = $input['updated_at'] ?? null;
if ($clientUpdatedAt) {
    $check = $d->prepare("SELECT updated_at FROM products WHERE id = ?");
    $check->execute([$id]);
    $serverUpdatedAt = $check->fetch()['updated_at'];
    if ($serverUpdatedAt !== $clientUpdatedAt) {
        fail('Data telah diubah oleh user lain. Refresh halaman dan coba lagi.', 409);
    }
}
```

#### 11.5 Layer 5: Mutex untuk Critical Sections

**Untuk operasi yang SANGAT critical (PO receiving, stock adjustment approval):**

```php
// Gunakan database-level mutex via app_settings
function acquireLock($lockName, $timeoutSeconds = 10) {
    $d = db();
    $lockKey = 'lock_' . $lockName;
    $now = time();
    
    // Cek apakah lock sudah ada
    $stmt = $d->prepare("SELECT value FROM app_settings WHERE key = ?");
    $stmt->execute([$lockKey]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        $lockTime = (int)$existing['value'];
        if ($now - $lockTime < $timeoutSeconds) {
            // Lock masih valid
            return false;
        }
    }
    
    // Acquire lock
    if ($existing) {
        $d->prepare("UPDATE app_settings SET value = ? WHERE key = ?")->execute([$now, $lockKey]);
    } else {
        $d->prepare("INSERT INTO app_settings (key, value, type, description) VALUES (?, ?, 'int', 'Mutex lock')")->execute([$lockKey, $now]);
    }
    return true;
}

function releaseLock($lockName) {
    $d = db();
    $lockKey = 'lock_' . $lockName;
    $d->prepare("DELETE FROM app_settings WHERE key = ?")->execute([$lockKey]);
}

// Penggunaan di PO receiving:
if (!acquireLock('po_receive_' . $id, 30)) {
    fail('PO sedang diproses oleh user lain. Tunggu sebentar.', 409);
}
try {
    // ... process PO receiving ...
    $d->commit();
} finally {
    releaseLock('po_receive_' . $id);
}
```

#### 11.6 Skenario Khusus: Dua Kasir Jual Produk Sama dengan Stok Sedikit

**Skenario**: Stok beras tinggal 5 karung. Kasir A dan Kasir B sama-sama jual 3 karung bersamaan.

**Dengan transaction + stock validation:**

```
Timeline:
T1: Kasir A → beginTransaction() → cek stok (5) → OK, 3 ≤ 5 → insert sale → insert stock_movement (-3) → commit()
T2: Kasir B → beginTransaction() → WAIT (SQLite WAL: writer lain sedang write) → 
    setelah T1 commit → cek stok (5-3=2) → 3 > 2 → FAIL "Stok tidak cukup. Sisa: 2 karung"
```

**Hasil**: Kasir A berhasil (stok 5→2). Kasir B gagal dengan pesan jelas. Tidak ada oversell.

**Tanpa transaction (kondisi saat ini):**

```
T1: Kasir A → cek stok (tidak dicek!) → insert sale → insert stock_movement (-3)
T2: Kasir B → cek stok (tidak dicek!) → insert sale → insert stock_movement (-3)
Hasil: Stok = 5 - 3 - 3 = -1. OVERSELL! Dua customer bayar tapi hanya 1 yang dapat barang.
```

#### 11.7 Skenario: Double-Click Submit

**Skenario**: Kasir klik "Simpan" 2x cepat karena mouse error atau internet lambat.

**Dengan idempotency key + button disable:**

```
Click 1: Generate key "abc123" → disable button → POST dengan key "abc123"
Click 2: Button disabled → tidak terjadi apa-apa

Jika click 1 gagal (network), click retry:
Click retry: POST dengan key "abc123" (sama) → backend cek key "abc123" sudah ada? 
  - Jika click 1 ternyata sukses di server → return existing → tidak duplikat
  - Jika click 1 benar-benar gagal → process normal → insert baru
```

#### 11.8 Skenario: PO Double-Receive

**Skenario**: Staff A dan Staff B sama-sama terima PO #123. Stok masuk 2x.

**Dengan mutex + transaction:**

```
Staff A: acquireLock('po_receive_123') → beginTransaction() → update received_quantity → insert stock_movement → commit() → releaseLock()
Staff B: acquireLock('po_receive_123') → FAIL "PO sedang diproses" → tidak jadi receive
```

**Tambahan**: Cek `received_quantity >= quantity` sebelum add received:

```php
if ((float)$item['received_quantity'] >= (float)$item['quantity']) {
    throw new Exception("Item sudah diterima lengkap. Tidak bisa receive lagi.");
}
```

#### 11.9 Ringkasan Concurrency Protection

| Layer | Proteksi | Implementasi | Priority |
|-------|----------|--------------|----------|
| Transaction | Atomic multi-step ops | `beginTransaction()` + `commit()` + `rollBack()` | P0 |
| Stock validation | Cek stok sebelum sale | `SELECT SUM(quantity) WHERE product_id` + compare | P0 |
| Idempotency key | Anti-double-submit | `idempotency_key` column + frontend generate | P0 |
| Button disable | Anti-double-click | `$btn.prop('disabled', true)` | P0 |
| Optimistic locking | Anti concurrent edit | `updated_at` compare | P1 |
| Mutex | Critical section lock | `app_settings` lock + timeout | P1 |
| WAL mode | Better SQLite concurrency | `PRAGMA journal_mode = WAL` | P0 |
| Busy timeout | Wait if locked | `PRAGMA busy_timeout = 5000` | P0 |

---

## 12. MASTER PRODUCT DATABASE (KATALOG PRODUK PUSAT)

### Konsep

Data produk adalah data utama milik aplikasi (bukan milik tenant). Aplikasi menyediakan **katalog produk pusat** (master product database) yang bisa dijadikan acuan oleh semua tenant. Tenant bisa:

1. **Browse** katalog pusat → pilih produk → import ke daftar produk tenant
2. **Tambah produk baru** → jika belum ada di katalog pusat, bisa kontribusi ke katalog pusat
3. **Update** katalog pusat → jika menemukan data salah/kurang, bisa usulkan koreksi
4. **Sync** → jika katalog pusat di-update oleh admin atau kontribusi tenant lain, tenant yang sudah import dapat notifikasi update

### Analogi

Seperti Google Play Store atau App Store — ada katalog aplikasi pusat. Developer (tenant) upload app (produk). User (tenant lain) bisa cari dan install (import). Data pusat dikelola oleh platform (Super Admin).

### Kondisi Saat Ini

- `products` table punya `tenant_id` — semua produk adalah milik tenant, tidak ada katalog pusat
- `categories`, `product_units`, `barcodes` juga tenant-specific
- Tidak ada konsep "master product" atau "product template"
- Tenant harus input produk dari nol — tidak ada acuan
- Tidak ada sharing data antar tenant

### Yang Sudah Ada (Bisa Dimanfaatkan)

- `categories` table — bisa dijadikan kategori pusat (master categories)
- `barcodes` table — barcode bisa dijadikan acuan identifikasi produk
- `product_units` — satuan standar (pcs, dus, kg, liter, karung, dll)
- Super Admin role — bisa mengelola katalog pusat

### Keputusan: Arsitektur Master Product Catalog

#### 12.1 Struktur Database

```sql
-- ===============================
-- MASTER PRODUCT CATALOG (app-level, tenant_id = NULL)
-- ===============================

-- Master products (katalog produk pusat)
CREATE TABLE master_products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(100) UNIQUE NOT NULL,        -- SKU standar (misal: EAN-13, ISBN)
    name VARCHAR(255) NOT NULL,
    alias TEXT,                                -- nama alternatif / nama lokal
    description TEXT,
    category_id INTEGER,                       -- link ke master_categories
    brand VARCHAR(100),
    image_url VARCHAR(500),
    -- Data standar (acuan, bukan harga tenant)
    default_unit VARCHAR(50),                  -- satuan dasar: pcs, kg, liter, dll
    barcode VARCHAR(100),                      -- barcode utama (EAN/UPC)
    weight_kg DECIMAL(10,3) DEFAULT 0,
    length_cm DECIMAL(10,2) DEFAULT 0,
    width_cm DECIMAL(10,2) DEFAULT 0,
    height_cm DECIMAL(10,2) DEFAULT 0,
    -- Metadata
    status ENUM('active', 'inactive', 'pending_review') DEFAULT 'active',
    source ENUM('admin', 'tenant_contribution', 'marketplace_import') DEFAULT 'admin',
    contributed_by_tenant_id INTEGER,          -- tenant yang kontribusi
    verified BOOLEAN DEFAULT 0,                -- diverifikasi oleh super_admin
    verification_date DATETIME,
    usage_count INTEGER DEFAULT 0,             -- berapa tenant pakai produk ini
    -- Timestamps
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (category_id) REFERENCES master_categories(id)
);

-- Master categories (kategori pusat)
CREATE TABLE master_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    parent_id INTEGER,
    level INTEGER DEFAULT 0,
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (parent_id) REFERENCES master_categories(id)
);

-- Master product units (satuan standar)
CREATE TABLE master_product_units (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    master_product_id INTEGER NOT NULL,
    unit_name VARCHAR(50) NOT NULL,            -- pcs, dus, lusin, kg, karung, dll
    conversion_factor DECIMAL(10,3) NOT NULL,  -- 1 dus = 12 pcs
    is_base_unit BOOLEAN DEFAULT 0,
    barcode VARCHAR(100),                      -- barcode per satuan (beda dus vs pcs)
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (master_product_id) REFERENCES master_products(id) ON DELETE CASCADE
);

-- Master product barcodes (bisa multiple barcode per produk)
CREATE TABLE master_product_barcodes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    master_product_id INTEGER NOT NULL,
    barcode VARCHAR(100) NOT NULL,
    barcode_type ENUM('ean13', 'ean8', 'upc', 'qr', 'internal') DEFAULT 'internal',
    is_primary BOOLEAN DEFAULT 0,
    created_at DATETIME,
    FOREIGN KEY (master_product_id) REFERENCES master_products(id) ON DELETE CASCADE
);

-- ===============================
-- TENANT PRODUCT MAPPING
-- ===============================

-- Link antara produk tenant dengan master product
-- Jika tenant import dari master, row ini dibuat
-- Jika tenant buat produk sendiri, master_product_id = NULL
ALTER TABLE products ADD COLUMN master_product_id INTEGER;
ALTER TABLE products ADD COLUMN master_sync_status VARCHAR(20) DEFAULT 'none';
-- Values: 'none' (produk lokal), 'synced' (tersinkron dengan master), 
--         'outdated' (master ada update), 'conflict' (ada perubahan kedua sisi)

-- ===============================
-- CONTRIBUTION SYSTEM
-- ===============================

-- Usulan kontribusi tenant ke master catalog
CREATE TABLE master_product_contributions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER NOT NULL,
    type ENUM('new_product', 'update_product', 'new_category', 'update_category', 'report_error'),
    master_product_id INTEGER,                 -- NULL jika new_product
    proposed_data TEXT NOT NULL,               -- JSON: data yang diusulkan
    current_data TEXT,                         -- JSON: data saat ini (untuk update)
    reason TEXT,                               -- alasan usulan
    status ENUM('pending', 'approved', 'rejected', 'merged') DEFAULT 'pending',
    reviewed_by INTEGER,                       -- super_admin yang review
    reviewed_at DATETIME,
    review_notes TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (master_product_id) REFERENCES master_products(id)
);
```

#### 12.2 Alur Import Produk dari Master Catalog

```
1. Tenant buka halaman "Katalog Produk Pusat" (master_catalog.php)
2. Search/filter: by category, brand, barcode, name
3. Pilih produk → klik "Import ke Toko Saya"
4. Sistem buat row di products table:
   - master_product_id = master_product.id
   - code = master_product.code (bisa diubah tenant)
   - name = master_product.name (bisa diubah tenant)
   - alias, category_id (map ke tenant categories), brand = dari master
   - buy_price, sell_price, min_stock, max_stock = KOSONG (tenant isi sendiri)
   - location = KOSONG (tenant isi sendiri)
   - tenant_id = tenant yang import
   - master_sync_status = 'synced'
5. Sistem buat product_units dari master_product_units
6. Sistem buat barcodes dari master_product_barcodes
7. Increment master_products.usage_count
8. Tenant lanjut set: harga beli, harga jual, min/max stock, lokasi
```

**Pemisahan data master vs data tenant:**

| Field | Sumber | Bisa Tenant Edit? |
|-------|--------|-------------------|
| code | master | Ya (override) |
| name | master | Ya (override) |
| alias | master | Ya |
| description | master | Ya |
| category | master → map ke tenant category | Ya (pilih kategori tenant) |
| brand | master | Ya (override) |
| barcode | master | Ya (tambah barcode sendiri) |
| default_unit | master | Tidak (acuan) |
| weight/dimension | master | Ya (override) |
| buy_price | tenant | Ya (isi sendiri) |
| sell_price | tenant | Ya (isi sendiri) |
| min_stock | tenant | Ya (isi sendiri) |
| max_stock | tenant | Ya (isi sendiri) |
| location | tenant | Ya (isi sendiri) |
| is_active | tenant | Ya |

#### 12.3 Alur Kontribusi Produk Baru ke Master Catalog

```
1. Tenant tambah produk baru di products.php
2. Saat simpan, sistem cek: apakah produk dengan code/barcode yang sama sudah ada di master_products?
   a. Jika SUDAH ADA → tampilkan: "Produk ini sudah ada di Katalog Pusat. Import dari katalog?"
      - Jika ya → link ke master (set master_product_id)
      - Jika tidak → tetap buat produk lokal (master_product_id = NULL)
   b. Jika BELUM ADA → tampilkan: "Produk ini belum ada di Katalog Pusat. Kontribusikan ke katalog?"
      - Jika ya → buat contribution (type='new_product', status='pending')
      - Jika tidak → tetap buat produk lokal (master_product_id = NULL)
3. Super Admin review contribution:
   a. Approve → insert ke master_products, set contribution.status='merged'
   b. Reject → set contribution.status='rejected' dengan review_notes
   c. Edit → Super Admin edit data sebelum approve
4. Setelah approve, produk tersedia di katalog pusat untuk tenant lain
```

#### 12.4 Alur Update/Sync Master Product

```
1. Super Admin update master_products (misal: koreksi nama, tambah barcode)
2. Sistem cek semua tenant yang import produk ini:
   SELECT * FROM products WHERE master_product_id = ? AND master_sync_status = 'synced'
3. Set master_sync_status = 'outdated' untuk semua produk tenant tersebut
4. Tenant lihat dashboard → ada badge "3 produk ada update dari Katalog Pusat"
5. Tenant buka produk → lihat perbandingan:
   - Data Master: "Beras Pandan Wangi 5kg" (updated)
   - Data Tenant: "Beras Pandan 5kg" (current)
6. Tenant pilih:
   a. "Update dari Master" → copy data master ke produk tenant, set status='synced'
   b. "Pertahankan Data Saya" → keep tenant data, set status='synced' (acknowledge tapi tidak apply)
   c. "Lihat Detail" → bandingkan field by field
```

#### 12.5 Alur Kontribusi Koreksi/Update

```
1. Tenant lihat produk dari master → temukan data salah (misal: barcode salah)
2. Klik "Laporkan/Koreksi"
3. Isi form: field yang salah, nilai benar, alasan
4. Buat contribution (type='update_product' atau 'report_error')
5. Super Admin review → approve/reject
6. Jika approve → update master_products → trigger sync ke semua tenant
```

#### 12.6 Seeding Master Catalog

**Sumber data awal untuk master catalog:**

1. **Barcode database** — import dari database barcode publik (Open Food Facts, GS1 Indonesia, dll)
2. **Produk umum Indonesia** — daftar produk yang umum dijual di toko:
   - Sembako: beras, gula, minyak goreng, tepung, garam
   - FMCG: sabun, shampoo, pasta gigi, deterjen
   - Minuman: teh, kopi, susu, air mineral
   - Makanan: mie instan, biskuit, snack, susu
   - Bangunan: semen, cat, paku, bor
   - Obat: paracetamol, vitamin, dll
3. **Dari tenant existing** — produk yang sudah diinput tenant bisa di-promote ke master:
   ```sql
   -- Pilih produk yang paling banyak dipakai tenant
   SELECT code, name, brand, COUNT(DISTINCT tenant_id) as tenant_count
   FROM products WHERE tenant_id IS NOT NULL
   GROUP BY code, name, brand
   HAVING tenant_count > 1
   ORDER BY tenant_count DESC
   ```
4. **Marketplace import** — import katalog dari Tokopedia/Shopee API (produk standar)

#### 12.7 UI/UX Design

**Halaman baru: `master_catalog.php`** (hanya untuk browse & import)

```
┌─────────────────────────────────────────────────────────┐
│ Katalog Produk Pusat                                    │
├─────────────────────────────────────────────────────────┤
│ [Search: ___________] [Category: ▼] [Brand: ▼]         │
│ [Barcode: _________]                                    │
├─────────────────────────────────────────────────────────┤
│ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐       │
│ │ Beras    │ │ Gula    │ │ Minyak  │ │ Tepung  │       │
│ │ 5kg      │ │ 1kg     │ │ 2L      │ │ 1kg     │       │
│ │ [Import] │ │ [Import]│ │Sudah Ada│ │ [Import] │       │
│ └─────────┘ └─────────┘ └─────────┘ └─────────┘       │
├─────────────────────────────────────────────────────────┤
│ Total: 1,247 produk | Anda pakai: 85 produk            │
└─────────────────────────────────────────────────────────┘
```

**Di halaman `products.php`** — tambah indikator:

```
┌─────────────────────────────────────────────────────────┐
│ Produk Saya                              [+ Tambah Produk]│
├─────────────────────────────────────────────────────────┤
│ Code    | Name           | Master?  | Sync Status       │
│ BR001   | Beras 5kg      | ✓ Linked | Synced            │
│ G001    | Gula 1kg       | ✓ Linked | ⚠ Update Available│
│ SN001   | Sabun Mandi    | — Local  | —                 │
│ KM001   | Kopi Kapal Api | ✓ Linked | Synced            │
└─────────────────────────────────────────────────────────┘
```

**Di halaman `products.php` — saat tambah produk:**

```
Step 1: Cari di Katalog Pusat
  [Search: _________] [Barcode scan: 📷]
  → Hasil: "Beras Pandan Wangi 5kg" ditemukan
  → [Import dari Katalog] atau [Buat Produk Baru]

Step 2: (Jika buat baru) Input produk
  Code: ______ Name: ______ Brand: ______
  ...
  
Step 3: Kontribusi?
  ☑ Kontribusikan produk ini ke Katalog Pusat
    (Super Admin akan review sebelum dipublikasikan)
```

**Super Admin panel: `master_catalog_admin.php`**

```
┌─────────────────────────────────────────────────────────┐
│ Kelola Katalog Pusat                          [Super Admin]│
├─────────────────────────────────────────────────────────┤
│ Tab: [Produk Master] [Kontribusi (12)] [Kategori]       │
├─────────────────────────────────────────────────────────┤
│ Kontribusi Pending:                                     │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ Tenant: Toko Maju Jaya                              │ │
│ │ Type: Produk Baru                                   │ │
│ │ Code: BR-001 | Name: Beras Pandan Wangi 5kg        │ │
│ │ Brand: Cap Ayam | Barcode: 8991234567890           │ │
│ │ [Approve] [Reject] [Edit & Approve]                 │ │
│ └─────────────────────────────────────────────────────┘ │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ Tenant: Toko Berkah                                 │ │
│ │ Type: Koreksi Produk                                │ │
│ │ Master: Gula Pasir 1kg (ID: 45)                     │ │
│ │ Field: Barcode | Current: 8990000 → Proposed: 8991  │ │
│ │ Reason: Barcode salah, yang benar 8991234           │ │
│ │ [Approve] [Reject]                                  │ │
│ └─────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

#### 12.8 Aturan Sinkronisasi

| Skenario | Behavior |
|----------|----------|
| Master update nama produk | Tenant dapat notifikasi, bisa update atau pertahankan |
| Tenant edit nama produk (override) | Tidak push ke master, tetap local override |
| Tenant edit harga beli/jual | Tidak sync ke master (data tenant, bukan master) |
| Master update barcode | Tenant dapat notifikasi, barcode baru ditambah (bukan replace) |
| Tenant hapus produk | Tidak hapus dari master, hanya hapus dari tenant |
| Master dihapus oleh Super Admin | Produk tenant tetap, master_product_id set NULL |
| Tenant kontribusi baru di-approve | Produk tenant otomatis link ke master yang baru dibuat |

#### 12.9 Keputusan: Data Apa yang Master vs Tenant

**Master Product (data pusat, shared):**
- code (SKU standar)
- name (nama standar)
- alias (nama alternatif/lokal)
- description
- category (master category)
- brand
- barcode(s)
- default_unit
- weight & dimensions
- image_url

**Tenant Product (data lokal, private):**
- buy_price (harga beli tenant)
- sell_price (harga jual tenant)
- min_stock / max_stock
- location (lokasi di gudang/toko)
- warehouse_id
- is_active (tenant bisa nonaktifkan tanpa hapus master)
- stock movements
- batches
- landed_cost
- custom units (tenant bisa tambah satuan sendiri)

#### 12.10 Manfaat

1. **Onboarding cepat**: Tenant baru tidak perlu input 500 produk dari nol — cukup import dari katalog pusat
2. **Konsistensi data**: Nama, barcode, satuan standar across tenants
3. **Kolaborasi**: Tenant berkontribusi, katalog pusat tumbuh organik
4. **Quality control**: Super Admin verifikasi sebelum publikasi
5. **Analytics**: Aplikasi tahu produk apa yang paling populer across tenants (untuk marketplace integration, demand forecasting)
6. **Marketplace sync**: Jika produk master ter-link ke Tokopedia/Shopee, semua tenant yang pakai produk itu bisa auto-sync stok

#### 12.11 Implementasi Bertahap

**Phase 1 (P1)**: Master catalog table + import flow
- Buat `master_products`, `master_categories`, `master_product_units`, `master_product_barcodes`
- Seed dengan 100-500 produk umum Indonesia
- UI: `master_catalog.php` untuk browse & import
- Tambah `master_product_id` di `products` table
- Di `products.php` tambah: search master saat tambah produk

**Phase 2 (P2)**: Contribution system
- Buat `master_product_contributions` table
- UI: kontribusi produk baru dari tenant
- Super Admin panel: review & approve contributions
- Auto-link produk tenant ke master setelah approve

**Phase 3 (P2)**: Sync & update notifications
- Sync status tracking (synced/outdated/conflict)
- Notifikasi update master ke tenant
- UI: compare & apply/reject updates
- Batch sync: update multiple products sekaligus

**Phase 4 (P3)**: Advanced
- Auto-suggest: saat tenant input produk, auto-match dengan master (fuzzy search)
- Bulk import: import 100 produk sekaligus dari master
- Category mapping: map tenant categories ke master categories
- Marketplace integration: link master product ke Tokopedia/Shopee product ID

---

## 13. SKENARIO PRODUCTION HOSTING & PRAKTEK NYATA

### Kondisi Saat Ini di Aplikasi

- **Database**: SQLite (file-based, single file `database/database.sqlite`)
- **Hosting**: Dirancang untuk XAMPP local — belum diuji di shared hosting/VPS
- **CDN**: Bootstrap, jQuery, Chart.js dari `cdn.jsdelivr.net` (external dependency)
- **Session**: PHP default file-based session, 30 menit timeout
- **CSRF**: Ada `generateCsrfToken()` + `verifyCsrfToken()` di auth.php
- **Rate limiting**: 30 POST/minute per user di ajax.php, login attempt tracking di auth.php
- **Password**: `password_hash()` + `password_verify()` (bcrypt) — sudah aman
- **Error handling**: `error_log()` di audit log catch, tidak ada `display_errors=0` setting
- **Timezone**: Tidak set `date_default_timezone_set()` — pakai server default
- **XSS protection**: `htmlspecialchars()` dipakai di sebagian besar output (265 matches)
- **File upload**: Tidak ada (belum implementasi upload gambar produk)
- **Multi-currency**: Tidak ada (semua Rupiah)
- **Backup**: `scripts/backup_database.sh` ada (cron-based SQLite backup)

---

### 13.1 SQLite di Production Hosting — Risiko dan Solusi

#### Masalah: SQLite Concurrent Write Lock

**Skenario**: 10 tenant aktif bersamaan, masing-masing ada 3-5 kasir. Total 30-50 concurrent users. Saat ramai (jam sibuk), semua kasir submit sale bersamaan.

**Realita**: SQLite WAL mode mengizinkan 1 writer pada satu waktu. Writer lain menunggu (busy_timeout). Jika antrian panjang, request timeout → kasir lihat error.

**Dampak**:
- 30-50 concurrent writers → lock contention → response time naik dari 50ms ke 2-5 detik
- Jika `busy_timeout` tercapai → `database is locked` error → sale gagal
- User experience buruk saat jam sibuk

**Keputusan**:
1. **P0**: Aktifkan WAL mode + `busy_timeout = 5000` (sudah di Section 11)
2. **P1**: Optimasi query — kurari query berat di endpoint yang sering dipanggil
3. **P2**: Migrasi ke MySQL/PostgreSQL saat tenant > 50 atau concurrent users > 100
4. **P2**: Tambah connection pooling atau read replica untuk report queries

```php
// Threshold migrasi ke MySQL:
// - > 50 tenant aktif
// - > 100 concurrent users
// - Database file > 500MB
// - Lock errors > 1% dari total requests
```

#### Masalah: SQLite File Corruption

**Skenario**: Server hard restart saat write operation berjalan. File database corrupt.

**Keputusan**:
- WAL mode lebih tahan crash (write ke WAL file, bukan main DB langsung)
- Backup otomatis setiap jam (cron `scripts/backup_database.sh`)
- Tambah integrity check saat startup:
  ```php
  // Di db.php — cek integrity saat koneksi pertama
  $result = $db->query('PRAGMA integrity_check')->fetch();
  if ($result['integrity_check'] !== 'ok') {
      // Kirim alert ke admin, switch ke backup terakhir
      error_log('DATABASE CORRUPTION DETECTED: ' . $result['integrity_check']);
      // Restore dari backup terbaru
      $backupFile = glob(__DIR__ . '/../backups/daily/*.sqlite.gz')[0] ?? null;
      if ($backupFile) {
          // Auto-restore logic
      }
  }
  ```

#### Masalah: Database File Size Growth

**Skenario**: Setelah 1 tahun, 50 tenant, masing-masing 1000 transaksi/bulan = 600,000 sales + 3,000,000 sale_items + 3,000,000 stock_movements. Database file bisa mencapai 500MB-1GB.

**Dampak**: Query lambat, backup lama, hosting disk quota tercapai.

**Keputusan**:
- **Data archiving**: Pindahkan transaksi > 2 tahun ke archive table (`archived_sales`, `archived_sale_items`)
- **Vacuum**: Jalankan `VACUUM` secara berkala untuk reclaim space
- **Index optimization**: Tambah index pada kolom yang sering di-query (sudah ada sebagian)
- **Monitoring**: Tambah endpoint `/health` yang report database size, table counts, slow queries

#### Masalah: Shared Hosting Limitations

**Skenario**: Tenant deploy ke shared hosting (cPanel, Niagahoster, Hostinger) dengan batasan:
- `max_execution_time = 30` — request > 30 detik timeout
- `memory_limit = 256MB` — import data besar OOM
- `upload_max_filesize = 2MB` — tidak bisa upload backup besar
- `max_user_connections = 10` — batas koneksi database
- `disk_quota` — disk space terbatas
- Tidak ada SSH — tidak bisa run cron/command line
- `open_basedir` restriction — tidak bisa akses file di luar direktori

**Keputusan**:
- **Dokumentasi**: Buat `HOSTING_GUIDE.md` dengan requirement minimum dan rekomendasi hosting yang compatible
- **Graceful degradation**: Tangani `max_execution_time` dengan chunk processing:
  ```php
  // Untuk import data besar — proses per batch
  $batchSize = 100;
  $offset = $_GET['offset'] ?? 0;
  // Proses 100 record, lalu redirect ke offset+100
  // Daripada proses 10,000 record sekaligus yang timeout
  ```
- **Memory-efficient queries**: Gunakan cursor/iterator вместо fetchAll() untuk dataset besar
- **Rekomendasi VPS**: Untuk > 10 tenant, rekomendasi VPS (DigitalOcean, Vultr) daripada shared hosting

---

### 13.2 Keamanan di Production

#### Masalah: Session Hijacking & Fixation

**Skenario**: Attacker curi session cookie user (via XSS atau network sniffing di WiFi publik), login sebagai user tersebut.

**Kondisi saat ini**: Tidak ada `session_regenerate_id()` setelah login. Session ID tetap sama sebelum dan setelah login — memudahkan fixation attack.

**Keputusan**:
```php
// Di auth.php setelah login berhasil
session_regenerate_id(true); // true = hapus session lama
```

#### Masalah: CSRF Token Tidak Rotasi

**Skenario**: CSRF token di-generate sekali per session, tidak pernah di-rotate. Attacker yang dapat token bisa pakai terus.

**Kondisi saat ini**: `generateCsrfToken()` hanya generate jika belum ada. Token sama sepanjang session.

**Keputusan**: Rotasi CSRF token setiap 30 menit atau setelah POST request:
```php
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    // Rotasi token setelah verifikasi (optional, tergantung UX trade-off)
    // $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return true;
}
```

#### Masalah: XSS via Stored Input

**Skenario**: User input nama produk dengan `<script>alert('xss')</script>`. Saat produk ditampilkan, script jalan di browser user lain.

**Kondisi saat ini**: `htmlspecialchars()` dipakai di 265 tempat — sebagian besar output sudah di-escape. Tapi perlu audit apakah ALL output di-escape.

**Keputusan**: Audit semua output — pastikan tidak ada `echo $data['field']` tanpa `htmlspecialchars()`. Tambah Content-Security-Policy header:
```php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net;");
```

#### Masalah: SQL Injection

**Kondisi saat ini**: Sebagian besar query pakai prepared statements (`$d->prepare()` + `execute()`). Tapi ada beberapa query dengan string concatenation:
```php
$sql .= " AND p.tenant_id = $tenantId"; // Ini aman karena $tenantId = (int), tapi tetap bad practice
```

**Keputusan**: Audit semua query — pastikan tidak ada string interpolation untuk user input. Semua via prepared statement parameters.

#### Masalah: Brute Force Login

**Kondisi saat ini**: Rate limiting ada di auth.php (login attempt tracking via session). Tapi session-based tracking reset jika session di-clear.

**Keputusan**: Pindah ke database-based rate limiting:
```sql
CREATE TABLE login_attempts (
    id INTEGER PRIMARY KEY,
    username VARCHAR(100),
    ip_address VARCHAR(45),
    attempted_at DATETIME,
    success BOOLEAN DEFAULT 0
);
```
- Block IP setelah 5 failed attempts dalam 15 menit
- Block username setelah 10 failed attempts dalam 1 jam
- Auto-unblock setelah 15 menit

#### Masalah: File Inclusion / Directory Traversal

**Skenario**: Attacker akses `ajax.php?endpoint=../../../etc/passwd` atau similar.

**Kondisi saat ini**: Endpoint matching pakai `if ($endpoint === 'name')` — tidak ada include file berdasarkan input. Aman dari LFI.

#### Masalah: Information Disclosure

**Skenario**: Error message menampilkan stack trace, query SQL, atau path file di production.

**Kondisi saat ini**: Tidak ada setting `display_errors = 0` untuk production. PDO ERRMODE_EXCEPTION akan tampilkan query di error.

**Keputusan**:
```php
// Di production environment
if (getenv('APP_ENV') === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}
```

---

### 13.3 CDN Dependency Risk

#### Masalah: cdn.jsdelivr.net Down

**Skenario**: CDN jsdelivr down (pernah terjadi 2022). Bootstrap, jQuery, Chart.js tidak load. UI rusak total. Kasir tidak bisa transaksi.

**Kondisi saat ini**: Semua CSS/JS dari CDN. `sw.js` cache CDN assets, tapi jika cache expired dan CDN down, tetap gagal.

**Keputusan**: Self-host critical assets:
```
frontend/assets/css/bootstrap.min.css
frontend/assets/js/jquery.min.js
frontend/assets/js/bootstrap.bundle.min.js
frontend/assets/js/chart.umd.min.js
frontend/assets/icons/bootstrap-icons.css
frontend/assets/icons/fonts/ (bootstrap icons font files)
```
- Update `renderHead()` di config.php untuk load dari local path, bukan CDN
- Fallback: jika local load gagal, fallback ke CDN:
```html
<script src="assets/js/jquery.min.js"></script>
<script>window.jQuery || document.write('<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"><\/script>')</script>
```

---

### 13.4 Timezone & Date/Time Issues

#### Masalah: Server Timezone Berbeda

**Skenario**: Hosting di server US (UTC-5) atau UTC. Transaksi dicatat dengan waktu server, bukan waktu Indonesia (WIB = UTC+7). Laporan harian salah — sale jam 23:30 WIB dicatat sebagai tanggal besok.

**Kondisi saat ini**: Tidak ada `date_default_timezone_set()`. `date('Y-m-d H:i:s')` pakai server timezone. SQLite `datetime('now')` juga pakai server timezone.

**Dampak**:
- Laporan harian tidak akurat
- Shift management (jika diimplementasi) salah hari
- EOD reconciliation salah periode
- Audit trail waktu tidak sesuai WIB

**Keputusan**:
```php
// Di config.php atau db.php — set di awal sebelum kode lain
date_default_timezone_set('Asia/Jakarta');
```
- Atau per-tenant: tambah field `timezone` di `tenants` table (default: 'Asia/Jakarta')
- Untuk tenant di timezone berbeda (Makassar WITA, Jayapura WIT): set per tenant
- Semua `date()` dan `datetime('now')` akan otomatis pakai timezone yang diset

---

### 13.5 Multi-Currency (Import dari Luar Negeri)

#### Masalah: Pembelian Import dengan USD

**Skenario**: Toko beli barang dari supplier luar negeri (China, Thailand). Harga dalam USD. Bayar dengan kurs hari ini. Besok kurs berubah.

**Kondisi saat ini**: Semua harga dalam Rupiah. `buy_price`, `sell_price`, PO total — semua IDR. Tidak ada field currency. Tidak ada exchange rate.

**Dampak**: Landed cost calculation tidak akurat untuk import. Laporan pembelian tidak bisa track selisih kurs.

**Keputusan**:
```sql
ALTER TABLE purchase_orders ADD COLUMN currency VARCHAR(3) DEFAULT 'IDR';
ALTER TABLE purchase_orders ADD COLUMN exchange_rate DECIMAL(15,4) DEFAULT 1;
ALTER TABLE purchase_items ADD COLUMN unit_price_foreign DECIMAL(15,2) DEFAULT 0;
ALTER TABLE suppliers ADD COLUMN default_currency VARCHAR(3) DEFAULT 'IDR';

CREATE TABLE exchange_rates (
    id INTEGER PRIMARY KEY,
    currency VARCHAR(3) NOT NULL,        -- USD, CNY, THB, SGD
    rate_to_idr DECIMAL(15,4) NOT NULL,
    rate_date DATE NOT NULL,
    source VARCHAR(50),                   -- 'manual', 'bca', 'bi'
    created_at DATETIME,
    UNIQUE(currency, rate_date)
);
```

**Alur**:
1. Buat PO dengan currency=USD, unit_price_foreign=$10, exchange_rate=15,800
2. Sistem hitung: unit_price_idr = $10 × 15,800 = Rp 158,000
3. Landed cost dihitung dari unit_price_idr + biaya import (shipping, bea masuk, PPN import)
4. Saat bayar (purchase_payment), jika kurs berubah: catat actual rate, selisih kurs = gain/loss

---

### 13.6 Data Migration dari Sistem Lama

#### Masalah: Import Data dari POS/ERP Lain

**Skenario**: Toko sudah pakai POS lain (Moka, Pawoon, Excel manual). Mau pindah ke Panglong ERP. Perlu import: produk, customer, supplier, stok awal, historis penjualan.

**Kondisi saat ini**: Tidak ada import/export feature. Tidak ada CSV/Excel parser. Tenant harus input manual satu per satu.

**Dampak**: Onboarding lambat. Toko dengan 500 produk butuh berhari-hari input manual. Batal pindah ke Panglong.

**Keputusan**: Buat import/export module:

```sql
CREATE TABLE import_jobs (
    id INTEGER PRIMARY KEY,
    tenant_id INTEGER,
    type ENUM('products', 'customers', 'suppliers', 'sales', 'stock_initial'),
    file_name VARCHAR(255),
    file_path VARCHAR(500),
    total_rows INT DEFAULT 0,
    processed_rows INT DEFAULT 0,
    success_rows INT DEFAULT 0,
    failed_rows INT DEFAULT 0,
    error_log TEXT,                    -- JSON array of errors
    status ENUM('pending', 'processing', 'completed', 'failed', 'partial') DEFAULT 'pending',
    created_by INTEGER,
    created_at DATETIME,
    updated_at DATETIME
);
```

**Format**: CSV dengan template downloadable:
- `products_import_template.csv`: code, name, brand, category, buy_price, sell_price, min_stock, barcode, unit
- `customers_import_template.csv`: name, phone, email, address, group, credit_limit
- `suppliers_import_template.csv`: name, phone, email, address, contact_person

**Validasi**:
- Dry-run mode: preview 10 baris pertama sebelum commit
- Error reporting: baris ke berapa yang error, kenapa error
- Rollback: jika import gagal di tengah, rollback semua data yang sudah di-insert

**Prioritas**: P1 — critical untuk onboarding tenant baru

---

### 13.7 Backup & Restore Strategy

#### Masalah: Backup Tidak Teruji

**Skenario**: Backup berjalan setiap jam via cron. Tapi tidak pernah diuji restore. Saat database corrupt dan perlu restore, ternyata backup juga corrupt atau tidak lengkap.

**Kondisi saat ini**: `scripts/backup_database.sh` ada. Backup disimpan di `backups/daily/`. Tidak ada verifikasi backup, tidak ada test restore, tidak ada retention policy.

**Keputusan**:
1. **Backup verification**: Setelah backup, jalankan `PRAGMA integrity_check` pada backup file
2. **Retention policy**: Simpan backup:
   - Setiap jam: simpan 24 jam terakhir
   - Harian: simpan 30 hari terakhir
   - Mingguan: simpan 12 minggu terakhir
   - Bulanan: simpan 12 bulan terakhir
3. **Off-site backup**: Upload backup ke cloud storage (S3, Google Drive, Dropbox)
4. **Test restore**: Cron job mingguan yang restore backup ke test database dan jalankan smoke test
5. **One-click restore UI**: Di settings.php, tombol "Restore dari Backup" dengan dropdown pilih tanggal

```php
// Endpoint: ?endpoint=backup-status
// Return: last_backup_at, backup_size, integrity_check, backup_count, oldest_backup, newest_backup
```

---

### 13.8 Performance & Scaling

#### Masalah: Query N+1 di ajax.php

**Skenario**: Saat load 200 produk di stock endpoint, setiap produk run subquery `SELECT SUM(quantity) FROM stock_movements WHERE product_id=?`. 200 produk = 200 subquery. Lambat.

**Kondisi saat ini**: Stock endpoint query:
```sql
SELECT p.id, p.name, ...,
  COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) as current_stock
FROM products p ...
```
Ini adalah correlated subquery — untuk setiap row produk, run subquery terpisah. Dengan 1000 produk dan 100,000 stock_movements, ini bisa lambat.

**Keputusan**: Optimasi dengan JOIN + GROUP BY:
```sql
SELECT p.id, p.name, ...,
  COALESCE(sm.total_qty, 0) as current_stock
FROM products p
LEFT JOIN (
  SELECT product_id, SUM(quantity) as total_qty
  FROM stock_movements
  GROUP BY product_id
) sm ON sm.product_id = p.id
WHERE p.is_active = 1
ORDER BY p.id DESC LIMIT 200
```

#### Masalah: Pagination Tidak Ada di Beberapa Endpoint

**Skenario**: Tenant punya 10,000 produk. Saat buka halaman products, load semua 10,000 sekaligus. Browser hang.

**Kondisi saat ini**: Products endpoint sudah ada pagination (`LIMIT $per_page OFFSET $offset`). Tapi beberapa endpoint lain mungkin tidak ada pagination (sales, reports, dll).

**Keputusan**: Audit semua GET list endpoint — pastikan semua punya pagination. Default page size: 50, max: 200.

#### Masalah: Search Performance

**Skenario**: User search produk dengan keyword "beras". Query `LIKE '%beras%'` scan full table. Dengan 100,000 produk, lambat.

**Keputusan**:
- Tambah FULLTEXT index di SQLite:
  ```sql
  CREATE VIRTUAL TABLE products_fts USING fts5(name, code, brand, content='products');
  ```
- Atau gunakan `LIKE 'beras%'` (prefix search, pakai index) daripada `LIKE '%beras%'` (full scan)
- Untuk search advanced: Elasticsearch atau Meilisearch (P3)

---

### 13.9 Session Management Issues

#### Masalah: Session Timeout Saat Transaksi

**Skenario**: Kasir input 20 item ke keranjang (butuh 10 menit). Di menit ke-31, session timeout. Saat klik "Simpan", redirect ke login. Semua input hilang.

**Kondisi saat ini**: Session timeout 30 menit. Auto-save cart ke localStorage (Section 10) akan handle ini, tapi belum diimplementasi.

**Keputusan**:
- **Session activity tracking**: Reset timeout setiap AJAX request, bukan hanya page load
- **Session warning**: Tampilkan modal "Session akan expired dalam 2 menit. Klik untuk perpanjang." (sudah ada di config.php:404)
- **Auto-save**: Implementasi P0 auto-save cart ke localStorage (Section 10.1)
- **Session perpanjang otomatis**: Setiap GET request (page load) reset timeout. Tambah AJAX heartbeat setiap 5 menit untuk keep alive:
  ```javascript
  setInterval(function() {
      $.get('ajax.php?endpoint=heartbeat&test_mode=true');
  }, 300000); // 5 minutes
  ```

#### Masalah: Concurrent Session Same User

**Skenario**: Kasir login di PC kasir, lalu login juga di HP. Session di PC bisa conflict dengan session di HP.

**Kondisi saat ini**: Tidak ada batas session per user. PHP default mengizinkan multiple session.

**Keputusan**:
- Untuk kasir: batas 1 active session (login baru kick session lama)
- Untuk owner/manager: izinkan multiple session (bisa akses dari PC + HP)
- Implementasi: tracking session_id per user di database:
  ```sql
  ALTER TABLE users ADD COLUMN active_session_id VARCHAR(255);
  ```
  Saat login, simpan session_id. Saat request, cek apakah session_id match. Jika tidak, kick.

---

### 13.10 Error Handling & Monitoring

#### Masalah: Silent Errors

**Skenario**: AJAX request gagal, tapi error tidak terlihat. User klik "Simpan", tidak terjadi apa-apa. Tidak ada error message, tidak ada loading indicator. User bingung.

**Kondisi saat ini**: Beberapa endpoint mungkin gagal tanpa response yang jelas. Tidak ada global error handler di frontend.

**Keputusan**:
- **Global AJAX error handler**:
  ```javascript
  $(document).ajaxError(function(event, xhr, settings, error) {
      if (xhr.status === 401) {
          showToast('Session habis. Silakan login kembali.', 'danger');
          setTimeout(() => window.location.href = 'login.php', 2000);
      } else if (xhr.status === 403) {
          showToast('Akses ditolak: ' + (xhr.responseJSON?.message || 'Tidak punya izin'), 'danger');
      } else if (xhr.status === 429) {
          showToast('Terlalu banyak request. Tunggu sebentar.', 'warning');
      } else if (xhr.status >= 500) {
          showToast('Server error. Coba lagi atau hubungi admin.', 'danger');
          console.error('Server error:', xhr.responseText);
      }
  });
  ```
- **Error logging endpoint**: Setiap error di-log ke `error_logs` table:
  ```sql
  CREATE TABLE error_logs (
      id INTEGER PRIMARY KEY,
      tenant_id INTEGER,
      user_id INTEGER,
      endpoint VARCHAR(100),
      error_message TEXT,
      stack_trace TEXT,
      request_data TEXT,
      ip_address VARCHAR(45),
      severity ENUM('info', 'warning', 'error', 'critical') DEFAULT 'error',
      created_at DATETIME
  );
  ```
- **Health check endpoint**: `?endpoint=health` return status: DB connection, disk space, table counts, last error

#### Masalah: Tidak Ada Monitoring/Alerting

**Skenario**: Database lock terjadi setiap jam sibuk. Tidak ada yang tahu sampai user complain.

**Keputusan**:
- **Dashboard monitoring** di Super Admin: total tenants, active users, requests/min, error rate, DB size, response time
- **Alert via email/WhatsApp**: jika error rate > 5% atau DB lock > 10x/jam
- **Slow query log**: catat query yang > 1 detik

---

### 13.11 Legal & Compliance (UU PDP)

#### Masalah: Data Privacy Law (UU PDP Indonesia)

**Skenario**: UU Pelindungan Data Pribadi (UU PDP) efektif Oktober 2024. Aplikasi simpan data pribadi customer (nama, phone, email, alamat). Tenant harus patuh.

**Kondisi saat ini**: Tidak ada consent form, tidak ada data deletion mechanism, tidak ada data export untuk individu, tidak ada privacy policy.

**Keputusan**:
1. **Privacy policy template**: Sediakan template privacy policy untuk tenant
2. **Customer consent**: Saat tambah customer, checkbox "Saya setuju data saya disimpan sesuai kebijakan privasi"
3. **Right to be forgotten**: Endpoint untuk hapus data customer permanen (GDPR-style):
   ```php
   // Anonymize customer data, don't hard delete (keep for audit)
   UPDATE customers SET name='[DELETED]', phone=NULL, email=NULL, address=NULL 
   WHERE id = ? AND tenant_id = ?
   ```
4. **Data export**: Customer bisa request export data mereka (JSON/PDF)
5. **Data retention policy**: Auto-delete customer yang tidak transaksi > 5 tahun (configurable)

---

### 13.12 Browser & Device Compatibility

#### Masalah: Browser Lama (Internet Explorer, Chrome Lama)

**Skenario**: Toko pakai PC lama dengan Chrome versi 80 atau IE 11. Beberapa fitur JavaScript modern tidak jalan.

**Keputusan**:
- **Minimum browser**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **Detection**: Cek browser version, tampilkan warning jika browser terlalu lama
- **Polyfill**: Tambah polyfill untuk ES6+ features jika perlu
- **Graceful degradation**: Fitur non-critical (Battery API, Service Worker) tidak block core functionality

#### Masalah: Mobile Browser (Kasir di Tablet/HP)

**Skenario**: Kasir pakai tablet 7 inch atau HP. UI tidak responsive untuk layar kecil. Tombol terlalu kecil.

**Kondisi saat ini**: Bootstrap 5 responsive, tapi halaman sales.php dengan 40,000 bytes mungkin tidak optimal di mobile.

**Keputusan**:
- **Mobile-optimized POS**: Buat layout khusus mobile untuk sales.php (larger buttons, simplified cart, swipe gestures)
- **Touch-friendly**: Minimum tap target 44px, larger font untuk input
- **PWA**: Installable sebagai app di home screen (manifest.json sudah ada)

#### Masalah: Printer Compatibility

**Skenario**: Toko punya printer thermal 58mm (ESC/POS) atau 80mm. Browser print dialog tidak optimal untuk thermal printer.

**Keputusan**:
- **Print CSS**: Tambah `@media print` CSS khusus thermal printer (58mm dan 80mm)
- **Direct print**: Gunakan WebUSB/WebSerial API untuk direct print tanpa dialog (Chrome only)
- **ESC/POS library**: Tambah PHP ESC/POS library untuk generate raw print commands

---

### 13.13 API Integration Risks

#### Masalah: WhatsApp API Rate Limit / Downtime

**Skenario**: Kirim notifikasi WhatsApp ke 100 customer. API rate limit tercapai. 50 customer tidak dapat notifikasi.

**Keputusan**:
- **Queue system**: Tambah tabel `notification_queue`:
  ```sql
  CREATE TABLE notification_queue (
      id INTEGER PRIMARY KEY,
      tenant_id INTEGER,
      type ENUM('whatsapp', 'email', 'sms'),
      recipient VARCHAR(100),
      message TEXT,
      status ENUM('pending', 'sent', 'failed', 'retry') DEFAULT 'pending',
      attempts INT DEFAULT 0,
      max_attempts INT DEFAULT 3,
      sent_at DATETIME,
      error_message TEXT,
      created_at DATETIME
  );
  ```
- **Cron processor**: Process queue setiap 1 menit, kirim 10 message per batch
- **Retry with backoff**: Failed message retry setelah 5 menit, 15 menit, 60 menit

#### Masalah: Marketplace API Changes

**Skenario**: Tokopedia update API v2, API v1 deprecated. Integrasi marketplace berhenti jalan.

**Keputusan**:
- **API version tracking**: Simpan API version di `marketplace_integrations` table
- **Graceful failure**: Jika API error, log error dan tetap jalan (non-blocking)
- **Manual sync button**: Owner bisa trigger manual sync jika auto-sync gagal

---

### 13.14 Data Quality Issues

#### Masalah: Duplicate Products

**Skenario**: Kasir input "Beras 5kg" tanpa cek apakah sudah ada. Sehari kemudian input lagi "Beras 5Kg" (beda kapital). Sekarang ada 2 produk untuk barang yang sama. Stok terpisah.

**Keputusan**:
- **Duplicate check saat input**: Cek by code (exact match) dan by name (case-insensitive + trim):
  ```php
  $checkCode = $d->prepare("SELECT id FROM products WHERE code = ? AND tenant_id = ?");
  $checkName = $d->prepare("SELECT id FROM products WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) AND tenant_id = ?");
  ```
- **Merge feature**: Owner bisa merge 2 produk duplicate → pindahkan semua stock_movements dan sale_items ke produk utama, hapus produk duplikat

#### Masalah: Orphaned Records

**Skenario**: Produk dihapus, tapi sale_items masih reference product_id yang sudah tidak ada. Report error.

**Kondisi saat ini**: Products delete pakai foreign key `onDelete('restrict')` di stock_movements, tapi sale_items tidak punya FK ke products.

**Keputusan**:
- **Soft delete**: Jangan hard delete produk — set `is_active = 0`
- **FK audit**: Pastikan semua child table punya FK ke parent dengan `ON DELETE RESTRICT` atau `ON DELETE SET NULL`
- **Orphan cleanup script**: Query untuk detect orphaned records dan cleanup

#### Masalah: Inconsistent Decimal Precision

**Skenario**: Produk dijual per kg dengan qty 0.250 kg. Tapi some code round ke 2 decimal, some ke 3 decimal. Stok tidak konsisten.

**Kondisi saat ini**: `quantity DECIMAL(10,3)` di stock_movements dan sale_items — 3 decimal places. Tapi `number_format()` di config.php mungkin format ke 2 decimal.

**Keputusan**: Standardisasi: 3 decimal untuk quantity, 2 decimal untuk harga/uang. Audit semua `number_format()` calls.

---

### 13.15 Onboarding & User Experience

#### Masalah: Tenant Barah Bingung Mulai dari Mana

**Skenario**: Tenant baru register, login, lihat dashboard dengan 34 menu. Tidak tahu harus mulai dari apa. Batal pakai.

**Keputusan**: **Onboarding wizard**:
1. Selamat datang → isi nama toko, alamat, logo
2. Set up warehouse (1 klik: "Gudang = Toko")
3. Import produk dari Katalog Pusat (atau input manual)
4. Set up kasir (buat user kasir)
5. Set up pembayaran (pilih: cash, QRIS, e-wallet)
6. Test transaksi pertama
7. Selesai → dashboard dengan badge "Toko siap berjualan!"

**Progress tracking**:
```sql
ALTER TABLE tenants ADD COLUMN onboarding_step INT DEFAULT 0;
ALTER TABLE tenants ADD COLUMN onboarding_completed BOOLEAN DEFAULT 0;
```

#### Masalah: User Tidak Bisa Pakai Aplikasi

**Skenario**: Owner toko tidak tech-savvy. Tidak mengerti cara input produk, cara transaksi.

**Keputusan**:
- **Tutorial inline**: Tooltip di setiap halaman ("Klik tombol + untuk tambah produk")
- **Video tutorial**: Embed video pendek (30 detik) di setiap halaman utama
- **Help center**: Halaman `help.php` dengan FAQ dan video
- **Demo mode**: Tenant bisa coba dengan data dummy sebelum input data asli

---

### 13.16 Disk Space & File Management

#### Masalah: Storage Penuh

**Skenario**: Backup database setiap jam, setiap backup 50MB. Setelah 30 hari = 36GB backup. Disk hosting penuh.

**Keputusan**:
- **Backup compression**: `gzip` backup (sudah ada: `.sqlite.gz`)
- **Retention policy** (Section 13.7): hapus backup lama
- **Log rotation**: Hapus error log > 30 hari
- **Disk monitoring**: Alert jika disk usage > 80%
- **Image storage**: Jika implementasi upload gambar produk, simpan di cloud (S3) bukan local disk

---

### 13.17 Email & Notification Infrastructure

#### Masalah: Email Tidak Terkirim

**Skenario**: Tenant set up email notification untuk stok menipis. Email tidak pernah sampai. Tidak ada SMTP configured.

**Kondisi saat ini**: `.env.example` ada SMTP settings (mailhog default), tapi frontend tidak pakai Laravel mail. Tidak ada mail sending di frontend PHP.

**Keputusan**:
- **SMTP configuration**: Tambah SMTP settings di `app_settings` (smtp_host, smtp_port, smtp_user, smtp_pass, smtp_from)
- **PHPMailer**: Include PHPMailer library untuk send email
- **Fallback**: Jika email gagal, kirim WhatsApp notification sebagai backup
- **Test email**: Tombol "Test Email" di settings untuk verifikasi SMTP config

---

### 13.18 Ringkasan Skenario Production

| # | Skenario | Dampak | Priority |
|---|----------|--------|----------|
| 1 | SQLite concurrent write lock | Sale gagal saat ramai | P0 (WAL mode) |
| 2 | SQLite corruption | Data hilang | P0 (backup + integrity) |
| 3 | DB file size growth | Query lambat | P1 (archiving + vacuum) |
| 4 | Shared hosting limits | Timeout, OOM | P1 (chunk + docs) |
| 5 | Session hijacking | Akun dibajak | P0 (session_regenerate) |
| 6 | CSRF token tidak rotasi | CSRF attack | P1 |
| 7 | XSS stored | Script injection | P1 (audit + CSP) |
| 8 | Brute force login | Akun dibobol | P1 (DB-based tracking) |
| 9 | CDN down | UI rusak total | P0 (self-host assets) |
| 10 | Timezone salah | Laporan salah hari | P0 (set Asia/Jakarta) |
| 11 | Multi-currency import | Landed cost salah | P2 |
| 12 | Data migration | Onboarding lambat | P1 (import module) |
| 13 | Backup tidak teruji | Restore gagal | P1 (test restore) |
| 14 | Query N+1 | Load lambat | P1 (optimasi query) |
| 15 | Search lambat | UX buruk | P2 (FTS index) |
| 16 | Session timeout saat transaksi | Data hilang | P0 (auto-save + heartbeat) |
| 17 | Concurrent session | Conflict | P1 (session tracking) |
| 18 | Silent errors | User bingung | P1 (global error handler) |
| 19 | Tidak ada monitoring | Problem tidak terdeteksi | P1 (monitoring dashboard) |
| 20 | UU PDP compliance | Sanksi hukum | P2 (consent + deletion) |
| 21 | Browser lama | Fitur tidak jalan | P2 (detection + polyfill) |
| 22 | Mobile tidak optimal | Kesulitan kasir | P2 (mobile POS layout) |
| 23 | Printer compatibility | Struk rusak | P2 (thermal print CSS) |
| 24 | WhatsApp API rate limit | Notifikasi gagal | P2 (queue system) |
| 25 | Marketplace API changes | Sync berhenti | P2 (version tracking) |
| 26 | Duplicate products | Stok terpisah | P1 (duplicate check + merge) |
| 27 | Orphaned records | Report error | P1 (soft delete + FK audit) |
| 28 | Decimal precision | Stok tidak konsisten | P1 (standardisasi) |
| 29 | Onboarding confusing | Tenant churn | P1 (onboarding wizard) |
| 30 | Email tidak terkirim | Notifikasi gagal | P2 (SMTP config + PHPMailer) |
| 31 | Disk space penuh | Sistem down | P1 (retention + monitoring) |
| 32 | Information disclosure | Data bocor | P0 (display_errors=0) |

---

## 14. MATRIKS PRIORITAS

### P0 — Kritis (Fraud, Compliance, Data Integrity, Production Safety) — Sprint Berikutnya
| # | Item | Estimasi | Dependency |
|---|------|----------|------------|
| 1 | Database transaction untuk semua multi-step ops | 0.5 sprint | Wrap semua POST/PUT/DELETE |
| 2 | Stock validation sebelum sale | 0.5 sprint | Transaction + cek stok |
| 3 | Idempotency key (anti-double-submit) | 0.5 sprint | DB column + frontend + backend |
| 4 | WAL mode + busy_timeout di db.php | 0.1 sprint | 2 baris PRAGMA |
| 5 | Auto-save cart ke localStorage | 0.5 sprint | sales.php JS |
| 6 | QRIS/e-wallet payment methods | 1 sprint | Migration + UI |
| 7 | Void sales approval workflow | 0.5 sprint | DB + ajax.php + UI |
| 8 | Branch scoping di ajax.php | 1 sprint | Filter semua query |
| 9 | Audit logging semua endpoint | 0.5 sprint | Tambah logAudit() |
| 10 | Role fallback system | 0.5 sprint | extra_permissions + UI |
| 11 | tenant_id ke semua tabel | 0.5 sprint | Migration + filter |
| 12 | Button disable anti-double-click | 0.2 sprint | sales.php + semua form |
| 13 | Set timezone Asia/Jakarta | 0.1 sprint | 1 baris di config.php |
| 14 | session_regenerate_id setelah login | 0.1 sprint | 1 baris di auth.php |
| 15 | Self-host critical CSS/JS assets | 0.3 sprint | Download + update renderHead() |
| 16 | display_errors=0 di production | 0.1 sprint | Environment check di config.php |
| 17 | Session heartbeat (keep alive) | 0.2 sprint | JS interval di config.php |

### P1 — Tinggi (Operasional & Resiliensi) — 2-3 Sprint
| # | Item | Estimasi | Dependency |
|---|------|----------|------------|
| 18 | Shift management + cashier session | 2 sprint | DB + UI + ajax.php |
| 19 | EOD reconciliation | 1 sprint | Shift management |
| 20 | Attendance + leave + payroll | 2 sprint | Shift management |
| 21 | Deduction rules + sanksi | 1 sprint | Payroll |
| 22 | Partial fulfillment (pickup/delivery) | 1 sprint | DB + sales UI |
| 23 | Stock reservation system | 1 sprint | Partial fulfillment |
| 24 | Discount approval threshold | 0.5 sprint | Settings + sales |
| 25 | FEFO auto-picking | 0.5 sprint | Batch + sales |
| 26 | Customer credit limit enforcement | 0.5 sprint | Sales validation |
| 27 | Stock opname dengan warehouse scope | 0.5 sprint | DB + UI |
| 28 | GRN (Goods Receipt Note) | 1 sprint | DB + PO receiving |
| 29 | Inter-branch stock transfer improvement | 1 sprint | stock_transfers + approval |
| 30 | Modular feature toggle | 0.5 sprint | Settings + nav |
| 31 | Retry mechanism + offline queue (IndexedDB) | 1 sprint | Frontend JS |
| 32 | Battery API + auto-save draft | 0.3 sprint | Frontend JS |
| 33 | Optimistic locking (updated_at check) | 0.5 sprint | Backend + frontend |
| 34 | Mutex untuk PO receiving & critical ops | 0.5 sprint | Backend |
| 35 | Connection status indicator | 0.2 sprint | Frontend JS |
| 36 | Master catalog: tables + import flow | 1 sprint | DB + master_catalog.php + ajax |
| 37 | Master catalog: seed produk umum Indonesia | 0.5 sprint | Seeder |
| 38 | DB-based brute force login tracking | 0.5 sprint | login_attempts table |
| 39 | XSS audit + Content-Security-Policy header | 0.5 sprint | Audit + header() |
| 40 | SQL injection audit (string concat queries) | 0.3 sprint | Refactor ke prepared stmt |
| 41 | CSV/Excel import module | 1 sprint | import_jobs + UI + parser |
| 42 | Backup verification + test restore | 0.5 sprint | Cron + integrity check |
| 43 | Query N+1 optimization | 0.5 sprint | Refactor correlated subquery |
| 44 | Pagination audit semua list endpoint | 0.3 sprint | Audit + add LIMIT |
| 45 | Global AJAX error handler | 0.3 sprint | JS di config.php |
| 46 | Error logs table + health check endpoint | 0.5 sprint | DB + ajax endpoint |
| 47 | Monitoring dashboard (Super Admin) | 1 sprint | UI + metrics |
| 48 | Duplicate product check + merge feature | 0.5 sprint | ajax.php + UI |
| 49 | Soft delete + FK audit | 0.3 sprint | Refactor delete ops |
| 50 | Decimal precision standardization | 0.3 sprint | Audit number_format |
| 51 | Onboarding wizard | 1 sprint | UI + tenants columns |
| 52 | Disk monitoring + backup retention | 0.3 sprint | Cron + alert |
| 53 | Data archiving (transaksi > 2 tahun) | 1 sprint | Archive tables + cron |
| 54 | Concurrent session tracking | 0.5 sprint | users.active_session_id |
| 55 | HOSTING_GUIDE.md documentation | 0.2 sprint | Markdown doc |

### P2 — Menengah (Growth) — 3-6 Sprint
| # | Item | Estimasi | Dependency |
|---|------|----------|------------|
| 56 | Master catalog: contribution system | 1 sprint | Phase 1 master catalog |
| 57 | Master catalog: sync & update notifications | 1 sprint | Contribution system |
| 58 | Offline POS mode (full) | 2 sprint | Service worker + IndexedDB |
| 59 | PPN 11% auto-calculation + e-Faktur | 1 sprint | Settings + sales + e-Faktur |
| 60 | Commission calculation | 0.5 sprint | Salesman_id + payroll |
| 61 | Thermal printer format + QRIS QR | 0.5 sprint | print_nota.php |
| 62 | Inter-branch billing (Model 2) | 1 sprint | PO + sales + journal |
| 63 | Multi-currency (import USD/CNY) | 1 sprint | PO + exchange_rates |
| 64 | FULLTEXT search index | 0.5 sprint | SQLite FTS5 |
| 65 | UU PDP compliance (consent + deletion) | 1 sprint | Privacy policy + UI |
| 66 | Mobile-optimized POS layout | 1 sprint | sales.php responsive |
| 67 | Thermal printer ESC/POS support | 1 sprint | PHP ESC/POS library |
| 68 | WhatsApp notification queue | 0.5 sprint | notification_queue + cron |
| 69 | Marketplace API version tracking | 0.3 sprint | marketplace_integrations |
| 70 | SMTP config + PHPMailer integration | 0.5 sprint | app_settings + library |
| 71 | Migrasi ke MySQL/PostgreSQL (jika > 50 tenant) | 2 sprint | DB migration script |

### P3 — Jangka Panjang
| # | Item | Estimasi |
|---|------|----------|
| 72 | Role customization (per-user permission) | 1 sprint |
| 73 | Temporary permissions (auditor) | 0.5 sprint |
| 74 | IP-based access restriction | 0.5 sprint |
| 75 | Advanced AI demand forecasting | 2 sprint |
| 76 | Marketplace auto-sync real-time | 2 sprint |
| 77 | Reservation expiry auto-release | 0.5 sprint |
| 78 | Master catalog: auto-suggest (fuzzy search) | 0.5 sprint |
| 79 | Master catalog: bulk import | 0.5 sprint |
| 80 | Master catalog: category mapping | 0.5 sprint |
| 81 | Master catalog: marketplace product ID link | 1 sprint |
| 82 | Browser compatibility detection + polyfill | 0.5 sprint |
| 83 | Video tutorial + help center | 1 sprint |
| 84 | Elasticsearch/Meilisearch integration | 2 sprint |
| 85 | Read replica untuk report queries | 1 sprint |
| 86 | Off-site backup ke cloud (S3/GDrive) | 0.5 sprint |
| 87 | Connection pooling | 1 sprint |

---

## RINGKASAN KEPUTUSAN

| Pertanyaan | Keputusan |
|------------|-----------|
| Role tidak ada | Role fallback chain + extra_permissions per user |
| Pemisahan data tenant | tenant_id di semua tabel header + filter di query |
| Fitur tidak relevan (kendaraan, dll) | Modular feature toggle via app_settings |
| Gudang same-site/off-site/tidak ada | Auto-create warehouse default, multi-warehouse optional |
| Shift karyawan | Tabel shift_schedules, employee_shifts, cashier_sessions |
| Karyawan tidak masuk | Auto-detect absent, fallback ke owner/manager, deduction rules |
| Sanksi/potongan gaji | Configurable deduction_rules table, auto-calculate di payroll |
| Pesanan sebagian diambil | Partial fulfillment + stock reservation system |
| Beli dari cabang lain | Stock transfer (Model 1) atau inter-branch PO (Model 2) |
| Listrik padam | Auto-save cart ke localStorage, resume saat kembali |
| Jaringan putus | Retry + offline queue (IndexedDB) + idempotency key |
| Baterai ponsel habis | Auto-save + Battery API warning + quick save draft |
| Server crash | WAL mode + auto-backup + integrity check on restart |
| Dua proses bersamaan (oversell) | Transaction + stock validation + WAL mode |
| Double-submit | Idempotency key + button disable |
| PO double-receive | Mutex lock + received_quantity check |
| Katalog produk pusat | Master products table (tenant_id=NULL) + import flow + contribution system + sync notifications |
| SQLite di production | WAL mode + busy_timeout, migrasi MySQL jika > 50 tenant |
| Session hijacking | session_regenerate_id(true) setelah login |
| CDN down | Self-host critical assets (Bootstrap, jQuery, Chart.js) |
| Timezone salah | date_default_timezone_set('Asia/Jakarta') di config.php |
| Brute force login | DB-based login_attempts tracking, block IP/username |
| Information disclosure | display_errors=0 di production, log_errors=1 |
| Data migration | CSV import module dengan dry-run + error reporting + rollback |
| Backup tidak teruji | Integrity check + retention policy + test restore cron |
| Query N+1 | Optimasi correlated subquery ke JOIN + GROUP BY |
| Duplicate products | Duplicate check saat input + merge feature |
| Onboarding confusing | 7-step onboarding wizard + inline tutorial |
| Multi-currency import | currency + exchange_rate di PO + exchange_rates table |
| UU PDP compliance | Consent form + right to be forgotten + data export |
| Email tidak terkirim | SMTP config di app_settings + PHPMailer + fallback WhatsApp |
