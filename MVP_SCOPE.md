# MVP SCOPE DOCUMENT

# PANGLONG ERP - PHASE 1

## Version: 1.0
## Target Timeline: 3-4 Months
## Focus: POS, Inventory, Hutang

---

# EXECUTIVE SUMMARY

MVP (Minimum Viable Product) ini fokus pada core functionality yang paling kritis untuk operasional panglong sehari-hari:

1. **POS (Point of Sale)** - Transaksi penjualan cepat
2. **Inventory Management** - Kontrol stok barang
3. **Hutang (Accounts)** - Manajemen piutang pelanggan

Fitur-fitur advanced (AI, SaaS, multi-tenant, delivery tracking, dll) akan dikerjakan di Phase 2-4.

---

# EXCLUSION CRITERIA

**TIDAK termasuk dalam MVP:**
- ❌ Multi-tenant SaaS
- ❌ AI & Predictive Analytics
- ❌ Delivery & GPS Tracking
- ❌ Accounting Engine (jurnal otomatis)
- ❌ Project Management
- ❌ Marketplace Integration
- ❌ Offline-first sync (basic local-only first)
- ❌ WhatsApp Integration
- ❌ Advanced Fraud Detection
- ❌ BOM System
- ❌ Cutting/Weighing System

---

# MODULE 1: POS (POINT OF SALE)

## 1.1 Core Features

### 1.1.1 Transaksi Penjualan
- **Create Sales Order**
  - Input customer (walk-in atau registered)
  - Scan barcode barang atau search manual
  - Input quantity
  - Auto-calculate subtotal
  - Apply diskon (manual atau dari customer group)
  - Pilih metode pembayaran:
    - Cash
    - Credit (hutang)
  - Generate invoice number otomatis
  - Print nota (thermal/A4)
  - Stock terpotong otomatis

### 1.1.2 Multi Satuan dalam Transaksi
- Support pembelian dengan satuan berbeda
- Auto-convert ke base unit untuk stock calculation
- Contoh: Jual "5 batang besi 6 meter" → auto hitung total meter

### 1.1.3 Void Transaction
- Void transaksi yang salah
- Wajib input alasan void
- Wajib approval (jika > batas tertentu)
- Stock kembali otomatis
- Audit log tercatat

### 1.1.4 Return Transaction
- Return barang
- Partial return atau full return
- Stock kembali otomatis
- Catat alasan return

### 1.1.5 Daily Sales Report
- Laporan penjualan harian
- Breakdown per product
- Breakdown per payment method
- Total revenue

## 1.2 User Roles untuk POS
- **Kasir** - Create sales, void (dengan limit)
- **Manager** - Create sales, void tanpa limit, view profit
- **Owner** - Full access

## 1.3 UI Requirements
- Cepat dan minimal klik
- Barcode scanner support
- Keyboard shortcuts untuk aksi umum
- Search product instant (autocomplete)
- Tampilan stok real-time

---

# MODULE 2: INVENTORY MANAGEMENT

## 2.1 Core Features

### 2.1.1 Product Management
- **Create Product**
  - Kode barang (auto-generate atau manual)
  - Nama barang
  - Alias/nama alternatif (bisa banyak)
  - Kategori
  - Brand
  - Barcode (bisa multiple)
  - QRCode (auto-generate)
  - Base unit
  - Multi satuan dengan conversion factor
  - Harga beli terakhir
  - Harga jual (base price)
  - Minimum stock level
  - Maximum stock level
  - Lokasi gudang (zone/rak)

- **Edit Product**
  - Update semua field kecuali kode barang
  - Audit log perubahan

- **Delete Product**
  - Soft delete (tidak hard delete)
  - Cek apakah ada transaksi terkait

### 2.1.2 Multi Satuan Engine
- **Product Units Table**
  - product_id
  - unit_name (pcs, meter, kg, batang, dll)
  - conversion_factor (relatif ke base unit)
  - is_base_unit (boolean)
  - price_per_unit

