# MASTER BLUEPRINT

# PANGLONG ERP + DISTRIBUTION + SaaS PLATFORM

## COMPLETE ENTERPRISE ARCHITECTURE PLAN

Versi: 1.3 (Updated 2026-06-26)
Status: ALL SPRINTS (1-12) + GAP FEATURES + UI/UX COMPLETED — 45 pages, 39 E2E tests, 78 tables
Target Teknologi:

* Backend: Laravel 10.x (PHP 8.1+) - REST API (scaffolded, tested, TIDAK digunakan frontend)
* Frontend: PHP Native (procedural, PDO SQLite langsung, jQuery AJAX ke ajax.php)
* Database: SQLite (dev/aktif: database/database.sqlite), MySQL 8.0+ (production target)
* jQuery 3.6.x (CDN) — `$.ajax()` calls to `frontend/ajax.php`
* Bootstrap 5.3.x (CDN)
* Auth: Session-based (`frontend/auth.php` dengan `password_verify()`)
* Laravel Sanctum (token-based, untuk Laravel API yang TIDAK digunakan frontend)
* Permission: spatie/laravel-permission (Laravel), `hasPermission()` (frontend)
* Offline First Hybrid (Phase 3)
* Multi Tenant SaaS (Phase 3)

> **ARSITEKTUR AKTUAL:** Frontend PHP Native mengakses database SQLite
> langsung via PDO. `frontend/ajax.php` adalah single endpoint untuk semua
> CRUD operations. Laravel backend API ada di repo tetapi TIDAK digunakan
> oleh frontend. Lihat DEVELOPMENT_ROADMAP.md dan PROJECT_STATUS.md.

---

# DAFTAR ISI

1. Filosofi Sistem
2. Analisa Real Lapangan
3. Arsitektur Enterprise
4. Struktur Folder PHP Native
5. Multi Tenant SaaS
6. Sistem User & Permission
7. Sistem Barang
8. Hierarchical Product Classification
9. Multi Satuan Engine
10. Inventory Core Engine
11. Stock Movement System
12. Stock Reservation
13. Stock Locking
14. Stock Minus Policy
15. Stock Adjustment
16. Damage Management
17. Dead Stock Analysis
18. Inventory Valuation
19. HPP Engine
20. Purchase System
21. Purchase Order Workflow
22. Supplier Management
23. Supplier Performance Analytics
24. Sales System
25. Sales Order
26. Quotation System
27. Invoice System
28. Customer Management
29. Customer Group System
30. Customer Credit Scoring
31. Piutang Management
32. Hutang Supplier
33. Cashflow Management
34. Expense Management
35. Accounting Engine
36. Journal Automation
37. Tax Engine
38. Multi Price Engine
39. Price History
40. Dynamic Pricing
41. Delivery System
42. GPS Delivery Tracking
43. Vehicle Management
44. Driver Performance
45. Logistic Cost Analytics
46. Warehouse Management
47. Multi Warehouse
48. Transfer Gudang
49. Barcode System
50. QRCode System
51. Weighing System
52. Cutting System
53. Bundle / Package System
54. BOM System
55. Project Management
56. Seasonal Analytics
57. AI & Predictive Analytics
58. Fraud Detection System
59. Audit System
60. Immutable Transaction System
61. Reversal System
62. Approval Workflow
63. Notification Engine
64. WhatsApp Integration
65. Print Engine
66. PDF Engine
67. Digital Signature
68. Document Management
69. File Storage Strategy
70. Cache System
71. Queue System
72. Offline First Architecture
73. Sync Engine
74. Conflict Resolution
75. Retry & Recovery System
76. Fail Safe Architecture
77. Database Architecture
78. Transaction Isolation
79. Race Condition Handling
80. Indexing Strategy
81. Partitioning Strategy
82. Backup System
83. Restore System
84. Monitoring System
85. Observability
86. Logging System
87. Telemetry System
88. API System
89. Authentication & Security
90. Encryption System
91. Device Management
92. Geolocation Fraud Detection
93. Licensing System
94. Billing SaaS System
95. Usage Metering Engine
96. Feature Flag System
97. Plugin Architecture
98. Migration System
99. Update Engine
100. White Label System
101. Marketplace Integration
102. Business Intelligence
103. Data Warehouse Thinking
104. Training & Sandbox System
105. SOP Digital
106. Human Psychology Consideration
107. UI/UX Philosophy
108. Operational Reality Handling
109. Legal & Compliance
110. Ethical Data Ownership
111. Exit Strategy
112. Business Continuity
113. Scalability Roadmap
114. Revenue Strategy
115. Final Architecture Conclusion

