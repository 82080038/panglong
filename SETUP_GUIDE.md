# SETUP GUIDE

# PANGLONG ERP - PHASE 1 MVP

## Version: 1.1 (Updated 2025-06-23)
## Status: Development In Progress

> **WARNING**: Sebelum menjalankan aplikasi, baca DEVELOPMENT_ROADMAP.md
> untuk mengetahui issue yang belum diselesaikan.

---

# WHAT HAS BEEN COMPLETED

## 1. Documentation
- ✅ MASTER_BLUEPRINT.md - Complete enterprise architecture plan
- ✅ MVP_SCOPE.md - MVP scope document
- ✅ LARAVEL_STRUCTURE.md - Laravel project structure
- ✅ DATABASE_SCHEMA.md - Complete database schema (v1.1 - circular FK fixed)
- ✅ API_SPECIFICATION.md - API endpoints specification
- ✅ TESTING_FRAMEWORK.md - Testing framework configuration
- ✅ TECHNICAL_DOCUMENTATION.md - Technical documentation structure
- ✅ LARAVEL_LEARNING_GUIDE.md - Complete Laravel learning guide
- ✅ DEVELOPMENT_ROADMAP.md - Execution plan & issue tracker
- ✅ PROJECT_STATUS.md - Accurate audit of current state

## 2. Project Structure (SCAFFOLDED - belum semua tested)
- ✅ Complete directory structure created
- ✅ composer.json configured
- ✅ .env.example created
- ✅ artisan CLI file created
- ✅ bootstrap files created
- ✅ public/index.php created
- ✅ routes/api.php configured
- ✅ routes/web.php configured
- ✅ frontend/ directory with PHP Native pages

## 3. Database Migrations (SCAFFOLDED - belum tested dengan MySQL)
- ✅ 25 migration files created for all tables:
  - roles, permissions, role_permission
  - customer_groups, categories, users, customers, suppliers
  - products, product_units, barcodes, stock_movements
  - sales, sale_items, sale_payments
  - purchase_orders, purchase_items, purchase_payments
  - accounts_receivable, accounts_payable, payments
  - stock_adjustments, stock_opnames, opname_items, audit_logs

> **NOTE**: Migration `products` masih memiliki `base_unit_id` column.
> DATABASE_SCHEMA.md v1.1 merekomendasikan penghapusan FK ini.
> Migration perlu di-update untuk match schema doc.

## 4. Models (SCAFFOLDED)
- ✅ 25 Eloquent models created with relationships:
  - User, Role, Permission, CustomerGroup, Category
  - Customer, Supplier, Product, ProductUnit, Barcode
  - StockMovement, Sale, SaleItem, SalePayment
  - PurchaseOrder, PurchaseItem, PurchasePayment
  - AccountReceivable, AccountPayable, Payment
  - StockAdjustment, StockOpname, OpnameItem, AuditLog

## 5. Services (SCAFFOLDED - belum verified)
- ✅ 7 service classes created:
  - SaleService, StockService, ProductService
  - CustomerService, PaymentService, ReportService, AuthService

## 6. Controllers (SCAFFOLDED - validasi masih inline)
- ✅ 10 API controllers created:
  - AuthController, SalesController, ProductsController
  - CustomersController, InventoryController, SuppliersController
  - PurchaseOrdersController, CategoriesController
  - CustomerGroupsController, ReportsController

## 7. Seeders (SCAFFOLDED - belum tested)
- ✅ 8 seeders created:
  - RoleSeeder, PermissionSeeder, UserSeeder
  - CustomerGroupSeeder, CategorySeeder, ProductSeeder
  - CustomerSeeder, DatabaseSeeder

---

# MISSING COMPONENTS (TODO)

Komponen berikut belum dibuat dan perlu diselesaikan sebelum aplikasi siap:

