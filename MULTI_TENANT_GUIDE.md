# Multi-Tenant Guide - Panglong ERP

## Struktur Hierarki Multi-Tenant

### Konsep Penting

**Platform Owner vs Tenant Owner:**

- **Platform Owner (Anda)** = Pemilik aplikasi SaaS Panglong ERP
  - Mengelola platform, billing, support, monitoring
  - Tidak punya toko, hanya punya aplikasi
  - `tenant_id = NULL`
  - Role: Super Admin

- **Tenant Owner** = Pemilik toko panglong yang menyewa aplikasi
  - Mengelola toko/cabang/gudang mereka
  - `tenant_id = ID tenant mereka`
  - Role: Owner (level tenant)

### Diagram Hubungan

```
Platform (Panglong ERP SaaS)
└── Platform Owner (Super Admin, tenant_id = NULL)
    └── Mengelola billing, support, monitoring platform
    └── Akses ke semua tenants

Tenant (Penyewa Aplikasi - Pemilik Toko Panglong)
├── Tenant Owner (Role: Owner, tenant_id = ID tenant)
├── Branch (Cabang Toko) - OPSIONAL
│   └── Warehouse (Gudang di cabang)
│       └── User (Karyawan cabang)
└── Warehouse (Gudang langsung di bawah tenant) - OPSIONAL
    └── User (Karyawan gudang)

User (Karyawan)
├── tenant_id (OPSIONAL) - NULL untuk Platform Owner, ID untuk Tenant Owner/karyawan
├── branch_id (OPSIONAL) - jika ada cabang
├── role_id (WAJIB) - Super Admin, Owner, Manager, Kasir, dll
└── warehouse_id (OPSIONAL) - jika khusus gudang
```

### Tabel dan Field Penting

| Tabel | Field | Keterangan |
|-------|-------|------------|
| **tenants** | id, code, name | Data tenant (penyewa aplikasi) |
| **branches** | id, tenant_id, code, name | Cabang toko (opsional) |
| **warehouses** | id, tenant_id, branch_id, code, name | Gudang (opsional) |
| **users** | id, tenant_id, branch_id, role_id | User/karyawan |
| **roles** | id, name, slug, tenant_id, is_custom | Role: Owner, Manager, Kasir, dll |
| **permissions** | id, name, description, module | Permission granular |
| **user_permissions** | user_id, permission_id, granted_by | Direct permission ke user |

---

## Skenario Implementasi

### Skenario 1: Tenant Sederhana (TANPA Cabang & TANPA Gudang)

**Kondisi:**
- Toko kecil dengan 1 lokasi
- Tidak perlu tracking gudang terpisah
- Semua stok dikelola di 1 tempat

**Setup:**
```sql
-- Tenant
INSERT INTO tenants (code, name, subdomain, company_name, ...) 
VALUES ('HQ', 'Panglong HQ', 'hq', 'Panglong Material Bangunan', ...);

-- User (tanpa branch_id)
INSERT INTO users (tenant_id, username, password, full_name, role_id, branch_id)
VALUES (1, 'admin', '...', 'Administrator', 1, NULL);  -- Owner
INSERT INTO users (tenant_id, username, password, full_name, role_id, branch_id)
VALUES (1, 'kasir1', '...', 'Kasir 1', 3, NULL);  -- Kasir
```

**Data:**
- 1 tenant
- N user (semua branch_id = NULL)
- 0 branches
- 0 warehouses

---

### Skenario 2: Tenant DENGAN Gudang (TANPA Cabang)

**Kondisi:**
- Toko dengan 1 lokasi tapi punya gudang terpisah
- Perlu tracking stok di gudang vs toko
- User khusus untuk mengelola gudang

