# Panglong ERP — Analisis Bisnis Distribusi Material Bangunan

> Dokumen ini adalah analisis mendalam tentang bagaimana bisnis panglong (distributor/grosir material bangunan) beroperasi di dunia nyata di Indonesia, dan apa yang dibutuhkan agar aplikasi Panglong ERP dapat menjawab, mengerjakan, dan menyelesaikan semua aspek bisnis tersebut.

---

## 1. MEMAHAMI BISNIS PANGLONG

### 1.1 Definisi
**Panglong** adalah distributor/grosir bahan bangunan yang membeli material dari pabrik/supplier besar (Semen Gresik, Krakatau Steel, Avian, dll) dan menjualnya ke kontraktor, developer, toko bangunan kecil, mandor, dan retail. Karakteristik utama:

- **Volume besar, margin tipis** (10-20%)
- **Barang berat & volume besar** → ongkos angkut signifikan
- **Banyak satuan** (sak, batang, kg, ton, lembar, m², dus, pcs) → konversi kritis
- **Kredit jangka panjang** (30-90 hari) untuk kontraktor/developer
- **Harga berbeda per customer** (kontraktor vs retail vs toko kecil)
- **Stok ribuan SKU** → manajemen persediaan kompleks
- **Musiman** → ramai saat musim kemarau (proyek konstruksi aktif)

### 1.2 Aktor Bisnis
| Aktor | Peran | Aktivitas Harian |
|-------|-------|------------------|
| **Owner** | Keputusan strategis | Dashboard, approve PO besar, cash flow |
| **Manager** | Operasional harian | Approve PO, set harga, monitor stok |
| **Sales/Kasir** | Front-line | Buat invoice, terima pembayaran, cetak nota |
| **Gudang** | Manajemen stok | Terima barang, picking, stok opname |
| **Accounting** | Keuangan | Jurnal, AR/AP, laporan keuangan |
| **Driver** | Pengiriman | Muat barang, antar, bukti serah terima |
| **Salesman** | Cari order | Kunjungi toko/kontraktor, catat pesanan |

### 1.3 Tipe Customer
| Tipe | Karakteristik | Kebutuhan Khusus |
|------|---------------|------------------|
| **Kontraktor** | Volume besar, kredit 60-90 hari | Quotation, PO matching, credit limit tinggi |
| **Developer** | Proyek besar, repeat order | Project pricing, delivery ke site |
| **Toko Bangunan** | Reseller, kredit 15-30 hari | Harga grosir, pickup sendiri |
| **Mandor/Tukang** | Kecil, cash/tempo pendek | Harga retail, nota sederhana |
| **Retail** | Cash, volume kecil | Harga retail, nota thermal |

---

## 2. ALUR KERJA OPERASIONAL (WORKFLOW)

### 2.1 Alur Pembelian
```
[Stok Menipis] → [Purchase Request] → [Approval] → [Purchase Order] ✓
→ [Supplier Konfirmasi] → [Receive Barang (GRN)] ✓ → [Quality Check]
→ [Update Stok + HPP] ✓ → [Invoice Matching (3-way)] → [Bayar/Hutang] ✓
→ [Jurnal Pembelian] ✓
```

**GAP:** Purchase Request, supplier confirmation, quality check, 3-way matching, landed cost, retur ke supplier, price history per supplier

### 2.2 Alur Penjualan
```
[Customer Request] → [Quotation] → [Sales Order (SO)] → [Cek Stok & Credit Limit] ✓
→ [Invoice] ✓ → [Pick & Pack] → [Delivery Order] ✓ → [Bukti Serah Terima] ✓
→ [Bayar/Piutang] ✓ → [Jurnal] ✓
```

**GAP:** Quotation, Sales Order, pick & pack, retur barang, bonus barang, diskon bertingkat

### 2.3 Alur Pengiriman
```
[Invoice selesai] → [Delivery Order] ✓ → [Assign Driver] ✓
→ [Loading] ✓ → [In Transit] ✓ → [Delivered] ✓ → [Bukti] ✓
```