- **Contoh:**
  - Base unit: meter
  - Satuan lain: batang (6 meter) → conversion_factor = 6
  - Jual 3 batang = 3 × 6 = 18 meter

### 2.1.3 Stock Movement System
- **Stock Movements Table (Source of Truth)**
  - id
  - product_id
  - quantity (positive/negative)
  - unit_id
  - movement_type (purchase, sale, return, adjustment, damage)
  - reference_id (invoice_id, purchase_id, dll)
  - notes
  - created_by
  - created_at

- **Movement Types:**
  - `purchase` - barang masuk dari supplier
  - `sale` - barang keluar terjual
  - `return_sale` - barang kembali dari customer
  - `return_purchase` - barang kembali ke supplier
  - `adjustment` - koreksi stok (plus/minus)
  - `damage` - barang rusak/hilang

### 2.1.4 Stock Calculation
- Current Stock = SUM(quantities dari stock_movements)
- Query real-time saat transaksi
- Cache untuk performance (opsional)

### 2.1.5 Stock Adjustment
- **Create Adjustment**
  - Pilih product
  - Input quantity adjustment (plus/minus)
  - Pilih adjustment type (physical count, damage, loss, dll)
  - Input alasan
  - Wajib approval (Manager/Owner)
  - Create stock_movement record type 'adjustment'

### 2.1.6 Stock Alert
- Alert jika stock < minimum stock level
- Alert jika stock > maximum stock level (overstock)
- Notifikasi di dashboard

### 2.1.7 Stock Opname
- **Create Stock Opname**
  - Pilih tanggal
  - Input physical count per product
  - System auto-calculate selisih
  - Auto-create adjustment records
  - Approval required

### 2.1.8 Product Classification
- **Categories Table**
  - id
  - name
  - parent_id (untuk subkategori)
  - level (1: divisi, 2: kategori, 3: subkategori)

- **Hierarchy:**
  - Divisi → Kategori → Subkategori → Brand → Produk

### 2.1.9 Inventory Reports
- Stock Report (current stock per product)
- Stock Movement Report (history per product)
- Low Stock Report (di bawah minimum)
- Overstock Report (di atas maximum)
- Dead Stock Report (tidak bergerak > 30 hari)

## 2.2 User Roles untuk Inventory
- **Gudang** - Input barang masuk, adjustment, stock opname
- **Manager** - Approval adjustment, view semua report
- **Owner** - Full access

---

# MODULE 3: HUTANG (ACCOUNTS)

## 3.1 Core Features

### 3.1.1 Customer Management
- **Create Customer**
  - Nama customer
  - Alamat
  - Telepon
  - Customer Group (retail, tukang, kontraktor, proyek)
  - Credit limit (maksimum hutang)
  - Payment terms (Net 7, Net 14, Net 30)
  - Default discount (berdasarkan group)

- **Edit Customer**
  - Update semua field
  - Audit log perubahan

### 3.1.2 Customer Group System
- **Customer Groups Table**
  - id
  - name (retail, tukang, kontraktor, proyek)
  - discount_percentage
  - credit_limit_default

### 3.1.3 Piutang Management (Accounts Receivable)
- **Piutang Records**
  - Auto-created saat transaksi credit
  - customer_id
  - invoice_id
  - amount
  - remaining_balance
  - due_date (berdasarkan payment terms)
  - status (pending, partial, paid, overdue)

- **Payment Recording**
  - Input payment untuk piutang
  - Partial payment atau full payment
  - Update remaining_balance
  - Catat payment date
  - Print receipt

- **Aging Report**
  - 0-30 days
  - 31-60 days
  - 61-90 days
  - >90 days

### 3.1.4 Hutang Supplier (Accounts Payable)
- **Hutang Records**
  - Auto-created saat purchase credit
  - supplier_id
  - purchase_order_id
  - amount
  - remaining_balance
  - due_date
  - status

- **Supplier Management**
  - Nama supplier
  - Alamat
  - Telepon
  - Payment terms
  - Credit limit

