# Panglong ERP — Analisis Bisnis Distribusi Material Bangunan

> Dokumen ini adalah analisis mendalam tentang bagaimana bisnis panglong (distributor/grosir material bangunan) beroperasi di dunia nyata di Indonesia, dan apa yang dibutuhkan agar aplikasi Panglong ERP dapat menjawab, mengerjakan, dan menyelesaikan semua aspek bisnis tersebut.
> Update: 29 Juni 2026

---

## Model Bisnis

Panglong ERP dirancang untuk distributor/grosir material bangunan dengan model SaaS multi-tenant:

1. **Super Admin** — Mengelola platform, master catalog, subscription
2. **Tenant (Distributor)** — Toko/grosir material bangunan yang berlangganan
3. **Branch** — Cabang dari distributor
4. **Customer** — Toko bangunan kecil, kontraktor, tukang
5. **Supplier** — Pabrik, importir, distributor besar

---

## Alur Bisnis Utama

### 1. Purchasing
Supplier → Purchase Order → Receive (dengan landed cost) → Stok masuk

### 2. Sales
Customer → Quotation → Sales Order → Invoice (POS) → Payment → Delivery (partial/multiple)

### 3. Inventory
Stok masuk (PO receive) → Stock movement → Stock opname → Stock adjustment → Stock transfer

### 4. Financial
Cash book → Bank reconciliation → Cash flow → P&L → Balance sheet → e-Faktur → Period closing

### 5. Delivery
Delivery order → Route planning → Vehicle assignment → Delivery tracking

### 6. Communication
WhatsApp notification (invoice, delivery, payment reminder) → Salesman mobile app

---

## Master Catalog Strategy

Distributor material bangunan punya banyak produk standar (semen, besi, cat, pipa, dll):
- **190 produk** master catalog (tenant_id = NULL)
- Tenant baru langsung punya akses ke katalog lengkap
- Tenant dapat import produk dari master catalog
- Produk baru tenant auto-sync ke master untuk tenant lain

---

## Tech Stack

- **100% PHP Native Procedural** — No framework
- **PDO SQLite** — 87 tables
- **jQuery 3.6 + Bootstrap 5.3** — Frontend
- **60 AJAX endpoints** — Single endpoint `ajax.php`
- **Playwright E2E** — 26 test specs
