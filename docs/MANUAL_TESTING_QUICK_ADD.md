# Manual Testing Guide - Quick Add Functionality

## Test Environment
- URL: http://localhost/panglong/
- Login: admin / password123

---

## Quick Add Tests

### 1. Quick Add Brand
1. Login sebagai owner/manager
2. Buka halaman Products
3. Klik "Tambah Produk"
4. Klik tombol quick add (icon +) di samping dropdown Brand
5. Masukkan nama brand, klik simpan
6. Verifikasi brand baru muncul di dropdown

### 2. Quick Add Category
1. Di form Tambah Produk
2. Klik tombol quick add di samping dropdown Category
3. Masukkan nama kategori, klik simpan
4. Verifikasi kategori baru muncul di dropdown

### 3. Quick Add Unit
1. Di form Tambah Produk
2. Klik tombol quick add di samping dropdown Unit
3. Masukkan kode dan nama satuan, klik simpan
4. Verifikasi satuan baru muncul di dropdown

### 4. Quick Add Customer Group
1. Buka halaman Customers
2. Klik "Tambah Pelanggan"
3. Klik tombol quick add di samping dropdown Group
4. Masukkan nama group, klik simpan
5. Verifikasi group baru muncul di dropdown

---

## Master Catalog Import Test

1. Login sebagai tenant (bukan super admin)
2. Buka halaman Products
3. Klik "Import dari Master Catalog"
4. Cari produk (misal: "semen")
5. Klik "Import" pada salah satu produk
6. Verifikasi produk muncul di katalog tenant
7. Verifikasi produk dapat digunakan di Sales

---

## Notes

- Quick add menggunakan `fetch(API_URL + '?endpoint=...')` pattern
- CSRF token otomatis ditambahkan oleh `config.php`
- Response format: `{ success: true, data: {...} }`