### 3.1.5 Credit Scoring (Basic)
- **Customer Credit Score**
  - Hitung berdasarkan:
    - Payment history (on-time vs late)
    - Average payment days
    - Number of overdue
  - Score: A (excellent), B (good), C (average), D (poor)

- **Credit Limit Adjustment**
  - Auto-suggest credit limit adjustment berdasarkan score
  - Manual approval oleh Manager/Owner

### 3.1.6 Reminder System
- **Basic Reminder**
  - List piutang yang due date hari ini
  - List piutang overdue
  - Manual reminder (bukan otomatis WA di MVP)

## 3.2 User Roles untuk Hutang
- **Kasir** - Input payment piutang
- **Accounting** - View aging report, credit scoring
- **Manager** - Approval credit limit adjustment
- **Owner** - Full access

---

# MODULE 4: USER & PERMISSION

## 4.1 Core Features

### 4.1.1 User Management
- **Create User**
  - Username
  - Password (hashed)
  - Full name
  - Role (Owner, Manager, Kasir, Gudang, Accounting)
  - Active/Inactive status

- **Edit User**
  - Update semua field kecuali username
  - Change password
  - Deactivate user

### 4.1.2 Role-Based Access Control (RBAC)
- **Roles:**
  - `owner` - Full access semua fitur
  - `manager` - Hampir full access, kecuali delete critical data
  - `kasir` - POS, view customer, input payment
  - `gudang` - Inventory, purchase, stock opname
  - `accounting` - Hutang, piutang, laporan

- **Permissions:**
  - create_sales
  - edit_sales
  - void_sales
  - view_profit
  - manage_products
  - stock_adjustment
  - approve_adjustment
  - manage_customers
  - manage_suppliers
  - record_payment
  - view_reports

### 4.1.3 Authentication
- Login dengan username/password
- Session timeout (30 menit idle)
- Password hashing (bcrypt)
- Login attempt limit (5 attempts = lock 15 menit)

---

# MODULE 5: REPORTING

## 5.1 Core Reports

### 5.1.1 Sales Reports
- Daily Sales Report
- Monthly Sales Report
- Sales by Product
- Sales by Customer
- Sales by Payment Method

### 5.1.2 Inventory Reports
- Current Stock Report
- Stock Movement Report
- Low Stock Report
- Overstock Report
- Dead Stock Report

### 5.1.3 Financial Reports
- Cashflow Report (kas masuk/keluar)
- Accounts Receivable Aging
- Accounts Payable Aging
- Profit/Loss (basic - revenue - COGS)

### 5.1.4 Export
- Export ke Excel (CSV/XLSX)
- Export ke PDF

---

# TECHNICAL REQUIREMENTS

## 6.1 Technology Stack

### Backend
- **Framework**: Laravel 10.x (PHP 8.1+)
- **Database**: MySQL 8.0+ / MariaDB 10.6+
- **Queue**: Database queue (built-in Laravel)
- **Cache**: File cache (Redis optional)

### Frontend
- **Framework**: Bootstrap 5.x
- **JS Library**: jQuery 3.x
- **Icons**: Bootstrap Icons atau FontAwesome
- **Chart**: Chart.js (untuk dashboard)

### Development Tools
- **Testing**: PHPUnit (built-in Laravel)
- **Code Quality**: PHPStan / Psalm (optional)
- **Version Control**: Git

## 6.2 Architecture Pattern

### MVC with Service Layer
```
Controller
↓
Service Layer (Business Logic)
↓
Repository (Data Access)
↓
Model (Eloquent)
↓
Database
```

### Key Principles
- **Single Responsibility**: Setiap class satu tanggung jawab
- **Dependency Injection**: Inject dependencies via constructor
- **Repository Pattern**: Abstraksi data access
- **Service Layer**: Business logic terpisah dari controller

## 6.3 Database Design

### Core Tables
- `users` - User accounts
- `roles` - Role definitions
- `permissions` - Permission definitions
- `role_user` - User-role mapping
- `permission_role` - Role-permission mapping

- `customers` - Customer data
- `customer_groups` - Customer groups
- `suppliers` - Supplier data

