<?php
/**
 * Script untuk membersihkan data transaksi dan user
 * Mempertahankan data master (produk, kategori, dll)
 */

$db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== MEMULAI PEMBERSIHAN DATA ===\n\n";

// Tabel yang akan dihapus (data transaksi & user)
$tables_to_delete = [
    // User & Tenant
    'users',
    'tenants',
    
    // Transaksi Sales
    'sale_payments',
    'sale_items',
    'sales',
    'sales_order_items',
    'sales_orders',
    'sales_return_items',
    'sales_returns',
    
    // Transaksi Purchase
    'purchase_payments',
    'purchase_items',
    'purchase_orders',
    'purchase_return_items',
    'purchase_returns',
    
    // Stock
    'stock_movements',
    'stock_adjustments',
    'opname_items',
    'stock_opnames',
    'stock_transfer_items',
    'stock_transfers',
    
    // Customer & Supplier
    'customers',
    'suppliers',
    
    // Accounting
    'accounts_receivable',
    'accounts_payable',
    'payments',
    'journal_entries',
    'journal_entry_lines',
    
    // Delivery
    'delivery_items',
    'deliveries',
    'partial_deliveries',
    
    // Quotations
    'quotation_items',
    'quotations',
    
    // Subscriptions
    'subscription_invoices',
    'subscriptions',
    'subscription_plans',
    
    // Marketplace
    'marketplace_integrations',
    'marketplace_product_mappings',
    
    // IoT
    'iot_sensor_readings',
    'iot_sensors',
    
    // Assets
    'fixed_assets',
    'asset_depreciations',
    
    // Banking
    'bank_statements',
    'cash_transactions',
    'cash_flow_categories',
    
    // Employees
    'employees',
    
    // Vehicles
    'vehicle_maintenance',
    'vehicles',
    
    // Warehouses & Branches
    'warehouse_locations',
    'warehouses',
    'branches',
    
    // Logs
    'audit_logs',
    'sync_logs',
    
    // Other transaction data
    'period_closings',
    'demand_forecasts',
    'price_optimizations',
    'reorder_suggestions',
    'whatsapp_messages',
    'whatsapp_templates',
    'product_batches',
    'product_tier_prices',
    'customer_product_prices',
    'supplier_price_history',
    'landed_cost_distributions',
    'app_settings',
    'chart_of_accounts',
    'route_stops',
    'delivery_routes'
];

// Cek tabel yang ada dan hapus datanya
foreach ($tables_to_delete as $table) {
    try {
        // Cek apakah tabel ada
        $check = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'")->fetch();
        
        if ($check) {
            // Hitung jumlah data sebelum hapus
            $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            
            if ($count > 0) {
                $db->exec("DELETE FROM $table");
                echo "✓ $table: $count rows deleted\n";
                
                // Reset auto-increment
                $db->exec("DELETE FROM sqlite_sequence WHERE name='$table'");
            } else {
                echo "- $table: already empty\n";
            }
        } else {
            echo "- $table: table not found\n";
        }
    } catch (Exception $e) {
        echo "✗ $table: ERROR - " . $e->getMessage() . "\n";
    }
}

echo "\n=== VERIFIKASI DATA MASTER ===\n";

// Tabel yang dipertahankan (data master)
$master_tables = [
    'categories',
    'products',
    'product_units',
    'barcodes',
    'customer_groups',
    'payment_methods',
    'delivery_methods',
    'adjustment_types',
    'e_faktur_types',
    'status_codes',
    'tax_rates',
    'roles',
    'permissions',
    'role_permission'
];

foreach ($master_tables as $table) {
    try {
        $check = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'")->fetch();
        
        if ($check) {
            $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "✓ $table: $count rows preserved\n";
        }
    } catch (Exception $e) {
        echo "- $table: not checked\n";
    }
}

echo "\n=== PEMBERSIHAN SELESAI ===\n";
