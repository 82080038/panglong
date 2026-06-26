# Implementation Guide - Panglong ERP

## Flow Onboarding Tenant

### Overview

Ketika tenant baru mendaftar ke Panglong ERP SaaS, mereka perlu melalui proses onboarding untuk mengatur aplikasi sesuai kebutuhan bisnis mereka.

---

## Flow Onboarding Tenant (8 Langkah)

```
1. Pendaftaran Tenant
   ↓
2. Setup Akun Owner
   ↓
3. Pengaturan Perusahaan (Wajib)
   ↓
4. Pengaturan Stok & Produk (Wajib)
   ↓
5. Setup Lokasi (Opsional)
   ├─ Cabang (Branch)
   ├─ Gudang (Warehouse)
   └─ Karyawan (User)
   ↓
6. Pengaturan Keuangan (Opsional)
   ↓
7. Import Data Awal (Opsional)
   ↓
8. Selesai - Tenant Siap Digunakan
```

---

## Detail Langkah Onboarding

### 1. Pendaftaran Tenant

**Data yang diinput:**
- Nama perusahaan/toko
- Alamat
- No. telepon
- Email
- NPWP (opsional)
- Subdomain (untuk URL: https://subdomain.panglong.com)

**Output:**
- Tenant record dibuat di tabel `tenants`
- Status: `trial` atau `active`
- Trial period: 30 hari (default)

### 2. Setup Akun Owner

**Data yang diinput:**
- Username
- Password
- Nama lengkap
- Email
- No. HP

**Output:**
- User record dibuat di tabel `users`
- Role: `Owner`
- tenant_id: ID tenant yang baru dibuat
- branch_id: NULL (awal tanpa cabang)

### 3. Pengaturan Perusahaan (Wajib)

**Pengaturan di tabel `app_settings` (tenant_id = ID tenant):**

| Key | Value | Type | Keterangan |
|-----|-------|------|------------|
| `company_name` | Nama perusahaan | string | Nama toko/panglong |
| `company_address` | Alamat lengkap | text | Alamat toko |
| `company_phone` | No. telepon | string | Telp toko |
| `company_email` | Email | string | Email toko |
| `tax_id` | NPWP | string | Nomor pajak (opsional) |
| `currency` | IDR | string | Mata uang (default: IDR) |
| `timezone` | Asia/Jakarta | string | Timezone (default: Asia/Jakarta) |
| `date_format` | d/m/Y | string | Format tanggal (default: d/m/Y) |
| `decimal_separator` | , | string | Pemisah desimal (default: ,) |
| `thousand_separator` | . | string | Pemisah ribuan (default: .) |

### 4. Pengaturan Stok & Produk (Wajib)

**Pengaturan di tabel `app_settings`:**

| Key | Value | Type | Keterangan |
|-----|-------|------|------------|
| `stock_minus_policy` | strict | enum | strict/tidak boleh minus, soft/boleh minus |
| `min_stock_alert` | 10 | integer | Alert jika stok di bawah nilai ini |
| `default_unit` | pcs | string | Satuan default untuk produk |
| `enable_barcode` | true | boolean | Aktifkan fitur barcode |
| `enable_multi_unit` | true | boolean | Aktifkan multi satuan |
| `enable_product_alias` | true | boolean | Aktifkan nama alias produk |

**Setup Customer Groups (Wajib):**
- Retail (diskon 0%)
- Tukang (diskon 5%)
- Kontraktor (diskon 10%)
- Proyek (diskon 15%)
- Langganan (diskon 8%)

### 5. Setup Lokasi (Opsional)

**Catatan:** Langkah ini opsional. Tenant bisa mulai tanpa cabang/gudang (sederhana) dan menambahkannya nanti saat bisnis berkembang.

#### 5.1 Setup Cabang (Branch)

**Kapan diperlukan:**
- Tenant punya lebih dari 1 lokasi toko
- Perlu tracking penjualan per cabang
- Perlu reporting per cabang

**Data yang diinput ke tabel `branches`:**
- Kode cabang (misal: BKS, JKT, BDG)
- Nama cabang (misal: Cabang Bekasi)
- Alamat lengkap
- No. telepon
- Email
- Nama manager cabang
- Tipe cabang (main, branch, outlet)
- Status (active/inactive)

#### 5.2 Setup Gudang (Warehouse)

**Kapan diperlukan:**
- Perlu tracking stok per lokasi
- Ada gudang terpisah dari toko
- Perlu transfer stok antar gudang

**Data yang diinput ke tabel `warehouses`:**
- Kode gudang (misal: WH-MAIN, WH-BKS)
- Nama gudang (misal: Gudang Utama, Gudang Bekasi)
- Alamat
- No. telepon
- Tipe gudang (main, branch, transit)
- Kapasitas (m²) - opsional
- `branch_id` - jika gudang terikat ke cabang, NULL jika gudang utama tenant

#### 5.3 Setup Karyawan (User)

**Kapan diperlukan:**
- Tenant punya karyawan selain owner
- Perlu role-based access control
- Perlu tracking siapa yang melakukan transaksi

**Data yang diinput ke tabel `users`:**
- Username (unique di semua tenants)
- Password
- Nama lengkap
- Email
- No. HP
- Role (Manager, Kasir, Gudang, Accounting, Supervisor)
- `branch_id` - jika karyawan ditugaskan ke cabang tertentu
- `warehouse_id` - jika karyawan khusus gudang (opsional)

#### 5.4 Skenario Setup Lokasi

**Skenario A: Tanpa Cabang & Tanpa Gudang (Sederhana)**
- Cocok untuk toko kecil dengan 1 lokasi
- Semua user `branch_id = NULL`
- Tidak perlu tracking per lokasi

**Skenario B: Dengan Gudang, Tanpa Cabang**
- Cocok untuk toko dengan gudang terpisah
- Warehouse `branch_id = NULL` (langsung di bawah tenant)
- User gudang `branch_id = NULL`

**Skenario C: Dengan Cabang & Gudang (Kompleks)**
- Cocok untuk toko besar dengan banyak cabang
- Setiap cabang punya warehouse sendiri
- User terikat ke branch tertentu
- Owner akses semua cabang (branch_id = NULL)

### 6. Pengaturan Keuangan (Opsional)

**Pengaturan di tabel `app_settings`:**

| Key | Value | Type | Keterangan |
|-----|-------|------|------------|
| `enable_credit` | true | boolean | Aktifkan sistem hutang piutang |
| `default_credit_limit` | 1000000 | decimal | Limit kredit default customer |
| `default_payment_terms` | 30 | integer | Termin pembayaran default (hari) |
| `enable_tax` | false | boolean | Aktifkan PPN |
| `tax_rate` | 11 | decimal | Pajak PPN (%) |
| `enable_invoice` | true | boolean | Aktifkan fitur invoice |
| `invoice_prefix` | INV | string | Prefix nomor invoice |
| `invoice_start_number` | 1 | integer | Nomor awal invoice |

### 7. Import Data Awal (Opsional)

**Data yang bisa diimport:**
- Produk (dari Excel/CSV)
- Kategori produk
- Customer
- Supplier
- Stok awal

**Format import:**
- Excel (.xlsx)
- CSV (.csv)
- Template disediakan oleh sistem

### 8. Selesai - Tenant Siap Digunakan

**Status akhir:**
- Tenant: `active`
- User Owner: aktif
- App settings: terkonfigurasi
- Data master: siap (bisa kosong atau sudah diimport)

---

## Default Settings untuk Tenant Baru

Sistem akan otomatis set default settings saat tenant dibuat:

```php
// Default app_settings untuk tenant baru
$default_settings = [
    // Perusahaan
    'company_name' => $tenant['name'],
    'company_address' => $tenant['address'],
    'company_phone' => $tenant['phone'],
    'company_email' => $tenant['email'],
    'currency' => 'IDR',
    'timezone' => 'Asia/Jakarta',
    'date_format' => 'd/m/Y',
    'decimal_separator' => ',',
    'thousand_separator' => '.',
    
    // Stok
    'stock_minus_policy' => 'strict',
    'min_stock_alert' => 10,
    'default_unit' => 'pcs',
    'enable_barcode' => true,
    'enable_multi_unit' => true,
    'enable_product_alias' => true,
    
    // Keuangan
    'enable_credit' => true,
    'default_credit_limit' => 1000000,
    'default_payment_terms' => 30,
    'enable_tax' => false,
    'tax_rate' => 11,
    'enable_invoice' => true,
    'invoice_prefix' => 'INV',
    'invoice_start_number' => 1,
];
```

---

## Query Pattern untuk App Settings

### Get Setting Value

```php
function getSetting($tenant_id, $key, $default = null) {
    global $db;
    $stmt = $db->prepare("SELECT value FROM app_settings WHERE tenant_id = ? AND key = ?");
    $stmt->execute([$tenant_id, $key]);
    $result = $stmt->fetchColumn();
    return $result !== false ? $result : $default;
}

// Contoh penggunaan
$company_name = getSetting($tenant_id, 'company_name', 'Default Company');
$currency = getSetting($tenant_id, 'currency', 'IDR');
```

### Set Setting Value

```php
function setSetting($tenant_id, $key, $value, $type = 'string', $description = '') {
    global $db;
    
    // Cek apakah setting sudah ada
    $stmt = $db->prepare("SELECT id FROM app_settings WHERE tenant_id = ? AND key = ?");
    $stmt->execute([$tenant_id, $key]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update
        $stmt = $db->prepare("UPDATE app_settings SET value = ?, type = ?, description = ?, updated_at = ? WHERE id = ?");
        $stmt->execute([$value, $type, $description, date('Y-m-d H:i:s'), $existing['id']]);
    } else {
        // Insert
        $stmt = $db->prepare("INSERT INTO app_settings (tenant_id, key, value, type, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tenant_id, $key, $value, $type, $description, date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
    }
}
```

---

## Hal yang Sering Terlupakan (Critical)

### 1. Security & Data Isolation (KRITIS)

**Masalah:**
- Developer lupa menambahkan `WHERE tenant_id = ?` di query
- Cross-tenant data access bisa terjadi
- Cache tidak di-scoped per tenant

**Solusi:**
- Automatic query scoping di data access layer
- Cache keys dengan prefix tenant_id
- Test cross-tenant isolation di CI

### 2. Backup & Restore Strategy

**Masalah:**
- Tidak ada backup per-tenant
- Restore tidak bisa per-tenant
- Backup tidak terjadwal otomatis

**Solusi:**
- Daily backup untuk active tenants
- Weekly backup untuk trial tenants
- Retention: 30 hari untuk active, 7 hari untuk trial
- Test restore bulanan

### 3. Monitoring & Alerting per Tenant

**Masalah:**
- Tidak ada monitoring per-tenant
- Tidak tahu tenant mana yang bermasalah
- Tidak ada alert untuk anomali

**Solusi:**
- Track metrics per tenant: API calls, storage, active users
- Alert jika anomali (high usage, error rate)
- Dashboard untuk monitoring

### 4. Data Deletion saat Tenant Offboarding

**Masalah:**
- Data tidak dihapus lengkap
- Backup tidak dihapus
- Log tidak dihapus

**Solusi:**
- Delete primary database records
- Delete app settings
- Delete tenant record
- Schedule backup deletion
- Clear cache
- Disconnect integrations
- Verify deletion

### 5. Integration dengan Sistem Lain

**Masalah:**
- Tenant punya sistem lain (accounting, e-commerce, dll)
- Perlu API integration
- Perlu webhook

**Solusi:**
- Setup integrasi saat onboarding
- Support: Jurnal, Accurate, Tokopedia, Shopee, Midtrans, Xendit, WhatsApp API

### 6. Training & Documentation

**Masalah:**
- Tenant tidak tahu cara pakai sistem
- Tidak ada user guide
- Tidak ada video tutorial

**Solusi:**
- User guide (PDF/online)
- Video tutorials per module
- FAQ
- Training session

### 7. Support Channels

**Masalah:**
- Tenant tidak tahu kemana minta bantuan
- Tidak ada SLA
- Tidak ada escalation path

**Solusi:**
- Support: Email, WhatsApp, Phone
- SLA berdasarkan plan (Basic: 24 jam, Premium: 4 jam, Platinum: 1 jam)
- Escalation matrix

### 8. SLA Documentation

**Masalah:**
- Tidak ada SLA yang jelas
- Tenant tidak tahu hak mereka

**Solusi:**
- Uptime: 99.9% untuk premium
- Response time: < 200ms
- Data loss: 0%
- Backup recovery time: < 4 jam
- Compensation policy

### 9. Compliance & Audit Logs

**Masalah:**
- Tidak ada audit log
- Tidak bisa trace siapa melakukan apa

**Solusi:**
- Log semua CRUD operations
- Log login/logout
- Log permission changes
- Log sensitive data access
- Immutable logs (tidak bisa dihapus)
- Retention: 1 tahun minimum

### 10. Performance Monitoring per Tenant

**Masalah:**
- Satu tenant bisa membebani sistem
- Tidak ada resource limiting
- Tidak ada throttling

**Solusi:**
- Rate limiting per tenant
- Query timeout per tenant
- Storage quota
- Concurrent user limit
- Background job quota

---

## Realita Lapangan Toko Material Indonesia

### Kondisi Lapangan

1. **Masih Banyak Manual**
   - Pencatatan di buku/Excel
   - Tidak ada sistem inventory yang akurat
   - Transaksi manual
   - Stok sering selisih

2. **Ketidakpastian Order**
   - Quantity order tidak pasti
   - Bisa excess atau shortage inventory
   - Perlu EOQ (Economic Order Quantity) method

3. **Kebutuhan Spesifik**
   - Multi satuan (pcs, kg, meter, batang)
   - Barang berat (semen, pasir, besi)
   - Barang cacat (pecah, bocor)
   - Nama alias produk (banyak sebutan lokal)

### Implications untuk Onboarding

- **Wajib:** Training intensif (user biasanya non-teknis)
- **Wajib:** Multi satuan engine
- **Wajib:** Product alias support
- **Wajib:** Damage/loss tracking
- **Opsional:** EOQ calculator
- **Opsional:** Barcode scanner (banyak toko belum punya)

---

## Kendala Production & Solusi

### 1. Kendala Teknis

**Internet Tidak Stabil:**
- Solusi: Offline-First Architecture dengan queue sync

**Device Tidak Memadai:**
- Solusi: Mobile-First UI, PWA, Lite Mode

**Downtime SaaS:**
- Solusi: High Availability, Maintenance Schedule, Status Page

**Scalability Issues:**
- Solusi: Database Indexing, Caching, Rate Limiting

### 2. Kendala User Adoption

**Literasi Digital Rendah:**
- Solusi: UI sederhana, Video Tutorial, WhatsApp Support

**Resistensi Perubahan:**
- Solusi: Change Management, Pilot Phase, Champion

**Training Tidak Cukup:**
- Solusi: Continuous Training, In-App Help, FAQ

**Budaya Kerja Lama:**
- Solusi: Mandatory Fields, Validation, Gamification

### 3. Kendala Data Quality

**Data Manual Tidak Akurat:**
- Solusi: Data Validation, Cleansing, Migration Checklist

**Inconsistent Naming:**
- Solusi: Product Alias System, Fuzzy Search, Merge Tool

**Data Migration Issues:**
- Solusi: Tested Migration Script, Rollback Plan, Verification

**Master Data Tidak Clean:**
- Solusi: Duplicate Detection, Merge Tool, Periodic Audit

### 4. Kendala ERP Implementation

**Scope Creep:**
- Solusi: Clear Scope, Change Request Process, MVP First

**Integration Breaks:**
- Solusi: Integration Testing, API Versioning, Fallback

**Governance Weak:**
- Solusi: Clear Governance, Steering Committee, Escalation Matrix

**Testing Tidak Cukup:**
- Solusi: Comprehensive Testing, Load Testing, UAT, Canary Deployment

**Go-Live Failure:**
- Solusi: Parallel Run, Rollback Plan, Go-Live Checklist

---

## Checklist Onboarding Lengkap

### Platform Owner (Super Admin)
- [ ] Review pendaftaran tenant
- [ ] Approve/reject tenant
- [ ] Set subscription plan
- [ ] Provision resources (database, cache, etc.)
- [ ] Setup monitoring
- [ ] Setup backup schedule
- [ ] Setup SLA
- [ ] Send welcome email
- [ ] Schedule training session

### Tenant Owner
- [ ] Lengkapi info perusahaan
- [ ] Setup akun owner
- [ ] Konfigurasi pengaturan stok
- [ ] Konfigurasi pengaturan keuangan
- [ ] Setup lokasi (cabang/gudang) - jika diperlukan
- [ ] Tambah user karyawan - jika diperlukan
- [ ] Setup products/categories
- [ ] Setup customer groups
- [ ] Setup integrasi - jika diperlukan
- [ ] Import data awal - jika diperlukan
- [ ] Review user guide
- [ ] Attend training session

### System (Automated)
- [ ] Create tenant record
- [ ] Create user owner
- [ ] Set default app settings
- [ ] Setup audit logging
- [ ] Setup rate limiting
- [ ] Setup monitoring
- [ ] Setup backup
- [ ] Setup cache isolation
- [ ] Verify tenant isolation
- [ ] Send notifications

---

## Production Readiness Checklist

### Infrastructure
- [ ] Server provisioned
- [ ] Database configured
- [ ] SSL certificate installed
- [ ] CDN configured
- [ ] Backup system ready
- [ ] Monitoring setup
- [ ] Alerting configured

### Application
- [ ] All tests passing
- [ ] Performance optimized
- [ ] Security audit passed
- [ ] Error handling configured
- [ ] Logging configured
- [ ] Cache configured

### Data
- [ ] Data migration completed
- [ ] Data verified
- [ ] Backup created
- [ ] Rollback plan ready

### Documentation
- [ ] User guide ready
- [ ] Admin guide ready
- [ ] API documentation ready
- [ ] Troubleshooting guide ready

### Support
- [ ] Support team trained
- [ ] Escalation matrix defined
- [ ] Communication channels ready
- [ ] SLA documented