- `categories` - Product categories
- `products` - Product master data
- `product_units` - Multi satuan definition
- `barcodes` - Barcode per product/satuan

- `stock_movements` - Stock movement history (source of truth)

- `sales` - Sales header
- `sale_items` - Sales line items
- `sale_payments` - Payment records

- `purchase_orders` - Purchase header
- `purchase_items` - Purchase line items
- `purchase_payments` - Payment records

- `accounts_receivable` - Piutang records
- `accounts_payable` - Hutang records
- `payments` - Payment transactions

- `stock_adjustments` - Adjustment records
- `stock_opnames` - Stock opname records

- `audit_logs` - Audit trail

## 6.4 API Design

### RESTful API
- Base URL: `/api/v1`
- Authentication: Session-based (web) + Token (future mobile)
- Response Format: JSON

### Example Endpoints
```
POST   /api/v1/auth/login
GET    /api/v1/sales
POST   /api/v1/sales
GET    /api/v1/sales/{id}
PUT    /api/v1/sales/{id}
DELETE /api/v1/sales/{id}

GET    /api/v1/products
POST   /api/v1/products
GET    /api/v1/products/{id}

GET    /api/v1/stock
POST   /api/v1/stock/adjustments

GET    /api/v1/customers
POST   /api/v1/customers

GET    /api/v1/reports/sales/daily
GET    /api/v1/reports/inventory/low-stock
```

## 6.5 Security

### Authentication & Authorization
- Password hashing (bcrypt)
- CSRF protection (built-in Laravel)
- Session management
- Role-based access control

### Data Validation
- Request validation (Laravel Form Request)
- Input sanitization
- SQL injection prevention (Eloquent ORM)

### Audit Trail
- Log semua create/update/delete
- Catat user, timestamp, changes
- Immutable untuk critical transactions

## 6.6 Performance

### Optimization
- Database indexing (invoice_number, product_code, barcode, dates)
- Eager loading (avoid N+1 queries)
- Query caching
- Pagination untuk large datasets

### Target Performance
- POS transaction < 2 seconds
- Product search < 500ms
- Report generation < 5 seconds

---

# DELIVERABLES

## 7.1 Software
- [ ] Laravel application
- [ ] Database migration files
- [ ] Seeders untuk data awal
- [ ] API endpoints
- [ ] Frontend views (Blade templates)

## 7.2 Documentation
- [ ] Database schema (ERD)
- [ ] API documentation
- [ ] User manual
- [ ] Installation guide
- [ ] Deployment guide

## 7.3 Testing
- [ ] Unit tests untuk core logic
- [ ] Feature tests untuk critical flows
- [ ] Manual testing checklist

---

# SUCCESS CRITERIA

## 8.1 Functional
- [ ] Kasir bisa melakukan transaksi penjualan dalam < 2 menit
- [ ] Stok terupdate real-time saat transaksi
- [ ] Piutang tercatat dan bisa dilunasi
- [ ] Laporan penjualan harian bisa di-generate
- [ ] Stock opname bisa dilakukan

## 8.2 Non-Functional
- [ ] Aplikasi stabil tanpa crash saat 10+ concurrent users
- [ ] Data tidak hilang saat power failure (database transaction)
- [ ] Backup database bisa dilakukan
- [ ] Restore dari backup berhasil

---

# NEXT STEPS (PHASE 2)

Setelah MVP selesai dan stabil, Phase 2 akan mencakup:
- Accounting Engine (jurnal otomatis, neraca, laba rugi)
- Delivery System (surat jalan, tracking basic)
- AI Basic (reorder suggestion sederhana)
- Multi-warehouse support
- Advanced reporting

---

# NOTES

- **Scope Creep Prevention**: Jangan tambahkan fitur di luar MVP scope tanpa approval
- **User Feedback**: Kumpulkan feedback dari user lapangan selama development
- **Iterative Development**: Release early, release often, gather feedback, iterate
- **Focus on Stability**: Lebih baik fitur sedikit tapi stabil daripada banyak tapi buggy
