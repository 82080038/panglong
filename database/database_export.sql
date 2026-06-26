-- Panglong ERP SQLite Export
-- Generated: 2026-06-26 14:21:52
-- Tables: 78
-- Import: sqlite3 database/database.sqlite < database/database_export.sql

PRAGMA foreign_keys=OFF;

-- Table: accounts_payable
DROP TABLE IF EXISTS "accounts_payable";
CREATE TABLE "accounts_payable" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "supplier_id" INTEGER NOT NULL, "po_id" INTEGER NOT NULL, "amount" REAL NOT NULL, "balance" REAL NOT NULL, "due_date" TEXT NOT NULL, "status" TEXT NOT NULL DEFAULT 'pending', "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: accounts_receivable
DROP TABLE IF EXISTS "accounts_receivable";
CREATE TABLE "accounts_receivable" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "customer_id" INTEGER NOT NULL, "sale_id" INTEGER NOT NULL, "amount" REAL NOT NULL, "balance" REAL NOT NULL, "due_date" TEXT NOT NULL, "status" TEXT NOT NULL DEFAULT 'pending', "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: app_settings
DROP TABLE IF EXISTS "app_settings";
CREATE TABLE "app_settings" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "tenant_id" INTEGER, "key" TEXT NOT NULL, "value" TEXT, "type" TEXT NOT NULL DEFAULT 'string', "description" TEXT, "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "app_settings" ("id", "tenant_id", "key", "value", "type", "description", "created_at", "updated_at") VALUES ('1', NULL, 'tax_rate', '0.11', 'float', 'PPN rate (0.11 = 11%)', NULL, NULL);
INSERT INTO "app_settings" ("id", "tenant_id", "key", "value", "type", "description", "created_at", "updated_at") VALUES ('2', NULL, 'tax_enabled', '1', 'boolean', 'Enable PPN tax calculation', NULL, NULL);
INSERT INTO "app_settings" ("id", "tenant_id", "key", "value", "type", "description", "created_at", "updated_at") VALUES ('3', NULL, 'company_name', 'PT Panglong Bangunan Jaya', 'string', 'Company name for print', NULL, NULL);
INSERT INTO "app_settings" ("id", "tenant_id", "key", "value", "type", "description", "created_at", "updated_at") VALUES ('4', NULL, 'company_address', 'Jl. Raya Industri No. 45, Bekasi, Jawa Barat', 'string', 'Company address', NULL, NULL);
INSERT INTO "app_settings" ("id", "tenant_id", "key", "value", "type", "description", "created_at", "updated_at") VALUES ('5', NULL, 'company_phone', '021-88556677', 'string', 'Company phone', NULL, NULL);
INSERT INTO "app_settings" ("id", "tenant_id", "key", "value", "type", "description", "created_at", "updated_at") VALUES ('6', NULL, 'currency', 'IDR', 'string', 'Currency code', NULL, NULL);
INSERT INTO "app_settings" ("id", "tenant_id", "key", "value", "type", "description", "created_at", "updated_at") VALUES ('7', NULL, 'session_timeout_minutes', '30', 'integer', 'Session timeout in minutes', NULL, NULL);
INSERT INTO "app_settings" ("id", "tenant_id", "key", "value", "type", "description", "created_at", "updated_at") VALUES ('8', NULL, 'low_stock_threshold_days', '7', 'integer', 'Days of stock before low alert', NULL, NULL);

-- Table: asset_depreciations
DROP TABLE IF EXISTS "asset_depreciations";
CREATE TABLE "asset_depreciations" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "fixed_asset_id" INTEGER NOT NULL, "depreciation_date" TEXT NOT NULL, "amount" REAL NOT NULL, "accumulated_after" REAL NOT NULL, "book_value_after" REAL NOT NULL, "journal_entry_id" INTEGER, "notes" TEXT, "created_by" INTEGER, "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "asset_depreciations" ("id", "fixed_asset_id", "depreciation_date", "amount", "accumulated_after", "book_value_after", "journal_entry_id", "notes", "created_by", "created_at", "updated_at") VALUES ('1', '1', '2024-06-30', '3750000', '3750000', '246250000', '2', NULL, '1', '2026-06-24 03:20:34', '2026-06-24 03:20:34');

-- Table: audit_logs
DROP TABLE IF EXISTS "audit_logs";
CREATE TABLE "audit_logs" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "user_id" INTEGER, "action" TEXT NOT NULL, "model_type" TEXT, "model_id" INTEGER, "old_values" TEXT, "new_values" TEXT, "ip_address" TEXT, "user_agent" TEXT, "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()');

-- (no data)

-- Table: bank_statements
DROP TABLE IF EXISTS "bank_statements";
CREATE TABLE "bank_statements" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "bank_account" TEXT NOT NULL, "transaction_date" TEXT NOT NULL, "description" TEXT NOT NULL, "debit" REAL NOT NULL DEFAULT '0.00', "credit" REAL NOT NULL DEFAULT '0.00', "balance" REAL NOT NULL DEFAULT '0.00', "reference_no" TEXT, "reconciliation_status" TEXT NOT NULL DEFAULT 'unreconciled', "journal_entry_id" INTEGER, "cash_transaction_id" INTEGER, "reconciled_at" TEXT, "reconciled_by" INTEGER, "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: barcodes
DROP TABLE IF EXISTS "barcodes";
CREATE TABLE "barcodes" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "product_id" INTEGER NOT NULL, "unit_id" INTEGER, "barcode" TEXT NOT NULL, "is_primary" INTEGER NOT NULL DEFAULT '0', "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: branches
DROP TABLE IF EXISTS "branches";
CREATE TABLE "branches" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "code" TEXT NOT NULL, "name" TEXT NOT NULL, "address" TEXT, "phone" TEXT, "email" TEXT, "manager_name" TEXT, "type" TEXT NOT NULL DEFAULT 'cabang', "is_active" INTEGER NOT NULL DEFAULT '1', "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "branches" ("id", "code", "name", "address", "phone", "email", "manager_name", "type", "is_active", "created_at", "updated_at") VALUES ('1', 'BR-PST', 'Kantor Pusat', 'Jl. Raya Bangunan No. 1, Jakarta Timur', '021-555-1001', 'pusat@panglongjaya.co.id', 'Budi Santoso', 'pusat', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "branches" ("id", "code", "name", "address", "phone", "email", "manager_name", "type", "is_active", "created_at", "updated_at") VALUES ('2', 'BR-CBG1', 'Cabang Bekasi', 'Jl. Industri Raya No. 15, Bekasi', '021-555-2001', 'bekasi@panglongjaya.co.id', 'Andi Wijaya', 'cabang', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "branches" ("id", "code", "name", "address", "phone", "email", "manager_name", "type", "is_active", "created_at", "updated_at") VALUES ('3', 'BR-CBG2', 'Cabang Tangerang', 'Jl. Raya Serpong No. 88, Tangerang', '021-555-3001', 'tangerang@panglongjaya.co.id', 'Dedi Kurniawan', 'cabang', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "branches" ("id", "code", "name", "address", "phone", "email", "manager_name", "type", "is_active", "created_at", "updated_at") VALUES ('4', 'BR-AGN1', 'Agen Depok', 'Jl. Margonda Raya No. 50, Depok', '021-555-4001', 'depok@panglongjaya.co.id', 'Rudi Hartono', 'agen', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');

-- Table: cash_flow_categories
DROP TABLE IF EXISTS "cash_flow_categories";
CREATE TABLE cash_flow_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_name TEXT NOT NULL,
    flow_type TEXT NOT NULL,
    account_codes TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TEXT
);

INSERT INTO "cash_flow_categories" ("id", "category_name", "flow_type", "account_codes", "is_active", "created_at") VALUES ('1', 'Operasi - Penerimaan dari Pelanggan', 'operating', '1100,1110', '1', '2026-06-26 13:10:40');
INSERT INTO "cash_flow_categories" ("id", "category_name", "flow_type", "account_codes", "is_active", "created_at") VALUES ('2', 'Operasi - Pembayaran ke Supplier', 'operating', '2100,2110', '1', '2026-06-26 13:10:40');
INSERT INTO "cash_flow_categories" ("id", "category_name", "flow_type", "account_codes", "is_active", "created_at") VALUES ('3', 'Operasi - Pembayaran Gaji & Operasional', 'operating', '5100,5200,5300', '1', '2026-06-26 13:10:40');
INSERT INTO "cash_flow_categories" ("id", "category_name", "flow_type", "account_codes", "is_active", "created_at") VALUES ('4', 'Operasi - Penerimaan/Pembayaran PPN', 'operating', '2200', '1', '2026-06-26 13:10:40');
INSERT INTO "cash_flow_categories" ("id", "category_name", "flow_type", "account_codes", "is_active", "created_at") VALUES ('5', 'Investasi - Pembelian Aset Tetap', 'investing', '1500', '1', '2026-06-26 13:10:40');
INSERT INTO "cash_flow_categories" ("id", "category_name", "flow_type", "account_codes", "is_active", "created_at") VALUES ('6', 'Investasi - Penjualan Aset Tetap', 'investing', '1500', '1', '2026-06-26 13:10:40');
INSERT INTO "cash_flow_categories" ("id", "category_name", "flow_type", "account_codes", "is_active", "created_at") VALUES ('7', 'Pendanaan - Pinjaman Diterima', 'financing', '2300', '1', '2026-06-26 13:10:40');
INSERT INTO "cash_flow_categories" ("id", "category_name", "flow_type", "account_codes", "is_active", "created_at") VALUES ('8', 'Pendanaan - Pembayaran Pinjaman', 'financing', '2300', '1', '2026-06-26 13:10:40');
INSERT INTO "cash_flow_categories" ("id", "category_name", "flow_type", "account_codes", "is_active", "created_at") VALUES ('9', 'Pendanaan - Pembayaran Dividen', 'financing', '3100', '1', '2026-06-26 13:10:40');

-- Table: cash_transactions
DROP TABLE IF EXISTS "cash_transactions";
CREATE TABLE "cash_transactions" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "transaction_no" TEXT NOT NULL, "type" TEXT NOT NULL, "account_type" TEXT NOT NULL DEFAULT 'kas_tunai', "transaction_date" TEXT NOT NULL, "amount" REAL NOT NULL, "description" TEXT NOT NULL, "category" TEXT NOT NULL DEFAULT 'operasional', "branch_id" INTEGER, "employee_id" INTEGER, "reference_no" TEXT, "recipient" TEXT, "journal_entry_id" INTEGER, "created_by" INTEGER, "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "cash_transactions" ("id", "transaction_no", "type", "account_type", "transaction_date", "amount", "description", "category", "branch_id", "employee_id", "reference_no", "recipient", "journal_entry_id", "created_by", "created_at", "updated_at") VALUES ('1', 'CT202606240001', 'cash_out', 'kas_tunai', '2024-06-01', '500000', 'Beli perlengkapan kantor', 'perlengkapan', NULL, NULL, NULL, NULL, '1', '1', '2026-06-24 03:20:33', '2026-06-24 03:20:34');

-- Table: categories
DROP TABLE IF EXISTS "categories";
CREATE TABLE "categories" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "tenant_id" INTEGER, "name" TEXT NOT NULL, "parent_id" INTEGER, "level" INTEGER NOT NULL DEFAULT '1', "is_active" INTEGER NOT NULL DEFAULT '1', "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('1', NULL, 'Semen & Beton', NULL, '1', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('2', NULL, 'Besi & Baja', NULL, '1', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('3', NULL, 'Cat & Finishing', NULL, '1', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('4', NULL, 'Keramik & Granit', NULL, '1', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('5', NULL, 'Kaca', NULL, '1', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('6', NULL, 'Kayu & Plywood', NULL, '1', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('7', NULL, 'Atap', NULL, '1', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('8', NULL, 'Sanitary & Plumbing', NULL, '1', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('9', NULL, 'Peralatan', NULL, '1', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('10', NULL, 'Semen Portland', '1', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('11', NULL, 'Semen Putih', '1', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('12', NULL, 'Mortar & Insta Cement', '1', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('13', NULL, 'Hebel & Bata Ringan', '1', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('14', NULL, 'Besi Beton', '2', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('15', NULL, 'Baja Ringan & Kanal', '2', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('16', NULL, 'Pipa Besi', '2', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('17', NULL, 'Kawat & Wiremesh', '2', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('18', NULL, 'Spandek & Genteng Metal', '2', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('19', NULL, 'Cat Tembok', '3', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('20', NULL, 'Cat Kayu & Besi', '3', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('21', NULL, 'Thinner & Pelarut', '3', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('22', NULL, 'Waterproofing & Plamir', '3', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('23', NULL, 'Keramik Lantai', '4', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('24', NULL, 'Keramik Dinding', '4', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('25', NULL, 'Granit & Homogeneous', '4', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('26', NULL, 'Marmer & Natural Stone', '4', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('27', NULL, 'Kaca Bening', '5', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('28', NULL, 'Kaca Tempered', '5', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('29', NULL, 'Cermin', '5', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('30', NULL, 'Kayu Solid', '6', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('31', NULL, 'Plywood', '6', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('32', NULL, 'MDF & Blockboard', '6', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('33', NULL, 'Genteng', '7', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('34', NULL, 'Spandek & Metal Roof', '7', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('35', NULL, 'Talang & Aksesoris Atap', '7', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('36', NULL, 'Closet & Urinoir', '8', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('37', NULL, 'Washtafel & Lavabo', '8', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('38', NULL, 'Kran & Valve', '8', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('39', NULL, 'Pipa PVC & Fitting', '8', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('40', NULL, 'Perkakas', '9', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "categories" ("id", "tenant_id", "name", "parent_id", "level", "is_active", "created_at", "updated_at") VALUES ('41', NULL, 'Safety Equipment', '9', '2', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');