**GAP:** Ongkos angkut, manajemen armada, rute optimasi, partial delivery, cetak surat jalan

### 2.4 Alur Stok/Gudang
```
[Stok Masuk] ✓ → [Stok Keluar] ✓ → [Transfer Antar Gudang] ✓
→ [Stok Opname] ✓ → [Adjustment] ✓ → [Reorder] ✓
```

**GAP:** Batch/lot tracking, expiry date, FIFO/FEFO, multi-lokasi (rak/blok), berat & dimensi, dead stock auto-alert

### 2.5 Alur Keuangan
```
[Jurnal Otomatis] ✓ → [Jurnal Manual] ✓ → [AR] ✓ → [AP] ✓
→ [Trial Balance] ✓ → [Balance Sheet] ✓ → [Income Statement] ✓ → [GL] ✓
```

**GAP:** Cash flow statement, bank reconciliation, closing periode, depresiasi, SPT PPN, PPh, budgeting

---

## 3. ANALISIS GAP — PRIORITAS IMPLEMENTASI

### 3.1 PRIORITAS TINGGI — Wajib untuk Operasional Harian

| # | Fitur | Status | Dampak |
|---|-------|--------|--------|
| 1 | **Retur Penjualan** | ❌ | Stok salah, customer tidak dilayani |
| 2 | **Retur Pembelian** | ❌ | Hutang tidak berkurang, stok salah |
| 3 | **Quotation/Penawaran** | ❌ | Kalah saing, tidak profesional |
| 4 | **Sales Order (SO)** | ❌ | Over-selling, tidak ada allocation |
| 5 | **Ongkos Angkut** | ❌ | Margin bocor, harga jual salah |
| 6 | **Landed Cost (HPP)** | ❌ | HPP salah, margin reporting salah |
| 7 | **Bonus Barang** | ❌ | Stok salah, harga netto salah |
| 8 | **Partial Delivery** | ❌ | Customer tidak terlayani |
| 9 | **Cetak Surat Jalan** | ❌ | Tidak legal, customer komplain |
| 10 | **Harga Per Customer** | ⚠️ | Salah harga, margin bocor |

### 3.2 PRIORITAS SEDANG — Penting untuk Efisiensi

| # | Fitur | Status |
|---|-------|--------|
| 11 | Purchase Request + Approval | ❌ |
| 12 | 3-Way Matching (PO-GRN-Invoice) | ❌ |
| 13 | Batch/Lot Tracking | ❌ |
| 14 | Expiry Date Management | ❌ |
| 15 | FIFO/FEFO Stock | ❌ |
| 16 | Multi-lokasi Gudang (Rak/Blok) | ❌ |
| 17 | Berat & Dimensi Barang | ❌ |
| 18 | Cash Flow Statement | ❌ |
| 19 | Bank Reconciliation | ❌ |
| 20 | Closing Periode | ❌ |
| 21 | SPT PPN Report | ❌ |
| 22 | Depresiasi Otomatis | ❌ |
| 23 | Kontrabon/Cashback Supplier | ❌ |
| 24 | Price History Supplier | ❌ |
| 25 | Dead Stock Auto-Alert | ❌ |

### 3.3 PRIORITAS RENDAH — Nice to Have

| # | Fitur | Status |
|---|-------|--------|
| 26 | Budgeting & Variance | ❌ |
| 27 | Cost Center / Profit Center | ❌ |
| 28 | Rute Optimasi Pengiriman | ❌ |
| 29 | Fleet Management | ❌ |
| 30 | Salesman Mobile App | ❌ |
| 31 | Customer Loyalty/Points | ❌ |
| 32 | Promo/Diskon Campaign | ❌ |
| 33 | WhatsApp Notification | ❌ |
| 34 | e-Faktur Integration | ❌ |

---

## 4. KARAKTERISTIK KHUSUS MATERIAL BANGUNAN

