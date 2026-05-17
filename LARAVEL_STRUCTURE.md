# LARAVEL PROJECT STRUCTURE

# PANGLONG ERP - PHASE 1

## Laravel 10.x Project Structure

---

# ROOT STRUCTURE

```text
panglong/
├── app/
│   ├── Actions/
│   ├── Console/
│   ├── Exceptions/
│   ├── Http/
│   ├── Models/
│   ├── Providers/
│   ├── Services/
│   ├── Repositories/
│   ├── Enums/
│   ├── Helpers/
│   └── Traits/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── public/
│   ├── css/
│   ├── js/
│   └── images/
├── resources/
│   ├── views/
│   ├── lang/
│   └── assets/
├── routes/
│   ├── api.php
│   ├── web.php
│   └── console.php
├── storage/
│   ├── app/
│   ├── framework/
│   └── logs/
├── tests/
│   ├── Feature/
│   └── Unit/
├── .env.example
├── .gitignore
├── artisan
├── composer.json
├── package.json
├── phpunit.xml
├── README.md
└── vite.config.js
```

---

# DETAILED STRUCTURE

## app/ - Application Core

### app/Actions/
- Single-action classes for complex operations
- Example: `CreateSale`, `ProcessPayment`, `AdjustStock`

### app/Console/
- Artisan commands
- Example: `StockOpnameCommand`, `GenerateDailyReportCommand`

### app/Exceptions/
- Custom exception classes
- Example: `InsufficientStockException`, `CreditLimitExceededException`

### app/Http/
- Controllers
- Middleware
- Requests (Form Request validation)
- Resources (API Resource transformers)

**Structure:**
```text
app/Http/
├── Controllers/
│   ├── Api/
│   │   ├── v1/
│   │   │   ├── AuthController.php
│   │   │   ├── SalesController.php
│   │   │   ├── ProductsController.php
│   │   │   ├── CustomersController.php
│   │   │   ├── InventoryController.php
│   │   │   └── ReportsController.php
│   │   └── BaseController.php
│   └── Web/
│       ├── DashboardController.php
│       ├── SaleController.php
│       ├── ProductController.php
│       └── ReportController.php
├── Middleware/
│   ├── Authenticate.php
│   ├── CheckPermission.php
│   ├── LogUserActivity.php
│   └── TransformRequest.php
├── Requests/
│   ├── Auth/
│   │   └── LoginRequest.php
│   ├── Sales/
│   │   ├── CreateSaleRequest.php
│   │   └── UpdateSaleRequest.php
│   ├── Products/
│   │   ├── CreateProductRequest.php
│   │   └── UpdateProductRequest.php
│   └── Inventory/
│       └── StockAdjustmentRequest.php
└── Resources/
    ├── SaleResource.php
    ├── ProductResource.php
    ├── CustomerResource.php
    └── StockMovementResource.php
```

### app/Models/
- Eloquent models
- Example: `User`, `Customer`, `Product`, `Sale`, `StockMovement`

**Structure:**
```text
app/Models/
├── User.php
├── Role.php
├── Permission.php
├── Customer.php
├── CustomerGroup.php
├── Supplier.php
├── Category.php
├── Product.php
├── ProductUnit.php
├── Barcode.php
├── StockMovement.php
├── Sale.php
├── SaleItem.php
├── SalePayment.php
├── PurchaseOrder.php
├── PurchaseItem.php
├── AccountReceivable.php
├── AccountPayable.php
├── Payment.php
├── StockAdjustment.php
├── StockOpname.php
└── AuditLog.php
```

### app/Services/
- Business logic layer
- Example: `SaleService`, `StockService`, `PaymentService`

**Structure:**
```text
app/Services/
├── AuthService.php
├── SaleService.php
├── StockService.php
├── ProductService.php
├── CustomerService.php
├── PaymentService.php
├── ReportService.php
└── AuditService.php
```

### app/Repositories/
- Data access layer (optional, can use Eloquent directly)
- Example: `SaleRepository`, `ProductRepository`

**Structure:**
```text
app/Repositories/
├── Contracts/
│   ├── SaleRepositoryInterface.php
│   ├── ProductRepositoryInterface.php
│   └── CustomerRepositoryInterface.php
└── Eloquent/
    ├── SaleRepository.php
    ├── ProductRepository.php
    └── CustomerRepository.php
```