**Setup:**
```sql
-- Tenant
INSERT INTO tenants (code, name, ...) VALUES ('HQ', 'Panglong HQ', ...);

-- Warehouse (langsung di bawah tenant, branch_id = NULL)
INSERT INTO warehouses (tenant_id, branch_id, code, name, type)
VALUES (1, NULL, 'WH-MAIN', 'Gudang Utama', 'main');

-- User Owner (tanpa branch_id)
INSERT INTO users (tenant_id, username, role_id, branch_id)
VALUES (1, 'admin', 1, NULL);

-- User Gudang (tanpa branch_id, tapi terikat ke warehouse)
INSERT INTO users (tenant_id, username, role_id, branch_id)
VALUES (1, 'gudang1', 4, NULL);
```

**Data:**
- 1 tenant
- 1+ warehouses (branch_id = NULL)
- N user (semua branch_id = NULL)
- 0 branches

---

### Skenario 3: Tenant DENGAN Cabang & Gudang (Kompleks)

**Kondisi:**
- Toko besar dengan beberapa cabang
- Setiap cabang punya gudang sendiri
- Perlu tracking stok per cabang
- User khusus per cabang

**Setup:**
```sql
-- Tenant
INSERT INTO tenants (code, name, ...) VALUES ('HQ', 'Panglong HQ', ...);

-- Branch Cabang Bekasi
INSERT INTO branches (tenant_id, code, name, address)
VALUES (1, 'BKS', 'Cabang Bekasi', 'Jl. Bekasi Raya No. 1');

-- Warehouse di Cabang Bekasi
INSERT INTO warehouses (tenant_id, branch_id, code, name, type)
VALUES (1, 1, 'WH-BKS', 'Gudang Bekasi', 'branch');

-- User Owner (tanpa branch_id - akses semua cabang)
INSERT INTO users (tenant_id, username, role_id, branch_id)
VALUES (1, 'admin', 1, NULL);

-- User Manager Cabang Bekasi (terikat ke branch)
INSERT INTO users (tenant_id, username, role_id, branch_id)
VALUES (1, 'mgr_bekasi', 2, 1);

-- User Gudang Bekasi (terikat ke branch)
INSERT INTO users (tenant_id, username, role_id, branch_id)
VALUES (1, 'gudang_bekasi', 4, 1);
```

**Data:**
- 1 tenant
- N branches (masing-masing tenant_id = 1)
- N warehouses (masing-masing tenant_id = 1, branch_id = branch_id)
- N user (beberapa dengan branch_id, beberapa tanpa)

---

## Role-Based Access Control (RBAC)

### Konsep RBAC

**Prinsip Utama: Role adalah Opsional, Permission adalah Wajib**

- Role adalah template/grouping dari permission
- Tenant bisa menggunakan role yang ada, membuat role custom, atau langsung assign permission ke user
- Tidak ada role yang wajib ada di tenant
- Tenant bisa punya role berbeda dari tenant lain

### Struktur RBAC

```
Tenant
├── Roles (Template Permission)
│   ├── Role: Owner
│   │   └── Permissions: create_sales, edit_sales, delete_sales, view_reports, manage_users, ...
│   ├── Role: Manager
│   │   └── Permissions: create_sales, edit_sales, view_reports, ...
│   ├── Role: Kasir
│   │   └── Permissions: create_sales, view_products, ...
│   └── Role: Custom (bisa dibuat tenant)
│       └── Permissions: [custom sesuai kebutuhan]
│
└── Users
    ├── User A
    │   ├── Role: Owner (inherit permission dari role)
    │   └── Additional Permissions: [bisa tambah permission spesifik]
    ├── User B
    │   ├── Role: Kasir (inherit permission dari role)
    │   └── Additional Permissions: [bisa tambah permission spesifik]
    └── User C
        └── Direct Permissions: [tanpa role, permission langsung assign]
```

### Role yang Tersedia

