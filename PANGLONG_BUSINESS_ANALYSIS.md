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
**Status:** ❌ TIDAK ADA — perlu bonus_qty field di SaleItem

### 4.3 Ongkos Angkut
```
- Pick-up: tidak ada ongkir
- Delivery dalam kota: Rp50.000 - Rp200.000
- Delivery luar kota: Rp500.000+ per truk
- Kapasitas: Colt Diesel 3-5 ton, Engkel 8-10 ton
```
**Status:** ❌ TIDAK ADA — perlu delivery_cost field di Sale & Delivery

### 4.4 Landed Cost (HPP Sebenarnya)
```
HPP = Harga Beli + Ongkos Angkut + Asuransi + Handling
Contoh: Semen Gresik
  Harga beli:     Rp55.000/sak
  Ongkos angkut:  Rp2.000/sak
  HPP sebenarnya: Rp57.500/sak (bukan Rp55.000!)
```
**Status:** ❌ TIDAK ADA — Product.buy_price hanya harga beli

### 4.5 Retur Barang
```
- Cat salah warna → retur, ganti
- Semen bocor/berbatu → retur ke supplier
- Besi salah ukuran → retur
- Keramik pecah → retur sebagian
```
**Status:** ❌ TIDAK ADA — perlu tabel sales_returns & purchase_returns

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
- ❌ Volume-based pricing (tier per qty)
- ❌ Customer-specific pricing (per customer, bukan per group)
- ❌ Cash discount (diskon untuk pembayaran cash)
- ❌ Contract pricing (harga kontrak untuk proyek)

---

## 6. LAPORAN & DASHBOARD YANG DIBUTUHKAN

### 6.1 Dashboard Harian
- ✅ Omzet hari ini
- ✅ Jumlah transaksi
- ✅ Chart penjualan mingguan
- ❌ Piutang jatuh tempo hari ini
- ❌ Hutang jatuh tempo hari ini
- ❌ Stok kritis (di bawah min)
- ❌ Cash position (saldo kas+bank)
- ❌ Top 5 produk terjual
- ❌ Delivery schedule hari ini

### 6.2 Laporan Operasional
- ✅ Daily/Monthly sales report
- ✅ Low stock report
- ✅ AR aging report
- ❌ AP aging report
- ❌ Stock movement (kartu stok) — ada tapi belum lengkap
- ❌ Dead stock report
- ❌ Stock valuation (FIFO)
- ❌ Profit margin per product
- ❌ Salesman performance
- ❌ Delivery performance
- ❌ Customer/Supplier purchase history

### 6.3 Laporan Keuangan
- ✅ Trial Balance, Balance Sheet, Income Statement, GL
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

## 8. STATUS SAAT INI (Sprint 1-6 COMPLETED)

| Sprint | Fokus | Status |
|--------|-------|--------|
| 1 | Foundation Fix (migrasi, model, auth) | ✅ COMPLETED |
| 2 | Core API Stabilization (form request, resource, factory) | ✅ COMPLETED |
| 3 | Frontend Development (login, dashboard, POS, CRUD) | ✅ COMPLETED |
| 4 | Integration & Polish (permission, audit, void, E2E) | ✅ COMPLETED |
| 5 | Building Materials Domain Data (seeder rewrite) | ✅ COMPLETED |
| 6 | Accounting Business Logic (jurnal otomatis, COA Indonesia) | ✅ COMPLETED |
| 7 | Retur & Quotation | ⏳ PENDING |
| 8 | Ongkos Angkut & Landed Cost | ⏳ PENDING |
| 9 | Pricing & Customer Management | ⏳ PENDING |
| 10 | Stock Advanced | ⏳ PENDING |
| 11 | Keuangan Advanced | ⏳ PENDING |
| 12 | Integrasi & Automation | ⏳ PENDING |

---

## 9. KESIMPULAN

Aplikasi Panglong ERP saat ini sudah memiliki **fondasi yang baik** untuk operasional dasar (POS, inventory, AR/AP, akuntansi double-entry). Namun masih **banyak hal kritis yang belum ada** untuk bisa benar-benar menjawab semua kebutuhan bisnis panglong di dunia nyata:

**Top 5 Yang Paling Mendesak:**
1. **Retur barang** — terjadi setiap hari di dunia nyata
2. **Quotation** — kontraktor tidak akan beli tanpa penawaran formal
3. **Ongkos angkut & landed cost** — margin akan terus bocor tanpa ini
4. **Bonus barang** — praktik umum yang tidak bisa diabaikan
5. **Sales Order** — pemisahan commit order vs invoice penting untuk allocation

Dengan implementasi Sprint 7-12, Panglong ERP akan menjadi sistem yang **lengkap dan siap** untuk mengelola seluruh aspek bisnis distribusi material bangunan di Indonesia.