### app/Enums/
- PHP 8.1 enums for constants
- Example: `MovementType`, `PaymentMethod`, `SaleStatus`

**Structure:**
```text
app/Enums/
├── MovementType.php
├── PaymentMethod.php
├── SaleStatus.php
├── AdjustmentType.php
└── UserRole.php
```

### app/Helpers/
- Helper functions
- Example: `formatCurrency()`, `generateInvoiceNumber()`

**Structure:**
```text
app/Helpers/
├── NumberHelper.php
├── DateHelper.php
└── StringHelper.php
```

### app/Traits/
- Reusable traits
- Example: `HasPermissions`, `Auditable`

**Structure:**
```text
app/Traits/
├── HasPermissions.php
├── Auditable.php
└── Filterable.php
```

---

## database/ - Database

### database/migrations/
- Database schema migrations
- Organized by module

**Structure:**
```text
database/migrations/
├── 2024_01_01_000000_create_users_table.php
├── 2024_01_01_000001_create_roles_table.php
├── 2024_01_01_000002_create_permissions_table.php
├── 2024_01_01_000003_create_customer_groups_table.php
├── 2024_01_01_000004_create_customers_table.php
├── 2024_01_01_000005_create_suppliers_table.php
├── 2024_01_01_000006_create_categories_table.php
├── 2024_01_01_000007_create_products_table.php
├── 2024_01_01_000008_create_product_units_table.php
├── 2024_01_01_000009_create_barcodes_table.php
├── 2024_01_01_000010_create_stock_movements_table.php
├── 2024_01_01_000011_create_sales_table.php
├── 2024_01_01_000012_create_sale_items_table.php
├── 2024_01_01_000013_create_sale_payments_table.php
├── 2024_01_01_000014_create_purchase_orders_table.php
├── 2024_01_01_000015_create_purchase_items_table.php
├── 2024_01_01_000016_create_accounts_receivable_table.php
├── 2024_01_01_000017_create_accounts_payable_table.php
├── 2024_01_01_000018_create_payments_table.php
├── 2024_01_01_000019_create_stock_adjustments_table.php
├── 2024_01_01_000020_create_stock_opnames_table.php
└── 2024_01_01_000021_create_audit_logs_table.php
```

### database/seeders/
- Seed data for development/testing

**Structure:**
```text
database/seeders/
├── RoleSeeder.php
├── PermissionSeeder.php
├── UserSeeder.php
├── CustomerGroupSeeder.php
├── CategorySeeder.php
└── ProductSeeder.php
```

### database/factories/
- Model factories for testing

**Structure:**
```text
database/factories/
├── UserFactory.php
├── CustomerFactory.php
├── ProductFactory.php
├── SaleFactory.php
└── StockMovementFactory.php
```

---

## routes/ - Routes

### routes/api.php
- API routes (RESTful)

**Structure:**
```php
// API v1
Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('sales', SalesController::class);
        Route::apiResource('products', ProductsController::class);
        Route::apiResource('customers', CustomersController::class);
        
        Route::post('/stock/adjustments', [InventoryController::class, 'adjustment']);
        Route::get('/reports/sales/daily', [ReportsController::class, 'dailySales']);
        Route::get('/reports/inventory/low-stock', [ReportsController::class, 'lowStock']);
    });
});
```

### routes/web.php
- Web routes (Blade views)

**Structure:**
```php
Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::prefix('sales')->group(function () {
        Route::get('/', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/create', [SaleController::class, 'create'])->name('sales.create');
        Route::post('/', [SaleController::class, 'store'])->name('sales.store');
        Route::get('/{id}', [SaleController::class, 'show'])->name('sales.show');
    });
    
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('products.index');
        Route::get('/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/', [ProductController::class, 'store'])->name('products.store');
    });
});
```

---

## resources/ - Frontend Resources

### resources/views/
- Blade templates

