<?php
/**
 * Add Performance Indexes to SQLite Database
 * 
 * This script adds indexes to frequently queried columns to improve performance.
 * Run this script once to optimize database queries.
 */

$dbPath = __DIR__ . '/database.sqlite';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Adding performance indexes...\n\n";

// Indexes for sales table
$indexes = [
    // Sales table indexes
    'idx_sales_sale_date' => 'CREATE INDEX IF NOT EXISTS idx_sales_sale_date ON sales(sale_date)',
    'idx_sales_tenant_id' => 'CREATE INDEX IF NOT EXISTS idx_sales_tenant_id ON sales(tenant_id)',
    'idx_sales_customer_id' => 'CREATE INDEX IF NOT EXISTS idx_sales_customer_id ON sales(customer_id)',
    'idx_sales_status' => 'CREATE INDEX IF NOT EXISTS idx_sales_status ON sales(status)',
    'idx_sales_tenant_date' => 'CREATE INDEX IF NOT EXISTS idx_sales_tenant_date ON sales(tenant_id, sale_date)',
    
    // Products table indexes (products table doesn't have tenant_id in current schema)
    'idx_products_is_active' => 'CREATE INDEX IF NOT EXISTS idx_products_is_active ON products(is_active)',
    'idx_products_code' => 'CREATE INDEX IF NOT EXISTS idx_products_code ON products(code)',
    'idx_products_name' => 'CREATE INDEX IF NOT EXISTS idx_products_name ON products(name)',
    
    // Stock movements table indexes
    'idx_stock_movements_product_id' => 'CREATE INDEX IF NOT EXISTS idx_stock_movements_product_id ON stock_movements(product_id)',
    'idx_stock_movements_warehouse_id' => 'CREATE INDEX IF NOT EXISTS idx_stock_movements_warehouse_id ON stock_movements(warehouse_id)',
    'idx_stock_movements_tenant_id' => 'CREATE INDEX IF NOT EXISTS idx_stock_movements_tenant_id ON stock_movements(tenant_id)',
    
    // Customers table indexes
    'idx_customers_tenant_id' => 'CREATE INDEX IF NOT EXISTS idx_customers_tenant_id ON customers(tenant_id)',
    'idx_customers_is_active' => 'CREATE INDEX IF NOT EXISTS idx_customers_is_active ON customers(is_active)',
    
    // Suppliers table indexes
    'idx_suppliers_tenant_id' => 'CREATE INDEX IF NOT EXISTS idx_suppliers_tenant_id ON suppliers(tenant_id)',
    'idx_suppliers_is_active' => 'CREATE INDEX IF NOT EXISTS idx_suppliers_is_active ON suppliers(is_active)',
    
    // Purchase orders table indexes
    'idx_purchase_orders_tenant_id' => 'CREATE INDEX IF NOT EXISTS idx_purchase_orders_tenant_id ON purchase_orders(tenant_id)',
    'idx_purchase_orders_supplier_id' => 'CREATE INDEX IF NOT EXISTS idx_purchase_orders_supplier_id ON purchase_orders(supplier_id)',
    'idx_purchase_orders_status' => 'CREATE INDEX IF NOT EXISTS idx_purchase_orders_status ON purchase_orders(status)',
    
    // Deliveries table indexes
    'idx_deliveries_tenant_id' => 'CREATE INDEX IF NOT EXISTS idx_deliveries_tenant_id ON deliveries(tenant_id)',
    'idx_deliveries_sale_id' => 'CREATE INDEX IF NOT EXISTS idx_deliveries_sale_id ON deliveries(sale_id)',
    'idx_deliveries_status' => 'CREATE INDEX IF NOT EXISTS idx_deliveries_status ON deliveries(status)',
    
    // Users table indexes
    'idx_users_tenant_id' => 'CREATE INDEX IF NOT EXISTS idx_users_tenant_id ON users(tenant_id)',
    'idx_users_username' => 'CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)',
    'idx_users_role_id' => 'CREATE INDEX IF NOT EXISTS idx_users_role_id ON users(role_id)',
];

foreach ($indexes as $name => $sql) {
    try {
        $db->exec($sql);
        echo "✓ Created index: $name\n";
    } catch (PDOException $e) {
        echo "✗ Failed to create index $name: " . $e->getMessage() . "\n";
    }
}

echo "\nRunning PRAGMA optimize to analyze database...\n";
$db->exec('PRAGMA optimize');
echo "✓ Database optimization complete\n";

echo "\nIndex creation complete!\n";
