# DATABASE SCHEMA

# PANGLONG ERP - PHASE 1 MVP

## Version: 1.0
## Database: MySQL 8.0+ / MariaDB 10.6+

---

# ENTITY RELATIONSHIP DIAGRAM (ERD)

```
┌─────────────────┐       ┌─────────────────┐
│     users       │       │      roles      │
├─────────────────┤       ├─────────────────┤
│ id (PK)         │◄──────┤ id (PK)         │
│ username        │       │ name            │
│ password        │       │ slug            │
│ full_name       │       └─────────────────┘
│ email           │               │
│ phone           │               │
│ role_id (FK)    │               │
│ is_active       │       ┌───────┴───────┐
│ created_at      │       │               │
│ updated_at      │       │               │
└─────────────────┘       │               │
                          │               │
                          │               │
         ┌────────────────┴──────────────┴─────────────────┐
         │                                               │
         │                                               │
┌─────────────────┐                           ┌─────────────────┐
│  permissions    │                           │ role_permission │
├─────────────────┤                           ├─────────────────┤
│ id (PK)         │                           │ role_id (FK)    │
│ name            │                           │ permission_id   │
│ description     │                           └─────────────────┘
└─────────────────┘
         │
         │
┌─────────────────┐
│ customer_groups │
├─────────────────┤
│ id (PK)         │
│ name            │
│ discount_pct    │
│ credit_limit    │
└─────────────────┘
         │
         │
┌─────────────────┐       ┌─────────────────┐
│   customers     │       │    suppliers    │
├─────────────────┤       ├─────────────────┤
│ id (PK)         │       │ id (PK)         │
│ name            │       │ name            │
│ address         │       │ address         │
│ phone           │       │ phone           │
│ email           │       │ email           │
│ group_id (FK)   │       │ payment_terms   │
│ credit_limit    │       │ credit_limit    │
│ payment_terms   │       │ is_active       │
│ credit_score    │       └─────────────────┘
│ is_active       │
└─────────────────┘
         │
         │
┌─────────────────┐
│  categories     │
├─────────────────┤
│ id (PK)         │◄──────┐
│ name            │       │
│ parent_id (FK)  │───────┘
│ level           │
└─────────────────┘
         │
         │
┌─────────────────┐       ┌─────────────────┐
│    products     │       │  product_units │
├─────────────────┤       ├─────────────────┤
│ id (PK)         │◄──────┤ id (PK)         │
│ code            │       │ product_id (FK) │
│ name            │       │ unit_name       │
│ alias           │       │ conversion_fctr │
│ category_id (FK)│       │ is_base_unit    │
│ brand           │       │ price_per_unit  │
│ base_unit_id    │       └─────────────────┘
│ min_stock       │
│ max_stock       │       ┌─────────────────┐
│ location        │       │    barcodes     │
│ buy_price       │       ├─────────────────┤
│ sell_price      │       │ id (PK)         │
│ is_active       │       │ product_id (FK) │
└─────────────────┘       │ unit_id (FK)    │
         │               │ barcode         │
         │               │ is_primary      │
         │               └─────────────────┘
         │
         │
┌─────────────────┐
│stock_movements  │
├─────────────────┤
│ id (PK)         │
│ product_id (FK) │
│ quantity        │
│ unit_id (FK)    │
│ movement_type   │
│ reference_id    │
│ reference_type  │
│ notes           │
│ created_by (FK) │
│ created_at      │
└─────────────────┘
         │
         │
┌─────────────────┐       ┌─────────────────┐
│     sales       │       │   sale_items    │
├─────────────────┤       ├─────────────────┤
│ id (PK)         │◄──────┤ id (PK)         │
│ invoice_no      │       │ sale_id (FK)    │
│ customer_id (FK)│       │ product_id (FK) │
│ sale_date       │       │ quantity        │
│ subtotal        │       │ unit_id (FK)    │
│ discount        │       │ unit_price      │
│ tax             │       │ discount        │
│ total           │       │ subtotal        │
│ payment_method  │       └─────────────────┘
│ payment_status  │
│ status          │       ┌─────────────────┐
│ notes           │       │ sale_payments   │
│ created_by (FK) │       ├─────────────────┤
│ created_at      │       │ id (PK)         │
└─────────────────┘       │ sale_id (FK)    │
         │               │ amount          │
         │               │ payment_method  │
         │               │ payment_date    │
         │               │ notes           │
         │               └─────────────────┘
         │
         │
┌─────────────────┐       ┌─────────────────┐
│purchase_orders  │       │ purchase_items  │
├─────────────────┤       ├─────────────────┤
│ id (PK)         │◄──────┤ id (PK)         │
│ po_number       │       │ po_id (FK)      │
│ supplier_id (FK)│       │ product_id (FK) │
│ po_date         │       │ quantity        │
│ subtotal        │       │ unit_id (FK)    │
│ discount        │       │ unit_price      │
│ tax             │       │ subtotal        │
│ total           │       └─────────────────┘
│ payment_status  │
│ status          │       ┌─────────────────┐
│ notes           │       │purchase_payments│
│ created_by (FK) │       ├─────────────────┤
│ created_at      │       │ id (PK)         │
└─────────────────┘       │ po_id (FK)      │
         │               │ amount          │
         │               │ payment_method  │
         │               │ payment_date    │
         │               └─────────────────┘
         │
         │
┌─────────────────┐       ┌─────────────────┐
│accounts_receiv  │       │accounts_payable │
├─────────────────┤       ├─────────────────┤
│ id (PK)         │       │ id (PK)         │
│ customer_id (FK)│       │ supplier_id (FK)│
│ sale_id (FK)    │       │ po_id (FK)      │
│ amount          │       │ amount          │
│ balance         │       │ balance         │
│ due_date        │       │ due_date        │
│ status          │       │ status          │
└─────────────────┘       └─────────────────┘
         │
         │
┌─────────────────┐       ┌─────────────────┐
│    payments     │       │stock_adjustments│
├─────────────────┤       ├─────────────────┤
│ id (PK)         │       │ id (PK)         │
│ payable_id (FK) │       │ product_id (FK) │
│ payable_type    │       │ quantity        │
│ amount          │       │ adjustment_type │
│ payment_date    │       │ reason          │
│ payment_method  │       │ approved_by (FK)│
│ notes           │       │ approved_at     │
└─────────────────┘       │ created_by (FK) │
                          │ created_at      │
                          └─────────────────┘
                                   │
                                   │
                          ┌─────────────────┐
                          │ stock_opnames   │
                          ├─────────────────┤
                          │ id (PK)         │
                          │ opname_date     │
                          │ notes           │
                          │ approved_by (FK)│
                          │ approved_at     │
                          │ created_by (FK) │
                          │ created_at      │
                          └─────────────────┘
                                   │
                                   │
                          ┌─────────────────┐
                          │opname_items     │
                          ├─────────────────┤
                          │ id (PK)         │
                          │ opname_id (FK)  │
                          │ product_id (FK) │
                          │ system_qty      │
                          │ physical_qty    │
                          │ difference      │
                          └─────────────────┘
         │
         │
┌─────────────────┐
│   audit_logs    │
├─────────────────┤
│ id (PK)         │
│ user_id (FK)    │
│ action          │
│ model_type      │
│ model_id        │
│ old_values      │ (JSON)
│ new_values      │ (JSON)
│ ip_address      │
│ user_agent      │
│ created_at      │
└─────────────────┘
```