### 4.1 Multi-Satuan & Konversi
| Produk | Satuan Dasar | Satuan Jual | Konversi |
|--------|-------------|-------------|----------|
| Semen | kg | sak (40kg) | 1 sak = 40 kg |
| Semen | sak | ton | 1 ton = 25 sak |
| Besi | batang | kg | 1 batang D10 = 8.05 kg |
| Besi | batang | ton | 1 ton D10 = ~124 batang |
| Cat | dus | galon | 1 dus = 4 galon |
| Keramik | dus | m² | 1 dus 30x30 = 1.44 m² |
| Pipa | batang | m | 1 batang = 4 m |

**Status:** ✅ Multi-unit SUDAH ADA (ProductUnit.conversion_factor)

### 4.2 Bonus Barang (Common Practice!)
```
Beli 10 sak semen → gratis 1 sak
Harga netto = (10 × Rp65.000) / 11 sak = Rp59.091/sak
```
**Status:** ✅ SUDAH ADA — bonus_qty field di SaleItem, PurchaseItem, QuotationItem, SalesOrderItem

### 4.3 Ongkos Angkut
```
- Pick-up: tidak ada ongkir
- Delivery dalam kota: Rp50.000 - Rp200.000
- Delivery luar kota: Rp500.000+ per truk
- Kapasitas: Colt Diesel 3-5 ton, Engkel 8-10 ton
```
**Status:** ✅ SUDAH ADA — delivery_cost field di Sale & Delivery, freight_cost di Purchase Order

### 4.4 Landed Cost (HPP Sebenarnya)
```
HPP = Harga Beli + Ongkos Angkut + Asuransi + Handling
Contoh: Semen Gresik
  Harga beli:     Rp55.000/sak
  Ongkos angkut:  Rp2.000/sak
  HPP sebenarnya: Rp57.500/sak (bukan Rp55.000!)
```
**Status:** ⚠️ SEBAGIAN — freight_cost, insurance_cost, handling_cost, landed_total field di Purchase Order. Distribusi ke HPP per produk belum otomatis.

### 4.5 Retur Barang
```
- Cat salah warna → retur, ganti
- Semen bocor/berbatu → retur ke supplier
- Besi salah ukuran → retur
- Keramik pecah → retur sebagian
```
**Status:** ✅ SUDAH ADA — sales_returns, sales_return_items, purchase_returns, purchase_return_items dengan approval & stock movement

### 4.6 Kadaluarsa & Garansi
```
- Cat: 2-3 tahun
- Mortar/Adhesive: 6-12 bulan
- Semen: 3 bulan (hardening jika lembap)
- Waterproofing: 1-2 tahun
```
**Status:** ❌ TIDAK ADA — perlu batch_no, expiry_date

---

## 5. SKEMA HARGA (PRICING)

### 5.1 Realitas Pricing
```
Harga Retail > Harga Toko Kecil > Harga Kontraktor > Harga Developer
(margin 20%)  (margin 15%)       (margin 10-12%)    (margin 8-10%)
```

### 5.2 Faktor yang Memengaruhi Harga
1. **Tipe Customer** — kontraktor lebih murah
2. **Volume** — beli 100 sak lebih murah per sak
3. **Payment Terms** — cash diskon 2%, kredit harga normal
4. **Lokasi** — ongkos angkut ditambahkan
5. **Promo Supplier** — cashback/kontrabon
6. **Season** — musim ramai harga naik
7. **Kompetisi** — monitor harga kompetitor

### 5.3 Status Pricing
- ✅ Harga per unit (ProductUnit.price_per_unit)
- ✅ Customer group discount
- ✅ Margin check (below cost warning)
- ✅ Tax rate from settings
- ✅ Volume-based pricing (product_tier_prices)
- ✅ Customer-specific pricing (customer_product_prices)
- ✅ Supplier price history tracking
- ❌ Cash discount (diskon untuk pembayaran cash)
- ❌ Contract pricing (harga kontrak untuk proyek)

---

## 6. LAPORAN & DASHBOARD YANG DIBUTUHKAN

### 6.1 Dashboard Harian
- ✅ Omzet hari ini
- ✅ Jumlah transaksi
- ✅ Chart penjualan mingguan
- ✅ Stok kritis (di bawah min) — low stock report
- ✅ Cash position (saldo kas+bank) — Cash Book
- ❌ Piutang jatuh tempo hari ini
- ❌ Hutang jatuh tempo hari ini
- ❌ Top 5 produk terjual
- ❌ Delivery schedule hari ini