| Komponen | Prioritas | Estimasi |
|----------|-----------|----------|
| `.gitignore` | CRITICAL | 5 menit |
| `phpunit.xml` | HIGH | 10 menit |
| `tests/` directory + base classes | HIGH | 15 menit |
| `database/factories/` | HIGH | 30 menit |
| Form Request classes | HIGH | 2 jam |
| API Resource classes | MEDIUM | 1 jam |
| `package.json` | LOW | 10 menit |
| Fix migration: hapus base_unit_id FK | HIGH | 30 menit |
| Register CheckPermission middleware | HIGH | 10 menit |

---

# SETUP INSTRUCTIONS

## Prerequisites

1. PHP 8.1 or higher
2. Composer
3. MySQL/MariaDB 5.7 or higher
4. Web Server (Apache/Nginx) or PHP built-in server

## Installation Steps

### 1. Install Laravel Dependencies

```bash
cd /opt/lampp/htdocs/panglong
composer install
```

### 2. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure Database

Edit `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=panglong
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 4. Create Database

```sql
CREATE DATABASE panglong CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Run Migrations

```bash
php artisan migrate
```

### 6. Run Seeders

```bash
php artisan db:seed
```

### 7. Start Development Server

```bash
php artisan serve
```

Application will be available at: http://localhost:8000

---

# DEFAULT USERS

After running seeders, the following users are created:

| Username | Password | Role | Description |
|----------|----------|------|-------------|
| admin | password123 | Owner | Full access |
| manager1 | password123 | Manager | Manager access |
| kasir1 | password123 | Kasir | Cashier access |
| gudang1 | password123 | Gudang | Warehouse access |

---

# API ENDPOINTS

## Authentication

- `POST /api/v1/auth/login` - Login
- `POST /api/v1/auth/logout` - Logout
- `GET /api/v1/auth/me` - Get current user

## Sales

- `GET /api/v1/sales` - List sales
- `POST /api/v1/sales` - Create sale
- `GET /api/v1/sales/{id}` - Get sale details
- `PUT /api/v1/sales/{id}` - Update sale
- `DELETE /api/v1/sales/{id}` - Void sale
- `POST /api/v1/sales/{id}/payment` - Record payment

## Products

- `GET /api/v1/products` - List products
- `POST /api/v1/products` - Create product
- `GET /api/v1/products/{id}` - Get product details
- `PUT /api/v1/products/{id}` - Update product
- `DELETE /api/v1/products/{id}` - Delete product
- `GET /api/v1/products/search` - Search products

## Customers

- `GET /api/v1/customers` - List customers
- `POST /api/v1/customers` - Create customer
- `GET /api/v1/customers/{id}` - Get customer details
- `PUT /api/v1/customers/{id}` - Update customer
- `DELETE /api/v1/customers/{id}` - Delete customer

## Inventory

- `GET /api/v1/stock` - Get stock report
- `GET /api/v1/stock/{product_id}` - Get product stock history
- `POST /api/v1/stock/adjustments` - Create stock adjustment
- `POST /api/v1/stock/adjustments/{id}/approve` - Approve adjustment
- `POST /api/v1/stock/opnames` - Create stock opname
- `POST /api/v1/stock/opnames/{id}/approve` - Approve opname

## Suppliers

- `GET /api/v1/suppliers` - List suppliers
- `POST /api/v1/suppliers` - Create supplier
- `GET /api/v1/suppliers/{id}` - Get supplier details
- `PUT /api/v1/suppliers/{id}` - Update supplier
- `DELETE /api/v1/suppliers/{id}` - Delete supplier

## Purchase Orders

- `GET /api/v1/purchase-orders` - List purchase orders
- `POST /api/v1/purchase-orders` - Create purchase order
- `GET /api/v1/purchase-orders/{id}` - Get PO details
- `POST /api/v1/purchase-orders/{id}/receive` - Receive PO
- `DELETE /api/v1/purchase-orders/{id}` - Delete PO

## Categories

- `GET /api/v1/categories` - List categories
- `POST /api/v1/categories` - Create category
- `GET /api/v1/categories/{id}` - Get category details
- `PUT /api/v1/categories/{id}` - Update category
- `DELETE /api/v1/categories/{id}` - Delete category