| Role | Slug | Keterangan | tenant_id |
|------|------|------------|------------|
| Super Admin | super_admin | Platform Owner - Full access ke semua tenants | NULL |
| Owner | owner | Tenant Owner - Full access ke data tenant mereka | ID tenant |
| Manager | manager | Akses cabang/branch tertentu | ID tenant |
| Kasir | kasir | Transaksi penjualan | ID tenant |
| Gudang | gudang | Manajemen stok/gudang | ID tenant |
| Accounting | accounting | Keuangan & pembayaran | ID tenant |
| Supervisor | supervisor | Approval & oversight | ID tenant |

### Scope Akses Berdasarkan Role

| Role | Scope Akses | Keterangan |
|------|------------|------------|
| **Super Admin** | Semua tenants | Platform Owner - akses ke semua data semua tenants |
| **Owner** | Satu tenant | Tenant Owner - akses penuh ke data tenant mereka |
| **Manager** | Branch tertentu | Hanya akses data di branch yang ditugaskan |
| **Kasir** | Branch tertentu | Transaksi di branch yang ditugaskan |
| **Gudang** | Warehouse/Branch | Stok di warehouse/branch yang ditugaskan |
| **Accounting** | Satu tenant | Laporan keuangan level tenant |
| **Supervisor** | Satu tenant | Approval & audit level tenant |

### Fleksibilitas Role

**1. Role Adalah Opsional**

Tenant bisa:
- Pakai role standar (Owner, Manager, Kasir, dll)
- Buat role custom sesuai kebutuhan
- Tidak pakai role sama sekali (direct permission)

**2. Role Bisa Dibuat Custom per Tenant**

```php
// Tenant bisa membuat role custom
function createCustomRole($tenant_id, $role_data) {
    $db->execute("
        INSERT INTO roles (name, slug, description, tenant_id, is_custom)
        VALUES (?, ?, ?, ?, 1)
    ", [
        $role_data['name'],
        $role_data['slug'],
        $role_data['description'],
        $tenant_id
    ]);
    
    $role_id = $db->lastInsertId();
    
    // Assign permissions ke role custom
    foreach ($role_data['permissions'] as $permission_id) {
        $db->execute("
            INSERT INTO role_permission (role_id, permission_id)
            VALUES (?, ?)
        ", [$role_id, $permission_id]);
    }
}
```

**3. User Bisa Tanpa Role (Direct Permission)**

```php
// Assign permission langsung ke user tanpa role
function assignDirectPermissions($user_id, $permission_ids) {
    foreach ($permission_ids as $permission_id) {
        $db->execute("
            INSERT INTO user_permissions (user_id, permission_id)
            VALUES (?, ?)
        ", [$user_id, $permission_id]);
    }
}
```

### Permission Granular

Permission adalah unit terkecil akses:

| Permission | Keterangan |
|------------|------------|
| `create_sales` | Buat transaksi penjualan |
| `edit_sales` | Edit transaksi penjualan |
| `void_sales` | Void/batalkan transaksi |
| `view_sales` | Lihat transaksi |
| `create_products` | Tambah produk |
| `edit_products` | Edit produk |
| `delete_products` | Hapus produk |
| `view_products` | Lihat produk |
| `manage_stock` | Kelola stok |
| `stock_adjustment` | Adjustment stok |
| `approve_adjustment` | Approve adjustment |
| `manage_customers` | Kelola customer |
| `manage_suppliers` | Kelola supplier |
| `view_reports` | Lihat laporan |
| `view_profit` | Lihat laporan laba rugi |
| `manage_users` | Kelola user |
| `manage_roles` | Kelola role |
| `manage_settings` | Kelola pengaturan |
| `export_data` | Export data |
| `import_data` | Import data |

### Cara Tenant Owner Memberikan Hak Akses

**1. Melalui Role (Rekomendasi untuk Tenant dengan Banyak User)**
- Buat role → assign permission → assign role ke user

**2. Langsung ke User (Rekomendasi untuk Tenant Kecil)**
- Assign permission langsung ke user tanpa role

**3. Kombinasi Role + Direct Permission**
- Role base + permission tambahan