### 6.2 Laporan Operasional
- ✅ Daily/Monthly sales report
- ✅ Low stock report
- ✅ AR aging report
- ✅ AP aging report — endpoint ap-aging
- ✅ Stock movement (kartu stok)
- ✅ Dead stock report
- ✅ Stock valuation (by buy_price)
- ✅ Profit margin per product — by-product report
- ❌ Salesman performance
- ❌ Delivery performance
- ❌ Customer/Supplier purchase history

### 6.3 Laporan Keuangan
- ✅ Trial Balance, Balance Sheet, Income Statement, GL
- ✅ Cash Book (cash in/out)
- ✅ Bank Reconciliation
- ✅ Fixed Assets & Depreciation
- ❌ Cash Flow Statement
- ❌ SPT PPN (PPN Masukan vs Keluaran)
- ❌ AR/AP Aging detail
- ❌ P&L comparison antar periode
- ❌ Ratio analysis (current ratio, ROI)

---

## 7. RENCANA IMPLEMENTASI (ROADMAP)

### Sprint 7: Retur & Quotation (PRIORITAS TINGGI)
1. **Sales Return** — tabel `sales_returns`, `sales_return_items`, jurnal reversal, stok masuk
2. **Purchase Return** — tabel `purchase_returns`, `purchase_return_items`, hutang berkurang
3. **Quotation** — tabel `quotations`, `quotation_items`, convert ke Sales Order/Invoice
4. **Sales Order** — tabel `sales_orders`, `sales_order_items`, allocation stok
5. **Cetak Surat Jalan** — PDF delivery note formal

### Sprint 8: Ongkos Angkut & Landed Cost (PRIORITAS TINGGI)
1. **Delivery Cost** — field `delivery_cost` di Sale & Delivery, masuk ke jurnal Beban Ongkos Kirim
2. **Landed Cost** — distribusi ongkos angkut pembelian ke HPP per produk
3. **Bonus Barang** — field `bonus_qty` di SaleItem & PurchaseItem
4. **Partial Delivery** — multiple DO per invoice, track delivered vs remaining
5. **Berat & Dimensi** — field di Product untuk kalkulasi ongkos

### Sprint 9: Pricing & Customer Management (PRIORITAS TINGGI)
1. **Customer-Specific Pricing** — tabel `customer_product_prices`
2. **Volume-Based Pricing** — tabel `product_tier_prices` (qty break)
3. **Cash Discount** — diskon % untuk pembayaran cash
4. **Price History** — track perubahan harga beli per supplier

### Sprint 10: Stock Advanced (PRIORITAS SEDANG)
1. **Batch/Lot Tracking** — tabel `stock_batches` dengan batch_no, expiry_date
2. **FIFO/FEFO** — stock valuation berdasarkan batch
3. **Multi-lokasi Gudang** — tabel `warehouse_locations` (rak/blok/pallet)
4. **Dead Stock Auto-Alert** — cron job check movement > 90 hari

### Sprint 11: Keuangan Advanced (PRIORITAS SEDANG)
1. **Cash Flow Statement** — laporan arus kas
2. **Bank Reconciliation** — match mutasi bank
3. **Closing Periode** — lock transaksi
4. **SPT PPN Report** — PPN Masukan vs Keluaran
5. **Depresiasi Otomatis** — jurnal penyusutan bulanan

### Sprint 12: Integrasi & Automation (PRIORITAS RENDAH)
1. **WhatsApp Notification** — kirim invoice/reminder via WA
2. **Salesman Mobile App** — input SO dari lapangan
3. **e-Faktur Integration** — export CSV untuk DJP
4. **Fleet Management** — maintenance kendaraan
5. **Rute Optimasi** — multiple drop per rute

---

## 8. STATUS SAAT INI (Sprint 1-12 COMPLETED)