---

# 1. FILOSOFI SISTEM

Aplikasi ini bukan sekadar:

* aplikasi kasir
* aplikasi stok
* aplikasi toko

Tetapi:

# PLATFORM ERP DISTRIBUSI MATERIAL

Yang mengontrol:

* stok
* uang
* hutang
* supplier
* gudang
* pengiriman
* pegawai
* audit
* AI analytics
* SaaS billing

---

# 2. ANALISA REAL LAPANGAN

## Kondisi Umum Panglong

* internet tidak stabil
* pegawai non teknis
* transaksi cepat
* barang berat
* multi satuan
* harga berubah cepat
* hutang besar
* gudang luas
* stok sering selisih
* nota manual masih dipakai
* barang sering memiliki nama alias

---

# 3. ARSITEKTUR ENTERPRISE

## Arsitektur Dasar (Implementasi Aktual)

```text
Browser (User)
↓
PHP Native Frontend (frontend/*.php)
↓ jQuery $.ajax()
frontend/ajax.php (single AJAX endpoint, 1802 lines)
↓ PDO SQLite
Database (database/database.sqlite, 78 tables)

---

Laravel Backend API (TIDAK DIGUNAKAN FRONTEND):
  app/Http/Controllers/Api/v1/ → app/Services/ → app/Models/ → Database
  routes/api.php (Sanctum + Spatie Permission middleware)
  Tested via PHPUnit, tapi tidak dipanggil oleh frontend
```

### Catatan Arsitektur
- Frontend dan Backend adalah kode terpisah dalam satu repo
- Frontend: `frontend/` directory (PHP Native, session-based, PDO SQLite)
- Backend: `app/`, `routes/`, `config/` (Laravel framework, TIDAK digunakan frontend)
- Frontend komunikasi: jQuery AJAX ke `frontend/ajax.php` → PDO SQLite
- Laravel API komunikasi: REST API (JSON over HTTP, Sanctum token) — tidak aktif
- Authentication frontend: Session-based (`password_verify()` + `$_SESSION`)
- Authentication Laravel: Sanctum token (tidak digunakan frontend)

---

# 4. STRUKTUR FOLDER AKTUAL

```text
panglong/
├── app/                        # Laravel backend
│   ├── Console/
│   ├── Exceptions/
│   ├── Helpers/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/v1/         # API controllers
│   │   └── Middleware/
│   ├── Models/                 # Eloquent models (25)
│   ├── Providers/
│   └── Services/               # Business logic (7 services)
├── bootstrap/
├── config/
├── database/
│   ├── migrations/             # 25 migration files
│   └── seeders/                # 8 seeder files
├── frontend/                   # PHP Native frontend
│   ├── index.php               # Dashboard
│   ├── login.php               # Auth page
│   ├── logout.php
│   ├── products.php            # Product CRUD
│   ├── customers.php           # Customer CRUD
│   ├── sales.php               # POS / Sales
│   ├── stock.php               # Inventory
│   └── test_login.php          # Test page
├── public/
│   └── index.php               # Laravel entry point
├── routes/
│   ├── api.php                 # API routes (v1)
│   ├── web.php
│   └── console.php
├── storage/
│   ├── logs/
│   └── framework/
├── vendor/                     # Composer dependencies
├── composer.json
├── artisan
├── .env.example
└── *.md                        # Documentation files
```

