# DATABASE SCHEMA

# PANGLONG ERP

## Version: 3.0 (Updated 2026-06-29)

---

## Overview

- **Engine:** SQLite (file-based, WAL mode)
- **File:** `database/database.sqlite` (committed to git)
- **Tables:** 87
- **Connection:** PDO singleton via `db()` in `frontend/db.php`

---

## Key Tables

### Multi-Tenant
| Table | Description |
|-------|-------------|
| `tenants` | Tenant accounts (id, company_name, subdomain, status) |
| `users` | Users with tenant_id, role_id, branch_id |
| `roles` | 7 roles: owner, manager, kasir, gudang, accounting, supervisor, super_admin |
| `permissions` | Permission slugs for RBAC |
| `role_permission` | Role-permission mapping |
| `branches` | Tenant branches |
| `subscriptions` | SaaS subscription plans |
| `subscription_invoices` | SaaS billing |
| `app_settings` | Per-tenant settings (key-value) |

### Products
| Table | Description |
|-------|-------------|
| `products` | Products (tenant_id NULL = master catalog, 190 produk) |
| `categories` | Categories (tenant_id NULL = master, 19 kategori) |
| `product_units` | Multi-unit support (base + conversions) |
| `product_barcodes` | Product barcodes |
| `unit_measurements` | Unit definitions (tenant_id NULL = master, 23 units) |
| `stock_movements` | Stock in/out/adjustment/transfer log |
| `product_batches` | Batch/lot tracking |

### Sales
| Table | Description |
|-------|-------------|
| `sales` | Sales transactions |
| `sale_items` | Sale line items |
| `sale_payments` | Payment records |
| `sales_returns` | Sales return headers |
| `sales_return_items` | Return line items |
| `sales_orders` | Sales orders |
| `sales_order_items` | SO line items |
| `quotations` | Quotations |
| `quotation_items` | Quotation line items |

### Purchasing
| Table | Description |
|-------|-------------|
| `suppliers` | Supplier master |
| `purchase_orders` | PO headers |
| `purchase_items` | PO line items |
| `purchase_payments` | PO payment records |
| `purchase_returns` | Purchase returns |
| `purchase_return_items` | Return line items |

### Inventory
| Table | Description |
|-------|-------------|
| `warehouses` | Warehouse master |
| `warehouse_locations` | Storage locations |
| `stock_adjustments` | Stock adjustment log |
| `stock_opnames` | Stock opname (physical count) |
| `stock_transfers` | Inter-warehouse transfers |
| `adjustment_types` | Adjustment reason codes |

### Customers
| Table | Description |
|-------|-------------|
| `customers` | Customer master |
| `customer_groups` | Customer groups (discount, credit limit) |
| `customer_product_prices` | Customer-specific pricing |
| `tier_prices` | Volume-based tier pricing |

### Delivery
| Table | Description |
|-------|-------------|
| `deliveries` | Delivery orders |
| `delivery_items` | Delivery line items |
| `delivery_methods` | Delivery method master |
| `delivery_routes` | Route planning |
| `vehicles` | Fleet vehicles |
| `vehicle_maintenance` | Vehicle maintenance log |
| `status_codes` | Status definitions per module |

### Financial
| Table | Description |
|-------|-------------|
| `accounts` | Chart of accounts |
| `journal_entries` | Accounting journal |
| `journal_items` | Journal line items |
| `cash_transactions` | Cash book entries |
| `bank_statements` | Bank reconciliation |
| `fixed_assets` | Fixed asset register |
| `cash_flows` | Cash flow statement |
| `tax_rates` | Tax rate definitions |
| `e_faktur` | e-Faktur records |
| `e_faktur_types` | e-Faktur type master |
| `period_closings` | Period lock records |
| `payment_methods` | Payment method master |

### Communication
| Table | Description |
|-------|-------------|
| `whatsapp_templates` | WhatsApp message templates |
| `whatsapp_messages` | Sent WhatsApp messages |
| `whatsapp_template_types` | Template type master |

### Other
| Table | Description |
|-------|-------------|
| `audit_logs` | Audit trail (user, action, table, old/new values) |
| `marketplace_listings` | Marketplace integration |
| `iot_devices` | IoT monitoring devices |
| `iot_readings` | IoT sensor readings |

---

## Master Catalog (tenant_id = NULL)

Tables that support master catalog with `tenant_id = NULL`:
- `products` — 190 produk material bangunan
- `categories` — 19 kategori
- `unit_measurements` — 23 satuan
- `tax_rates` — Global tax rates
- `adjustment_types` — Global adjustment types

Query pattern for tenant access:
```sql
SELECT * FROM products WHERE (tenant_id = ? OR tenant_id IS NULL)
```

---

## Database File

- **Path:** `database/database.sqlite`
- **Permission:** `chmod 666 database/database.sqlite && chmod 777 database/`
- **Backup:** `scripts/backup_database.sh`
- **Export:** `scripts/export_sqlite.php`
- **Import:** `scripts/import_sqlite.php`