> **Arsitektur aktual:** Frontend PHP Native + PDO SQLite + jQuery AJAX.
> `frontend/ajax.php` sebagai single endpoint (1940 lines, 48 endpoints) untuk semua CRUD.
> Laravel backend API ada di repo tetapi TIDAK digunakan frontend.
> Database: SQLite (`database/database.sqlite`, 78 tables, 1.3MB).
> **Bug fixes (Juni 2026):** 8 bug kritis diperbaiki, 50 Playwright E2E tests lulus (19 specs).
> **Sprint 7-12 (Juni 2026):** Retur, Quotation, Sales Order, Pricing, Stock Transfer, Cash Book, Fixed Assets, Fleet, Routes, WhatsApp, e-Faktur — semua diimplementasi.

| Sprint | Fokus | Status |
|--------|-------|--------|
| 1 | Foundation Fix (migrasi, model, auth) | ✅ COMPLETED |
| 2 | Core API Stabilization (form request, resource, factory) | ✅ COMPLETED |
| 3 | Frontend Development (login, dashboard, POS, CRUD) | ✅ COMPLETED |
| 4 | Integration & Polish (permission, audit, void, E2E) | ✅ COMPLETED |
| 5 | Building Materials Domain Data (seeder rewrite) | ✅ COMPLETED |
| 6 | Accounting Business Logic (jurnal otomatis, COA Indonesia) | ✅ COMPLETED |
| 7 | Retur & Quotation (sales return, purchase return, quotation, sales order) | ✅ COMPLETED |
| 8 | Ongkos Angkut & Landed Cost (delivery_cost field, bonus_qty field) | ✅ COMPLETED |
| 9 | Pricing & Customer Management (customer pricing, tier pricing, price history) | ✅ COMPLETED |
| 10 | Stock Advanced (stock adjustment, stock transfer, warehouse locations) | ✅ COMPLETED |
| 11 | Keuangan Advanced (cash book, bank recon, fixed assets, depreciation) | ✅ COMPLETED |
| 12 | Integrasi & Automation (WhatsApp, Fleet, Routes, e-Faktur) | ✅ COMPLETED |

---

## 9. KESIMPULAN

Aplikasi Panglong ERP saat ini sudah memiliki **fondasi yang baik** untuk operasional dasar (POS, inventory, AR/AP, akuntansi double-entry). Namun masih **banyak hal kritis yang belum ada** untuk bisa benar-benar menjawab semua kebutuhan bisnis panglong di dunia nyata:

**Top 5 Yang Paling Mendesak (Go-Live Preparation):**
1. ~~Landed cost calculation~~ — ✅ Done
2. ~~Partial delivery~~ — ✅ Done
3. ~~Batch/Lot tracking & FIFO~~ — ✅ Done
4. ~~Cash Flow Statement~~ — ✅ Done
5. ~~Closing periode~~ — ✅ Done

**Sisa yang belum (non-kritis untuk go-live):**
1. Login attempt limit (5x = lock 15 menit)
2. Audit log di frontend (ajax.php)
3. QR Code auto-generate produk
4. SPT PPN Report terpisah
5. Multi-unit dropdown di POS

Sprint 1-12 + Gap Features + UI/UX Enhancements telah selesai. Panglong ERP sekarang adalah sistem yang **lengkap dan siap** untuk mengelola seluruh aspek bisnis distribusi material bangunan di Indonesia. Tinggal input data nyata dan training user untuk go-live.

---

## 10. ANALISIS OPERASIONAL NYATA (Berdasarkan Data Aktual)

> Data per Juni 2026, diambil dari `database/database.sqlite` (78 tables).

### 10.1 Profil Perusahaan
- **Nama:** PT Panglong Bangunan Jaya
- **Alamat:** Jl. Raya Industri No. 45, Bekasi, Jawa Barat
- **Telepon:** 021-88556677
- **PPN:** 11% (aktif)
- **Session timeout:** 30 menit