---

# 5. MULTI TENANT SAAS

## Semua tabel penting wajib memiliki:

```sql
tenant_id
```

Contoh:

* products
* sales
* customers
* stock_movements
* journals

---

# 6. SISTEM USER & PERMISSION

## Role

* Owner
* Manager
* Kasir
* Gudang
* Sopir
* Accounting
* Supervisor

---

## Permission Granular

Contoh:

* create_sales
* edit_sales
* void_sales
* stock_adjustment
* edit_price
* view_profit

---

# 7. SISTEM BARANG

Barang panglong harus mendukung:

* alias
* multi satuan
* barcode
* QRCode
* batch
* varian
* brand
* spesifikasi
* harga dinamis

---

# 8. HIERARCHICAL PRODUCT CLASSIFICATION

```text
Divisi
→ Kategori
→ Subkategori
→ Merk
→ Spesifikasi
→ Varian
→ Produk
```

---

# 9. MULTI SATUAN ENGINE

## Semua stok disimpan dalam:

# BASE UNIT

Contoh:

| Barang | Base Unit |
| ------ | --------- |
| Paku   | pcs       |
| Besi   | meter     |
| Cat    | liter     |

---

## Struktur

```sql
product_units
```

Menyimpan:

* satuan
* conversion factor
* harga per satuan

---

# 10. INVENTORY CORE ENGINE

## Prinsip Utama

Jangan update stok langsung.

Gunakan:

# stock_movements

Sebagai:

# source of truth

---

# 11. STOCK MOVEMENT SYSTEM

Jenis:

* purchase
* sale
* return
* adjustment
* transfer
* damage
* project

---

# 12. STOCK RESERVATION

Membedakan:

| Jenis           | Fungsi        |
| --------------- | ------------- |
| physical_stock  | stok nyata    |
| reserved_stock  | stok dipesan  |
| available_stock | stok tersedia |

---

# 13. STOCK LOCKING

Saat transaksi berlangsung:

* stok dikunci sementara
* menghindari double selling

---

# 14. STOCK MINUS POLICY

## Configurable:

* strict mode
* soft mode

---

# 15. STOCK ADJUSTMENT

Semua adjustment wajib:

* reason
* approval
* audit log

---

# 16. DAMAGE MANAGEMENT

Mencatat:

* semen rusak
* cat bocor
* keramik pecah
* kehilangan

---

# 17. DEAD STOCK ANALYSIS

AI mendeteksi:

* slow moving
* dead stock
* overstock

---

# 18. INVENTORY VALUATION

Menghitung:

* nilai stok
* aset berjalan

---

# 19. HPP ENGINE

Metode:

* FIFO
* Moving Average

Rekomendasi:

# Moving Average atau FIFO

---

# 20. PURCHASE SYSTEM

Flow:

```text
Purchase Request
↓
Approval
↓
PO
↓
Barang Datang
↓
Penerimaan
↓
Stok Bertambah
```

---

# 21. PURCHASE ORDER WORKFLOW

Status:

* draft
* ordered
* partial
* completed
* cancelled

---

# 22. SUPPLIER MANAGEMENT

Menyimpan:

* supplier
* hutang
* termin
* histori harga

---

# 23. SUPPLIER PERFORMANCE ANALYTICS

Analisa:

* ketepatan pengiriman
* kualitas barang
* harga terbaik
* tingkat retur

---

# 24. SALES SYSTEM

Flow:

```text
Quotation
↓
Sales Order
↓
Delivery
↓
Invoice
↓
Payment
```

---

# 25. SALES ORDER

Mendukung:

* reserve stock
* partial delivery
* project order

---

# 26. QUOTATION SYSTEM

Mendukung:

* estimasi
* approval
* masa berlaku
* konversi otomatis ke SO

---

# 27. INVOICE SYSTEM

Jenis:

* cash
* credit
* termin proyek

---