### Antisipasi Jika Role Tidak Ada

**Aplikasi Tidak Bergantung pada Role Spesifik**

```php
// JANGAN - bergantung pada role spesifik
if ($user['role'] === 'kasir') {
    // show kasir features
}

// GUNAKAN - bergantung pada permission
if (hasPermission($user_id, 'create_sales')) {
    // show sales feature
}
```

**Role Hanya sebagai Template**

Role untuk convenience, bukan requirement. User bisa tanpa role.

---

## Query Pattern untuk Multi-Tenant

### Filter Data Berdasarkan Tenant

```php
// Untuk Super Admin (Platform Owner, tenant_id = NULL)
// Akses ke semua tenants
if ($_SESSION['user']['role_slug'] === 'super_admin') {
    $sql = "SELECT * FROM sales";  // Akses semua data
}

// Untuk Tenant Owner (role = Owner, tenant_id = ID)
// Akses hanya ke data tenant mereka
if ($_SESSION['user']['role_slug'] === 'owner') {
    $tenant_id = $_SESSION['user']['tenant_id'];
    $sql = "SELECT * FROM sales WHERE tenant_id = $tenant_id";
}

// Untuk user dengan role Manager/Kasir/Gudang
// Filter berdasarkan branch_id user
$branch_id = $_SESSION['user']['branch_id'];

if ($branch_id) {
    // Filter data di branch ini
    $sql = "SELECT * FROM sales WHERE branch_id = $branch_id";
} else {
    // Akses semua branch di tenant ini
    $tenant_id = $_SESSION['user']['tenant_id'];
    $sql = "SELECT * FROM sales WHERE tenant_id = $tenant_id";
}
```

### Filter Stok Berdasarkan Warehouse

```php
// Super Admin - akses semua warehouse
if ($_SESSION['user']['role_slug'] === 'super_admin') {
    $sql = "SELECT * FROM stock_movements";
}

// User gudang hanya melihat stok di warehouse-nya
$warehouse_id = $_SESSION['user']['warehouse_id'];

if ($warehouse_id) {
    $sql = "SELECT * FROM stock_movements WHERE warehouse_id = $warehouse_id";
} else {
    // Akses semua warehouse di tenant/branch
    $tenant_id = $_SESSION['user']['tenant_id'];
    $sql = "SELECT * FROM stock_movements WHERE tenant_id = $tenant_id";
}
```

### Get User Permissions

```php
function getUserPermissions($user_id) {
    global $db;
    
    $permissions = [];
    
    // 1. Get dari role
    $stmt = $db->prepare("
        SELECT p.name 
        FROM permissions p
        INNER JOIN role_permission rp ON p.id = rp.permission_id
        INNER JOIN users u ON u.role_id = rp.role_id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $role_perms = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $permissions = array_merge($permissions, $role_perms);
    
    // 2. Get permission langsung
    $stmt = $db->prepare("
        SELECT p.name 
        FROM permissions p
        INNER JOIN user_permissions up ON p.id = up.permission_id
        WHERE up.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $direct_perms = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $permissions = array_merge($permissions, $direct_perms);
    
    return array_unique($permissions);
}

function hasPermission($user_id, $permission) {
    $permissions = getUserPermissions($user_id);
    return in_array($permission, $permissions);
}
```

---

## Migration yang Sudah Dijalankan

1. ✅ `tenant_id` ditambahkan ke tabel `users`
2. ✅ `tenant_id` ditambahkan ke tabel `branches`
3. ✅ `tenant_id` sudah ada di tabel `warehouses`
4. ✅ `branch_id` sudah ada di tabel `users`
5. ✅ `branch_id` sudah ada di tabel `warehouses`
6. ✅ Role `Super Admin` ditambahkan (ID: 7)
7. ✅ User admin dibuat sebagai Platform Owner (tenant_id = NULL, role_id = 7)

---

## Rekomendasi Implementasi