### 10.2 Skala Operasional
| Metrik | Jumlah | Keterangan |
|--------|--------|------------|
| Produk aktif | 861 | Material bangunan lengkap |
| Kategori | 66 | MCB, Besi Beton, Saklar, Cat, Semen, dll. |
| Customer | 10 | Perlu ekspansi data customer |
| Supplier | 10 | Perlu ekspansi data supplier |
| Cabang (Branch) | 4 | Multi-branch ready |
| Gudang (Warehouse) | 4 | Multi-warehouse ready |
| User | 4 | admin, manager1, kasir1, gudang1 |
| Role | 6 | Owner, Manager, Kasir, Gudang, Salesman, Akuntan |
| Transaksi Penjualan | 0 | Belum ada transaksi nyata |
| Purchase Order | 0 | Belum ada PO nyata |
| Delivery | 0 | Belum ada pengiriman nyata |
| Stock Movement | 39 | Semua dari initial purchase/seed |

### 10.3 Kategori Produk Terbesar
1. **MCB & Panel Listrik** — 60 produk
2. **Besi Beton** — 38 produk
3. **Saklar & Stop Kontak** — 38 produk
4. **Lampu & Penerangan** — 37 produk
5. **Kabel & Instalasi Listrik** — 35 produk
6. **Fitting & Aksesoris Pipa** — 33 produk
7. **Baut, Mur & Sekrup** — 31 produk
8. **Paku** — 26 produk
9. **Aksesoris Kamar Mandi** — 25 produk
10. **Kran & Valve** — 24 produk

### 10.4 Produk Bernilai Stok Tertinggi
| Kode | Nama | Stok | Harga Beli | Harga Jual | Margin |
|------|------|------|-----------|-----------|--------|
| CLS-TTO-621 | Closet TOTO CW621J | 10 | Rp 1.850.000 | Rp 2.150.000 | 16.2% |
| CAT-DLX-25 | Cat Dulux Vitex 25kg | 20 | Rp 780.000 | Rp 950.000 | 21.8% |
| KCA-8-183244 | Kaca Bening 8mm | 8 | Rp 1.350.000 | Rp 1.550.000 | 14.8% |
| WMH-M4-612 | Wiremesh M4 6x12m | 15 | Rp 580.000 | Rp 660.000 | 13.8% |
| CAT-NPP-25 | Cat Nippon 25kg | 18 | Rp 750.000 | Rp 860.000 | 14.7% |
| CAT-AVN-25 | Cat Avian 25kg | 25 | Rp 680.000 | Rp 780.000 | 14.7% |
| KCA-5-183244 | Kaca Bening 5mm | 15 | Rp 850.000 | Rp 980.000 | 15.3% |
| PLY-MRN-9 | Plywood Meranti 9mm | 40 | Rp 195.000 | Rp 230.000 | 17.9% |
| SMT-GRK-40 | Semen Gresik 40kg | 200 | Rp 58.000 | Rp 65.000 | 12.1% |
| SMT-TRD-40 | Semen Tiga Roda 40kg | 150 | Rp 57.000 | Rp 64.000 | 12.3% |

### 10.5 Pola Stock Movement
- **purchase (initial):** 39 movements, total 3.470 unit
- **sale:** 0 (belum ada penjualan)
- **adjustment:** 0
- **physical_count:** 0

### 10.6 Gap Analisis Operasional Nyata

**Yang sudah berfungsi:**
- ✅ Manajemen produk dengan 861 SKU lengkap
- ✅ Kategorisasi 66 kategori material bangunan
- ✅ Multi-branch (4 cabang) dan multi-warehouse (4 gudang)
- ✅ Role-based access (6 role: Owner, Manager, Kasir, Gudang, Salesman, Akuntan)
- ✅ Session-based authentication dengan password_verify
- ✅ Dashboard dengan statistik real-time
- ✅ POS / Sales creation dengan auto stock deduction
- ✅ Purchase Order dengan receive & stock-in
- ✅ Delivery management
- ✅ Stock adjustment & stock opname
- ✅ Reports (daily, monthly, low-stock, stock-valuation, profit-loss, AR-aging, dead-stock)
- ✅ Settings (tax rate, company info, session timeout)
- ✅ 39 Playwright E2E tests semua lulus