# 28. CUSTOMER MANAGEMENT

Data:

* customer
* alamat proyek
* histori pembelian
* histori hutang
* credit score

---

# 29. CUSTOMER GROUP SYSTEM

Contoh:

* retail
* tukang
* kontraktor
* proyek
* langganan

---

# 30. CUSTOMER CREDIT SCORING

Analisa:

* ketepatan bayar
* limit hutang
* histori keterlambatan

---

# 31. PIUTANG MANAGEMENT

Fitur:

* jatuh tempo
* reminder
* cicilan
* aging report

---

# 32. HUTANG SUPPLIER

Mencatat:

* termin
* jatuh tempo
* pembayaran supplier

---

# 33. CASHFLOW MANAGEMENT

Dashboard:

* kas masuk
* kas keluar
* piutang
* hutang
* saldo berjalan

---

# 34. EXPENSE MANAGEMENT

Mencatat:

* solar
* makan pekerja
* bongkar muat
* operasional

---

# 35. ACCOUNTING ENGINE

Otomatis membuat:

* jurnal
* buku besar
* neraca
* laba rugi

---

# 36. JOURNAL AUTOMATION

Event bisnis otomatis membuat jurnal.

---

# 37. TAX ENGINE

Mendukung:

* PPN
* non PPN
* e-Faktur future ready

---

# 38. MULTI PRICE ENGINE

Jenis harga:

* retail
* grosir
* kontraktor
* proyek
* promo
* minimum price

---

# 39. PRICE HISTORY

Menyimpan histori:

* harga beli
* harga jual
* perubahan harga

---

# 40. DYNAMIC PRICING

Harga dapat berubah berdasarkan:

* customer group
* quantity
* proyek
* waktu

---

# 41. DELIVERY SYSTEM

Flow:

```text
SO
↓
Surat Jalan
↓
Loading
↓
Pengiriman
↓
Tanda Terima
```

---

# 42. GPS DELIVERY TRACKING

Melacak:

* posisi sopir
* estimasi tiba
* histori rute

---

# 43. VEHICLE MANAGEMENT

Mencatat:

* servis
* BBM
* pajak
* kapasitas

---

# 44. DRIVER PERFORMANCE

Analisa:

* keterlambatan
* konsumsi BBM
* pengiriman gagal

---

# 45. LOGISTIC COST ANALYTICS

Menghitung:

* biaya kirim
* margin setelah delivery

---

# 46. WAREHOUSE MANAGEMENT

Fitur:

* stock opname
* zone gudang
* rak
* lokasi barang

---

# 47. MULTI WAREHOUSE

Mendukung:

* gudang utama
* gudang proyek
* cabang

---

# 48. TRANSFER GUDANG

Flow:

```text
Request Transfer
↓
Approval
↓
Pengiriman
↓
Penerimaan Gudang Tujuan
```

---

# 49. BARCODE SYSTEM

Mendukung:

* multi barcode
* barcode per satuan

---

# 50. QRCODE SYSTEM

Digunakan untuk:

* tracking
* delivery
* dokumen

---

# 51. WEIGHING SYSTEM

Integrasi:

* timbangan digital
* barang kiloan

---

# 52. CUTTING SYSTEM

Untuk:

* besi
* kaca
* kayu

Mencatat:

* sisa potongan

---

# 53. BUNDLE / PACKAGE SYSTEM

Contoh:

* paket renovasi
* paket kamar mandi

---

# 54. BOM SYSTEM

Bill of Material untuk:

* proyek
* estimasi material

---

# 55. PROJECT MANAGEMENT

Mendukung:

* termin
* material proyek
* progress proyek

---

# 56. SEASONAL ANALYTICS

AI menganalisa:

* musim hujan
* proyek jalan
* renovasi

---

# 57. AI & PREDICTIVE ANALYTICS

AI dapat memprediksi:

* reorder
* cashflow
* piutang macet
* stok mati
* fraud

---

# 58. FRAUD DETECTION SYSTEM