## Customer Groups

- `GET /api/v1/customer-groups` - List customer groups
- `POST /api/v1/customer-groups` - Create customer group
- `GET /api/v1/customer-groups/{id}` - Get group details
- `PUT /api/v1/customer-groups/{id}` - Update customer group
- `DELETE /api/v1/customer-groups/{id}` - Delete customer group

## Reports

- `GET /api/v1/reports/sales/daily` - Daily sales report
- `GET /api/v1/reports/sales/monthly` - Monthly sales report
- `GET /api/v1/reports/inventory/low-stock` - Low stock report
- `GET /api/v1/reports/accounts/receivable/aging` - AR aging report

---

# TESTING THE API

## Login Example

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password123"}'
```

Response:
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
        "user": {
            "id": 1,
            "username": "admin",
            "full_name": "Administrator"
        }
    }
}
```

## Using the Token

```bash
curl -X GET http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer {token}"
```

---

# NEXT STEPS FOR DEVELOPMENT

## Immediate Tasks

1. **Fix Missing Imports**
   - Add missing imports in controllers (ProductUnit, etc.)
   - Fix any namespace issues

2. **Add Middleware**
   - Create CheckPermission middleware
   - Update Kernel.php with middleware aliases

3. **Add Form Requests**
   - Create validation request classes for complex validations
   - Replace inline validation with Form Requests

4. **Add API Resources**
   - Create API Resource classes for consistent responses
   - Replace direct model returns with resources

5. **Add Exception Handlers**
   - Create custom exception handlers
   - Implement proper error responses

## Short-term Enhancements

1. **Authentication Middleware**
   - Implement proper authentication middleware
   - Add token refresh mechanism

2. **File Uploads**
   - Add product image upload functionality
   - Add document upload for customers

3. **Barcode Scanning**
   - Implement barcode scanning endpoint
   - Add barcode validation

4. **PDF Generation**
   - Add invoice PDF generation
   - Add report PDF export

## Long-term Features

1. **Frontend Development**
   - Build POS interface
   - Build admin dashboard
   - Build inventory management UI

2. **Advanced Features**
   - Real-time stock updates
   - Email notifications
   - SMS notifications
   - Multi-location support

3. **Integration**
   - Payment gateway integration
   - Accounting software integration
   - E-commerce integration

---

# TROUBLESHOOTING

## Common Issues

### Migration Error: Table Already Exists

```bash
php artisan migrate:fresh
```

### Composer Install Fails

```bash
composer install --no-interaction --prefer-dist
```

### Permission Denied on Storage

```bash
chmod -R 775 storage bootstrap/cache
```

### API Returns 401 Unauthorized

- Ensure you have the correct token
- Check that the token hasn't expired
- Verify the user is active

---

# LEARNING RESOURCES

- Read `LARAVEL_LEARNING_GUIDE.md` for Laravel fundamentals
- Read `API_SPECIFICATION.md` for API details
- Read `DATABASE_SCHEMA.md` for database structure
- Read `TESTING_FRAMEWORK.md` for testing guidelines

---

# SUPPORT

For questions or issues:
1. Check the documentation files
2. Review Laravel official documentation
3. Check the code comments

---

# SUMMARY

Foundational scaffolding is complete. Namun aplikasi belum fully functional.

**Yang perlu dilakukan sebelum testing:**
1. Buat `.gitignore` dan remove sensitive files dari git
2. Fix circular FK di migration products
3. Register middleware di Kernel.php
4. Jalankan `composer install`
5. Jalankan `php artisan migrate --seed`
6. Test API login endpoint

**Yang perlu dilakukan sebelum production:**
1. Buat Form Request validation classes
2. Buat API Resource classes
3. Tulis unit & feature tests
4. Standardisasi error handling
5. Frontend integration testing

> Lihat DEVELOPMENT_ROADMAP.md untuk sprint plan detail.
