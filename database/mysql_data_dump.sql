-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: panglong
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `accounts_payable`
--

LOCK TABLES `accounts_payable` WRITE;
/*!40000 ALTER TABLE `accounts_payable` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounts_payable` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `accounts_receivable`
--

LOCK TABLES `accounts_receivable` WRITE;
/*!40000 ALTER TABLE `accounts_receivable` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounts_receivable` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `app_settings`
--

LOCK TABLES `app_settings` WRITE;
/*!40000 ALTER TABLE `app_settings` DISABLE KEYS */;
INSERT INTO `app_settings` (`id`, `tenant_id`, `key`, `value`, `type`, `description`, `created_at`, `updated_at`) VALUES (1,NULL,'tax_rate','0.11','float','PPN rate (0.11 = 11%)',NULL,NULL),(2,NULL,'tax_enabled','1','boolean','Enable PPN tax calculation',NULL,NULL),(3,NULL,'company_name','PT Panglong Bangunan Jaya','string','Company name for print',NULL,NULL),(4,NULL,'company_address','Jl. Raya Industri No. 45, Bekasi, Jawa Barat','string','Company address',NULL,NULL),(5,NULL,'company_phone','021-88556677','string','Company phone',NULL,NULL),(6,NULL,'currency','IDR','string','Currency code',NULL,NULL),(7,NULL,'session_timeout_minutes','30','integer','Session timeout in minutes',NULL,NULL),(8,NULL,'low_stock_threshold_days','7','integer','Days of stock before low alert',NULL,NULL);
/*!40000 ALTER TABLE `app_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `asset_depreciations`
--

LOCK TABLES `asset_depreciations` WRITE;
/*!40000 ALTER TABLE `asset_depreciations` DISABLE KEYS */;
INSERT INTO `asset_depreciations` (`id`, `fixed_asset_id`, `depreciation_date`, `amount`, `accumulated_after`, `book_value_after`, `journal_entry_id`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES (1,1,'2024-06-30',3750000.00,3750000.00,246250000.00,2,NULL,1,'2026-06-23 20:20:34','2026-06-23 20:20:34');
/*!40000 ALTER TABLE `asset_depreciations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `bank_statements`
--

LOCK TABLES `bank_statements` WRITE;
/*!40000 ALTER TABLE `bank_statements` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_statements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `barcodes`
--

LOCK TABLES `barcodes` WRITE;
/*!40000 ALTER TABLE `barcodes` DISABLE KEYS */;
/*!40000 ALTER TABLE `barcodes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` (`id`, `code`, `name`, `address`, `phone`, `email`, `manager_name`, `type`, `is_active`, `created_at`, `updated_at`) VALUES (1,'BR-PST','Kantor Pusat','Jl. Raya Bangunan No. 1, Jakarta Timur','021-555-1001','pusat@panglongjaya.co.id','Budi Santoso','pusat',1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(2,'BR-CBG1','Cabang Bekasi','Jl. Industri Raya No. 15, Bekasi','021-555-2001','bekasi@panglongjaya.co.id','Andi Wijaya','cabang',1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(3,'BR-CBG2','Cabang Tangerang','Jl. Raya Serpong No. 88, Tangerang','021-555-3001','tangerang@panglongjaya.co.id','Dedi Kurniawan','cabang',1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(4,'BR-AGN1','Agen Depok','Jl. Margonda Raya No. 50, Depok','021-555-4001','depok@panglongjaya.co.id','Rudi Hartono','agen',1,'2026-06-23 20:18:14','2026-06-23 20:18:14');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `cash_transactions`
--

LOCK TABLES `cash_transactions` WRITE;
/*!40000 ALTER TABLE `cash_transactions` DISABLE KEYS */;
INSERT INTO `cash_transactions` (`id`, `transaction_no`, `type`, `account_type`, `transaction_date`, `amount`, `description`, `category`, `branch_id`, `employee_id`, `reference_no`, `recipient`, `journal_entry_id`, `created_by`, `created_at`, `updated_at`) VALUES (1,'CT202606240001','cash_out','kas_tunai','2024-06-01',500000.00,'Beli perlengkapan kantor','perlengkapan',NULL,NULL,NULL,NULL,1,1,'2026-06-23 20:20:33','2026-06-23 20:20:34');
/*!40000 ALTER TABLE `cash_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` (`id`, `tenant_id`, `name`, `parent_id`, `level`, `is_active`, `created_at`, `updated_at`) VALUES (1,NULL,'Semen & Beton',NULL,1,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(2,NULL,'Besi & Baja',NULL,1,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(3,NULL,'Cat & Finishing',NULL,1,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(4,NULL,'Keramik & Granit',NULL,1,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(5,NULL,'Kaca',NULL,1,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(6,NULL,'Kayu & Plywood',NULL,1,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(7,NULL,'Atap',NULL,1,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(8,NULL,'Sanitary & Plumbing',NULL,1,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(9,NULL,'Peralatan',NULL,1,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(10,NULL,'Semen Portland',1,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(11,NULL,'Semen Putih',1,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(12,NULL,'Mortar & Insta Cement',1,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(13,NULL,'Hebel & Bata Ringan',1,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(14,NULL,'Besi Beton',2,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(15,NULL,'Baja Ringan & Kanal',2,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(16,NULL,'Pipa Besi',2,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(17,NULL,'Kawat & Wiremesh',2,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(18,NULL,'Spandek & Genteng Metal',2,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(19,NULL,'Cat Tembok',3,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(20,NULL,'Cat Kayu & Besi',3,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(21,NULL,'Thinner & Pelarut',3,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(22,NULL,'Waterproofing & Plamir',3,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(23,NULL,'Keramik Lantai',4,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(24,NULL,'Keramik Dinding',4,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(25,NULL,'Granit & Homogeneous',4,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(26,NULL,'Marmer & Natural Stone',4,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(27,NULL,'Kaca Bening',5,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(28,NULL,'Kaca Tempered',5,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(29,NULL,'Cermin',5,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(30,NULL,'Kayu Solid',6,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(31,NULL,'Plywood',6,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(32,NULL,'MDF & Blockboard',6,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(33,NULL,'Genteng',7,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(34,NULL,'Spandek & Metal Roof',7,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(35,NULL,'Talang & Aksesoris Atap',7,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(36,NULL,'Closet & Urinoir',8,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(37,NULL,'Washtafel & Lavabo',8,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(38,NULL,'Kran & Valve',8,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(39,NULL,'Pipa PVC & Fitting',8,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(40,NULL,'Perkakas',9,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(41,NULL,'Safety Equipment',9,2,1,'2026-06-23 20:18:12','2026-06-23 20:18:12');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `chart_of_accounts`
--

LOCK TABLES `chart_of_accounts` WRITE;
/*!40000 ALTER TABLE `chart_of_accounts` DISABLE KEYS */;
INSERT INTO `chart_of_accounts` (`id`, `tenant_id`, `code`, `name`, `type`, `subtype`, `parent_id`, `is_active`, `created_at`, `updated_at`) VALUES (1,NULL,'1000','Kas & Bank','asset','current_asset',NULL,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(2,NULL,'1010','Kas Tunai','asset','current_asset',1,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(3,NULL,'1011','Kas Kecil (Petty Cash)','asset','current_asset',1,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(4,NULL,'1020','Bank BCA','asset','current_asset',1,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(5,NULL,'1021','Bank Mandiri','asset','current_asset',1,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(6,NULL,'1022','Bank BNI','asset','current_asset',1,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(7,NULL,'1100','Piutang Usaha','asset','current_asset',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(8,NULL,'1150','Uang Muka Pembelian','asset','current_asset',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(9,NULL,'1200','Persediaan Barang','asset','current_asset',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(10,NULL,'1300','PPN Masukan','asset','current_asset',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(11,NULL,'1400','Aset Pajak Dibayar Dimuka','asset','current_asset',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(12,NULL,'1500','Aset Tetap','asset','fixed_asset',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(13,NULL,'1510','Kendaraan','asset','fixed_asset',12,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(14,NULL,'1520','Peralatan Kantor','asset','fixed_asset',12,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(15,NULL,'1530','Bangunan Gudang','asset','fixed_asset',12,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(16,NULL,'1590','Akumulasi Penyusutan','asset','fixed_asset',12,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(17,NULL,'2000','Hutang Usaha','liability','current_liability',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(18,NULL,'2100','PPN Keluaran','liability','current_liability',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(19,NULL,'2200','Pinjaman Jangka Pendek','liability','current_liability',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(20,NULL,'2300','Hutang Pajak','liability','current_liability',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(21,NULL,'2400','Hutang Gaji','liability','current_liability',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(22,NULL,'2500','Pinjaman Jangka Panjang','liability','long_term_liability',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(23,NULL,'3000','Modal Pemilik','equity','capital',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(24,NULL,'3100','Laba Ditahan','equity','retained_earnings',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(25,NULL,'3200','Laba Tahun Berjalan','equity','retained_earnings',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(26,NULL,'4000','Pendapatan Penjualan','revenue','sales_revenue',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(27,NULL,'4010','Penjualan Semen & Beton','revenue','sales_revenue',26,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(28,NULL,'4020','Penjualan Besi & Baja','revenue','sales_revenue',26,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(29,NULL,'4030','Penjualan Cat & Finishing','revenue','sales_revenue',26,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(30,NULL,'4040','Penjualan Keramik & Granit','revenue','sales_revenue',26,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(31,NULL,'4050','Penjualan Sanitary & Plumbing','revenue','sales_revenue',26,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(32,NULL,'4090','Penjualan Lain-lain','revenue','sales_revenue',26,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(33,NULL,'4100','Pendapatan Lain-lain','revenue','other_revenue',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(34,NULL,'4200','Potongan Penjualan','revenue','sales_revenue',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(35,NULL,'5000','HPP (Cost of Goods Sold)','expense','cogs',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(36,NULL,'5100','Ongkos Angkut Pembelian','expense','cogs',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(37,NULL,'5200','Kerugian Persediaan','expense','cogs',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(38,NULL,'6000','Beban Operasional','expense','operating_expense',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(39,NULL,'6100','Beban Gaji & Tunjangan','expense','operating_expense',38,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(40,NULL,'6200','Beban Sewa','expense','operating_expense',38,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(41,NULL,'6300','Beban Listrik & Air','expense','operating_expense',38,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(42,NULL,'6400','Beban Telekomunikasi','expense','operating_expense',38,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(43,NULL,'6500','Beban Ongkos Kirim','expense','operating_expense',38,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(44,NULL,'6600','Beban Perlengkapan Kantor','expense','operating_expense',38,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(45,NULL,'6700','Beban Pemeliharaan Kendaraan','expense','operating_expense',38,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(46,NULL,'6800','Beban Asuransi','expense','operating_expense',38,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(47,NULL,'6900','Beban Penyusutan','expense','operating_expense',38,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(48,NULL,'7000','Beban Pajak','expense','other_expense',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(49,NULL,'7100','Potongan Pembelian','expense','other_expense',NULL,1,'2026-06-23 20:18:14','2026-06-23 20:18:14');
/*!40000 ALTER TABLE `chart_of_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `customer_groups`
--

LOCK TABLES `customer_groups` WRITE;
/*!40000 ALTER TABLE `customer_groups` DISABLE KEYS */;
INSERT INTO `customer_groups` (`id`, `tenant_id`, `name`, `discount_pct`, `credit_limit`, `is_active`, `created_at`, `updated_at`) VALUES (1,NULL,'Retail',0.00,1000000.00,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(2,NULL,'Tukang',5.00,5000000.00,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(3,NULL,'Kontraktor',10.00,20000000.00,1,'2026-06-23 20:18:12','2026-06-23 20:18:12'),(4,NULL,'Proyek',15.00,50000000.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(5,NULL,'Langganan',8.00,10000000.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13');
/*!40000 ALTER TABLE `customer_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `customer_product_prices`
--

LOCK TABLES `customer_product_prices` WRITE;
/*!40000 ALTER TABLE `customer_product_prices` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_product_prices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` (`id`, `tenant_id`, `name`, `address`, `phone`, `email`, `group_id`, `credit_limit`, `payment_terms`, `credit_score`, `is_active`, `created_at`, `updated_at`) VALUES (1,NULL,'PT Wijaya Karya Konstruksi','Jl. Konstruksi Raya No. 1, Jakarta Selatan','021-5701234','procurement@wijayakarya.co.id',3,100000000.00,45,'A',1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(2,NULL,'PT Pembangunan Perumahan Nusantara','Jl. Perumahan No. 88, Jakarta Barat','021-5552345','purchasing@ppn.co.id',4,200000000.00,60,'A',1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(3,NULL,'CV Bangun Mandiri Sejahtera','Jl. Bahan Bangunan No. 15, Bekasi','0812-3456-7890','cvbangunmandiri@gmail.com',5,50000000.00,30,'A',1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(4,NULL,'Toko Bangunan Sumber Rejeki','Jl. Raya Bekasi KM 25, Bekasi','0813-1111-2222','sumberrejeki.bangunan@gmail.com',2,25000000.00,30,'B',1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(5,NULL,'Toko Bangunan Jaya Abadi','Jl. Raya Bogor KM 30, Depok','0813-3333-4444','jayaabadi.bangunan@gmail.com',2,20000000.00,30,'B',1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(6,NULL,'Pak Suhardi (Mandor)','Jl. Swadaya No. 7, Cibitung','0857-1234-5678',NULL,2,5000000.00,15,'C',1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(7,NULL,'Pak Joko Santoso (Tukang)','Jl. Gotong Royong No. 12, Cikarang','0858-9876-5432',NULL,1,2000000.00,7,'C',1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(8,NULL,'PT Graha Property Development','Jl. Property Raya No. 100, Tangerang Selatan','021-7778888','procurement@grahaproperty.co.id',4,150000000.00,60,'A',1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(9,NULL,'UD Sentosa Bangun Jaya','Jl. Industri No. 45, Cileungsi','021-8889999','sentosabangunjaya@yahoo.com',5,40000000.00,30,'B',1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(10,NULL,'Ibu Wati (Renovasi Rumah)','Jl. Melati No. 3, Cibitung','0812-7777-8888',NULL,1,1000000.00,0,'C',1,'2026-06-23 20:18:13','2026-06-23 20:18:13');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `deliveries`
--

LOCK TABLES `deliveries` WRITE;
/*!40000 ALTER TABLE `deliveries` DISABLE KEYS */;
/*!40000 ALTER TABLE `deliveries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `delivery_items`
--

LOCK TABLES `delivery_items` WRITE;
/*!40000 ALTER TABLE `delivery_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `delivery_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `demand_forecasts`
--

LOCK TABLES `demand_forecasts` WRITE;
/*!40000 ALTER TABLE `demand_forecasts` DISABLE KEYS */;
/*!40000 ALTER TABLE `demand_forecasts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` (`id`, `employee_no`, `nik`, `full_name`, `phone`, `email`, `address`, `position`, `branch_id`, `warehouse_id`, `user_id`, `base_salary`, `commission_pct`, `hire_date`, `resign_date`, `status`, `vehicle_plate`, `sim_no`, `created_at`, `updated_at`) VALUES (1,'EMP-001','3171010101900001','Budi Santoso',NULL,NULL,NULL,'manager',1,2,1,15000000.00,0.00,'2020-01-15',NULL,'active',NULL,NULL,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(2,'EMP-002','3171020202900002','Andi Wijaya',NULL,NULL,NULL,'manager',2,3,2,12000000.00,0.00,'2020-03-01',NULL,'active',NULL,NULL,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(3,'EMP-003','3171030303900003','Slamet Riyadi',NULL,NULL,NULL,'salesman',1,NULL,3,5000000.00,2.00,'2021-06-01',NULL,'active',NULL,NULL,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(4,'EMP-004','3171040404900004','Joko Susilo',NULL,NULL,NULL,'salesman',2,NULL,4,5000000.00,2.00,'2021-07-01',NULL,'active',NULL,NULL,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(5,'EMP-005','3171050505900005','Ahmad Fauzi',NULL,NULL,NULL,'driver',1,NULL,NULL,4500000.00,0.00,'2021-01-10',NULL,'active','B 1234 ABC','SIM-B12345','2026-06-23 20:18:14','2026-06-23 20:18:14'),(6,'EMP-006','3171060606900006','Dedi Kurniawan',NULL,NULL,NULL,'driver',1,NULL,NULL,4500000.00,0.00,'2021-02-15',NULL,'active','B 5678 DEF','SIM-B56789','2026-06-23 20:18:14','2026-06-23 20:18:14'),(7,'EMP-007','3171070707900007','Siti Aminah',NULL,NULL,NULL,'kasir',1,NULL,NULL,4000000.00,0.00,'2022-01-01',NULL,'active',NULL,NULL,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(8,'EMP-008','3171080808900008','Hendra Gunawan',NULL,NULL,NULL,'gudang',1,2,NULL,4000000.00,0.00,'2022-03-01',NULL,'active',NULL,NULL,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(9,'EMP-009','3171090909900009','Rina Marlina',NULL,NULL,NULL,'accounting',1,NULL,NULL,8000000.00,0.00,'2021-01-15',NULL,'active',NULL,NULL,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(10,'EMP-010','3171101010900010','Rudi Hartono',NULL,NULL,NULL,'supervisor',3,NULL,NULL,7000000.00,0.00,'2022-06-01',NULL,'active',NULL,NULL,'2026-06-23 20:18:14','2026-06-23 20:18:14');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `fixed_assets`
--

LOCK TABLES `fixed_assets` WRITE;
/*!40000 ALTER TABLE `fixed_assets` DISABLE KEYS */;
INSERT INTO `fixed_assets` (`id`, `asset_code`, `name`, `category`, `branch_id`, `serial_no`, `plate_no`, `acquisition_date`, `acquisition_cost`, `salvage_value`, `useful_life_months`, `depreciation_method`, `monthly_depreciation`, `accumulated_depreciation`, `book_value`, `account_asset_id`, `account_accum_dep_id`, `account_dep_expense_id`, `status`, `disposal_date`, `disposal_value`, `notes`, `created_at`, `updated_at`) VALUES (1,'FA-001','Truk Colt Diesel Engkel','kendaraan',1,NULL,'B 1234 ABC','2020-01-20',250000000.00,25000000.00,60,'straight_line',3750000.00,3750000.00,246250000.00,NULL,NULL,NULL,'active',NULL,0.00,NULL,'2026-06-23 20:18:14','2026-06-23 20:20:34'),(2,'FA-002','Truk Engkel Bekasi','kendaraan',2,NULL,'B 5678 DEF','2020-03-15',180000000.00,18000000.00,60,'straight_line',2700000.00,0.00,180000000.00,NULL,NULL,NULL,'active',NULL,0.00,NULL,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(3,'FA-003','Gudang Pusat Jakarta','bangunan',1,NULL,NULL,'2019-06-01',1500000000.00,150000000.00,300,'straight_line',4500000.00,0.00,1500000000.00,NULL,NULL,NULL,'active',NULL,0.00,NULL,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(4,'FA-004','Gudang Bekasi','bangunan',2,NULL,NULL,'2020-03-01',800000000.00,80000000.00,300,'straight_line',2400000.00,0.00,800000000.00,NULL,NULL,NULL,'active',NULL,0.00,NULL,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(5,'FA-005','Forklift Toyota 2.5T','peralatan',1,'TY-250T-001',NULL,'2021-01-10',75000000.00,7500000.00,60,'straight_line',1125000.00,0.00,75000000.00,NULL,NULL,NULL,'active',NULL,0.00,NULL,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(6,'FA-006','Komputer & Printer Kasir','inventaris',1,NULL,NULL,'2022-01-01',15000000.00,1500000.00,36,'straight_line',375000.00,0.00,15000000.00,NULL,NULL,NULL,'active',NULL,0.00,NULL,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(7,'FA-007','Rak Besi Gudang Pusat','inventaris',1,NULL,NULL,'2020-01-15',35000000.00,3500000.00,120,'straight_line',262500.00,0.00,35000000.00,NULL,NULL,NULL,'active',NULL,0.00,NULL,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(8,'FA-008','Sepeda Motor Sales','kendaraan',1,NULL,'B 9999 XYZ','2021-06-01',25000000.00,2500000.00,60,'straight_line',375000.00,0.00,25000000.00,NULL,NULL,NULL,'active',NULL,0.00,NULL,'2026-06-23 20:18:14','2026-06-23 20:18:14');
/*!40000 ALTER TABLE `fixed_assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `iot_sensor_readings`
--

LOCK TABLES `iot_sensor_readings` WRITE;
/*!40000 ALTER TABLE `iot_sensor_readings` DISABLE KEYS */;
/*!40000 ALTER TABLE `iot_sensor_readings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `iot_sensors`
--

LOCK TABLES `iot_sensors` WRITE;
/*!40000 ALTER TABLE `iot_sensors` DISABLE KEYS */;
/*!40000 ALTER TABLE `iot_sensors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `journal_entries`
--

LOCK TABLES `journal_entries` WRITE;
/*!40000 ALTER TABLE `journal_entries` DISABLE KEYS */;
INSERT INTO `journal_entries` (`id`, `tenant_id`, `journal_no`, `entry_date`, `description`, `reference_type`, `reference_id`, `status`, `created_by`, `created_at`, `updated_at`) VALUES (1,NULL,'JE-CT-1-20260624','2024-06-01','Beli perlengkapan kantor (CT202606240001)','cash_transaction',1,'posted',1,'2026-06-23 20:20:34','2026-06-23 20:20:34'),(2,NULL,'JE-DEP-1-20240630','2024-06-30','Penyusutan aset FA-001 - Truk Colt Diesel Engkel','asset_depreciation',1,'posted',1,'2026-06-23 20:20:34','2026-06-23 20:20:34');
/*!40000 ALTER TABLE `journal_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `journal_entry_lines`
--

LOCK TABLES `journal_entry_lines` WRITE;
/*!40000 ALTER TABLE `journal_entry_lines` DISABLE KEYS */;
INSERT INTO `journal_entry_lines` (`id`, `journal_entry_id`, `account_id`, `debit`, `credit`, `description`, `created_at`, `updated_at`) VALUES (1,1,42,500000.00,0.00,'Beli perlengkapan kantor','2026-06-23 20:20:34','2026-06-23 20:20:34'),(2,1,2,0.00,500000.00,'Beli perlengkapan kantor','2026-06-23 20:20:34','2026-06-23 20:20:34'),(3,2,39,3750000.00,0.00,'Beban penyusutan Truk Colt Diesel Engkel','2026-06-23 20:20:34','2026-06-23 20:20:34'),(4,2,13,0.00,3750000.00,'Akumulasi penyusutan Truk Colt Diesel Engkel','2026-06-23 20:20:34','2026-06-23 20:20:34');
/*!40000 ALTER TABLE `journal_entry_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `marketplace_integrations`
--

LOCK TABLES `marketplace_integrations` WRITE;
/*!40000 ALTER TABLE `marketplace_integrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `marketplace_integrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `marketplace_product_mappings`
--

LOCK TABLES `marketplace_product_mappings` WRITE;
/*!40000 ALTER TABLE `marketplace_product_mappings` DISABLE KEYS */;
/*!40000 ALTER TABLE `marketplace_product_mappings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2019_12_14_000001_create_personal_access_tokens_table',1),(2,'2024_01_01_000001_create_roles_table',1),(3,'2024_01_01_000002_create_permissions_table',1),(4,'2024_01_01_000003_create_role_permission_table',1),(5,'2024_01_01_000004_create_customer_groups_table',1),(6,'2024_01_01_000005_create_categories_table',1),(7,'2024_01_01_000006_create_users_table',1),(8,'2024_01_01_000007_create_customers_table',1),(9,'2024_01_01_000008_create_suppliers_table',1),(10,'2024_01_01_000009_create_product_units_table',1),(11,'2024_01_01_000010_create_products_table',1),(12,'2024_01_01_000011_create_barcodes_table',1),(13,'2024_01_01_000012_create_stock_movements_table',1),(14,'2024_01_01_000013_create_sales_table',1),(15,'2024_01_01_000014_create_sale_items_table',1),(16,'2024_01_01_000015_create_sale_payments_table',1),(17,'2024_01_01_000016_create_purchase_orders_table',1),(18,'2024_01_01_000017_create_purchase_items_table',1),(19,'2024_01_01_000018_create_purchase_payments_table',1),(20,'2024_01_01_000019_create_accounts_receivable_table',1),(21,'2024_01_01_000020_create_accounts_payable_table',1),(22,'2024_01_01_000021_create_payments_table',1),(23,'2024_01_01_000022_create_stock_adjustments_table',1),(24,'2024_01_01_000023_create_stock_opnames_table',1),(25,'2024_01_01_000024_create_opname_items_table',1),(26,'2024_01_01_000025_create_audit_logs_table',1),(27,'2024_01_01_000026_create_deliveries_and_app_settings',1),(28,'2024_01_01_000027_create_accounting_tables',1),(29,'2024_01_01_000028_create_multi_tenant_tables',1),(30,'2024_01_01_000029_create_phase4_tables',1),(31,'2024_01_01_000030_create_sales_returns_tables',1),(32,'2024_01_01_000031_create_purchase_returns_tables',1),(33,'2024_01_01_000032_create_quotations_tables',1),(34,'2024_01_01_000033_create_sales_orders_tables',1),(35,'2024_01_01_000034_add_delivery_cost_landed_cost_bonus_fields',1),(36,'2024_01_01_000035_create_pricing_tables',1),(37,'2024_01_01_000036_create_branches_employees_assets_cash_tables',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `opname_items`
--

LOCK TABLES `opname_items` WRITE;
/*!40000 ALTER TABLE `opname_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `opname_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES (1,'create_sales','Create sales','2026-06-23 20:18:11','2026-06-23 20:18:11'),(2,'edit_sales','Edit sales','2026-06-23 20:18:11','2026-06-23 20:18:11'),(3,'void_sales','Void sales','2026-06-23 20:18:11','2026-06-23 20:18:11'),(4,'view_profit','View profit','2026-06-23 20:18:11','2026-06-23 20:18:11'),(5,'manage_products','Manage products','2026-06-23 20:18:11','2026-06-23 20:18:11'),(6,'stock_adjustment','Stock adjustment','2026-06-23 20:18:11','2026-06-23 20:18:11'),(7,'approve_adjustment','Approve adjustment','2026-06-23 20:18:11','2026-06-23 20:18:11'),(8,'manage_customers','Manage customers','2026-06-23 20:18:11','2026-06-23 20:18:11'),(9,'manage_suppliers','Manage suppliers','2026-06-23 20:18:11','2026-06-23 20:18:11'),(10,'record_payment','Record payment','2026-06-23 20:18:11','2026-06-23 20:18:11'),(11,'view_reports','View reports','2026-06-23 20:18:11','2026-06-23 20:18:11'),(12,'manage_users','Manage users','2026-06-23 20:18:11','2026-06-23 20:18:11');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES (1,'App\\Models\\User',1,'auth-token','74fde766ff8e50d60c0e5a16e85a706c677e77c6e40659b134336dabc8e510c7','[\"*\"]','2026-06-23 20:20:34',NULL,'2026-06-23 20:20:33','2026-06-23 20:20:34');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `price_optimizations`
--

LOCK TABLES `price_optimizations` WRITE;
/*!40000 ALTER TABLE `price_optimizations` DISABLE KEYS */;
/*!40000 ALTER TABLE `price_optimizations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `product_tier_prices`
--

LOCK TABLES `product_tier_prices` WRITE;
/*!40000 ALTER TABLE `product_tier_prices` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_tier_prices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `product_units`
--

LOCK TABLES `product_units` WRITE;
/*!40000 ALTER TABLE `product_units` DISABLE KEYS */;
INSERT INTO `product_units` (`id`, `product_id`, `unit_name`, `conversion_factor`, `is_base_unit`, `price_per_unit`, `created_at`, `updated_at`) VALUES (1,1,'sak',1.000,1,65000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(2,1,'ton',25.000,0,1625000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(3,2,'sak',1.000,1,64000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(4,2,'ton',25.000,0,1600000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(5,3,'sak',1.000,1,63000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(6,3,'ton',25.000,0,1575000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(7,4,'sak',1.000,1,110000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(8,4,'ton',25.000,0,2750000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(9,5,'sak',1.000,1,98000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(10,6,'sak',1.000,1,370000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(11,7,'pcs',1.000,1,28000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(12,7,'m3',83.000,0,2324000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(13,8,'batang',1.000,1,56000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(14,8,'kg',7.400,0,7568.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(15,8,'ton',7400.000,0,56000000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(16,9,'batang',1.000,1,81000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(17,9,'kg',10.660,0,7598.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(18,9,'ton',10660.000,0,81000000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(19,10,'batang',1.000,1,77000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(20,10,'kg',12.500,0,6160.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(21,10,'ton',12500.000,0,77000000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(22,11,'roll',1.000,1,155000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(23,11,'kg',15.000,0,10333.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(24,12,'lembar',1.000,1,660000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(25,12,'m2',72.000,0,9167.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(26,13,'lembar',1.000,1,145000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(27,13,'m',3.000,0,48333.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(28,14,'galon',1.000,1,890000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(29,14,'kg',25.000,0,35600.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(30,15,'galon',1.000,1,780000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(31,15,'kg',25.000,0,31200.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(32,16,'galon',1.000,1,860000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(33,16,'kg',25.000,0,34400.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(34,17,'kaleng',1.000,1,168000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(35,17,'kg',2.500,0,67200.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(36,18,'galon',1.000,1,78000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(37,18,'liter',5.000,0,15600.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(38,19,'kaleng',1.000,1,92000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(39,19,'kg',5.000,0,18400.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(40,20,'kaleng',1.000,1,215000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(41,20,'kg',4.000,0,53750.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(42,21,'dus',1.000,1,38000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(43,21,'m2',1.080,0,35185.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(44,21,'pcs',12.000,0,3167.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(45,22,'dus',1.000,1,56000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(46,22,'m2',1.600,0,35000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(47,22,'pcs',10.000,0,5600.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(48,23,'dus',1.000,1,195000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(49,23,'m2',1.440,0,135417.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(50,23,'pcs',4.000,0,48750.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(51,24,'lembar',1.000,1,980000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(52,24,'m2',4.460,0,219731.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(53,25,'lembar',1.000,1,1550000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(54,25,'m2',4.460,0,347534.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(55,26,'lembar',1.000,1,230000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(56,26,'m2',2.980,0,77181.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(57,27,'lembar',1.000,1,330000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(58,27,'m2',2.980,0,110738.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(59,28,'batang',1.000,1,100000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(60,28,'m3',0.010,0,10416667.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(61,29,'pcs',1.000,1,5500.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(62,29,'m2',10.000,0,55000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(63,30,'lembar',1.000,1,145000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(64,30,'m',3.000,0,48333.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(65,31,'batang',1.000,1,112000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(66,31,'m',4.000,0,28000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(67,32,'set',1.000,1,2150000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(68,33,'batang',1.000,1,92000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(69,33,'m',4.000,0,23000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(70,34,'batang',1.000,1,62000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(71,34,'m',4.000,0,15500.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(72,35,'pcs',1.000,1,102000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(73,36,'set',1.000,1,780000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(74,37,'pcs',1.000,1,35000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(75,38,'set',1.000,1,680000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(76,39,'pcs',1.000,1,42000.00,'2026-06-23 20:18:13','2026-06-23 20:18:13');
/*!40000 ALTER TABLE `product_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` (`id`, `tenant_id`, `code`, `name`, `alias`, `category_id`, `brand`, `min_stock`, `max_stock`, `location`, `warehouse_location_id`, `buy_price`, `sell_price`, `weight_kg`, `length_cm`, `width_cm`, `height_cm`, `is_active`, `created_at`, `updated_at`) VALUES (1,NULL,'SMT-GRK-40','Semen Gresik Portland 40kg',NULL,10,'Semen Gresik',50.000,1000.000,'A-01',NULL,58000.00,65000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(2,NULL,'SMT-TRD-40','Semen Tiga Roda Portland 40kg',NULL,10,'Tiga Roda',50.000,1000.000,'A-02',NULL,57000.00,64000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(3,NULL,'SMT-HLC-40','Semen Holcim Portland 40kg',NULL,10,'Holcim',50.000,800.000,'A-03',NULL,56000.00,63000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(4,NULL,'SMT-PTH-40','Semen Putih Gresik 40kg',NULL,11,'Semen Gresik',20.000,300.000,'A-04',NULL,95000.00,110000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(5,NULL,'MRT-WBR-25','Mortar Weber TileFix 25kg',NULL,12,'Weber',10.000,200.000,'A-05',NULL,85000.00,98000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(6,NULL,'MRT-SKA-25','SikaGrout 215 Powder 25kg',NULL,12,'Sika',5.000,50.000,'A-06',NULL,320000.00,370000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(7,NULL,'HBL-600-100','Hebel Block 600x200x100mm',NULL,13,'Hebel',100.000,2000.000,'A-07',NULL,22000.00,28000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(8,NULL,'BSI-KS-D10','Besi Beton KS D10mm 12m',NULL,14,'KS',50.000,500.000,'B-01',NULL,49000.00,56000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(9,NULL,'BSI-KS-D12','Besi Beton KS D12mm 12m',NULL,14,'KS',50.000,500.000,'B-02',NULL,71000.00,81000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(10,NULL,'BSI-SNI-D13','Besi Beton SNI D13mm 12m',NULL,14,'SNI',50.000,500.000,'B-03',NULL,68000.00,77000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(11,NULL,'KWT-BND-2','Kawat Bendrat BWG 2mm 15kg',NULL,17,'Bendrat',20.000,200.000,'B-04',NULL,135000.00,155000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(12,NULL,'WMH-M4-612','Wiremesh M4 6x12m',NULL,17,'SNI',10.000,100.000,'B-05',NULL,580000.00,660000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(13,NULL,'SPD-04-109','Spandek 0.4mm 1090mm',NULL,18,'SNI',30.000,300.000,'B-06',NULL,125000.00,145000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(14,NULL,'CAT-DLX-25','Cat Tembok Dulux Vitex 25kg',NULL,19,'Dulux',10.000,100.000,'C-01',NULL,780000.00,890000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(15,NULL,'CAT-AVN-25','Cat Tembok Avian 25kg',NULL,19,'Avian',10.000,100.000,'C-02',NULL,680000.00,780000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(16,NULL,'CAT-NPP-25','Cat Tembok Nippon 25kg',NULL,19,'Nippon Paint',10.000,100.000,'C-03',NULL,750000.00,860000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(17,NULL,'CAT-KY-NP','Cat Kayu Nippon 2.5kg',NULL,20,'Nippon Paint',10.000,100.000,'C-04',NULL,145000.00,168000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(18,NULL,'THN-A-5L','Thinner A 5 Liter',NULL,21,'Generic',15.000,150.000,'C-05',NULL,65000.00,78000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(19,NULL,'PLM-DLX-5','Plamir Dulux 5kg',NULL,22,'Dulux',10.000,100.000,'C-06',NULL,78000.00,92000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(20,NULL,'WP-SKA-4','Waterproofing Sika 4kg',NULL,22,'Sika',5.000,50.000,'C-07',NULL,185000.00,215000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(21,NULL,'KRM-RMN-3030','Keramik Roman 30x30cm',NULL,23,'Roman',30.000,300.000,'D-01',NULL,32000.00,38000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(22,NULL,'KRM-RMN-4040','Keramik Roman 40x40cm',NULL,23,'Roman',30.000,300.000,'D-02',NULL,48000.00,56000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(23,NULL,'GRN-6060-POL','Granit 60x60cm Polished',NULL,25,'Roman',20.000,200.000,'D-03',NULL,165000.00,195000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(24,NULL,'KCA-5-183244','Kaca Bening 5mm 183x244cm',NULL,27,'Asahimas',10.000,80.000,'E-01',NULL,850000.00,980000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(25,NULL,'KCA-8-183244','Kaca Bening 8mm 183x244cm',NULL,27,'Asahimas',5.000,50.000,'E-02',NULL,1350000.00,1550000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(26,NULL,'PLY-MRN-9','Plywood Meranti 9mm 122x244cm',NULL,31,'Meranti',20.000,200.000,'F-01',NULL,195000.00,230000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(27,NULL,'MDF-18-122244','MDF Board 18mm 122x244cm',NULL,32,'Sunshine',15.000,150.000,'F-02',NULL,285000.00,330000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(28,NULL,'KYU-KMP-46','Kayu Kamper 4x6cm 4m',NULL,30,'Kamper',30.000,300.000,'F-03',NULL,85000.00,100000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(29,NULL,'GNT-BTN-KMR','Genteng Beton Kanmuri',NULL,33,'Kanmuri',500.000,10000.000,'G-01',NULL,4500.00,5500.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(30,NULL,'SPD-MR-109','Spandek Metal Roof 0.4mm 1090mm',NULL,34,'SNI',30.000,300.000,'G-02',NULL,125000.00,145000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(31,NULL,'TLG-PVC-6','Talang Air PVC 6 inch 4m',NULL,35,'Vinilon',20.000,200.000,'G-03',NULL,95000.00,112000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(32,NULL,'CLS-TTO-621','Closet TOTO CW621J',NULL,36,'TOTO',5.000,50.000,'H-01',NULL,1850000.00,2150000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(33,NULL,'PVC-RUC-4','Pipa PVC Ruciruca 4 inch 4m',NULL,39,'Ruciruca',30.000,300.000,'H-02',NULL,78000.00,92000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(34,NULL,'PVC-VNL-3','Pipa PVC Vinilon 3 inch 4m',NULL,39,'Vinilon',30.000,300.000,'H-03',NULL,52000.00,62000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(35,NULL,'KRN-TTO-12','Kran Air TOTO 1/2 inch',NULL,38,'TOTO',15.000,150.000,'H-04',NULL,85000.00,102000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(36,NULL,'WST-TTO-LSN','Washtafel TOTO Lavatory',NULL,37,'TOTO',5.000,50.000,'H-05',NULL,650000.00,780000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(37,NULL,'MTR-FST-5','Meteran Fiber 5m',NULL,40,'Fastway',20.000,200.000,'I-01',NULL,28000.00,35000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(38,NULL,'BOR-MKT-13','Bor Makita HP1630 13mm',NULL,40,'Makita',5.000,50.000,'I-02',NULL,580000.00,680000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(39,NULL,'HMT-SFT-YL','Helm Safety SNI Yellow',NULL,41,'Safetoe',20.000,200.000,'I-03',NULL,32000.00,42000.00,0.000,0.00,0.00,0.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `purchase_items`
--

LOCK TABLES `purchase_items` WRITE;
/*!40000 ALTER TABLE `purchase_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `purchase_payments`
--

LOCK TABLES `purchase_payments` WRITE;
/*!40000 ALTER TABLE `purchase_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `purchase_return_items`
--

LOCK TABLES `purchase_return_items` WRITE;
/*!40000 ALTER TABLE `purchase_return_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_return_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `purchase_returns`
--

LOCK TABLES `purchase_returns` WRITE;
/*!40000 ALTER TABLE `purchase_returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `quotation_items`
--

LOCK TABLES `quotation_items` WRITE;
/*!40000 ALTER TABLE `quotation_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `quotation_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `quotations`
--

LOCK TABLES `quotations` WRITE;
/*!40000 ALTER TABLE `quotations` DISABLE KEYS */;
/*!40000 ALTER TABLE `quotations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `reorder_suggestions`
--

LOCK TABLES `reorder_suggestions` WRITE;
/*!40000 ALTER TABLE `reorder_suggestions` DISABLE KEYS */;
/*!40000 ALTER TABLE `reorder_suggestions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `role_permission`
--

LOCK TABLES `role_permission` WRITE;
/*!40000 ALTER TABLE `role_permission` DISABLE KEYS */;
INSERT INTO `role_permission` (`id`, `role_id`, `permission_id`, `created_at`, `updated_at`) VALUES (1,1,1,NULL,NULL),(2,1,2,NULL,NULL),(3,1,3,NULL,NULL),(4,1,4,NULL,NULL),(5,1,5,NULL,NULL),(6,1,6,NULL,NULL),(7,1,7,NULL,NULL),(8,1,8,NULL,NULL),(9,1,9,NULL,NULL),(10,1,10,NULL,NULL),(11,1,11,NULL,NULL),(12,1,12,NULL,NULL),(13,2,1,NULL,NULL),(14,2,2,NULL,NULL),(15,2,3,NULL,NULL),(16,2,4,NULL,NULL),(17,2,5,NULL,NULL),(18,2,6,NULL,NULL),(19,2,7,NULL,NULL),(20,2,8,NULL,NULL),(21,2,9,NULL,NULL),(22,2,10,NULL,NULL),(23,2,11,NULL,NULL),(24,3,1,NULL,NULL),(25,3,2,NULL,NULL),(26,3,8,NULL,NULL),(27,3,10,NULL,NULL),(28,4,5,NULL,NULL),(29,4,6,NULL,NULL),(30,4,9,NULL,NULL),(31,5,8,NULL,NULL),(32,5,10,NULL,NULL),(33,5,11,NULL,NULL),(34,6,4,NULL,NULL),(35,6,11,NULL,NULL);
/*!40000 ALTER TABLE `role_permission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES (1,'Owner','owner','Full access to all features','2026-06-23 20:18:11','2026-06-23 20:18:11'),(2,'Manager','manager','Manager level access','2026-06-23 20:18:11','2026-06-23 20:18:11'),(3,'Kasir','kasir','Cashier access','2026-06-23 20:18:11','2026-06-23 20:18:11'),(4,'Gudang','gudang','Warehouse access','2026-06-23 20:18:11','2026-06-23 20:18:11'),(5,'Accounting','accounting','Accounting access','2026-06-23 20:18:11','2026-06-23 20:18:11'),(6,'Supervisor','supervisor','Supervisor access','2026-06-23 20:18:11','2026-06-23 20:18:11');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sale_items`
--

LOCK TABLES `sale_items` WRITE;
/*!40000 ALTER TABLE `sale_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `sale_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sale_payments`
--

LOCK TABLES `sale_payments` WRITE;
/*!40000 ALTER TABLE `sale_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `sale_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sales_order_items`
--

LOCK TABLES `sales_order_items` WRITE;
/*!40000 ALTER TABLE `sales_order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sales_orders`
--

LOCK TABLES `sales_orders` WRITE;
/*!40000 ALTER TABLE `sales_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sales_return_items`
--

LOCK TABLES `sales_return_items` WRITE;
/*!40000 ALTER TABLE `sales_return_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_return_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sales_returns`
--

LOCK TABLES `sales_returns` WRITE;
/*!40000 ALTER TABLE `sales_returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `stock_adjustments`
--

LOCK TABLES `stock_adjustments` WRITE;
/*!40000 ALTER TABLE `stock_adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
INSERT INTO `stock_movements` (`id`, `tenant_id`, `product_id`, `warehouse_id`, `warehouse_location_id`, `quantity`, `unit_id`, `movement_type`, `reference_id`, `reference_type`, `notes`, `created_by`, `created_at`) VALUES (1,NULL,1,NULL,NULL,200.000,1,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(2,NULL,2,NULL,NULL,150.000,3,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(3,NULL,3,NULL,NULL,100.000,5,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(4,NULL,4,NULL,NULL,30.000,7,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(5,NULL,5,NULL,NULL,25.000,9,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(6,NULL,6,NULL,NULL,8.000,10,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(7,NULL,7,NULL,NULL,300.000,11,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(8,NULL,8,NULL,NULL,120.000,13,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(9,NULL,9,NULL,NULL,80.000,16,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(10,NULL,10,NULL,NULL,60.000,19,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(11,NULL,11,NULL,NULL,40.000,22,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(12,NULL,12,NULL,NULL,15.000,24,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(13,NULL,13,NULL,NULL,50.000,26,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(14,NULL,14,NULL,NULL,20.000,28,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(15,NULL,15,NULL,NULL,25.000,30,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(16,NULL,16,NULL,NULL,18.000,32,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(17,NULL,17,NULL,NULL,30.000,34,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(18,NULL,18,NULL,NULL,35.000,36,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(19,NULL,19,NULL,NULL,22.000,38,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(20,NULL,20,NULL,NULL,10.000,40,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(21,NULL,21,NULL,NULL,80.000,42,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(22,NULL,22,NULL,NULL,60.000,45,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(23,NULL,23,NULL,NULL,30.000,48,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(24,NULL,24,NULL,NULL,15.000,51,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(25,NULL,25,NULL,NULL,8.000,53,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(26,NULL,26,NULL,NULL,40.000,55,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(27,NULL,27,NULL,NULL,25.000,57,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(28,NULL,28,NULL,NULL,50.000,59,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(29,NULL,29,NULL,NULL,1500.000,61,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(30,NULL,30,NULL,NULL,45.000,63,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(31,NULL,31,NULL,NULL,35.000,65,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(32,NULL,32,NULL,NULL,10.000,67,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(33,NULL,33,NULL,NULL,60.000,68,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(34,NULL,34,NULL,NULL,55.000,70,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(35,NULL,35,NULL,NULL,28.000,72,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(36,NULL,36,NULL,NULL,8.000,73,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(37,NULL,37,NULL,NULL,40.000,74,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(38,NULL,38,NULL,NULL,8.000,75,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14'),(39,NULL,39,NULL,NULL,35.000,76,'purchase',NULL,'initial_stock','Stok awal dari supplier',1,'2026-06-23 20:18:14');
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `stock_opnames`
--

LOCK TABLES `stock_opnames` WRITE;
/*!40000 ALTER TABLE `stock_opnames` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_opnames` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `stock_transfer_items`
--

LOCK TABLES `stock_transfer_items` WRITE;
/*!40000 ALTER TABLE `stock_transfer_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_transfer_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `stock_transfers`
--

LOCK TABLES `stock_transfers` WRITE;
/*!40000 ALTER TABLE `stock_transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `subscription_invoices`
--

LOCK TABLES `subscription_invoices` WRITE;
/*!40000 ALTER TABLE `subscription_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscription_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `subscription_plans`
--

LOCK TABLES `subscription_plans` WRITE;
/*!40000 ALTER TABLE `subscription_plans` DISABLE KEYS */;
INSERT INTO `subscription_plans` (`id`, `name`, `code`, `description`, `price_monthly`, `price_yearly`, `max_users`, `max_products`, `max_warehouses`, `has_accounting`, `has_multi_warehouse`, `has_api_access`, `has_custom_branding`, `is_active`, `created_at`, `updated_at`) VALUES (1,'Starter','STARTER','Untuk toko kecil, 1 user, 100 produk',99000.00,990000.00,1,100,1,0,0,1,0,1,'2026-06-23 20:18:11','2026-06-23 20:18:11'),(2,'Business','BUSINESS','Untuk toko menengah, 5 user, 1000 produk, accounting',299000.00,2990000.00,5,1000,2,1,1,1,0,1,'2026-06-23 20:18:11','2026-06-23 20:18:11'),(3,'Enterprise','ENTERPRISE','Untuk distributor besar, unlimited user, multi-warehouse, white label',999000.00,9990000.00,100,100000,50,1,1,1,1,1,'2026-06-23 20:18:11','2026-06-23 20:18:11');
/*!40000 ALTER TABLE `subscription_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `supplier_price_history`
--

LOCK TABLES `supplier_price_history` WRITE;
/*!40000 ALTER TABLE `supplier_price_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `supplier_price_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` (`id`, `tenant_id`, `name`, `address`, `phone`, `email`, `payment_terms`, `credit_limit`, `is_active`, `created_at`, `updated_at`) VALUES (1,NULL,'PT Semen Gresik Distributor','Jl. Industri No. 1, Gresik, Jawa Timur','031-3951234','sales@semen-gresik-dist.co.id',30,500000000.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(2,NULL,'PT Krakatau Steel Distributor','Jl. Industri Baja No. 7, Cilegon, Banten','0254-3721234','sales@krakatau-steel.co.id',45,1000000000.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(3,NULL,'PT Avian Brands Indonesia','Jl. Cat Industri No. 22, Tangerang, Banten','021-5551234','distributor@avianbrands.co.id',30,300000000.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(4,NULL,'PT Roman Ceramic Group','Jl. Keramik Raya No. 15, Surabaya, Jawa Timur','031-7481234','distributor@romanceramic.co.id',30,250000000.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(5,NULL,'PT Asahimas Flat Glass','Jl. Kaca Industri No. 3, Cikampek, Jawa Barat','021-8951234','sales@asahimas.co.id',30,200000000.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(6,NULL,'PT Sumalindo Lestari Jaya','Jl. Kayu Industri No. 9, Banjarmasin, Kalimantan Selatan','0511-3361234','sales@sumalindo.co.id',30,150000000.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(7,NULL,'PT Kanmuri Roof Indonesia','Jl. Genteng Industri No. 12, Mojokerto, Jawa Timur','0321-3211234','sales@kanmuri.co.id',30,100000000.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(8,NULL,'PT TOTO Indonesia','Jl. Sanitary Industri No. 5, Bekasi, Jawa Barat','021-8851234','distributor@toto.co.id',30,200000000.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(9,NULL,'PT Vinilon Pipe Distributor','Jl. Pipa Industri No. 8, Cikarang, Jawa Barat','021-8981234','sales@vinilon-dist.co.id',30,150000000.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13'),(10,NULL,'PT Makita Power Tools Indonesia','Jl. Perkakas Industri No. 20, Jakarta Utara','021-6661234','distributor@makita.co.id',30,80000000.00,1,'2026-06-23 20:18:13','2026-06-23 20:18:13');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sync_logs`
--

LOCK TABLES `sync_logs` WRITE;
/*!40000 ALTER TABLE `sync_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `sync_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tenants`
--

LOCK TABLES `tenants` WRITE;
/*!40000 ALTER TABLE `tenants` DISABLE KEYS */;
INSERT INTO `tenants` (`id`, `code`, `name`, `subdomain`, `logo_url`, `primary_color`, `secondary_color`, `company_name`, `company_address`, `company_phone`, `company_email`, `tax_id`, `status`, `trial_ends_at`, `subscription_ends_at`, `created_at`, `updated_at`) VALUES (1,'TEN-DEFAULT','Panglong Default','default',NULL,'#0d6efd','#6c757d','Panglong Material Bangunan','Jl. Raya Panglong No. 1','021-1234567','info@panglong.com',NULL,'active',NULL,'2027-06-23 20:18:11','2026-06-23 20:18:11','2026-06-23 20:18:11');
/*!40000 ALTER TABLE `tenants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `tenant_id`, `username`, `password`, `full_name`, `email`, `phone`, `role_id`, `branch_id`, `is_active`, `last_login_at`, `created_at`, `updated_at`) VALUES (1,NULL,'admin','$2y$12$KPZpe9aozFj6LRSQpgC35OtEdU.Xg4oscH.C9urOnUW7kQVnKSC.G','Administrator','admin@panglong.com',NULL,1,1,1,'2026-06-23 20:20:33','2026-06-23 20:18:12','2026-06-23 20:20:33'),(2,NULL,'manager1','$2y$12$SR0dE67DrLU7ZwkYd58D4.TUsSfZ/8woJ7z0R1M3ZuoLhWFtsPQ8i','Manager 1','manager1@panglong.com',NULL,2,2,1,NULL,'2026-06-23 20:18:12','2026-06-23 20:18:14'),(3,NULL,'kasir1','$2y$12$VxYitFRcvuA9gRbsFiloYOBj.ixp5yNleF4CMIk288B80sCzkwrMW','Kasir 1','kasir1@panglong.com',NULL,3,1,1,NULL,'2026-06-23 20:18:12','2026-06-23 20:18:14'),(4,NULL,'gudang1','$2y$12$om76LtPv2qXG5mpi8EGhoORECdpBV6kJSvGztR9jOgn5sXztUM8j.','Gudang 1','gudang1@panglong.com',NULL,4,2,1,NULL,'2026-06-23 20:18:12','2026-06-23 20:18:14');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `warehouse_locations`
--

LOCK TABLES `warehouse_locations` WRITE;
/*!40000 ALTER TABLE `warehouse_locations` DISABLE KEYS */;
INSERT INTO `warehouse_locations` (`id`, `warehouse_id`, `code`, `name`, `zone_type`, `aisle`, `level`, `max_weight_kg`, `capacity_m2`, `is_active`, `created_at`, `updated_at`) VALUES (1,2,'A-01','Rak A-01 Semen','rack','A','1',0.00,0.00,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(2,2,'A-02','Rak A-02 Semen','rack','A','2',0.00,0.00,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(3,2,'B-01','Blok B-01 Besi','block','B','1',0.00,0.00,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(4,2,'B-02','Blok B-02 Besi','block','B','2',0.00,0.00,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(5,2,'C-01','Rak C-01 Cat','rack','C','1',0.00,0.00,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(6,2,'D-FLOOR','Lantai D Keramik','floor','D','GF',0.00,0.00,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(7,2,'E-01','Pallet E-01 Sanitary','pallet','E','1',0.00,0.00,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(8,3,'A-01','Rak A-01 Semen','rack','A','1',0.00,0.00,1,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(9,3,'B-01','Blok B-01 Besi','block','B','1',0.00,0.00,1,'2026-06-23 20:18:14','2026-06-23 20:18:14');
/*!40000 ALTER TABLE `warehouse_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `warehouses`
--

LOCK TABLES `warehouses` WRITE;
/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
INSERT INTO `warehouses` (`id`, `tenant_id`, `code`, `name`, `address`, `phone`, `is_active`, `type`, `branch_id`, `manager_employee_id`, `capacity_m2`, `created_at`, `updated_at`) VALUES (1,NULL,'WH-MAIN','Gudang Utama','Jl. Raya Panglong No. 1','021-1234567',1,'utama',NULL,NULL,0.00,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(2,NULL,'WH-001','Gudang Pusat Jakarta','Jl. Raya Bangunan No. 1, Jakarta Timur',NULL,1,'utama',1,1,500.00,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(3,NULL,'WH-BKS','Gudang Bekasi','Jl. Industri Raya No. 15, Bekasi',NULL,1,'cabang',2,2,300.00,'2026-06-23 20:18:14','2026-06-23 20:18:14'),(4,NULL,'WH-TGR','Gudang Tangerang','Jl. Raya Serpong No. 88, Tangerang',NULL,1,'cabang',3,NULL,250.00,'2026-06-23 20:18:14','2026-06-23 20:18:14');
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-26 21:30:55