-- Table: chart_of_accounts
DROP TABLE IF EXISTS "chart_of_accounts";
CREATE TABLE "chart_of_accounts" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "tenant_id" INTEGER, "code" TEXT NOT NULL, "name" TEXT NOT NULL, "type" TEXT NOT NULL, "subtype" TEXT, "parent_id" INTEGER, "is_active" INTEGER NOT NULL DEFAULT '1', "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('1', NULL, '1000', 'Kas & Bank', 'asset', 'current_asset', NULL, '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('2', NULL, '1010', 'Kas Tunai', 'asset', 'current_asset', '1', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('3', NULL, '1011', 'Kas Kecil (Petty Cash)', 'asset', 'current_asset', '1', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('4', NULL, '1020', 'Bank BCA', 'asset', 'current_asset', '1', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('5', NULL, '1021', 'Bank Mandiri', 'asset', 'current_asset', '1', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('6', NULL, '1022', 'Bank BNI', 'asset', 'current_asset', '1', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('7', NULL, '1100', 'Piutang Usaha', 'asset', 'current_asset', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('8', NULL, '1150', 'Uang Muka Pembelian', 'asset', 'current_asset', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('9', NULL, '1200', 'Persediaan Barang', 'asset', 'current_asset', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('10', NULL, '1300', 'PPN Masukan', 'asset', 'current_asset', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('11', NULL, '1400', 'Aset Pajak Dibayar Dimuka', 'asset', 'current_asset', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('12', NULL, '1500', 'Aset Tetap', 'asset', 'fixed_asset', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('13', NULL, '1510', 'Kendaraan', 'asset', 'fixed_asset', '12', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('14', NULL, '1520', 'Peralatan Kantor', 'asset', 'fixed_asset', '12', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('15', NULL, '1530', 'Bangunan Gudang', 'asset', 'fixed_asset', '12', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('16', NULL, '1590', 'Akumulasi Penyusutan', 'asset', 'fixed_asset', '12', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('17', NULL, '2000', 'Hutang Usaha', 'liability', 'current_liability', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('18', NULL, '2100', 'PPN Keluaran', 'liability', 'current_liability', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('19', NULL, '2200', 'Pinjaman Jangka Pendek', 'liability', 'current_liability', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('20', NULL, '2300', 'Hutang Pajak', 'liability', 'current_liability', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('21', NULL, '2400', 'Hutang Gaji', 'liability', 'current_liability', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('22', NULL, '2500', 'Pinjaman Jangka Panjang', 'liability', 'long_term_liability', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('23', NULL, '3000', 'Modal Pemilik', 'equity', 'capital', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('24', NULL, '3100', 'Laba Ditahan', 'equity', 'retained_earnings', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('25', NULL, '3200', 'Laba Tahun Berjalan', 'equity', 'retained_earnings', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('26', NULL, '4000', 'Pendapatan Penjualan', 'revenue', 'sales_revenue', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('27', NULL, '4010', 'Penjualan Semen & Beton', 'revenue', 'sales_revenue', '26', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('28', NULL, '4020', 'Penjualan Besi & Baja', 'revenue', 'sales_revenue', '26', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('29', NULL, '4030', 'Penjualan Cat & Finishing', 'revenue', 'sales_revenue', '26', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('30', NULL, '4040', 'Penjualan Keramik & Granit', 'revenue', 'sales_revenue', '26', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('31', NULL, '4050', 'Penjualan Sanitary & Plumbing', 'revenue', 'sales_revenue', '26', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('32', NULL, '4090', 'Penjualan Lain-lain', 'revenue', 'sales_revenue', '26', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('33', NULL, '4100', 'Pendapatan Lain-lain', 'revenue', 'other_revenue', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('34', NULL, '4200', 'Potongan Penjualan', 'revenue', 'sales_revenue', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('35', NULL, '5000', 'HPP (Cost of Goods Sold)', 'expense', 'cogs', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('36', NULL, '5100', 'Ongkos Angkut Pembelian', 'expense', 'cogs', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('37', NULL, '5200', 'Kerugian Persediaan', 'expense', 'cogs', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('38', NULL, '6000', 'Beban Operasional', 'expense', 'operating_expense', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('39', NULL, '6100', 'Beban Gaji & Tunjangan', 'expense', 'operating_expense', '38', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('40', NULL, '6200', 'Beban Sewa', 'expense', 'operating_expense', '38', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('41', NULL, '6300', 'Beban Listrik & Air', 'expense', 'operating_expense', '38', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('42', NULL, '6400', 'Beban Telekomunikasi', 'expense', 'operating_expense', '38', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('43', NULL, '6500', 'Beban Ongkos Kirim', 'expense', 'operating_expense', '38', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('44', NULL, '6600', 'Beban Perlengkapan Kantor', 'expense', 'operating_expense', '38', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('45', NULL, '6700', 'Beban Pemeliharaan Kendaraan', 'expense', 'operating_expense', '38', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('46', NULL, '6800', 'Beban Asuransi', 'expense', 'operating_expense', '38', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('47', NULL, '6900', 'Beban Penyusutan', 'expense', 'operating_expense', '38', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('48', NULL, '7000', 'Beban Pajak', 'expense', 'other_expense', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "chart_of_accounts" ("id", "tenant_id", "code", "name", "type", "subtype", "parent_id", "is_active", "created_at", "updated_at") VALUES ('49', NULL, '7100', 'Potongan Pembelian', 'expense', 'other_expense', NULL, '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');