**Structure:**
```text
resources/views/
├── layouts/
│   ├── app.blade.php
│   └── auth.blade.php
├── auth/
│   ├── login.blade.php
│   └── register.blade.php
├── dashboard/
│   └── index.blade.php
├── sales/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── show.blade.php
├── products/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
├── customers/
│   ├── index.blade.php
│   └── create.blade.php
├── inventory/
│   ├── index.blade.php
│   ├── adjustment.blade.php
│   └── opname.blade.php
├── reports/
│   ├── sales.blade.php
│   └── inventory.blade.php
└── partials/
    ├── header.blade.php
    ├── sidebar.blade.php
    └── footer.blade.php
```

### resources/assets/
- Frontend assets (SASS, JS)

**Structure:**
```text
resources/assets/
├── sass/
│   ├── app.scss
│   └── variables.scss
└── js/
    ├── app.js
    ├── sales.js
    └── products.js
```

---

## tests/ - Testing

### tests/Feature/
- Feature tests (integration tests)

**Structure:**
```text
tests/Feature/
├── AuthTest.php
├── SaleTest.php
├── ProductTest.php
├── StockMovementTest.php
├── CustomerTest.php
└── PaymentTest.php
```

### tests/Unit/
- Unit tests (individual components)

**Structure:**
```text
tests/Unit/
├── SaleServiceTest.php
├── StockServiceTest.php
└── PriceCalculatorTest.php
```

---

## config/ - Configuration

### Key config files to customize:
- `app.php` - Application settings
- `database.php` - Database connections
- `auth.php` - Authentication configuration
- `permissions.php` - Permission definitions

---

## public/ - Public Assets

### Structure:
```text
public/
├── css/
│   └── app.css
├── js/
│   └── app.js
├── images/
│   ├── logo.png
│   └── favicon.ico
├── uploads/
│   ├── products/
│   ├── documents/
│   └── signatures/
└── index.php
```

---

# NAMING CONVENTIONS

## Controllers
- Singular: `SaleController`, `ProductController`
- API controllers in `app/Http/Controllers/Api/`
- Web controllers in `app/Http/Controllers/Web/`

## Models
- Singular: `User`, `Customer`, `Product`
- Use PascalCase

## Services
- Singular: `SaleService`, `StockService`
- Business logic only

## Repositories
- Singular: `SaleRepository`, `ProductRepository`
- Data access only

## Migrations
- Snake case with timestamp: `create_sales_table.php`
- Descriptive names

## Views
- Snake case: `sales/create.blade.php`
- Organized by resource

## Routes
- RESTful resource names
- Use route names: `sales.index`, `sales.create`

---

# SETUP INSTRUCTIONS

## 1. Create Laravel Project
```bash
composer create-project laravel/laravel panglong
cd panglong
```

## 2. Create Custom Folders
```bash
mkdir -p app/Actions
mkdir -p app/Services
mkdir -p app/Repositories/Contracts
mkdir -p app/Repositories/Eloquent
mkdir -p app/Enums
mkdir -p app/Helpers
mkdir -p app/Traits
mkdir -p app/Http/Controllers/Api/v1
mkdir -p app/Http/Controllers/Web
mkdir -p app/Http/Requests/Auth
mkdir -p app/Http/Requests/Sales
mkdir -p app/Http/Requests/Products
mkdir -p app/Http/Requests/Inventory
mkdir -p resources/views/layouts
mkdir -p resources/views/auth
mkdir -p resources/views/dashboard
mkdir -p resources/views/sales
mkdir -p resources/views/products
mkdir -p resources/views/customers
mkdir -p resources/views/inventory
mkdir -p resources/views/reports
mkdir -p resources/views/partials
mkdir -p public/uploads/products
mkdir -p public/uploads/documents
mkdir -p public/uploads/signatures
```

## 3. Install Dependencies
```bash
composer require laravel/sanctum
composer require spatie/laravel-permission
npm install bootstrap
npm install jquery
npm install chart.js
```

## 4. Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

## 5. Configure Database
Edit `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=panglong
DB_USERNAME=root
DB_PASSWORD=
```

## 6. Run Migrations
```bash
php artisan migrate
```

## 7. Seed Data
```bash
php artisan db:seed
```

## 8. Start Development Server
```bash
php artisan serve
```

---

# NEXT STEPS

1. Create database migrations (see DATABASE_SCHEMA.md)
2. Create models with relationships
3. Create services for business logic
4. Create controllers for API/Web
5. Create views for web interface
6. Write tests
7. Deploy and test