Deteksi:

* diskon aneh
* void berlebihan
* stok hilang
* login abnormal

---

# 59. AUDIT SYSTEM

Mencatat:

* siapa
* kapan
* dari device mana
* sebelum & sesudah perubahan

---

# 60. IMMUTABLE TRANSACTION SYSTEM

Jangan edit transaksi lama.

Gunakan:

* reversal
* correction transaction

---

# 61. REVERSAL SYSTEM

Untuk:

* salah input
* salah qty
* salah harga

---

# 62. APPROVAL WORKFLOW

Approval untuk:

* void
* diskon besar
* adjustment
* retur besar

---

# 63. NOTIFICATION ENGINE

Notifikasi:

* stok kritis
* hutang jatuh tempo
* transaksi mencurigakan

---

# 64. WHATSAPP INTEGRATION

Digunakan untuk:

* nota
* reminder hutang
* promo
* status pengiriman

---

# 65. PRINT ENGINE

Jenis:

* thermal
* A4
* PDF

---

# 66. PDF ENGINE

Membuat:

* invoice
* laporan
* surat jalan

---

# 67. DIGITAL SIGNATURE

Digunakan pada:

* penerimaan barang
* delivery
* approval

---

# 68. DOCUMENT MANAGEMENT

Menyimpan:

* foto
* kontrak
* invoice supplier
* surat jalan

---

# 69. FILE STORAGE STRATEGY

Struktur:

```text
/storage/invoices
/storage/products
/storage/signatures
```

---

# 70. CACHE SYSTEM

Menggunakan:

* Redis
  atau:
* file cache

---

# 71. QUEUE SYSTEM

Untuk proses berat:

* backup
* sync
* WA blast
* generate PDF

---

# 72. OFFLINE FIRST ARCHITECTURE

Prinsip:

# aplikasi tetap berjalan tanpa internet

---

# 73. SYNC ENGINE

Flow:

```text
Local Transaction
↓
Queue
↓
Background Sync
↓
Cloud
```

---

# 74. CONFLICT RESOLUTION

Mengatasi:

* edit bersamaan
* stok bentrok
* data ganda

---

# 75. RETRY & RECOVERY SYSTEM

Jika sync gagal:

* retry otomatis
* resume sync

---

# 76. FAIL SAFE ARCHITECTURE

Saat:

* internet mati
* listrik mati
* cloud down

Sistem tetap hidup.

---

# 77. DATABASE ARCHITECTURE

Menggunakan:

* transaction
* indexing
* normalization

---

# 78. TRANSACTION ISOLATION

Menghindari:

* dirty read
* race condition

---

# 79. RACE CONDITION HANDLING

Gunakan:

* row locking
* optimistic locking

---

# 80. INDEXING STRATEGY

Index penting:

* invoice_number
* product_code
* barcode
* created_at

---

# 81. PARTITIONING STRATEGY

Saat data besar:

* yearly partition
* archive table

---

# 82. BACKUP SYSTEM

Backup:

* lokal
* cloud
* incremental
* otomatis

---

# 83. RESTORE SYSTEM

Harus mampu:

* restore database
* restore transaksi
* restore file

---

# 84. MONITORING SYSTEM

Monitoring:

* CPU
* RAM
* disk
* sync status

---

# 85. OBSERVABILITY

Mencakup:

* metrics
* tracing
* error monitoring

---

# 86. LOGGING SYSTEM

Jenis:

* audit log
* error log
* event log
* security log

---

# 87. TELEMETRY SYSTEM

Mengirim:

* crash report
* performa aplikasi
* usage statistics

---

# 88. API SYSTEM

API digunakan untuk:

* mobile app
* dashboard
* AI
* marketplace

---

# 89. AUTHENTICATION & SECURITY

Menggunakan:

* password hashing
* CSRF protection
* session timeout
* token auth

---

# 90. ENCRYPTION SYSTEM

Mengenkripsi:

* token
* credential
* data sensitif