-- Table: customer_groups
DROP TABLE IF EXISTS "customer_groups";
CREATE TABLE "customer_groups" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "tenant_id" INTEGER, "name" TEXT NOT NULL, "discount_pct" REAL NOT NULL DEFAULT '0.00', "credit_limit" REAL NOT NULL DEFAULT '0.00', "is_active" INTEGER NOT NULL DEFAULT '1', "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "customer_groups" ("id", "tenant_id", "name", "discount_pct", "credit_limit", "is_active", "created_at", "updated_at") VALUES ('1', NULL, 'Retail', '0', '1000000', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "customer_groups" ("id", "tenant_id", "name", "discount_pct", "credit_limit", "is_active", "created_at", "updated_at") VALUES ('2', NULL, 'Tukang', '5', '5000000', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "customer_groups" ("id", "tenant_id", "name", "discount_pct", "credit_limit", "is_active", "created_at", "updated_at") VALUES ('3', NULL, 'Kontraktor', '10', '20000000', '1', '2026-06-24 03:18:12', '2026-06-24 03:18:12');
INSERT INTO "customer_groups" ("id", "tenant_id", "name", "discount_pct", "credit_limit", "is_active", "created_at", "updated_at") VALUES ('4', NULL, 'Proyek', '15', '50000000', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "customer_groups" ("id", "tenant_id", "name", "discount_pct", "credit_limit", "is_active", "created_at", "updated_at") VALUES ('5', NULL, 'Langganan', '8', '10000000', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');

-- Table: customer_product_prices
DROP TABLE IF EXISTS "customer_product_prices";
CREATE TABLE "customer_product_prices" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "customer_id" INTEGER NOT NULL, "product_id" INTEGER NOT NULL, "unit_id" INTEGER NOT NULL, "custom_price" REAL NOT NULL, "min_qty" REAL NOT NULL DEFAULT '1.000', "is_active" INTEGER NOT NULL DEFAULT '1', "notes" TEXT, "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: customers
DROP TABLE IF EXISTS "customers";
CREATE TABLE "customers" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "tenant_id" INTEGER, "name" TEXT NOT NULL, "address" TEXT, "phone" TEXT, "email" TEXT, "group_id" INTEGER, "credit_limit" REAL NOT NULL DEFAULT '0.00', "payment_terms" INTEGER NOT NULL DEFAULT '30', "credit_score" TEXT NOT NULL DEFAULT 'C', "is_active" INTEGER NOT NULL DEFAULT '1', "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "customers" ("id", "tenant_id", "name", "address", "phone", "email", "group_id", "credit_limit", "payment_terms", "credit_score", "is_active", "created_at", "updated_at") VALUES ('1', NULL, 'PT Wijaya Karya Konstruksi', 'Jl. Konstruksi Raya No. 1, Jakarta Selatan', '021-5701234', 'procurement@wijayakarya.co.id', '3', '100000000', '45', 'A', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "customers" ("id", "tenant_id", "name", "address", "phone", "email", "group_id", "credit_limit", "payment_terms", "credit_score", "is_active", "created_at", "updated_at") VALUES ('2', NULL, 'PT Pembangunan Perumahan Nusantara', 'Jl. Perumahan No. 88, Jakarta Barat', '021-5552345', 'purchasing@ppn.co.id', '4', '200000000', '60', 'A', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "customers" ("id", "tenant_id", "name", "address", "phone", "email", "group_id", "credit_limit", "payment_terms", "credit_score", "is_active", "created_at", "updated_at") VALUES ('3', NULL, 'CV Bangun Mandiri Sejahtera', 'Jl. Bahan Bangunan No. 15, Bekasi', '0812-3456-7890', 'cvbangunmandiri@gmail.com', '5', '50000000', '30', 'A', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "customers" ("id", "tenant_id", "name", "address", "phone", "email", "group_id", "credit_limit", "payment_terms", "credit_score", "is_active", "created_at", "updated_at") VALUES ('4', NULL, 'Toko Bangunan Sumber Rejeki', 'Jl. Raya Bekasi KM 25, Bekasi', '0813-1111-2222', 'sumberrejeki.bangunan@gmail.com', '2', '25000000', '30', 'B', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "customers" ("id", "tenant_id", "name", "address", "phone", "email", "group_id", "credit_limit", "payment_terms", "credit_score", "is_active", "created_at", "updated_at") VALUES ('5', NULL, 'Toko Bangunan Jaya Abadi', 'Jl. Raya Bogor KM 30, Depok', '0813-3333-4444', 'jayaabadi.bangunan@gmail.com', '2', '20000000', '30', 'B', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "customers" ("id", "tenant_id", "name", "address", "phone", "email", "group_id", "credit_limit", "payment_terms", "credit_score", "is_active", "created_at", "updated_at") VALUES ('6', NULL, 'Pak Suhardi (Mandor)', 'Jl. Swadaya No. 7, Cibitung', '0857-1234-5678', NULL, '2', '5000000', '15', 'C', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "customers" ("id", "tenant_id", "name", "address", "phone", "email", "group_id", "credit_limit", "payment_terms", "credit_score", "is_active", "created_at", "updated_at") VALUES ('7', NULL, 'Pak Joko Santoso (Tukang)', 'Jl. Gotong Royong No. 12, Cikarang', '0858-9876-5432', NULL, '1', '2000000', '7', 'C', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "customers" ("id", "tenant_id", "name", "address", "phone", "email", "group_id", "credit_limit", "payment_terms", "credit_score", "is_active", "created_at", "updated_at") VALUES ('8', NULL, 'PT Graha Property Development', 'Jl. Property Raya No. 100, Tangerang Selatan', '021-7778888', 'procurement@grahaproperty.co.id', '4', '150000000', '60', 'A', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "customers" ("id", "tenant_id", "name", "address", "phone", "email", "group_id", "credit_limit", "payment_terms", "credit_score", "is_active", "created_at", "updated_at") VALUES ('9', NULL, 'UD Sentosa Bangun Jaya', 'Jl. Industri No. 45, Cileungsi', '021-8889999', 'sentosabangunjaya@yahoo.com', '5', '40000000', '30', 'B', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "customers" ("id", "tenant_id", "name", "address", "phone", "email", "group_id", "credit_limit", "payment_terms", "credit_score", "is_active", "created_at", "updated_at") VALUES ('10', NULL, 'Ibu Wati (Renovasi Rumah)', 'Jl. Melati No. 3, Cibitung', '0812-7777-8888', NULL, '1', '1000000', '0', 'C', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');

-- Table: deliveries
DROP TABLE IF EXISTS "deliveries";
CREATE TABLE "deliveries" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "tenant_id" INTEGER, "delivery_no" TEXT NOT NULL, "sale_id" INTEGER, "customer_name" TEXT NOT NULL, "delivery_address" TEXT, "origin_address" TEXT, "phone" TEXT, "delivery_date" TEXT NOT NULL, "delivery_time" TEXT, "driver_name" TEXT, "vehicle_plate" TEXT, "delivery_cost" REAL NOT NULL DEFAULT '0.00', "status" TEXT NOT NULL DEFAULT 'pending', "notes" TEXT, "delivered_at" TEXT, "delivery_proof" TEXT, "created_by" INTEGER, "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: delivery_items
DROP TABLE IF EXISTS "delivery_items";
CREATE TABLE "delivery_items" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "delivery_id" INTEGER, "sale_item_id" INTEGER, "product_id" INTEGER, "quantity" REAL NOT NULL, "unit_id" INTEGER, "notes" TEXT, "delivery_status" TEXT NOT NULL DEFAULT 'pending', "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()');

-- (no data)

-- Table: delivery_routes
DROP TABLE IF EXISTS "delivery_routes";
CREATE TABLE delivery_routes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        route_no TEXT UNIQUE,
        route_date TEXT NOT NULL,
        vehicle_id INTEGER,
        driver_name TEXT,
        status TEXT DEFAULT 'planned',
        total_distance_km REAL,
        estimated_time_minutes INTEGER,
        notes TEXT,
        created_by INTEGER,
        created_at TEXT,
        updated_at TEXT
    );

-- (no data)

-- Table: demand_forecasts
DROP TABLE IF EXISTS "demand_forecasts";
CREATE TABLE "demand_forecasts" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "product_id" INTEGER NOT NULL, "tenant_id" INTEGER, "forecast_date" TEXT NOT NULL, "horizon_days" INTEGER NOT NULL, "predicted_demand" REAL NOT NULL, "confidence_lower" REAL NOT NULL, "confidence_upper" REAL NOT NULL, "confidence_score" REAL NOT NULL, "method" TEXT NOT NULL, "factors" TEXT, "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: e_faktur
DROP TABLE IF EXISTS "e_faktur";
CREATE TABLE e_faktur (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        faktur_no TEXT UNIQUE,
        faktur_type TEXT,
        transaction_date TEXT NOT NULL,
        counterparty_name TEXT,
        counterparty_npwp TEXT,
        dpp REAL,
        ppn REAL,
        description TEXT,
        reference_type TEXT,
        reference_id INTEGER,
        export_status TEXT DEFAULT 'pending',
        created_by INTEGER,
        created_at TEXT,
        updated_at TEXT
    );

-- (no data)

-- Table: employees
DROP TABLE IF EXISTS "employees";
CREATE TABLE "employees" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "employee_no" TEXT NOT NULL, "nik" TEXT, "full_name" TEXT NOT NULL, "phone" TEXT, "email" TEXT, "address" TEXT, "position" TEXT NOT NULL, "branch_id" INTEGER, "warehouse_id" INTEGER, "user_id" INTEGER, "base_salary" REAL NOT NULL DEFAULT '0.00', "commission_pct" REAL NOT NULL DEFAULT '0.00', "hire_date" TEXT, "resign_date" TEXT, "status" TEXT NOT NULL DEFAULT 'active', "vehicle_plate" TEXT, "sim_no" TEXT, "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "employees" ("id", "employee_no", "nik", "full_name", "phone", "email", "address", "position", "branch_id", "warehouse_id", "user_id", "base_salary", "commission_pct", "hire_date", "resign_date", "status", "vehicle_plate", "sim_no", "created_at", "updated_at") VALUES ('1', 'EMP-001', '3171010101900001', 'Budi Santoso', NULL, NULL, NULL, 'manager', '1', '2', '1', '15000000', '0', '2020-01-15', NULL, 'active', NULL, NULL, '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "employees" ("id", "employee_no", "nik", "full_name", "phone", "email", "address", "position", "branch_id", "warehouse_id", "user_id", "base_salary", "commission_pct", "hire_date", "resign_date", "status", "vehicle_plate", "sim_no", "created_at", "updated_at") VALUES ('2', 'EMP-002', '3171020202900002', 'Andi Wijaya', NULL, NULL, NULL, 'manager', '2', '3', '2', '12000000', '0', '2020-03-01', NULL, 'active', NULL, NULL, '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "employees" ("id", "employee_no", "nik", "full_name", "phone", "email", "address", "position", "branch_id", "warehouse_id", "user_id", "base_salary", "commission_pct", "hire_date", "resign_date", "status", "vehicle_plate", "sim_no", "created_at", "updated_at") VALUES ('3', 'EMP-003', '3171030303900003', 'Slamet Riyadi', NULL, NULL, NULL, 'salesman', '1', NULL, '3', '5000000', '2', '2021-06-01', NULL, 'active', NULL, NULL, '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "employees" ("id", "employee_no", "nik", "full_name", "phone", "email", "address", "position", "branch_id", "warehouse_id", "user_id", "base_salary", "commission_pct", "hire_date", "resign_date", "status", "vehicle_plate", "sim_no", "created_at", "updated_at") VALUES ('4', 'EMP-004', '3171040404900004', 'Joko Susilo', NULL, NULL, NULL, 'salesman', '2', NULL, '4', '5000000', '2', '2021-07-01', NULL, 'active', NULL, NULL, '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "employees" ("id", "employee_no", "nik", "full_name", "phone", "email", "address", "position", "branch_id", "warehouse_id", "user_id", "base_salary", "commission_pct", "hire_date", "resign_date", "status", "vehicle_plate", "sim_no", "created_at", "updated_at") VALUES ('5', 'EMP-005', '3171050505900005', 'Ahmad Fauzi', NULL, NULL, NULL, 'driver', '1', NULL, NULL, '4500000', '0', '2021-01-10', NULL, 'active', 'B 1234 ABC', 'SIM-B12345', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "employees" ("id", "employee_no", "nik", "full_name", "phone", "email", "address", "position", "branch_id", "warehouse_id", "user_id", "base_salary", "commission_pct", "hire_date", "resign_date", "status", "vehicle_plate", "sim_no", "created_at", "updated_at") VALUES ('6', 'EMP-006', '3171060606900006', 'Dedi Kurniawan', NULL, NULL, NULL, 'driver', '1', NULL, NULL, '4500000', '0', '2021-02-15', NULL, 'active', 'B 5678 DEF', 'SIM-B56789', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "employees" ("id", "employee_no", "nik", "full_name", "phone", "email", "address", "position", "branch_id", "warehouse_id", "user_id", "base_salary", "commission_pct", "hire_date", "resign_date", "status", "vehicle_plate", "sim_no", "created_at", "updated_at") VALUES ('7', 'EMP-007', '3171070707900007', 'Siti Aminah', NULL, NULL, NULL, 'kasir', '1', NULL, NULL, '4000000', '0', '2022-01-01', NULL, 'active', NULL, NULL, '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "employees" ("id", "employee_no", "nik", "full_name", "phone", "email", "address", "position", "branch_id", "warehouse_id", "user_id", "base_salary", "commission_pct", "hire_date", "resign_date", "status", "vehicle_plate", "sim_no", "created_at", "updated_at") VALUES ('8', 'EMP-008', '3171080808900008', 'Hendra Gunawan', NULL, NULL, NULL, 'gudang', '1', '2', NULL, '4000000', '0', '2022-03-01', NULL, 'active', NULL, NULL, '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "employees" ("id", "employee_no", "nik", "full_name", "phone", "email", "address", "position", "branch_id", "warehouse_id", "user_id", "base_salary", "commission_pct", "hire_date", "resign_date", "status", "vehicle_plate", "sim_no", "created_at", "updated_at") VALUES ('9', 'EMP-009', '3171090909900009', 'Rina Marlina', NULL, NULL, NULL, 'accounting', '1', NULL, NULL, '8000000', '0', '2021-01-15', NULL, 'active', NULL, NULL, '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "employees" ("id", "employee_no", "nik", "full_name", "phone", "email", "address", "position", "branch_id", "warehouse_id", "user_id", "base_salary", "commission_pct", "hire_date", "resign_date", "status", "vehicle_plate", "sim_no", "created_at", "updated_at") VALUES ('10', 'EMP-010', '3171101010900010', 'Rudi Hartono', NULL, NULL, NULL, 'supervisor', '3', NULL, NULL, '7000000', '0', '2022-06-01', NULL, 'active', NULL, NULL, '2026-06-24 03:18:14', '2026-06-24 03:18:14');

-- Table: fixed_assets
DROP TABLE IF EXISTS "fixed_assets";
CREATE TABLE "fixed_assets" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "asset_code" TEXT NOT NULL, "name" TEXT NOT NULL, "category" TEXT NOT NULL, "branch_id" INTEGER, "serial_no" TEXT, "plate_no" TEXT, "acquisition_date" TEXT NOT NULL, "acquisition_cost" REAL NOT NULL, "salvage_value" REAL NOT NULL DEFAULT '0.00', "useful_life_months" INTEGER NOT NULL, "depreciation_method" TEXT NOT NULL DEFAULT 'straight_line', "monthly_depreciation" REAL NOT NULL DEFAULT '0.00', "accumulated_depreciation" REAL NOT NULL DEFAULT '0.00', "book_value" REAL NOT NULL DEFAULT '0.00', "account_asset_id" INTEGER, "account_accum_dep_id" INTEGER, "account_dep_expense_id" INTEGER, "status" TEXT NOT NULL DEFAULT 'active', "disposal_date" TEXT, "disposal_value" REAL NOT NULL DEFAULT '0.00', "notes" TEXT, "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "fixed_assets" ("id", "asset_code", "name", "category", "branch_id", "serial_no", "plate_no", "acquisition_date", "acquisition_cost", "salvage_value", "useful_life_months", "depreciation_method", "monthly_depreciation", "accumulated_depreciation", "book_value", "account_asset_id", "account_accum_dep_id", "account_dep_expense_id", "status", "disposal_date", "disposal_value", "notes", "created_at", "updated_at") VALUES ('1', 'FA-001', 'Truk Colt Diesel Engkel', 'kendaraan', '1', NULL, 'B 1234 ABC', '2020-01-20', '250000000', '25000000', '60', 'straight_line', '3750000', '3750000', '246250000', NULL, NULL, NULL, 'active', NULL, '0', NULL, '2026-06-24 03:18:14', '2026-06-24 03:20:34');
INSERT INTO "fixed_assets" ("id", "asset_code", "name", "category", "branch_id", "serial_no", "plate_no", "acquisition_date", "acquisition_cost", "salvage_value", "useful_life_months", "depreciation_method", "monthly_depreciation", "accumulated_depreciation", "book_value", "account_asset_id", "account_accum_dep_id", "account_dep_expense_id", "status", "disposal_date", "disposal_value", "notes", "created_at", "updated_at") VALUES ('2', 'FA-002', 'Truk Engkel Bekasi', 'kendaraan', '2', NULL, 'B 5678 DEF', '2020-03-15', '180000000', '18000000', '60', 'straight_line', '2700000', '0', '180000000', NULL, NULL, NULL, 'active', NULL, '0', NULL, '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "fixed_assets" ("id", "asset_code", "name", "category", "branch_id", "serial_no", "plate_no", "acquisition_date", "acquisition_cost", "salvage_value", "useful_life_months", "depreciation_method", "monthly_depreciation", "accumulated_depreciation", "book_value", "account_asset_id", "account_accum_dep_id", "account_dep_expense_id", "status", "disposal_date", "disposal_value", "notes", "created_at", "updated_at") VALUES ('3', 'FA-003', 'Gudang Pusat Jakarta', 'bangunan', '1', NULL, NULL, '2019-06-01', '1500000000', '150000000', '300', 'straight_line', '4500000', '0', '1500000000', NULL, NULL, NULL, 'active', NULL, '0', NULL, '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "fixed_assets" ("id", "asset_code", "name", "category", "branch_id", "serial_no", "plate_no", "acquisition_date", "acquisition_cost", "salvage_value", "useful_life_months", "depreciation_method", "monthly_depreciation", "accumulated_depreciation", "book_value", "account_asset_id", "account_accum_dep_id", "account_dep_expense_id", "status", "disposal_date", "disposal_value", "notes", "created_at", "updated_at") VALUES ('4', 'FA-004', 'Gudang Bekasi', 'bangunan', '2', NULL, NULL, '2020-03-01', '800000000', '80000000', '300', 'straight_line', '2400000', '0', '800000000', NULL, NULL, NULL, 'active', NULL, '0', NULL, '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "fixed_assets" ("id", "asset_code", "name", "category", "branch_id", "serial_no", "plate_no", "acquisition_date", "acquisition_cost", "salvage_value", "useful_life_months", "depreciation_method", "monthly_depreciation", "accumulated_depreciation", "book_value", "account_asset_id", "account_accum_dep_id", "account_dep_expense_id", "status", "disposal_date", "disposal_value", "notes", "created_at", "updated_at") VALUES ('5', 'FA-005', 'Forklift Toyota 2.5T', 'peralatan', '1', 'TY-250T-001', NULL, '2021-01-10', '75000000', '7500000', '60', 'straight_line', '1125000', '0', '75000000', NULL, NULL, NULL, 'active', NULL, '0', NULL, '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "fixed_assets" ("id", "asset_code", "name", "category", "branch_id", "serial_no", "plate_no", "acquisition_date", "acquisition_cost", "salvage_value", "useful_life_months", "depreciation_method", "monthly_depreciation", "accumulated_depreciation", "book_value", "account_asset_id", "account_accum_dep_id", "account_dep_expense_id", "status", "disposal_date", "disposal_value", "notes", "created_at", "updated_at") VALUES ('6', 'FA-006', 'Komputer & Printer Kasir', 'inventaris', '1', NULL, NULL, '2022-01-01', '15000000', '1500000', '36', 'straight_line', '375000', '0', '15000000', NULL, NULL, NULL, 'active', NULL, '0', NULL, '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "fixed_assets" ("id", "asset_code", "name", "category", "branch_id", "serial_no", "plate_no", "acquisition_date", "acquisition_cost", "salvage_value", "useful_life_months", "depreciation_method", "monthly_depreciation", "accumulated_depreciation", "book_value", "account_asset_id", "account_accum_dep_id", "account_dep_expense_id", "status", "disposal_date", "disposal_value", "notes", "created_at", "updated_at") VALUES ('7', 'FA-007', 'Rak Besi Gudang Pusat', 'inventaris', '1', NULL, NULL, '2020-01-15', '35000000', '3500000', '120', 'straight_line', '262500', '0', '35000000', NULL, NULL, NULL, 'active', NULL, '0', NULL, '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "fixed_assets" ("id", "asset_code", "name", "category", "branch_id", "serial_no", "plate_no", "acquisition_date", "acquisition_cost", "salvage_value", "useful_life_months", "depreciation_method", "monthly_depreciation", "accumulated_depreciation", "book_value", "account_asset_id", "account_accum_dep_id", "account_dep_expense_id", "status", "disposal_date", "disposal_value", "notes", "created_at", "updated_at") VALUES ('8', 'FA-008', 'Sepeda Motor Sales', 'kendaraan', '1', NULL, 'B 9999 XYZ', '2021-06-01', '25000000', '2500000', '60', 'straight_line', '375000', '0', '25000000', NULL, NULL, NULL, 'active', NULL, '0', NULL, '2026-06-24 03:18:14', '2026-06-24 03:18:14');

-- Table: iot_sensor_readings
DROP TABLE IF EXISTS "iot_sensor_readings";
CREATE TABLE "iot_sensor_readings" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "sensor_id" INTEGER NOT NULL, "value" REAL NOT NULL, "unit" TEXT, "read_at" TEXT NOT NULL DEFAULT 'current_timestamp()', "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: iot_sensors
DROP TABLE IF EXISTS "iot_sensors";
CREATE TABLE "iot_sensors" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "tenant_id" INTEGER, "warehouse_id" INTEGER, "sensor_id" TEXT NOT NULL, "name" TEXT NOT NULL, "type" TEXT NOT NULL, "location" TEXT, "is_active" INTEGER NOT NULL DEFAULT '1', "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: journal_entries
DROP TABLE IF EXISTS "journal_entries";
CREATE TABLE "journal_entries" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "tenant_id" INTEGER, "journal_no" TEXT NOT NULL, "entry_date" TEXT NOT NULL, "description" TEXT NOT NULL, "reference_type" TEXT, "reference_id" INTEGER, "status" TEXT NOT NULL DEFAULT 'posted', "created_by" INTEGER NOT NULL, "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "journal_entries" ("id", "tenant_id", "journal_no", "entry_date", "description", "reference_type", "reference_id", "status", "created_by", "created_at", "updated_at") VALUES ('1', NULL, 'JE-CT-1-20260624', '2024-06-01', 'Beli perlengkapan kantor (CT202606240001)', 'cash_transaction', '1', 'posted', '1', '2026-06-24 03:20:34', '2026-06-24 03:20:34');
INSERT INTO "journal_entries" ("id", "tenant_id", "journal_no", "entry_date", "description", "reference_type", "reference_id", "status", "created_by", "created_at", "updated_at") VALUES ('2', NULL, 'JE-DEP-1-20240630', '2024-06-30', 'Penyusutan aset FA-001 - Truk Colt Diesel Engkel', 'asset_depreciation', '1', 'posted', '1', '2026-06-24 03:20:34', '2026-06-24 03:20:34');

-- Table: journal_entry_lines
DROP TABLE IF EXISTS "journal_entry_lines";
CREATE TABLE "journal_entry_lines" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "journal_entry_id" INTEGER NOT NULL, "account_id" INTEGER NOT NULL, "debit" REAL NOT NULL DEFAULT '0.00', "credit" REAL NOT NULL DEFAULT '0.00', "description" TEXT, "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "journal_entry_lines" ("id", "journal_entry_id", "account_id", "debit", "credit", "description", "created_at", "updated_at") VALUES ('1', '1', '42', '500000', '0', 'Beli perlengkapan kantor', '2026-06-24 03:20:34', '2026-06-24 03:20:34');
INSERT INTO "journal_entry_lines" ("id", "journal_entry_id", "account_id", "debit", "credit", "description", "created_at", "updated_at") VALUES ('2', '1', '2', '0', '500000', 'Beli perlengkapan kantor', '2026-06-24 03:20:34', '2026-06-24 03:20:34');
INSERT INTO "journal_entry_lines" ("id", "journal_entry_id", "account_id", "debit", "credit", "description", "created_at", "updated_at") VALUES ('3', '2', '39', '3750000', '0', 'Beban penyusutan Truk Colt Diesel Engkel', '2026-06-24 03:20:34', '2026-06-24 03:20:34');
INSERT INTO "journal_entry_lines" ("id", "journal_entry_id", "account_id", "debit", "credit", "description", "created_at", "updated_at") VALUES ('4', '2', '13', '0', '3750000', 'Akumulasi penyusutan Truk Colt Diesel Engkel', '2026-06-24 03:20:34', '2026-06-24 03:20:34');

-- Table: landed_cost_distributions
DROP TABLE IF EXISTS "landed_cost_distributions";
CREATE TABLE landed_cost_distributions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    purchase_order_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    freight_allocated REAL DEFAULT 0,
    insurance_allocated REAL DEFAULT 0,
    handling_allocated REAL DEFAULT 0,
    total_landed_cost REAL DEFAULT 0,
    quantity REAL NOT NULL,
    landed_unit_cost REAL NOT NULL,
    distribution_method TEXT DEFAULT "by_value",
    created_at TEXT
);

-- (no data)

-- Table: marketplace_integrations
DROP TABLE IF EXISTS "marketplace_integrations";
CREATE TABLE "marketplace_integrations" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "tenant_id" INTEGER, "platform" TEXT NOT NULL, "shop_id" TEXT NOT NULL, "shop_name" TEXT NOT NULL, "access_token" TEXT, "refresh_token" TEXT, "token_expires_at" TEXT, "status" TEXT NOT NULL DEFAULT 'disconnected', "last_synced_at" TEXT, "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: marketplace_product_mappings
DROP TABLE IF EXISTS "marketplace_product_mappings";
CREATE TABLE "marketplace_product_mappings" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "integration_id" INTEGER NOT NULL, "product_id" INTEGER NOT NULL, "marketplace_item_id" TEXT NOT NULL, "marketplace_url" TEXT, "marketplace_price" REAL, "marketplace_stock" INTEGER, "last_synced_at" TEXT, "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: migrations
DROP TABLE IF EXISTS "migrations";
CREATE TABLE "migrations" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "migration" TEXT NOT NULL, "batch" INTEGER NOT NULL);

INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('1', '2019_12_14_000001_create_personal_access_tokens_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('2', '2024_01_01_000001_create_roles_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('3', '2024_01_01_000002_create_permissions_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('4', '2024_01_01_000003_create_role_permission_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('5', '2024_01_01_000004_create_customer_groups_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('6', '2024_01_01_000005_create_categories_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('7', '2024_01_01_000006_create_users_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('8', '2024_01_01_000007_create_customers_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('9', '2024_01_01_000008_create_suppliers_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('10', '2024_01_01_000009_create_product_units_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('11', '2024_01_01_000010_create_products_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('12', '2024_01_01_000011_create_barcodes_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('13', '2024_01_01_000012_create_stock_movements_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('14', '2024_01_01_000013_create_sales_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('15', '2024_01_01_000014_create_sale_items_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('16', '2024_01_01_000015_create_sale_payments_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('17', '2024_01_01_000016_create_purchase_orders_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('18', '2024_01_01_000017_create_purchase_items_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('19', '2024_01_01_000018_create_purchase_payments_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('20', '2024_01_01_000019_create_accounts_receivable_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('21', '2024_01_01_000020_create_accounts_payable_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('22', '2024_01_01_000021_create_payments_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('23', '2024_01_01_000022_create_stock_adjustments_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('24', '2024_01_01_000023_create_stock_opnames_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('25', '2024_01_01_000024_create_opname_items_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('26', '2024_01_01_000025_create_audit_logs_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('27', '2024_01_01_000026_create_deliveries_and_app_settings', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('28', '2024_01_01_000027_create_accounting_tables', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('29', '2024_01_01_000028_create_multi_tenant_tables', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('30', '2024_01_01_000029_create_phase4_tables', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('31', '2024_01_01_000030_create_sales_returns_tables', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('32', '2024_01_01_000031_create_purchase_returns_tables', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('33', '2024_01_01_000032_create_quotations_tables', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('34', '2024_01_01_000033_create_sales_orders_tables', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('35', '2024_01_01_000034_add_delivery_cost_landed_cost_bonus_fields', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('36', '2024_01_01_000035_create_pricing_tables', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('37', '2024_01_01_000036_create_branches_employees_assets_cash_tables', '1');

-- Table: opname_items
DROP TABLE IF EXISTS "opname_items";
CREATE TABLE "opname_items" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "opname_id" INTEGER NOT NULL, "product_id" INTEGER NOT NULL, "system_qty" REAL NOT NULL, "physical_qty" REAL NOT NULL, "difference" REAL NOT NULL, "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()');

-- (no data)

-- Table: partial_deliveries
DROP TABLE IF EXISTS "partial_deliveries";
CREATE TABLE partial_deliveries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sale_id INTEGER NOT NULL,
    delivery_id INTEGER,
    sale_item_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    ordered_qty REAL NOT NULL,
    delivered_qty REAL NOT NULL,
    remaining_qty REAL NOT NULL,
    delivery_date TEXT NOT NULL,
    status TEXT DEFAULT "partial",
    notes TEXT,
    created_at TEXT
);

-- (no data)

-- Table: payments
DROP TABLE IF EXISTS "payments";
CREATE TABLE "payments" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "payable_id" INTEGER NOT NULL, "payable_type" TEXT NOT NULL, "amount" REAL NOT NULL, "payment_date" TEXT NOT NULL, "payment_method" TEXT NOT NULL, "notes" TEXT, "created_by" INTEGER, "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()');

-- (no data)

-- Table: period_closings
DROP TABLE IF EXISTS "period_closings";
CREATE TABLE period_closings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    period_year INTEGER NOT NULL,
    period_month INTEGER NOT NULL,
    status TEXT DEFAULT "open",
    closed_by INTEGER,
    closed_at TEXT,
    notes TEXT,
    created_at TEXT,
    updated_at TEXT,
    UNIQUE(period_year, period_month)
);

-- (no data)

-- Table: permissions
DROP TABLE IF EXISTS "permissions";
CREATE TABLE "permissions" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" TEXT NOT NULL, "description" TEXT, "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "permissions" ("id", "name", "description", "created_at", "updated_at") VALUES ('1', 'create_sales', 'Create sales', '2026-06-24 03:18:11', '2026-06-24 03:18:11');
INSERT INTO "permissions" ("id", "name", "description", "created_at", "updated_at") VALUES ('2', 'edit_sales', 'Edit sales', '2026-06-24 03:18:11', '2026-06-24 03:18:11');
INSERT INTO "permissions" ("id", "name", "description", "created_at", "updated_at") VALUES ('3', 'void_sales', 'Void sales', '2026-06-24 03:18:11', '2026-06-24 03:18:11');
INSERT INTO "permissions" ("id", "name", "description", "created_at", "updated_at") VALUES ('4', 'view_profit', 'View profit', '2026-06-24 03:18:11', '2026-06-24 03:18:11');
INSERT INTO "permissions" ("id", "name", "description", "created_at", "updated_at") VALUES ('5', 'manage_products', 'Manage products', '2026-06-24 03:18:11', '2026-06-24 03:18:11');
INSERT INTO "permissions" ("id", "name", "description", "created_at", "updated_at") VALUES ('6', 'stock_adjustment', 'Stock adjustment', '2026-06-24 03:18:11', '2026-06-24 03:18:11');
INSERT INTO "permissions" ("id", "name", "description", "created_at", "updated_at") VALUES ('7', 'approve_adjustment', 'Approve adjustment', '2026-06-24 03:18:11', '2026-06-24 03:18:11');
INSERT INTO "permissions" ("id", "name", "description", "created_at", "updated_at") VALUES ('8', 'manage_customers', 'Manage customers', '2026-06-24 03:18:11', '2026-06-24 03:18:11');
INSERT INTO "permissions" ("id", "name", "description", "created_at", "updated_at") VALUES ('9', 'manage_suppliers', 'Manage suppliers', '2026-06-24 03:18:11', '2026-06-24 03:18:11');
INSERT INTO "permissions" ("id", "name", "description", "created_at", "updated_at") VALUES ('10', 'record_payment', 'Record payment', '2026-06-24 03:18:11', '2026-06-24 03:18:11');
INSERT INTO "permissions" ("id", "name", "description", "created_at", "updated_at") VALUES ('11', 'view_reports', 'View reports', '2026-06-24 03:18:11', '2026-06-24 03:18:11');
INSERT INTO "permissions" ("id", "name", "description", "created_at", "updated_at") VALUES ('12', 'manage_users', 'Manage users', '2026-06-24 03:18:11', '2026-06-24 03:18:11');

-- Table: personal_access_tokens
DROP TABLE IF EXISTS "personal_access_tokens";
CREATE TABLE "personal_access_tokens" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "tokenable_type" TEXT NOT NULL, "tokenable_id" INTEGER NOT NULL, "name" TEXT NOT NULL, "token" TEXT NOT NULL, "abilities" TEXT, "last_used_at" TEXT, "expires_at" TEXT, "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "personal_access_tokens" ("id", "tokenable_type", "tokenable_id", "name", "token", "abilities", "last_used_at", "expires_at", "created_at", "updated_at") VALUES ('1', 'App\Models\User', '1', 'auth-token', '74fde766ff8e50d60c0e5a16e85a706c677e77c6e40659b134336dabc8e510c7', '["*"]', '2026-06-24 03:20:34', NULL, '2026-06-24 03:20:33', '2026-06-24 03:20:34');

-- Table: price_optimizations
DROP TABLE IF EXISTS "price_optimizations";
CREATE TABLE "price_optimizations" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "product_id" INTEGER NOT NULL, "tenant_id" INTEGER, "current_price" REAL NOT NULL, "suggested_price" REAL NOT NULL, "current_margin" REAL NOT NULL, "suggested_margin" REAL NOT NULL, "estimated_demand_change" REAL NOT NULL, "estimated_revenue_change" REAL NOT NULL, "reasoning" TEXT, "generated_date" TEXT NOT NULL, "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: product_batches
DROP TABLE IF EXISTS "product_batches";
CREATE TABLE product_batches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    batch_no TEXT,
    lot_no TEXT,
    received_date TEXT NOT NULL,
    expiry_date TEXT,
    quantity_received REAL NOT NULL,
    quantity_remaining REAL NOT NULL,
    unit_cost REAL NOT NULL,
    landed_unit_cost REAL,
    supplier_id INTEGER,
    purchase_order_id INTEGER,
    status TEXT DEFAULT "active",
    notes TEXT,
    created_at TEXT,
    updated_at TEXT
);

-- (no data)

-- Table: product_tier_prices
DROP TABLE IF EXISTS "product_tier_prices";
CREATE TABLE "product_tier_prices" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "product_id" INTEGER NOT NULL, "unit_id" INTEGER NOT NULL, "min_qty" REAL NOT NULL, "max_qty" REAL, "unit_price" REAL NOT NULL, "is_active" INTEGER NOT NULL DEFAULT '1', "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: product_units
DROP TABLE IF EXISTS "product_units";
CREATE TABLE "product_units" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "product_id" INTEGER NOT NULL, "unit_name" TEXT NOT NULL, "conversion_factor" REAL NOT NULL, "is_base_unit" INTEGER NOT NULL DEFAULT '0', "price_per_unit" REAL NOT NULL DEFAULT '0.00', "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('1', '1', 'sak', '1', '1', '65000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('2', '1', 'ton', '25', '0', '1625000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('3', '2', 'sak', '1', '1', '64000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('4', '2', 'ton', '25', '0', '1600000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('5', '3', 'sak', '1', '1', '63000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('6', '3', 'ton', '25', '0', '1575000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('7', '4', 'sak', '1', '1', '110000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('8', '4', 'ton', '25', '0', '2750000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('9', '5', 'sak', '1', '1', '98000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('10', '6', 'sak', '1', '1', '370000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('11', '7', 'pcs', '1', '1', '28000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('12', '7', 'm3', '83', '0', '2324000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('13', '8', 'batang', '1', '1', '56000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('14', '8', 'kg', '7.4', '0', '7568', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('15', '8', 'ton', '7400', '0', '56000000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('16', '9', 'batang', '1', '1', '81000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('17', '9', 'kg', '10.66', '0', '7598', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('18', '9', 'ton', '10660', '0', '81000000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('19', '10', 'batang', '1', '1', '77000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('20', '10', 'kg', '12.5', '0', '6160', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('21', '10', 'ton', '12500', '0', '77000000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('22', '11', 'roll', '1', '1', '155000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('23', '11', 'kg', '15', '0', '10333', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('24', '12', 'lembar', '1', '1', '660000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('25', '12', 'm2', '72', '0', '9167', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('26', '13', 'lembar', '1', '1', '145000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('27', '13', 'm', '3', '0', '48333', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('28', '14', 'galon', '1', '1', '890000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('29', '14', 'kg', '25', '0', '35600', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('30', '15', 'galon', '1', '1', '780000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('31', '15', 'kg', '25', '0', '31200', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('32', '16', 'galon', '1', '1', '860000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('33', '16', 'kg', '25', '0', '34400', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('34', '17', 'kaleng', '1', '1', '168000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('35', '17', 'kg', '2.5', '0', '67200', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('36', '18', 'galon', '1', '1', '78000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('37', '18', 'liter', '5', '0', '15600', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('38', '19', 'kaleng', '1', '1', '92000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('39', '19', 'kg', '5', '0', '18400', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('40', '20', 'kaleng', '1', '1', '215000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('41', '20', 'kg', '4', '0', '53750', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('42', '21', 'dus', '1', '1', '38000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('43', '21', 'm2', '1.08', '0', '35185', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('44', '21', 'pcs', '12', '0', '3167', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('45', '22', 'dus', '1', '1', '56000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('46', '22', 'm2', '1.6', '0', '35000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('47', '22', 'pcs', '10', '0', '5600', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('48', '23', 'dus', '1', '1', '195000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('49', '23', 'm2', '1.44', '0', '135417', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('50', '23', 'pcs', '4', '0', '48750', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('51', '24', 'lembar', '1', '1', '980000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('52', '24', 'm2', '4.46', '0', '219731', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('53', '25', 'lembar', '1', '1', '1550000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('54', '25', 'm2', '4.46', '0', '347534', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('55', '26', 'lembar', '1', '1', '230000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('56', '26', 'm2', '2.98', '0', '77181', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('57', '27', 'lembar', '1', '1', '330000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('58', '27', 'm2', '2.98', '0', '110738', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('59', '28', 'batang', '1', '1', '100000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('60', '28', 'm3', '0.01', '0', '10416667', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('61', '29', 'pcs', '1', '1', '5500', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('62', '29', 'm2', '10', '0', '55000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('63', '30', 'lembar', '1', '1', '145000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('64', '30', 'm', '3', '0', '48333', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('65', '31', 'batang', '1', '1', '112000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('66', '31', 'm', '4', '0', '28000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('67', '32', 'set', '1', '1', '2150000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('68', '33', 'batang', '1', '1', '92000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('69', '33', 'm', '4', '0', '23000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('70', '34', 'batang', '1', '1', '62000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('71', '34', 'm', '4', '0', '15500', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('72', '35', 'pcs', '1', '1', '102000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('73', '36', 'set', '1', '1', '780000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('74', '37', 'pcs', '1', '1', '35000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('75', '38', 'set', '1', '1', '680000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "product_units" ("id", "product_id", "unit_name", "conversion_factor", "is_base_unit", "price_per_unit", "created_at", "updated_at") VALUES ('76', '39', 'pcs', '1', '1', '42000', '2026-06-24 03:18:13', '2026-06-24 03:18:13');

-- Table: products
DROP TABLE IF EXISTS "products";
CREATE TABLE "products" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "tenant_id" INTEGER, "code" TEXT NOT NULL, "name" TEXT NOT NULL, "alias" TEXT, "category_id" INTEGER, "brand" TEXT, "min_stock" REAL NOT NULL DEFAULT '0.000', "max_stock" REAL NOT NULL DEFAULT '0.000', "location" TEXT, "warehouse_location_id" INTEGER, "buy_price" REAL NOT NULL DEFAULT '0.00', "sell_price" REAL NOT NULL DEFAULT '0.00', "weight_kg" REAL NOT NULL DEFAULT '0.000', "length_cm" REAL NOT NULL DEFAULT '0.00', "width_cm" REAL NOT NULL DEFAULT '0.00', "height_cm" REAL NOT NULL DEFAULT '0.00', "is_active" INTEGER NOT NULL DEFAULT '1', "created_at" TEXT, "updated_at" TEXT, landed_cost REAL DEFAULT 0);

INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('1', NULL, 'SMT-GRK-40', 'Semen Gresik Portland 40kg', NULL, '10', 'Semen Gresik', '50', '1000', 'A-01', NULL, '58000', '65000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('2', NULL, 'SMT-TRD-40', 'Semen Tiga Roda Portland 40kg', NULL, '10', 'Tiga Roda', '50', '1000', 'A-02', NULL, '57000', '64000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('3', NULL, 'SMT-HLC-40', 'Semen Holcim Portland 40kg', NULL, '10', 'Holcim', '50', '800', 'A-03', NULL, '56000', '63000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('4', NULL, 'SMT-PTH-40', 'Semen Putih Gresik 40kg', NULL, '11', 'Semen Gresik', '20', '300', 'A-04', NULL, '95000', '110000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('5', NULL, 'MRT-WBR-25', 'Mortar Weber TileFix 25kg', NULL, '12', 'Weber', '10', '200', 'A-05', NULL, '85000', '98000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('6', NULL, 'MRT-SKA-25', 'SikaGrout 215 Powder 25kg', NULL, '12', 'Sika', '5', '50', 'A-06', NULL, '320000', '370000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('7', NULL, 'HBL-600-100', 'Hebel Block 600x200x100mm', NULL, '13', 'Hebel', '100', '2000', 'A-07', NULL, '22000', '28000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('8', NULL, 'BSI-KS-D10', 'Besi Beton KS D10mm 12m', NULL, '14', 'KS', '50', '500', 'B-01', NULL, '49000', '56000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('9', NULL, 'BSI-KS-D12', 'Besi Beton KS D12mm 12m', NULL, '14', 'KS', '50', '500', 'B-02', NULL, '71000', '81000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('10', NULL, 'BSI-SNI-D13', 'Besi Beton SNI D13mm 12m', NULL, '14', 'SNI', '50', '500', 'B-03', NULL, '68000', '77000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('11', NULL, 'KWT-BND-2', 'Kawat Bendrat BWG 2mm 15kg', NULL, '17', 'Bendrat', '20', '200', 'B-04', NULL, '135000', '155000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('12', NULL, 'WMH-M4-612', 'Wiremesh M4 6x12m', NULL, '17', 'SNI', '10', '100', 'B-05', NULL, '580000', '660000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('13', NULL, 'SPD-04-109', 'Spandek 0.4mm 1090mm', NULL, '18', 'SNI', '30', '300', 'B-06', NULL, '125000', '145000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('14', NULL, 'CAT-DLX-25', 'Cat Tembok Dulux Vitex 25kg', NULL, '19', 'Dulux', '10', '100', 'C-01', NULL, '780000', '890000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('15', NULL, 'CAT-AVN-25', 'Cat Tembok Avian 25kg', NULL, '19', 'Avian', '10', '100', 'C-02', NULL, '680000', '780000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('16', NULL, 'CAT-NPP-25', 'Cat Tembok Nippon 25kg', NULL, '19', 'Nippon Paint', '10', '100', 'C-03', NULL, '750000', '860000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('17', NULL, 'CAT-KY-NP', 'Cat Kayu Nippon 2.5kg', NULL, '20', 'Nippon Paint', '10', '100', 'C-04', NULL, '145000', '168000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('18', NULL, 'THN-A-5L', 'Thinner A 5 Liter', NULL, '21', 'Generic', '15', '150', 'C-05', NULL, '65000', '78000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('19', NULL, 'PLM-DLX-5', 'Plamir Dulux 5kg', NULL, '22', 'Dulux', '10', '100', 'C-06', NULL, '78000', '92000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('20', NULL, 'WP-SKA-4', 'Waterproofing Sika 4kg', NULL, '22', 'Sika', '5', '50', 'C-07', NULL, '185000', '215000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('21', NULL, 'KRM-RMN-3030', 'Keramik Roman 30x30cm', NULL, '23', 'Roman', '30', '300', 'D-01', NULL, '32000', '38000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('22', NULL, 'KRM-RMN-4040', 'Keramik Roman 40x40cm', NULL, '23', 'Roman', '30', '300', 'D-02', NULL, '48000', '56000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('23', NULL, 'GRN-6060-POL', 'Granit 60x60cm Polished', NULL, '25', 'Roman', '20', '200', 'D-03', NULL, '165000', '195000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('24', NULL, 'KCA-5-183244', 'Kaca Bening 5mm 183x244cm', NULL, '27', 'Asahimas', '10', '80', 'E-01', NULL, '850000', '980000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('25', NULL, 'KCA-8-183244', 'Kaca Bening 8mm 183x244cm', NULL, '27', 'Asahimas', '5', '50', 'E-02', NULL, '1350000', '1550000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('26', NULL, 'PLY-MRN-9', 'Plywood Meranti 9mm 122x244cm', NULL, '31', 'Meranti', '20', '200', 'F-01', NULL, '195000', '230000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('27', NULL, 'MDF-18-122244', 'MDF Board 18mm 122x244cm', NULL, '32', 'Sunshine', '15', '150', 'F-02', NULL, '285000', '330000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('28', NULL, 'KYU-KMP-46', 'Kayu Kamper 4x6cm 4m', NULL, '30', 'Kamper', '30', '300', 'F-03', NULL, '85000', '100000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('29', NULL, 'GNT-BTN-KMR', 'Genteng Beton Kanmuri', NULL, '33', 'Kanmuri', '500', '10000', 'G-01', NULL, '4500', '5500', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('30', NULL, 'SPD-MR-109', 'Spandek Metal Roof 0.4mm 1090mm', NULL, '34', 'SNI', '30', '300', 'G-02', NULL, '125000', '145000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('31', NULL, 'TLG-PVC-6', 'Talang Air PVC 6 inch 4m', NULL, '35', 'Vinilon', '20', '200', 'G-03', NULL, '95000', '112000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('32', NULL, 'CLS-TTO-621', 'Closet TOTO CW621J', NULL, '36', 'TOTO', '5', '50', 'H-01', NULL, '1850000', '2150000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('33', NULL, 'PVC-RUC-4', 'Pipa PVC Ruciruca 4 inch 4m', NULL, '39', 'Ruciruca', '30', '300', 'H-02', NULL, '78000', '92000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('34', NULL, 'PVC-VNL-3', 'Pipa PVC Vinilon 3 inch 4m', NULL, '39', 'Vinilon', '30', '300', 'H-03', NULL, '52000', '62000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('35', NULL, 'KRN-TTO-12', 'Kran Air TOTO 1/2 inch', NULL, '38', 'TOTO', '15', '150', 'H-04', NULL, '85000', '102000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('36', NULL, 'WST-TTO-LSN', 'Washtafel TOTO Lavatory', NULL, '37', 'TOTO', '5', '50', 'H-05', NULL, '650000', '780000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('37', NULL, 'MTR-FST-5', 'Meteran Fiber 5m', NULL, '40', 'Fastway', '20', '200', 'I-01', NULL, '28000', '35000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('38', NULL, 'BOR-MKT-13', 'Bor Makita HP1630 13mm', NULL, '40', 'Makita', '5', '50', 'I-02', NULL, '580000', '680000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');
INSERT INTO "products" ("id", "tenant_id", "code", "name", "alias", "category_id", "brand", "min_stock", "max_stock", "location", "warehouse_location_id", "buy_price", "sell_price", "weight_kg", "length_cm", "width_cm", "height_cm", "is_active", "created_at", "updated_at", "landed_cost") VALUES ('39', NULL, 'HMT-SFT-YL', 'Helm Safety SNI Yellow', NULL, '41', 'Safetoe', '20', '200', 'I-03', NULL, '32000', '42000', '0', '0', '0', '0', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13', '0');

-- Table: purchase_items
DROP TABLE IF EXISTS "purchase_items";
CREATE TABLE "purchase_items" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "po_id" INTEGER NOT NULL, "product_id" INTEGER NOT NULL, "quantity" REAL NOT NULL, "bonus_qty" REAL NOT NULL DEFAULT '0.000', "received_quantity" REAL NOT NULL DEFAULT '0.000', "unit_id" INTEGER NOT NULL, "unit_price" REAL NOT NULL, "subtotal" REAL NOT NULL, "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()');

-- (no data)

-- Table: purchase_orders
DROP TABLE IF EXISTS "purchase_orders";
CREATE TABLE "purchase_orders" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "tenant_id" INTEGER, "po_number" TEXT NOT NULL, "supplier_id" INTEGER NOT NULL, "po_date" TEXT NOT NULL, "subtotal" REAL NOT NULL, "discount" REAL NOT NULL DEFAULT '0.00', "tax" REAL NOT NULL DEFAULT '0.00', "total" REAL NOT NULL, "freight_cost" REAL NOT NULL DEFAULT '0.00', "insurance_cost" REAL NOT NULL DEFAULT '0.00', "handling_cost" REAL NOT NULL DEFAULT '0.00', "landed_total" REAL NOT NULL DEFAULT '0.00', "payment_status" TEXT NOT NULL DEFAULT 'unpaid', "status" TEXT NOT NULL DEFAULT 'draft', "notes" TEXT, "created_by" INTEGER, "branch_id" INTEGER, "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: purchase_payments
DROP TABLE IF EXISTS "purchase_payments";
CREATE TABLE "purchase_payments" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "po_id" INTEGER NOT NULL, "amount" REAL NOT NULL, "payment_method" TEXT NOT NULL, "payment_date" TEXT NOT NULL, "notes" TEXT, "created_by" INTEGER, "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()');

-- (no data)

-- Table: purchase_return_items
DROP TABLE IF EXISTS "purchase_return_items";
CREATE TABLE "purchase_return_items" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "purchase_return_id" INTEGER NOT NULL, "purchase_item_id" INTEGER NOT NULL, "product_id" INTEGER NOT NULL, "quantity" REAL NOT NULL, "unit_id" INTEGER NOT NULL, "unit_price" REAL NOT NULL, "refund_amount" REAL NOT NULL, "reason" TEXT, "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()');

-- (no data)

-- Table: purchase_returns
DROP TABLE IF EXISTS "purchase_returns";
CREATE TABLE "purchase_returns" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "return_no" TEXT NOT NULL, "po_id" INTEGER NOT NULL, "supplier_id" INTEGER NOT NULL, "return_date" TEXT NOT NULL, "total_refund" REAL NOT NULL, "refund_method" TEXT NOT NULL DEFAULT 'credit', "status" TEXT NOT NULL DEFAULT 'pending', "reason" TEXT NOT NULL, "notes" TEXT, "approved_by" INTEGER, "created_by" INTEGER, "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: quotation_items
DROP TABLE IF EXISTS "quotation_items";
CREATE TABLE "quotation_items" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "quotation_id" INTEGER NOT NULL, "product_id" INTEGER NOT NULL, "quantity" REAL NOT NULL, "bonus_qty" REAL NOT NULL DEFAULT '0.000', "unit_id" INTEGER NOT NULL, "unit_price" REAL NOT NULL, "discount" REAL NOT NULL DEFAULT '0.00', "subtotal" REAL NOT NULL, "notes" TEXT, "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()');

-- (no data)

-- Table: quotations
DROP TABLE IF EXISTS "quotations";
CREATE TABLE "quotations" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "quote_no" TEXT NOT NULL, "customer_id" INTEGER, "customer_name" TEXT NOT NULL DEFAULT 'Walk-in Customer', "quote_date" TEXT NOT NULL, "valid_until" TEXT NOT NULL, "subtotal" REAL NOT NULL, "discount" REAL NOT NULL DEFAULT '0.00', "tax" REAL NOT NULL DEFAULT '0.00', "total" REAL NOT NULL, "status" TEXT NOT NULL DEFAULT 'draft', "notes" TEXT, "delivery_address" TEXT, "created_by" INTEGER, "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: reorder_suggestions
DROP TABLE IF EXISTS "reorder_suggestions";
CREATE TABLE "reorder_suggestions" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "product_id" INTEGER NOT NULL, "current_stock" REAL NOT NULL, "avg_daily_usage" REAL NOT NULL, "days_of_supply" INTEGER NOT NULL, "suggested_order_qty" REAL NOT NULL, "priority" TEXT NOT NULL, "reason" TEXT, "generated_date" TEXT NOT NULL, "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: role_permission
DROP TABLE IF EXISTS "role_permission";
CREATE TABLE "role_permission" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "role_id" INTEGER NOT NULL, "permission_id" INTEGER NOT NULL, "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('1', '1', '1', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('2', '1', '2', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('3', '1', '3', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('4', '1', '4', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('5', '1', '5', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('6', '1', '6', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('7', '1', '7', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('8', '1', '8', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('9', '1', '9', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('10', '1', '10', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('11', '1', '11', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('12', '1', '12', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('13', '2', '1', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('14', '2', '2', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('15', '2', '3', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('16', '2', '4', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('17', '2', '5', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('18', '2', '6', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('19', '2', '7', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('20', '2', '8', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('21', '2', '9', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('22', '2', '10', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('23', '2', '11', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('24', '3', '1', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('25', '3', '2', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('26', '3', '8', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('27', '3', '10', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('28', '4', '5', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('29', '4', '6', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('30', '4', '9', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('31', '5', '8', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('32', '5', '10', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('33', '5', '11', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('34', '6', '4', NULL, NULL);
INSERT INTO "role_permission" ("id", "role_id", "permission_id", "created_at", "updated_at") VALUES ('35', '6', '11', NULL, NULL);

-- Table: roles
DROP TABLE IF EXISTS "roles";
CREATE TABLE "roles" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" TEXT NOT NULL, "slug" TEXT NOT NULL, "description" TEXT, "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "roles" ("id", "name", "slug", "description", "created_at", "updated_at") VALUES ('1', 'Owner', 'owner', 'Full access to all features', '2026-06-24 03:18:11', '2026-06-24 03:18:11');
INSERT INTO "roles" ("id", "name", "slug", "description", "created_at", "updated_at") VALUES ('2', 'Manager', 'manager', 'Manager level access', '2026-06-24 03:18:11', '2026-06-24 03:18:11');
INSERT INTO "roles" ("id", "name", "slug", "description", "created_at", "updated_at") VALUES ('3', 'Kasir', 'kasir', 'Cashier access', '2026-06-24 03:18:11', '2026-06-24 03:18:11');
INSERT INTO "roles" ("id", "name", "slug", "description", "created_at", "updated_at") VALUES ('4', 'Gudang', 'gudang', 'Warehouse access', '2026-06-24 03:18:11', '2026-06-24 03:18:11');
INSERT INTO "roles" ("id", "name", "slug", "description", "created_at", "updated_at") VALUES ('5', 'Accounting', 'accounting', 'Accounting access', '2026-06-24 03:18:11', '2026-06-24 03:18:11');
INSERT INTO "roles" ("id", "name", "slug", "description", "created_at", "updated_at") VALUES ('6', 'Supervisor', 'supervisor', 'Supervisor access', '2026-06-24 03:18:11', '2026-06-24 03:18:11');

-- Table: route_stops
DROP TABLE IF EXISTS "route_stops";
CREATE TABLE route_stops (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        route_id INTEGER NOT NULL,
        delivery_id INTEGER,
        stop_order INTEGER NOT NULL,
        customer_name TEXT,
        address TEXT,
        phone TEXT,
        status TEXT DEFAULT 'pending',
        arrived_at TEXT,
        departed_at TEXT,
        notes TEXT,
        created_at TEXT
    );

-- (no data)

-- Table: sale_items
DROP TABLE IF EXISTS "sale_items";
CREATE TABLE "sale_items" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "sale_id" INTEGER NOT NULL, "product_id" INTEGER NOT NULL, "quantity" REAL NOT NULL, "bonus_qty" REAL NOT NULL DEFAULT '0.000', "unit_id" INTEGER NOT NULL, "unit_price" REAL NOT NULL, "discount" REAL NOT NULL DEFAULT '0.00', "subtotal" REAL NOT NULL, "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()', remaining_qty REAL DEFAULT 0);

-- (no data)

-- Table: sale_payments
DROP TABLE IF EXISTS "sale_payments";
CREATE TABLE "sale_payments" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "sale_id" INTEGER NOT NULL, "amount" REAL NOT NULL, "payment_method" TEXT NOT NULL, "payment_date" TEXT NOT NULL, "notes" TEXT, "created_by" INTEGER, "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()');

-- (no data)

-- Table: sales
DROP TABLE IF EXISTS "sales";
CREATE TABLE "sales" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "tenant_id" INTEGER, "invoice_no" TEXT NOT NULL, "customer_id" INTEGER, "customer_name_snapshot" TEXT NOT NULL DEFAULT 'Walk-in Customer', "sale_date" TEXT NOT NULL, "subtotal" REAL NOT NULL, "discount" REAL NOT NULL DEFAULT '0.00', "tax" REAL NOT NULL DEFAULT '0.00', "total" REAL NOT NULL, "delivery_cost" REAL NOT NULL DEFAULT '0.00', "payment_method" TEXT NOT NULL, "payment_status" TEXT NOT NULL DEFAULT 'unpaid', "status" TEXT NOT NULL DEFAULT 'draft', "notes" TEXT, "delivery_address" TEXT, "created_by" INTEGER, "branch_id" INTEGER, "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: sales_order_items
DROP TABLE IF EXISTS "sales_order_items";
CREATE TABLE "sales_order_items" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "sales_order_id" INTEGER NOT NULL, "product_id" INTEGER NOT NULL, "quantity" REAL NOT NULL, "bonus_qty" REAL NOT NULL DEFAULT '0.000', "delivered_qty" REAL NOT NULL DEFAULT '0.000', "unit_id" INTEGER NOT NULL, "unit_price" REAL NOT NULL, "discount" REAL NOT NULL DEFAULT '0.00', "subtotal" REAL NOT NULL, "notes" TEXT, "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()');

-- (no data)

-- Table: sales_orders
DROP TABLE IF EXISTS "sales_orders";
CREATE TABLE "sales_orders" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "so_number" TEXT NOT NULL, "customer_id" INTEGER, "customer_name" TEXT NOT NULL DEFAULT 'Walk-in Customer', "order_date" TEXT NOT NULL, "expected_delivery_date" TEXT, "subtotal" REAL NOT NULL, "discount" REAL NOT NULL DEFAULT '0.00', "tax" REAL NOT NULL DEFAULT '0.00', "total" REAL NOT NULL, "payment_method" TEXT NOT NULL DEFAULT 'cash', "status" TEXT NOT NULL DEFAULT 'draft', "notes" TEXT, "delivery_address" TEXT, "quotation_id" INTEGER, "sale_id" INTEGER, "created_by" INTEGER, "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: sales_return_items
DROP TABLE IF EXISTS "sales_return_items";
CREATE TABLE "sales_return_items" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "sales_return_id" INTEGER NOT NULL, "sale_item_id" INTEGER NOT NULL, "product_id" INTEGER NOT NULL, "quantity" REAL NOT NULL, "unit_id" INTEGER NOT NULL, "unit_price" REAL NOT NULL, "refund_amount" REAL NOT NULL, "reason" TEXT, "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()');

-- (no data)

-- Table: sales_returns
DROP TABLE IF EXISTS "sales_returns";
CREATE TABLE "sales_returns" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "return_no" TEXT NOT NULL, "sale_id" INTEGER NOT NULL, "customer_id" INTEGER, "return_date" TEXT NOT NULL, "total_refund" REAL NOT NULL, "refund_method" TEXT NOT NULL DEFAULT 'cash', "status" TEXT NOT NULL DEFAULT 'pending', "reason" TEXT NOT NULL, "notes" TEXT, "approved_by" INTEGER, "created_by" INTEGER, "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: stock_adjustments
DROP TABLE IF EXISTS "stock_adjustments";
CREATE TABLE "stock_adjustments" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "tenant_id" INTEGER, "product_id" INTEGER NOT NULL, "quantity" REAL NOT NULL, "adjustment_type" TEXT NOT NULL, "reason" TEXT NOT NULL, "status" TEXT NOT NULL DEFAULT 'pending', "approved_by" INTEGER, "approved_at" TEXT, "created_by" INTEGER, "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()');

-- (no data)

-- Table: stock_movements
DROP TABLE IF EXISTS "stock_movements";
CREATE TABLE "stock_movements" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "tenant_id" INTEGER, "product_id" INTEGER NOT NULL, "warehouse_id" INTEGER, "warehouse_location_id" INTEGER, "quantity" REAL NOT NULL, "unit_id" INTEGER NOT NULL, "movement_type" TEXT NOT NULL, "reference_id" INTEGER, "reference_type" TEXT, "notes" TEXT, "created_by" INTEGER, "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()', batch_id INTEGER);

INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('1', NULL, '1', NULL, NULL, '200', '1', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('2', NULL, '2', NULL, NULL, '150', '3', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('3', NULL, '3', NULL, NULL, '100', '5', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('4', NULL, '4', NULL, NULL, '30', '7', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('5', NULL, '5', NULL, NULL, '25', '9', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('6', NULL, '6', NULL, NULL, '8', '10', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('7', NULL, '7', NULL, NULL, '300', '11', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('8', NULL, '8', NULL, NULL, '120', '13', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('9', NULL, '9', NULL, NULL, '80', '16', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('10', NULL, '10', NULL, NULL, '60', '19', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('11', NULL, '11', NULL, NULL, '40', '22', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('12', NULL, '12', NULL, NULL, '15', '24', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('13', NULL, '13', NULL, NULL, '50', '26', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('14', NULL, '14', NULL, NULL, '20', '28', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('15', NULL, '15', NULL, NULL, '25', '30', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('16', NULL, '16', NULL, NULL, '18', '32', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('17', NULL, '17', NULL, NULL, '30', '34', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('18', NULL, '18', NULL, NULL, '35', '36', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('19', NULL, '19', NULL, NULL, '22', '38', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('20', NULL, '20', NULL, NULL, '10', '40', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('21', NULL, '21', NULL, NULL, '80', '42', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('22', NULL, '22', NULL, NULL, '60', '45', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('23', NULL, '23', NULL, NULL, '30', '48', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('24', NULL, '24', NULL, NULL, '15', '51', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('25', NULL, '25', NULL, NULL, '8', '53', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('26', NULL, '26', NULL, NULL, '40', '55', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('27', NULL, '27', NULL, NULL, '25', '57', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('28', NULL, '28', NULL, NULL, '50', '59', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('29', NULL, '29', NULL, NULL, '1500', '61', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('30', NULL, '30', NULL, NULL, '45', '63', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('31', NULL, '31', NULL, NULL, '35', '65', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('32', NULL, '32', NULL, NULL, '10', '67', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('33', NULL, '33', NULL, NULL, '60', '68', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('34', NULL, '34', NULL, NULL, '55', '70', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('35', NULL, '35', NULL, NULL, '28', '72', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('36', NULL, '36', NULL, NULL, '8', '73', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('37', NULL, '37', NULL, NULL, '40', '74', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('38', NULL, '38', NULL, NULL, '8', '75', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);
INSERT INTO "stock_movements" ("id", "tenant_id", "product_id", "warehouse_id", "warehouse_location_id", "quantity", "unit_id", "movement_type", "reference_id", "reference_type", "notes", "created_by", "created_at", "batch_id") VALUES ('39', NULL, '39', NULL, NULL, '35', '76', 'purchase', NULL, 'initial_stock', 'Stok awal dari supplier', '1', '2026-06-24 03:18:14', NULL);

-- Table: stock_opnames
DROP TABLE IF EXISTS "stock_opnames";
CREATE TABLE "stock_opnames" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "tenant_id" INTEGER, "opname_date" TEXT NOT NULL, "notes" TEXT, "status" TEXT NOT NULL DEFAULT 'pending', "approved_by" INTEGER, "approved_at" TEXT, "created_by" INTEGER, "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()');

-- (no data)

-- Table: stock_transfer_items
DROP TABLE IF EXISTS "stock_transfer_items";
CREATE TABLE "stock_transfer_items" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "transfer_id" INTEGER NOT NULL, "product_id" INTEGER NOT NULL, "quantity" REAL NOT NULL, "unit_id" INTEGER, "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: stock_transfers
DROP TABLE IF EXISTS "stock_transfers";
CREATE TABLE "stock_transfers" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "transfer_no" TEXT NOT NULL, "transfer_date" TEXT NOT NULL, "from_warehouse_id" INTEGER NOT NULL, "to_warehouse_id" INTEGER NOT NULL, "status" TEXT NOT NULL DEFAULT 'pending', "notes" TEXT, "created_by" INTEGER NOT NULL, "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: subscription_invoices
DROP TABLE IF EXISTS "subscription_invoices";
CREATE TABLE "subscription_invoices" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "invoice_no" TEXT NOT NULL, "tenant_id" INTEGER NOT NULL, "subscription_id" INTEGER NOT NULL, "invoice_date" TEXT NOT NULL, "due_date" TEXT NOT NULL, "amount" REAL NOT NULL, "status" TEXT NOT NULL DEFAULT 'unpaid', "paid_at" TEXT, "payment_method" TEXT, "notes" TEXT, "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: subscription_plans
DROP TABLE IF EXISTS "subscription_plans";
CREATE TABLE "subscription_plans" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" TEXT NOT NULL, "code" TEXT NOT NULL, "description" TEXT, "price_monthly" REAL NOT NULL DEFAULT '0.00', "price_yearly" REAL NOT NULL DEFAULT '0.00', "max_users" INTEGER NOT NULL DEFAULT '5', "max_products" INTEGER NOT NULL DEFAULT '1000', "max_warehouses" INTEGER NOT NULL DEFAULT '1', "has_accounting" INTEGER NOT NULL DEFAULT '0', "has_multi_warehouse" INTEGER NOT NULL DEFAULT '0', "has_api_access" INTEGER NOT NULL DEFAULT '1', "has_custom_branding" INTEGER NOT NULL DEFAULT '0', "is_active" INTEGER NOT NULL DEFAULT '1', "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "subscription_plans" ("id", "name", "code", "description", "price_monthly", "price_yearly", "max_users", "max_products", "max_warehouses", "has_accounting", "has_multi_warehouse", "has_api_access", "has_custom_branding", "is_active", "created_at", "updated_at") VALUES ('1', 'Starter', 'STARTER', 'Untuk toko kecil, 1 user, 100 produk', '99000', '990000', '1', '100', '1', '0', '0', '1', '0', '1', '2026-06-24 03:18:11', '2026-06-24 03:18:11');
INSERT INTO "subscription_plans" ("id", "name", "code", "description", "price_monthly", "price_yearly", "max_users", "max_products", "max_warehouses", "has_accounting", "has_multi_warehouse", "has_api_access", "has_custom_branding", "is_active", "created_at", "updated_at") VALUES ('2', 'Business', 'BUSINESS', 'Untuk toko menengah, 5 user, 1000 produk, accounting', '299000', '2990000', '5', '1000', '2', '1', '1', '1', '0', '1', '2026-06-24 03:18:11', '2026-06-24 03:18:11');
INSERT INTO "subscription_plans" ("id", "name", "code", "description", "price_monthly", "price_yearly", "max_users", "max_products", "max_warehouses", "has_accounting", "has_multi_warehouse", "has_api_access", "has_custom_branding", "is_active", "created_at", "updated_at") VALUES ('3', 'Enterprise', 'ENTERPRISE', 'Untuk distributor besar, unlimited user, multi-warehouse, white label', '999000', '9990000', '100', '100000', '50', '1', '1', '1', '1', '1', '2026-06-24 03:18:11', '2026-06-24 03:18:11');

-- Table: subscriptions
DROP TABLE IF EXISTS "subscriptions";
CREATE TABLE "subscriptions" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "tenant_id" INTEGER NOT NULL, "plan_id" INTEGER NOT NULL, "billing_cycle" TEXT NOT NULL DEFAULT 'monthly', "start_date" TEXT NOT NULL, "end_date" TEXT NOT NULL, "status" TEXT NOT NULL DEFAULT 'active', "amount" REAL NOT NULL, "payment_method" TEXT, "trial_ends_at" TEXT, "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: supplier_price_history
DROP TABLE IF EXISTS "supplier_price_history";
CREATE TABLE "supplier_price_history" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "supplier_id" INTEGER NOT NULL, "product_id" INTEGER NOT NULL, "unit_id" INTEGER NOT NULL, "unit_price" REAL NOT NULL, "effective_date" TEXT NOT NULL, "end_date" TEXT, "po_reference" TEXT, "notes" TEXT, "created_by" INTEGER, "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: suppliers
DROP TABLE IF EXISTS "suppliers";
CREATE TABLE "suppliers" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "tenant_id" INTEGER, "name" TEXT NOT NULL, "address" TEXT, "phone" TEXT, "email" TEXT, "payment_terms" INTEGER NOT NULL DEFAULT '30', "credit_limit" REAL NOT NULL DEFAULT '0.00', "is_active" INTEGER NOT NULL DEFAULT '1', "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "suppliers" ("id", "tenant_id", "name", "address", "phone", "email", "payment_terms", "credit_limit", "is_active", "created_at", "updated_at") VALUES ('1', NULL, 'PT Semen Gresik Distributor', 'Jl. Industri No. 1, Gresik, Jawa Timur', '031-3951234', 'sales@semen-gresik-dist.co.id', '30', '500000000', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "suppliers" ("id", "tenant_id", "name", "address", "phone", "email", "payment_terms", "credit_limit", "is_active", "created_at", "updated_at") VALUES ('2', NULL, 'PT Krakatau Steel Distributor', 'Jl. Industri Baja No. 7, Cilegon, Banten', '0254-3721234', 'sales@krakatau-steel.co.id', '45', '1000000000', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "suppliers" ("id", "tenant_id", "name", "address", "phone", "email", "payment_terms", "credit_limit", "is_active", "created_at", "updated_at") VALUES ('3', NULL, 'PT Avian Brands Indonesia', 'Jl. Cat Industri No. 22, Tangerang, Banten', '021-5551234', 'distributor@avianbrands.co.id', '30', '300000000', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "suppliers" ("id", "tenant_id", "name", "address", "phone", "email", "payment_terms", "credit_limit", "is_active", "created_at", "updated_at") VALUES ('4', NULL, 'PT Roman Ceramic Group', 'Jl. Keramik Raya No. 15, Surabaya, Jawa Timur', '031-7481234', 'distributor@romanceramic.co.id', '30', '250000000', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "suppliers" ("id", "tenant_id", "name", "address", "phone", "email", "payment_terms", "credit_limit", "is_active", "created_at", "updated_at") VALUES ('5', NULL, 'PT Asahimas Flat Glass', 'Jl. Kaca Industri No. 3, Cikampek, Jawa Barat', '021-8951234', 'sales@asahimas.co.id', '30', '200000000', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "suppliers" ("id", "tenant_id", "name", "address", "phone", "email", "payment_terms", "credit_limit", "is_active", "created_at", "updated_at") VALUES ('6', NULL, 'PT Sumalindo Lestari Jaya', 'Jl. Kayu Industri No. 9, Banjarmasin, Kalimantan Selatan', '0511-3361234', 'sales@sumalindo.co.id', '30', '150000000', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "suppliers" ("id", "tenant_id", "name", "address", "phone", "email", "payment_terms", "credit_limit", "is_active", "created_at", "updated_at") VALUES ('7', NULL, 'PT Kanmuri Roof Indonesia', 'Jl. Genteng Industri No. 12, Mojokerto, Jawa Timur', '0321-3211234', 'sales@kanmuri.co.id', '30', '100000000', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "suppliers" ("id", "tenant_id", "name", "address", "phone", "email", "payment_terms", "credit_limit", "is_active", "created_at", "updated_at") VALUES ('8', NULL, 'PT TOTO Indonesia', 'Jl. Sanitary Industri No. 5, Bekasi, Jawa Barat', '021-8851234', 'distributor@toto.co.id', '30', '200000000', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "suppliers" ("id", "tenant_id", "name", "address", "phone", "email", "payment_terms", "credit_limit", "is_active", "created_at", "updated_at") VALUES ('9', NULL, 'PT Vinilon Pipe Distributor', 'Jl. Pipa Industri No. 8, Cikarang, Jawa Barat', '021-8981234', 'sales@vinilon-dist.co.id', '30', '150000000', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');
INSERT INTO "suppliers" ("id", "tenant_id", "name", "address", "phone", "email", "payment_terms", "credit_limit", "is_active", "created_at", "updated_at") VALUES ('10', NULL, 'PT Makita Power Tools Indonesia', 'Jl. Perkakas Industri No. 20, Jakarta Utara', '021-6661234', 'distributor@makita.co.id', '30', '80000000', '1', '2026-06-24 03:18:13', '2026-06-24 03:18:13');

-- Table: sync_logs
DROP TABLE IF EXISTS "sync_logs";
CREATE TABLE "sync_logs" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "tenant_id" INTEGER, "device_id" TEXT, "entity_type" TEXT NOT NULL, "entity_id" INTEGER, "action" TEXT NOT NULL, "payload" TEXT, "sync_status" TEXT NOT NULL DEFAULT 'pending', "error_message" TEXT, "synced_at" TEXT, "created_at" TEXT, "updated_at" TEXT);

-- (no data)

-- Table: tenants
DROP TABLE IF EXISTS "tenants";
CREATE TABLE "tenants" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "code" TEXT NOT NULL, "name" TEXT NOT NULL, "subdomain" TEXT NOT NULL, "logo_url" TEXT, "primary_color" TEXT NOT NULL DEFAULT '#0d6efd', "secondary_color" TEXT NOT NULL DEFAULT '#6c757d', "company_name" TEXT, "company_address" TEXT, "company_phone" TEXT, "company_email" TEXT, "tax_id" TEXT, "status" TEXT NOT NULL DEFAULT 'trial', "trial_ends_at" TEXT, "subscription_ends_at" TEXT, "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "tenants" ("id", "code", "name", "subdomain", "logo_url", "primary_color", "secondary_color", "company_name", "company_address", "company_phone", "company_email", "tax_id", "status", "trial_ends_at", "subscription_ends_at", "created_at", "updated_at") VALUES ('1', 'TEN-DEFAULT', 'Panglong Default', 'default', NULL, '#0d6efd', '#6c757d', 'Panglong Material Bangunan', 'Jl. Raya Panglong No. 1', '021-1234567', 'info@panglong.com', NULL, 'active', NULL, '2027-06-24 03:18:11', '2026-06-24 03:18:11', '2026-06-24 03:18:11');

-- Table: users
DROP TABLE IF EXISTS "users";
CREATE TABLE "users" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "tenant_id" INTEGER, "username" TEXT NOT NULL, "password" TEXT NOT NULL, "full_name" TEXT NOT NULL, "email" TEXT, "phone" TEXT, "role_id" INTEGER NOT NULL, "branch_id" INTEGER, "is_active" INTEGER NOT NULL DEFAULT '1', "last_login_at" TEXT, "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "users" ("id", "tenant_id", "username", "password", "full_name", "email", "phone", "role_id", "branch_id", "is_active", "last_login_at", "created_at", "updated_at") VALUES ('1', NULL, 'admin', '$2y$12$KPZpe9aozFj6LRSQpgC35OtEdU.Xg4oscH.C9urOnUW7kQVnKSC.G', 'Administrator', 'admin@panglong.com', NULL, '1', '1', '1', '2026-06-26 12:14:01', '2026-06-24 03:18:12', '2026-06-24 03:20:33');
INSERT INTO "users" ("id", "tenant_id", "username", "password", "full_name", "email", "phone", "role_id", "branch_id", "is_active", "last_login_at", "created_at", "updated_at") VALUES ('2', NULL, 'manager1', '$2y$12$SR0dE67DrLU7ZwkYd58D4.TUsSfZ/8woJ7z0R1M3ZuoLhWFtsPQ8i', 'Manager 1', 'manager1@panglong.com', NULL, '2', '2', '1', '2026-06-26 12:11:41', '2026-06-24 03:18:12', '2026-06-24 03:18:14');
INSERT INTO "users" ("id", "tenant_id", "username", "password", "full_name", "email", "phone", "role_id", "branch_id", "is_active", "last_login_at", "created_at", "updated_at") VALUES ('3', NULL, 'kasir1', '$2y$12$VxYitFRcvuA9gRbsFiloYOBj.ixp5yNleF4CMIk288B80sCzkwrMW', 'Kasir 1', 'kasir1@panglong.com', NULL, '3', '1', '1', '2026-06-26 12:11:52', '2026-06-24 03:18:12', '2026-06-24 03:18:14');
INSERT INTO "users" ("id", "tenant_id", "username", "password", "full_name", "email", "phone", "role_id", "branch_id", "is_active", "last_login_at", "created_at", "updated_at") VALUES ('4', NULL, 'gudang1', '$2y$12$om76LtPv2qXG5mpi8EGhoORECdpBV6kJSvGztR9jOgn5sXztUM8j.', 'Gudang 1', 'gudang1@panglong.com', NULL, '4', '2', '1', '2026-06-26 12:11:52', '2026-06-24 03:18:12', '2026-06-24 03:18:14');

-- Table: vehicle_maintenance
DROP TABLE IF EXISTS "vehicle_maintenance";
CREATE TABLE vehicle_maintenance (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        vehicle_id INTEGER NOT NULL,
        maintenance_date TEXT NOT NULL,
        maintenance_type TEXT,
        cost REAL,
        odometer_km INTEGER,
        description TEXT,
        next_maintenance_date TEXT,
        created_by INTEGER,
        created_at TEXT,
        updated_at TEXT
    );

-- (no data)

-- Table: vehicles
DROP TABLE IF EXISTS "vehicles";
CREATE TABLE vehicles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        plate_no TEXT UNIQUE,
        vehicle_type TEXT,
        brand TEXT,
        model TEXT,
        capacity_kg REAL,
        fuel_type TEXT,
        acquisition_date TEXT,
        status TEXT DEFAULT 'active',
        notes TEXT,
        created_at TEXT,
        updated_at TEXT
    );

-- (no data)

-- Table: warehouse_locations
DROP TABLE IF EXISTS "warehouse_locations";
CREATE TABLE "warehouse_locations" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "warehouse_id" INTEGER NOT NULL, "code" TEXT NOT NULL, "name" TEXT NOT NULL, "zone_type" TEXT NOT NULL DEFAULT 'rack', "aisle" TEXT, "level" TEXT, "max_weight_kg" REAL NOT NULL DEFAULT '0.00', "capacity_m2" REAL NOT NULL DEFAULT '0.00', "is_active" INTEGER NOT NULL DEFAULT '1', "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "warehouse_locations" ("id", "warehouse_id", "code", "name", "zone_type", "aisle", "level", "max_weight_kg", "capacity_m2", "is_active", "created_at", "updated_at") VALUES ('1', '2', 'A-01', 'Rak A-01 Semen', 'rack', 'A', '1', '0', '0', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "warehouse_locations" ("id", "warehouse_id", "code", "name", "zone_type", "aisle", "level", "max_weight_kg", "capacity_m2", "is_active", "created_at", "updated_at") VALUES ('2', '2', 'A-02', 'Rak A-02 Semen', 'rack', 'A', '2', '0', '0', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "warehouse_locations" ("id", "warehouse_id", "code", "name", "zone_type", "aisle", "level", "max_weight_kg", "capacity_m2", "is_active", "created_at", "updated_at") VALUES ('3', '2', 'B-01', 'Blok B-01 Besi', 'block', 'B', '1', '0', '0', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "warehouse_locations" ("id", "warehouse_id", "code", "name", "zone_type", "aisle", "level", "max_weight_kg", "capacity_m2", "is_active", "created_at", "updated_at") VALUES ('4', '2', 'B-02', 'Blok B-02 Besi', 'block', 'B', '2', '0', '0', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "warehouse_locations" ("id", "warehouse_id", "code", "name", "zone_type", "aisle", "level", "max_weight_kg", "capacity_m2", "is_active", "created_at", "updated_at") VALUES ('5', '2', 'C-01', 'Rak C-01 Cat', 'rack', 'C', '1', '0', '0', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "warehouse_locations" ("id", "warehouse_id", "code", "name", "zone_type", "aisle", "level", "max_weight_kg", "capacity_m2", "is_active", "created_at", "updated_at") VALUES ('6', '2', 'D-FLOOR', 'Lantai D Keramik', 'floor', 'D', 'GF', '0', '0', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "warehouse_locations" ("id", "warehouse_id", "code", "name", "zone_type", "aisle", "level", "max_weight_kg", "capacity_m2", "is_active", "created_at", "updated_at") VALUES ('7', '2', 'E-01', 'Pallet E-01 Sanitary', 'pallet', 'E', '1', '0', '0', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "warehouse_locations" ("id", "warehouse_id", "code", "name", "zone_type", "aisle", "level", "max_weight_kg", "capacity_m2", "is_active", "created_at", "updated_at") VALUES ('8', '3', 'A-01', 'Rak A-01 Semen', 'rack', 'A', '1', '0', '0', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "warehouse_locations" ("id", "warehouse_id", "code", "name", "zone_type", "aisle", "level", "max_weight_kg", "capacity_m2", "is_active", "created_at", "updated_at") VALUES ('9', '3', 'B-01', 'Blok B-01 Besi', 'block', 'B', '1', '0', '0', '1', '2026-06-24 03:18:14', '2026-06-24 03:18:14');

-- Table: warehouses
DROP TABLE IF EXISTS "warehouses";
CREATE TABLE "warehouses" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "tenant_id" INTEGER, "code" TEXT NOT NULL, "name" TEXT NOT NULL, "address" TEXT, "phone" TEXT, "is_active" INTEGER NOT NULL DEFAULT '1', "type" TEXT NOT NULL DEFAULT 'utama', "branch_id" INTEGER, "manager_employee_id" INTEGER, "capacity_m2" REAL NOT NULL DEFAULT '0.00', "created_at" TEXT, "updated_at" TEXT);

INSERT INTO "warehouses" ("id", "tenant_id", "code", "name", "address", "phone", "is_active", "type", "branch_id", "manager_employee_id", "capacity_m2", "created_at", "updated_at") VALUES ('1', NULL, 'WH-MAIN', 'Gudang Utama', 'Jl. Raya Panglong No. 1', '021-1234567', '1', 'utama', NULL, NULL, '0', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "warehouses" ("id", "tenant_id", "code", "name", "address", "phone", "is_active", "type", "branch_id", "manager_employee_id", "capacity_m2", "created_at", "updated_at") VALUES ('2', NULL, 'WH-001', 'Gudang Pusat Jakarta', 'Jl. Raya Bangunan No. 1, Jakarta Timur', NULL, '1', 'utama', '1', '1', '500', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "warehouses" ("id", "tenant_id", "code", "name", "address", "phone", "is_active", "type", "branch_id", "manager_employee_id", "capacity_m2", "created_at", "updated_at") VALUES ('3', NULL, 'WH-BKS', 'Gudang Bekasi', 'Jl. Industri Raya No. 15, Bekasi', NULL, '1', 'cabang', '2', '2', '300', '2026-06-24 03:18:14', '2026-06-24 03:18:14');
INSERT INTO "warehouses" ("id", "tenant_id", "code", "name", "address", "phone", "is_active", "type", "branch_id", "manager_employee_id", "capacity_m2", "created_at", "updated_at") VALUES ('4', NULL, 'WH-TGR', 'Gudang Tangerang', 'Jl. Raya Serpong No. 88, Tangerang', NULL, '1', 'cabang', '3', NULL, '250', '2026-06-24 03:18:14', '2026-06-24 03:18:14');

-- Table: whatsapp_messages
DROP TABLE IF EXISTS "whatsapp_messages";
CREATE TABLE whatsapp_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        phone_number TEXT NOT NULL,
        message_body TEXT,
        template_name TEXT,
        reference_type TEXT,
        reference_id INTEGER,
        status TEXT DEFAULT 'pending',
        sent_at TEXT,
        error_message TEXT,
        created_by INTEGER,
        created_at TEXT
    );

-- (no data)

-- Table: whatsapp_templates
DROP TABLE IF EXISTS "whatsapp_templates";
CREATE TABLE whatsapp_templates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        template_name TEXT UNIQUE,
        template_type TEXT,
        message_body TEXT,
        variables TEXT,
        is_active INTEGER DEFAULT 1,
        created_at TEXT,
        updated_at TEXT
    );

INSERT INTO "whatsapp_templates" ("id", "template_name", "template_type", "message_body", "variables", "is_active", "created_at", "updated_at") VALUES ('1', 'invoice_reminder', 'reminder', 'Yth {customer_name}, invoice {invoice_no} senilai Rp {total} jatuh tempo {due_date}. Mohon segera dilunasi. Terima kasih - PT Panglong Bangunan Jaya', 'customer_name,invoice_no,total,due_date', '1', '2026-06-26 12:47:33', '2026-06-26 12:47:33');
INSERT INTO "whatsapp_templates" ("id", "template_name", "template_type", "message_body", "variables", "is_active", "created_at", "updated_at") VALUES ('2', 'delivery_notification', 'notification', 'Yth {customer_name}, pesanan Anda dengan invoice {invoice_no} akan dikirim pada {delivery_date} oleh {driver_name} ({vehicle_plate}). Terima kasih - PT Panglong Bangunan Jaya', 'customer_name,invoice_no,delivery_date,driver_name,vehicle_plate', '1', '2026-06-26 12:47:33', '2026-06-26 12:47:33');
INSERT INTO "whatsapp_templates" ("id", "template_name", "template_type", "message_body", "variables", "is_active", "created_at", "updated_at") VALUES ('3', 'payment_confirmation', 'confirmation', 'Yth {customer_name}, pembayaran Rp {amount} untuk invoice {invoice_no} telah kami terima pada {payment_date}. Saldo piutang: Rp {balance}. Terima kasih - PT Panglong Bangunan Jaya', 'customer_name,amount,invoice_no,payment_date,balance', '1', '2026-06-26 12:47:33', '2026-06-26 12:47:33');
INSERT INTO "whatsapp_templates" ("id", "template_name", "template_type", "message_body", "variables", "is_active", "created_at", "updated_at") VALUES ('4', 'quotation_sent', 'notification', 'Yth {customer_name}, penawaran harga {quote_no} telah kami kirim. Berlaku sampai {valid_until}. Total: Rp {total}. Terima kasih - PT Panglong Bangunan Jaya', 'customer_name,quote_no,valid_until,total', '1', '2026-06-26 12:47:33', '2026-06-26 12:47:33');

PRAGMA foreign_keys=ON;