### Untuk Platform Owner (Anda) Saat Ini

**Status:**
- User: admin (Super Admin, tenant_id = NULL)
- Role: Super Admin - akses ke semua tenants
- Tidak punya toko, hanya mengelola platform

**Fungsi Platform Owner:**
- Membuat/menghapus tenants (penyewa aplikasi)
- Monitoring usage dan billing
- Support teknis untuk tenants
- Melihat analytics semua tenants

### Untuk Tenant (Penyewa Aplikasi)

**Mulai dengan Skenario 1 (Sederhana):**
- 1 tenant: Toko Panglong ABC
- User owner (Role: Owner, tenant_id = ID tenant)
- User karyawan (Manager, Kasir, dll) dengan tenant_id yang sama
- Tidak perlu branches/warehouses untuk awal

**Upgrade ke Skenario 2/3 jika:**
- Bisnis tenant berkembang dengan banyak lokasi
- Perlu tracking stok per gudang
- Perlu reporting per cabang

### Langkah Upgrade Tenant

1. Buat branch baru di tabel `branches` (tenant_id = ID tenant)
2. Update user yang terkait dengan `branch_id`
3. Buat warehouse di tabel `warehouses` (tenant_id = ID tenant, branch_id = ID branch)
4. Update data stok dengan `warehouse_id`
5. Update query untuk filter berdasarkan branch/warehouse

---

## Best Practices

### 1. Permission-based, Bukan Role-based

**Selalu cek permission, bukan role:**
```php
// BAD
if ($user['role'] === 'kasir') {
    showSalesForm();
}

// GOOD
if (hasPermission($user_id, 'create_sales')) {
    showSalesForm();
}
```

### 2. Role Hanya sebagai Template

Role untuk convenience, bukan requirement. User bisa tanpa role.

### 3. Granular Permission

Permission harus spesifik, bukan generik:
- `create_sales` (good)
- `manage_sales` (too broad)

### 4. Audit Log untuk Permission Changes

```php
function assignPermission($user_id, $permission_id, $granted_by) {
    global $db;
    
    $db->execute("
        INSERT INTO user_permissions (user_id, permission_id, granted_by, granted_at)
        VALUES (?, ?, ?, ?)
    ", [$user_id, $permission_id, $granted_by, date('Y-m-d H:i:s')]);
    
    // Log audit
    logAudit([
        'action' => 'permission_granted',
        'user_id' => $user_id,
        'permission_id' => $permission_id,
        'granted_by' => $granted_by
    ]);
}
```

### 5. Cache Permission Check

Permission check sering dilakukan, gunakan cache:
```php
function getUserPermissions($user_id) {
    $cache_key = "user_perms:{$user_id}";
    $cached = $cache->get($cache_key);
    
    if ($cached !== null) {
        return json_decode($cached, true);
    }
    
    $permissions = fetchUserPermissionsFromDB($user_id);
    $cache->set($cache_key, json_encode($permissions), 3600);
    
    return $permissions;
}
```

---

## Summary

### Fleksibilitas Role:
- Role adalah opsional, bukan wajib
- Tenant bisa membuat role custom
- User bisa tanpa role (direct permission)
- Role hanya sebagai template permission

### Cara Memberikan Hak Akses:
1. **Melalui Role** (rekomendasi untuk banyak user)
   - Buat role → assign permission → assign role ke user
2. **Langsung ke User** (rekomendasi untuk tenant kecil)
   - Assign permission langsung ke user tanpa role
3. **Kombinasi**
   - Role base + permission tambahan

### Antisipasi Role Tidak Ada:
- Aplikasi cek permission, bukan role
- Role hanya convenience, bukan requirement
- User tanpa role tetap bisa punya permission
- Fallback untuk user tanpa permission

### Tenant Owner Full Access:
- Assign role "Owner" (semua permission)
- Atau assign semua permission langsung
- Atau gunakan flag "is_owner" untuk bypass