---

# 91. DEVICE MANAGEMENT

Membatasi:

* device tertentu
* login bersamaan

---

# 92. GEOLOCATION FRAUD DETECTION

Mendeteksi:

* login aneh
* lokasi mencurigakan

---

# 93. LICENSING SYSTEM

Status:

* active
* expired
* suspended

---

# 94. BILLING SAAS SYSTEM

Model:

# hybrid usage billing

---

# 95. USAGE METERING ENGINE

Menghitung:

* transaksi
* sync
* storage
* fitur premium

---

# 96. FEATURE FLAG SYSTEM

Mengaktifkan fitur:

* beta
* premium
* enterprise

---

# 97. PLUGIN ARCHITECTURE

Agar fitur baru:

* modular
* tidak merusak core

---

# 98. MIGRATION SYSTEM

Versi database:

* v1
* v2
* v3

---

# 99. UPDATE ENGINE

Mendukung:

* auto update
* patch update

---

# 100. WHITE LABEL SYSTEM

Mendukung:

* brand berbeda
* engine sama

---

# 101. MARKETPLACE INTEGRATION

Future:

* Tokopedia
* Shopee
* TikTok Shop

---

# 102. BUSINESS INTELLIGENCE

Analisa:

* laba
* tren
* margin
* pola pelanggan

---

# 103. DATA WAREHOUSE THINKING

Pisahkan:

| Jenis | Fungsi    |
| ----- | --------- |
| OLTP  | transaksi |
| OLAP  | analytics |

---

# 104. TRAINING & SANDBOX SYSTEM

Pegawai baru dapat:

* simulasi transaksi
* latihan sistem

---

# 105. SOP DIGITAL

Menyimpan:

* SOP gudang
* SOP kasir
* SOP delivery

---

# 106. HUMAN PSYCHOLOGY CONSIDERATION

UI harus:

* cepat
* mudah
* minim klik
* tahan human error

---

# 107. UI/UX PHILOSOPHY

Fokus:

# kecepatan operasional

bukan sekadar tampilan cantik.

---

# 108. OPERATIONAL REALITY HANDLING

Sistem harus menerima:

* keterlambatan input
* stok selisih
* human error
* kondisi lapangan

---

# 109. LEGAL & COMPLIANCE

Future ready:

* pajak
* audit
* e-Faktur

---

# 110. ETHICAL DATA OWNERSHIP

Data tetap milik:

# client / panglong

---

# 111. EXIT STRATEGY

Jika client berhenti:

* export Excel
* PDF
* backup data

---

# 112. BUSINESS CONTINUITY

Jika:

* server rusak
* developer tidak ada
* cloud mati

Sistem harus tetap berjalan.

---

# 113. SCALABILITY ROADMAP

> Status: Sprint 1-12 ALL COMPLETED
> Lihat DEVELOPMENT_ROADMAP.md untuk detail sprint plan

Tahapan:

## Phase 1

* POS
* inventory
* hutang

## Phase 2

* accounting
* delivery
* AI basic

## Phase 3

* SaaS
* multi tenant
* sync cloud

## Phase 4

* AI advanced
* marketplace
* predictive analytics

---

# 114. REVENUE STRATEGY

Gunakan:

# HYBRID VALUE-BASED BILLING

Model:

```text
Biaya dasar kecil
+
Usage transaksi
+
Fitur premium
```

---

# 115. FINAL ARCHITECTURE CONCLUSION

Aplikasi ini pada akhirnya adalah:

# ERP + INVENTORY + ACCOUNTING + LOGISTIC + AI + FRAUD CONTROL + SaaS PLATFORM

Dan tantangan terbesar bukan coding,
melainkan:

# menyesuaikan software dengan realita manusia lapangan.

Keberhasilan sistem bukan di:

* fitur terbanyak
* UI tercantik

Tetapi:

# sistem tetap stabil, dipercaya, cepat, dan nyaman dipakai bertahun-tahun.
