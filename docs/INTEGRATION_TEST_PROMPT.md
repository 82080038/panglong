# INTEGRATION TEST PROMPT — Panglong ERP

## Version: 1.0 (2026-06-30)

> Prompt ini untuk menguji integrasi FE-BE menyeluruh pada seluruh halaman dan menu aplikasi.
> Jalankan setiap langkah, dokumentasikan hasil, dan perbaiki bug yang ditemukan.

---

## Tujuan

1. Memastikan setiap halaman di `frontend/*.php` dapat dimuat tanpa error
2. Memastikan setiap menu navigasi dapat diakses sesuai role
3. Memastikan setiap endpoint AJAX di `ajax.php` merespons dengan benar
4. Memastikan setiap form submission (POST) berfungsi
5. Memastikan tidak ada error di browser console
6. Memastikan tidak ada error di server log (`error_log`)

---

## Lingkungan Uji

- **Base URL:** `http://localhost/panglong/frontend`
- **PHP:** `/opt/lampp/bin/php` (8.2.12)
- **Database:** `/opt/lampp/htdocs/panglong/database/database.sqlite`
- **SUDO password:** `8208`
- **Test users:**
  - admin / password123 (Owner)
  - manager1 / password123 (Manager)
  - kasir1 / password123 (Kasir)
  - gudang1 / password123 (Gudang)
  - accounting1 / password123 (Accounting)
  - supervisor1 / password123 (Supervisor)

---

## Daftar Halaman Frontend (49 file)

### Halaman Autentikasi (2)
- `login.php` — Halaman login
- `register.php` — Registrasi tenant baru
- `logout.php` — Logout

### Halaman Utama (1)
- `index.php` — Dashboard

### Master Data (8)
- `products.php` — Produk
- `customers.php` — Pelanggan
- `suppliers.php` — Supplier
- `warehouses.php` — Gudang
- `product_detail.php` — Detail produk
- `customer_detail.php` — Detail pelanggan
- `sale_detail.php` — Detail penjualan
- `users.php` — User management

### Penjualan (6)
- `sales.php` — POS / transaksi
- `sales_orders.php` — Sales order
- `quotations.php` — Penawaran
- `deliveries.php` — Pengiriman
- `returns.php` — Retur
- `whatsapp.php` — WhatsApp
- `salesman_app.php` — Salesman app
- `print_nota.php` — Cetak nota
- `qr_generator.php` — QR generator

### Inventory (7)
- `stock.php` — Stok
- `stock_opname.php` — Opname
- `stock_transfers.php` — Mutasi stok
- `batches.php` — Batch/FIFO
- `reorder.php` — Reorder AI
- `iot.php` — IoT monitoring
- `purchase-orders.php` — Purchase order

### Logistik (3)
- `fleet.php` — Kendaraan
- `routes.php` — Rute pengiriman
- `landed_cost.php` — Landed cost

### Keuangan (7)
- `accounting.php` — Akuntansi
- `cashbook.php` — Kas buku
- `cash_flow.php` — Arus kas
- `fixed_assets.php` — Aset tetap
- `e_faktur.php` — e-Faktur
- `closing.php` — Tutup buku
- `reports.php` — Laporan

### AI & Marketplace (3)
- `ai_insights.php` — AI insights
- `marketplace.php` — Marketplace
- `pricing.php` — Harga

### Pengaturan & SaaS (3)
- `settings.php` — Konfigurasi
- `saas.php` — SaaS management
- `tenants.php` — Tenant management

---

## Daftar Endpoint AJAX (59 endpoint)

```
products, master-products, categories, brands, product-units, barcode-lookup,
sales-price, customers, customer-groups, customer-prices, suppliers,
supplier-price-history, warehouses, warehouse-locations, sales, sale-payment,
sales-orders, sales-returns, purchase-orders, purchase-returns, quotations,
deliveries, partial-deliveries, stock, stock-adjustments, stock-transfers,
stock-valuation-fifo, product-batches, landed-cost, vehicles, vehicle-maintenance,
delivery-routes, delivery-methods, cash-transactions, bank-statements,
fixed-assets, e-faktur, e-faktur-types, cash-flow, period-closings, check-period-locked,
reports, marketplace, whatsapp-templates, whatsapp-messages, whatsapp-template-types,
tier-prices, settings, users, tenants, subscriptions, subscription-invoices,
saas-revenue, branches, payment-methods, adjustment-types, unit-measurements,
tax-rates, status-codes
```

---

## Prosedur Uji

### 1. Pre-flight Checks

```bash
# Syntax check semua file PHP
for f in frontend/*.php; do /opt/lampp/bin/php -l "$f"; done

# Check error log kosong (atau hanya error lama)
echo "8208" | sudo -S tail -50 /opt/lampp/logs/error_log

# Cek permission database
ls -la database/database.sqlite
chmod 666 database/database.sqlite && chmod 777 database/
```

### 2. Page Load Test (per role)

Untuk setiap role, login dan cek setiap halaman yang diizinkan:

```bash
# Login sebagai role X
ROLE=admin
curl -s -c /tmp/c_${ROLE}.txt -L -X POST http://localhost/panglong/frontend/login.php \
  -d "username=${ROLE}&password=password123" -o /dev/null

# Cek halaman
for page in products customers suppliers warehouses sales sales_orders quotations deliveries purchase-orders stock stock_opname stock_transfers batches reorder iot fleet routes accounting cashbook cash_flow fixed_assets e_faktur closing reports ai_insights marketplace landed_cost pricing settings saas users tenants returns whatsapp salesman_app; do
  code=$(curl -s -o /dev/null -w "%{http_code}" -b /tmp/c_${ROLE}.txt "http://localhost/panglong/frontend/$page.php")
  echo "${ROLE} ${page}: ${code}"
done
```

Ulangi untuk semua role:
- `admin`, `manager1`, `kasir1`, `gudang1`, `accounting1`, `supervisor1`

### 3. Menu Navigation Test (per role)

Buka browser/Playwright, login per role, dan klik setiap menu:
- Pastikan dropdown menu terbuka
- Pastikan setiap submenu mengarah ke halaman yang benar
- Pastikan halaman aktif memiliki class `active` di nav

### 4. AJAX Endpoint Test

```bash
# Login sebagai admin
curl -s -c /tmp/c_admin.txt -L -X POST http://localhost/panglong/frontend/login.php \
  -d "username=admin&password=password123" -o /dev/null

# Test GET endpoint
curl -s -b /tmp/c_admin.txt "http://localhost/panglong/frontend/ajax.php?endpoint=products&test_mode=true"

# Test POST endpoint (contoh: create product)
curl -s -b /tmp/c_admin.txt -X POST "http://localhost/panglong/frontend/ajax.php?endpoint=products&test_mode=true" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test Product","code":"TEST-001","category_id":1,"unit_id":1,"buy_price":1000,"sell_price":1500}'
```

Lakukan untuk semua endpoint dengan payload minimal. Dokumentasikan yang mengembalikan `success: false` atau HTTP != 200.

### 5. Form Submission Test

Untuk setiap halaman yang memiliki form POST (bukan AJAX):
- Buka halaman
- Isi form dengan data valid
- Submit
- Verifikasi redirect atau pesan sukses
- Verifikasi data tersimpan di database

Form POST yang perlu diuji:
- `stock_opname.php` — create/approve/delete opname
- `register.php` — register tenant
- `login.php` — login
- `settings.php` — update settings
- `e_faktur.php` — create e-Faktur
- `closing.php` — period closing

### 6. Browser Console Test

Jalankan Playwright untuk setiap halaman dan tangkap console error:

```javascript
// Pattern test
const consoleErrors = [], pageErrors = [];
page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
page.on('pageerror', err => pageErrors.push(err.message));
await page.goto('http://localhost/panglong/frontend/PAGE.php');
expect(consoleErrors).toEqual([]);
expect(pageErrors).toEqual([]);
```

### 7. Full Playwright Test Suite

```bash
npx playwright test --reporter=list --workers=1
```

---

## Checklist Hasil

### Halaman (49)
- [ ] Semua halaman dimuat HTTP 200
- [ ] Tidak ada PHP fatal error di server log
- [ ] Tidak ada JS error di console

### Menu (per role)
- [ ] Owner dapat mengakses semua menu
- [ ] Manager dapat mengakses semua menu kecuali SaaS
- [ ] Kasir hanya dapat mengakses menu penjualan
- [ ] Gudang hanya dapat mengakses menu inventory
- [ ] Accounting hanya dapat mengakses menu keuangan
- [ ] Supervisor dapat mengakses dashboard dan reports
- [ ] Super admin dapat mengakses platform menu

### AJAX (59 endpoint)
- [ ] Semua endpoint GET merespons JSON
- [ ] Semua endpoint POST dengan data valid merespons `success: true`
- [ ] Semua endpoint memiliki permission check
- [ ] Tidak ada error SQL/PDO

### Form (POST halaman)
- [ ] Stock opname create/approve/delete berfungsi
- [ ] Register tenant berfungsi
- [ ] Login berfungsi
- [ ] Settings update berfungsi
- [ ] e-Faktur create berfungsi

### Database
- [ ] Tenant isolation berfungsi
- [ ] Multi-tenant UNIQUE constraints tidak konflik
- [ ] Master catalog `(tenant_id = ? OR tenant_id IS NULL)` berfungsi

---

## Exit Criteria

1. **Zero critical bugs** — No crashes, data loss, security vulnerabilities
2. **All pages load** — 49 halaman tanpa error
3. **All menus accessible** — Sesuai role permission
4. **All AJAX endpoints work** — 59 endpoint merespons benar
5. **All forms work** — POST submission berhasil
6. **All tests passing** — 88/88 Playwright tests
7. **Error log clean** — Tidak ada error baru

---

## Dokumentasi

Setiap bug yang ditemukan harus didokumentasikan:

```markdown
### Bug #{N}
- **Halaman/Endpoint:** ...
- **Role:** ...
- **Langkah reproduce:** ...
- **Error:** ...
- **Root cause:** ...
- **Fix:** ...
- **Status:** fixed / pending
```

Simpan ke `docs/development-iteration-4.md`.
