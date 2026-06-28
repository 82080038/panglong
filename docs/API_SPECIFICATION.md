# API SPECIFICATION

# PANGLONG ERP — AJAX Endpoints

## Version: 3.0 (Updated 2026-06-29)

---

## Overview

Frontend menggunakan `frontend/ajax.php` sebagai single endpoint untuk semua CRUD operations. Endpoint dipanggil dengan parameter `?endpoint=name`. Response format: JSON `{ success, data, meta }`.

**Base URL:** `ajax.php` (via `API_URL` JavaScript constant)

---

## Endpoints (60)

### Products
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `products` | List products (paginated, search) |
| GET | `products&id={id}` | Product detail |
| POST | `products` | Create product (+ auto-sync to master) |
| PUT | `products&id={id}` | Update product |
| DELETE | `products&id={id}` | Delete/deactivate product |

### Master Catalog
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `master-products` | List master catalog (tenant_id IS NULL) |
| POST | `master-products` | Import master product to tenant |

### Categories
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `categories` | List categories |
| POST | `categories` | Create category |
| PUT | `categories&id={id}` | Update category |
| DELETE | `categories&id={id}` | Delete category |

### Brands
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `brands` | List brands |
| POST | `brands` | Create brand |

### Product Units
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `product-units` | List product units |
| POST | `product-units` | Create product unit |

### Barcode
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `barcode-lookup&barcode={code}` | Lookup product by barcode |

### Sales Price
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `sales-price&product_id={id}` | Get product sell price |

### Sales
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `sales` | List sales |
| GET | `sales&id={id}` | Sale detail |
| POST | `sales` | Create sale (with stock validation) |
| DELETE | `sales&id={id}` | Void sale |

### Sale Payment
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `sale-payment` | Record payment for sale |

### Customers
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `customers` | List customers |
| POST | `customers` | Create customer |
| PUT | `customers&id={id}` | Update customer |
| DELETE | `customers&id={id}` | Delete customer |

### Customer Groups
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `customer-groups` | List groups |
| POST | `customer-groups` | Create group |

### Customer Prices
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `customer-prices` | List customer-specific prices |
| POST | `customer-prices` | Set customer-specific price |

### Suppliers
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `suppliers` | List suppliers |
| POST | `suppliers` | Create supplier |
| PUT | `suppliers&id={id}` | Update supplier |
| DELETE | `suppliers&id={id}` | Delete supplier |

### Warehouses
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `warehouses` | List warehouses |
| POST | `warehouses` | Create warehouse |

### Stock
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `stock` | Stock report |
| POST | `stock-adjustments` | Stock adjustment |
| POST | `stock-transfers` | Stock transfer |

### Purchase Orders
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `purchase-orders` | List POs |
| POST | `purchase-orders` | Create PO |
| POST | `purchase-orders&action=receive` | Receive PO items |

### Deliveries
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `deliveries` | List deliveries |
| POST | `deliveries` | Create delivery |

### Quotations
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `quotations` | List quotations |
| POST | `quotations` | Create quotation |

### Reports
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `reports&type={type}` | Various reports |

### Settings
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `settings` | Get settings |
| PUT | `settings` | Update settings |

### Users
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `users` | List users |
| POST | `users` | Create user |

### Tenants (Super Admin)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `tenants` | List tenants |
| POST | `tenants` | Create/update tenant |

### Other Endpoints
- `sales-orders`, `sales-returns`, `purchase-returns`
- `partial-deliveries`, `delivery-routes`, `vehicles`, `vehicle-maintenance`
- `cash-transactions`, `bank-statements`, `fixed-assets`, `e-faktur`, `e-faktur-types`
- `cash-flow`, `period-closings`, `check-period-locked`
- `marketplace`, `whatsapp-templates`, `whatsapp-messages`
- `tier-prices`, `branches`, `payment-methods`, `adjustment-types`
- `unit-measurements`, `tax-rates`, `status-codes`
- `subscriptions`, `subscription-invoices`, `saas-revenue`
- `product-batches`, `landed-cost`, `stock-valuation-fifo`
- `warehouse-locations`, `supplier-price-history`
- `heartbeat`

---

## Response Format

```json
// Success
{ "success": true, "data": {...} }

// Success with pagination
{ "success": true, "data": [...], "meta": { "total": 100, "per_page": 20, "current_page": 1, "last_page": 5 } }

// Error
{ "success": false, "message": "Error description" }
```

---

## Authentication & Security

- Session-based (cookie `PHPSESSID`)
- CSRF token required for POST/PUT/DELETE (header `X-CSRF-Token`)
- Rate limiting: 30 requests/60s for write operations
- Role-based endpoint permissions