---

# TABLE DEFINITIONS

## 1. USERS TABLE

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    role_id BIGINT UNSIGNED NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role_id (role_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 2. ROLES TABLE

```sql
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed data
INSERT INTO roles (name, slug, description) VALUES
('Owner', 'owner', 'Full access to all features'),
('Manager', 'manager', 'Manager level access'),
('Kasir', 'kasir', 'Cashier access'),
('Gudang', 'gudang', 'Warehouse access'),
('Accounting', 'accounting', 'Accounting access');
```

## 3. PERMISSIONS TABLE

```sql
CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed data
INSERT INTO permissions (name, description) VALUES
('create_sales', 'Create sales transactions'),
('edit_sales', 'Edit sales transactions'),
('void_sales', 'Void sales transactions'),
('view_profit', 'View profit reports'),
('manage_products', 'Create, edit, delete products'),
('stock_adjustment', 'Adjust stock quantities'),
('approve_adjustment', 'Approve stock adjustments'),
('manage_customers', 'Create, edit, delete customers'),
('manage_suppliers', 'Create, edit, delete suppliers'),
('record_payment', 'Record payments'),
('view_reports', 'View all reports'),
('manage_users', 'Create, edit, delete users');
```

## 4. ROLE_PERMISSION TABLE

```sql
CREATE TABLE role_permission (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 5. CUSTOMER_GROUPS TABLE

```sql
CREATE TABLE customer_groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    discount_pct DECIMAL(5,2) DEFAULT 0.00,
    credit_limit DECIMAL(15,2) DEFAULT 0.00,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed data
INSERT INTO customer_groups (name, discount_pct, credit_limit) VALUES
('Retail', 0.00, 1000000),
('Tukang', 5.00, 5000000),
('Kontraktor', 10.00, 20000000),
('Proyek', 15.00, 50000000),
('Langganan', 8.00, 10000000);
```

## 6. CUSTOMERS TABLE

```sql
CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    group_id BIGINT UNSIGNED,
    credit_limit DECIMAL(15,2) DEFAULT 0.00,
    payment_terms INT DEFAULT 30, -- days
    credit_score CHAR(1) DEFAULT 'C', -- A, B, C, D
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_group_id (group_id),
    INDEX idx_credit_score (credit_score),
    FOREIGN KEY (group_id) REFERENCES customer_groups(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 7. SUPPLIERS TABLE

```sql
CREATE TABLE suppliers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    payment_terms INT DEFAULT 30,
    credit_limit DECIMAL(15,2) DEFAULT 0.00,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 8. CATEGORIES TABLE

```sql
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    parent_id BIGINT UNSIGNED,
    level TINYINT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_parent_id (parent_id),
    INDEX idx_level (level),
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 9. PRODUCTS TABLE

```sql
CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    alias TEXT, -- JSON array of alternative names
    category_id BIGINT UNSIGNED,
    brand VARCHAR(100),
    base_unit_id BIGINT UNSIGNED,
    min_stock DECIMAL(10,3) DEFAULT 0,
    max_stock DECIMAL(10,3) DEFAULT 0,
    location VARCHAR(50), -- zone/rak
    buy_price DECIMAL(15,2) DEFAULT 0,
    sell_price DECIMAL(15,2) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_name (name),
    INDEX idx_category_id (category_id),
    INDEX idx_brand (brand),
    FULLTEXT idx_search (name, alias, brand),
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (base_unit_id) REFERENCES product_units(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 10. PRODUCT_UNITS TABLE

```sql
CREATE TABLE product_units (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    unit_name VARCHAR(20) NOT NULL,
    conversion_factor DECIMAL(10,3) NOT NULL, -- relative to base unit
    is_base_unit TINYINT(1) DEFAULT 0,
    price_per_unit DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product_id (product_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 11. BARCODES TABLE

```sql
CREATE TABLE barcodes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    unit_id BIGINT UNSIGNED,
    barcode VARCHAR(50) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_barcode (barcode),
    INDEX idx_product_id (product_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (unit_id) REFERENCES product_units(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 12. STOCK_MOVEMENTS TABLE

```sql
CREATE TABLE stock_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(10,3) NOT NULL, -- positive for in, negative for out
    unit_id BIGINT UNSIGNED NOT NULL,
    movement_type ENUM('purchase', 'sale', 'return_sale', 'return_purchase', 'adjustment', 'damage', 'opname') NOT NULL,
    reference_id BIGINT UNSIGNED,
    reference_type VARCHAR(50), -- sale, purchase, adjustment, opname
    notes TEXT,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product_id (product_id),
    INDEX idx_movement_type (movement_type),
    INDEX idx_reference (reference_type, reference_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (unit_id) REFERENCES product_units(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 13. SALES TABLE

```sql
CREATE TABLE sales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(50) UNIQUE NOT NULL,
    customer_id BIGINT UNSIGNED,
    sale_date DATE NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    discount DECIMAL(15,2) DEFAULT 0,
    tax DECIMAL(15,2) DEFAULT 0,
    total DECIMAL(15,2) NOT NULL,
    payment_method ENUM('cash', 'credit', 'transfer') NOT NULL,
    payment_status ENUM('paid', 'partial', 'unpaid') DEFAULT 'unpaid',
    status ENUM('draft', 'completed', 'voided', 'returned') DEFAULT 'draft',
    notes TEXT,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_invoice_no (invoice_no),
    INDEX idx_customer_id (customer_id),
    INDEX idx_sale_date (sale_date),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 14. SALE_ITEMS TABLE

```sql
CREATE TABLE sale_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(10,3) NOT NULL,
    unit_id BIGINT UNSIGNED NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    discount DECIMAL(15,2) DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sale_id (sale_id),
    INDEX idx_product_id (product_id),
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (unit_id) REFERENCES product_units(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 15. SALE_PAYMENTS TABLE

```sql
CREATE TABLE sale_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_method ENUM('cash', 'transfer', 'check') NOT NULL,
    payment_date DATE NOT NULL,
    notes TEXT,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sale_id (sale_id),
    INDEX idx_payment_date (payment_date),
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 16. PURCHASE_ORDERS TABLE

```sql
CREATE TABLE purchase_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(50) UNIQUE NOT NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    po_date DATE NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    discount DECIMAL(15,2) DEFAULT 0,
    tax DECIMAL(15,2) DEFAULT 0,
    total DECIMAL(15,2) NOT NULL,
    payment_status ENUM('paid', 'partial', 'unpaid') DEFAULT 'unpaid',
    status ENUM('draft', 'ordered', 'received', 'cancelled') DEFAULT 'draft',
    notes TEXT,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_po_number (po_number),
    INDEX idx_supplier_id (supplier_id),
    INDEX idx_po_date (po_date),
    INDEX idx_status (status),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 17. PURCHASE_ITEMS TABLE

```sql
CREATE TABLE purchase_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(10,3) NOT NULL,
    unit_id BIGINT UNSIGNED NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_po_id (po_id),
    INDEX idx_product_id (product_id),
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (unit_id) REFERENCES product_units(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 18. PURCHASE_PAYMENTS TABLE

```sql
CREATE TABLE purchase_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_method ENUM('cash', 'transfer', 'check') NOT NULL,
    payment_date DATE NOT NULL,
    notes TEXT,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_po_id (po_id),
    INDEX idx_payment_date (payment_date),
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 19. ACCOUNTS_RECEIVABLE TABLE

```sql
CREATE TABLE accounts_receivable (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    sale_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    balance DECIMAL(15,2) NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('pending', 'partial', 'paid', 'overdue') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_id (customer_id),
    INDEX idx_sale_id (sale_id),
    INDEX idx_due_date (due_date),
    INDEX idx_status (status),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 20. ACCOUNTS_PAYABLE TABLE

```sql
CREATE TABLE accounts_payable (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id BIGINT UNSIGNED NOT NULL,
    po_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    balance DECIMAL(15,2) NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('pending', 'partial', 'paid', 'overdue') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_supplier_id (supplier_id),
    INDEX idx_po_id (po_id),
    INDEX idx_due_date (due_date),
    INDEX idx_status (status),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 21. PAYMENTS TABLE

```sql
CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payable_id BIGINT UNSIGNED NOT NULL,
    payable_type ENUM('receivable', 'payable') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('cash', 'transfer', 'check') NOT NULL,
    notes TEXT,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payable (payable_type, payable_id),
    INDEX idx_payment_date (payment_date),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 22. STOCK_ADJUSTMENTS TABLE

```sql
CREATE TABLE stock_adjustments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(10,3) NOT NULL, -- positive or negative
    adjustment_type ENUM('physical_count', 'damage', 'loss', 'theft', 'correction') NOT NULL,
    reason TEXT NOT NULL,
    approved_by BIGINT UNSIGNED,
    approved_at TIMESTAMP NULL,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product_id (product_id),
    INDEX idx_adjustment_type (adjustment_type),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 23. STOCK_OPNAMES TABLE

```sql
CREATE TABLE stock_opnames (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    opname_date DATE NOT NULL,
    notes TEXT,
    approved_by BIGINT UNSIGNED,
    approved_at TIMESTAMP NULL,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_opname_date (opname_date),
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 24. OPNAME_ITEMS TABLE

```sql
CREATE TABLE opname_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    opname_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    system_qty DECIMAL(10,3) NOT NULL,
    physical_qty DECIMAL(10,3) NOT NULL,
    difference DECIMAL(10,3) NOT NULL, -- physical - system
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_opname_id (opname_id),
    INDEX idx_product_id (product_id),
    FOREIGN KEY (opname_id) REFERENCES stock_opnames(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 25. AUDIT_LOGS TABLE

```sql
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    action ENUM('create', 'update', 'delete', 'login', 'logout') NOT NULL,
    model_type VARCHAR(100),
    model_id BIGINT UNSIGNED,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_model (model_type, model_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

# INDEXING STRATEGY

## Critical Indexes
1. **invoice_no** (sales) - Fast invoice lookup
2. **po_number** (purchase_orders) - Fast PO lookup
3. **code** (products) - Fast product search
4. **barcode** (barcodes) - Fast barcode scan
5. **created_at** (stock_movements) - Stock history queries
6. **due_date** (accounts_receivable/payable) - Aging reports
7. **sale_date** (sales) - Daily sales reports

## Composite Indexes
1. **(reference_type, reference_id)** (stock_movements) - Reference lookups
2. **(payable_type, payable_id)** (payments) - Payment lookups
3. **(model_type, model_id)** (audit_logs) - Audit trail queries

## Full-Text Index
1. **(name, alias, brand)** (products) - Product search

---

# DATA INTEGRITY

## Foreign Key Constraints
- All foreign keys have ON DELETE RESTRICT or SET NULL
- Cascade delete for child records (sale_items, purchase_items)
- Prevent orphan records

## Check Constraints (Application Level)
- quantity cannot be negative in sale_items
- discount cannot exceed subtotal
- balance cannot exceed amount in accounts
- conversion_factor must be > 0

---

# VIEWS

## V_CURRENT_STOCK
```sql
CREATE VIEW v_current_stock AS
SELECT 
    p.id,
    p.code,
    p.name,
    p.min_stock,
    p.max_stock,
    COALESCE(SUM(sm.quantity * pu.conversion_factor), 0) as current_stock,
    pu.unit_name as base_unit
FROM products p
LEFT JOIN product_units pu ON p.base_unit_id = pu.id
LEFT JOIN stock_movements sm ON p.id = sm.product_id
GROUP BY p.id, p.code, p.name, p.min_stock, p.max_stock, pu.unit_name;
```

## V_LOW_STOCK
```sql
CREATE VIEW v_low_stock AS
SELECT * FROM v_current_stock 
WHERE current_stock < min_stock AND min_stock > 0;
```

## V_OVERSTOCK
```sql
CREATE VIEW v_overstock AS
SELECT * FROM v_current_stock 
WHERE current_stock > max_stock AND max_stock > 0;
```

---

# STORED PROCEDURES

## SP_GENERATE_INVOICE_NUMBER
```sql
DELIMITER //
CREATE PROCEDURE SP_GENERATE_INVOICE_NUMBER(IN sale_date DATE, OUT invoice_no VARCHAR(50))
BEGIN
    DECLARE prefix VARCHAR(10);
    DECLARE sequence INT;
    
    SET prefix = DATE_FORMAT(sale_date, 'INV%Y%m%d');
    SET sequence = (SELECT COALESCE(MAX(CAST(SUBSTRING(invoice_no, -4) AS UNSIGNED)), 0) + 1 
                    FROM sales 
                    WHERE invoice_no LIKE CONCAT(prefix, '%'));
    
    SET invoice_no = CONCAT(prefix, LPAD(sequence, 4, '0'));
END //
DELIMITER ;
```

## SP_GENERATE_PO_NUMBER
```sql
DELIMITER //
CREATE PROCEDURE SP_GENERATE_PO_NUMBER(IN po_date DATE, OUT po_number VARCHAR(50))
BEGIN
    DECLARE prefix VARCHAR(10);
    DECLARE sequence INT;
    
    SET prefix = DATE_FORMAT(po_date, 'PO%Y%m%d');
    SET sequence = (SELECT COALESCE(MAX(CAST(SUBSTRING(po_number, -4) AS UNSIGNED)), 0) + 1 
                    FROM purchase_orders 
                    WHERE po_number LIKE CONCAT(prefix, '%'));
    
    SET po_number = CONCAT(prefix, LPAD(sequence, 4, '0'));
END //
DELIMITER ;
```

---

# TRIGGERS

## TR_UPDATE_SALE_STATUS
```sql
DELIMITER //
CREATE TRIGGER tr_update_sale_status
AFTER INSERT ON sale_payments
FOR EACH ROW
BEGIN
    DECLARE total_paid DECIMAL(15,2);
    DECLARE sale_total DECIMAL(15,2);
    
    SELECT total INTO sale_total FROM sales WHERE id = NEW.sale_id;
    SELECT COALESCE(SUM(amount), 0) INTO total_paid FROM sale_payments WHERE sale_id = NEW.sale_id;
    
    IF total_paid >= sale_total THEN
        UPDATE sales SET payment_status = 'paid' WHERE id = NEW.sale_id;
    ELSEIF total_paid > 0 THEN
        UPDATE sales SET payment_status = 'partial' WHERE id = NEW.sale_id;
    END IF;
END //
DELIMITER ;
```

## TR_UPDATE_AR_BALANCE
```sql
DELIMITER //
CREATE TRIGGER tr_update_ar_balance
AFTER INSERT ON payments
FOR EACH ROW
BEGIN
    IF NEW.payable_type = 'receivable' THEN
        UPDATE accounts_receivable 
        SET balance = balance - NEW.amount,
            status = CASE 
                WHEN balance - NEW.amount <= 0 THEN 'paid'
                ELSE 'partial'
            END
        WHERE id = NEW.payable_id;
    END IF;
END //
DELIMITER ;
```

---

# BACKUP STRATEGY

## Daily Backup
```bash
mysqldump -u root -p panglong > /backup/panglong_daily_$(date +%Y%m%d).sql
```

## Weekly Full Backup
```bash
mysqldump --single-transaction --routines --triggers -u root -p panglong > /backup/panglong_full_$(date +%Y%m%d).sql
```

---

# MIGRATION ORDER

Execute in this order:
1. roles
2. permissions
3. role_permission
4. customer_groups
5. categories
6. users
7. customers
8. suppliers
9. products
10. product_units
11. barcodes
12. stock_movements
13. sales
14. sale_items
15. sale_payments
16. purchase_orders
17. purchase_items
18. purchase_payments
19. accounts_receivable
20. accounts_payable
21. payments
22. stock_adjustments
23. stock_opnames
24. opname_items
25. audit_logs

Then create:
- Views
- Stored Procedures
- Triggers
- Indexes