**Yang sudah diimplementasi (Sprint 7-11):**
- ✅ Sales Return (retur penjualan) dengan stock-in otomatis & approval
- ✅ Purchase Return (retur pembelian) dengan stock-out otomatis & approval
- ✅ Quotation (penawaran harga) dengan bonus qty, valid until, convert ke SO
- ✅ Sales Order (commit order) dengan delivered_qty tracking & fulfill
- ✅ Customer-Specific Pricing (harga per customer)
- ✅ Tier Pricing (volume-based / qty break pricing)
- ✅ Supplier Price History (track perubahan harga beli)
- ✅ Stock Adjustment dengan approval workflow
- ✅ Stock Transfer antar gudang dengan stock movement
- ✅ Warehouse Locations (rak/blok/zone)
- ✅ Cash Book (cash in/out, bank transactions)
- ✅ Bank Reconciliation (match mutasi bank)
- ✅ Fixed Assets dengan auto depreciation (straight-line)
- ✅ Delivery cost field di Sale & Delivery (Sprint 8)
- ✅ Bonus qty field di Sale Items & Purchase Items (Sprint 8)
- ✅ Weight & dimension fields di Product (Sprint 8)

**Yang sudah diimplementasi (Sprint 12):**
- ✅ Fleet Management (vehicles, maintenance log, capacity tracking)
- ✅ Delivery Routes (multi-stop route planning, driver assignment, stop status)
- ✅ WhatsApp Notification (templates, message logging, invoice reminder, delivery notification)
- ✅ e-Faktur (PPN Masukan/Keluaran, CSV export DJP format, NPWP tracking)

**Yang sudah diimplementasi (Gap Features, June 2026):**
- ✅ Landed cost calculation (distribusi ongkos ke HPP per produk) — `landed_cost.php`
- ✅ Partial delivery (multiple DO per invoice) — `deliveries.php` + `sales_orders.php`
- ✅ Batch/Lot tracking & FIFO/FEFO stock valuation — `batches.php`
- ✅ Cash Flow Statement — `cash_flow.php`
- ✅ Closing periode (lock transaksi) — `closing.php`
- ✅ Salesman mobile app (PWA-based) — `salesman_app.php`

**Yang belum ada / belum berjalan:**
- ❌ Belum ada transaksi penjualan nyata (0 sales)
- ❌ Belum ada PO nyata (0 purchase orders)
- ❌ Belum ada delivery nyata (0 deliveries)
- ❌ Customer data masih sangat terbatas (10 customer)
- ❌ Supplier data masih sangat terbatas (10 supplier)
- ❌ Login attempt limit (5x = lock 15 menit) — belum diimplementasi
- ❌ Audit log di frontend (ajax.php) — belum diimplementasi
- ❌ QR Code auto-generate untuk produk — belum ada
- ❌ SPT PPN Report terpisah — belum ada (e-Faktur sudah ada)
- ❌ Multi-unit dropdown di POS (pilih satuan saat transaksi) — belum ada

**UI/UX Enhancements (June 2026):**
- ✅ RBAC-based navigation menu per role (owner, manager, kasir, gudang, accounting, supervisor)
- ✅ Dark mode toggle (session-based, untuk kesehatan mata pemakaian 24 jam)
- ✅ Eye-care mode (sepia theme, kontras rendah)
- ✅ Fullscreen toggle button (untuk desktop/large screen)
- ✅ Responsive design (mobile, tablet, desktop, ultra-wide)
- ✅ Professional UI (gradient navbar, card shadows, elegant login page)

### 10.7 Rekomendasi Prioritas Go-Live

Untuk siap go-live operasional, urutan prioritas:

1. **Input data customer & supplier nyata** — tanpa ini tidak bisa transaksi
2. **Training user** — kasir & gudang harus paham alur POS dan stock
3. **Transaksi pilot** — mulai dengan penjualan cash harian
4. **Stock opname awal** — pastikan stok sistem = stok fisik
5. ~~Landed cost calculation~~ — ✅ Done
6. ~~Batch/Lot tracking~~ — ✅ Done
7. ~~Cash Flow Statement~~ — ✅ Done
