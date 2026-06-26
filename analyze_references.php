<?php
$db = new PDO('sqlite:database/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== ENTITY REFERENCES ANALYSIS ===\n\n";

// Define main entities to analyze
$mainEntities = [
    'customers',
    'suppliers',
    'products',
    'warehouses',
    'employees',
    'users',
    'branches',
    'vehicles'
];

// Get all tables
$allTables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

foreach ($mainEntities as $entity) {
    if (!in_array($entity, $allTables)) {
        continue;
    }
    
    echo "--- $entity ---\n";
    
    // Get columns
    $columns = $db->query("PRAGMA table_info($entity)")->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns:\n";
    foreach ($columns as $col) {
        echo "  - {$col['name']} ({$col['type']})\n";
    }
    
    // Check which tables reference this entity
    echo "Referenced by:\n";
    $found = false;
    foreach ($allTables as $table) {
        if ($table === $entity) continue;
        
        $columns = $db->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            // Check if column name suggests a reference
            $colName = $col['name'];
            if (strpos($colName, strtolower($entity)) !== false || 
                strpos($colName, str_replace('s', '', strtolower($entity))) !== false ||
                $colName === str_replace('s', '', $entity) . '_id' ||
                $colName === $entity . '_id') {
                
                // Check if there's actual data
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM $table WHERE $colName IS NOT NULL");
                $stmt->execute();
                $result = $stmt->fetch();
                
                if ($result['count'] > 0) {
                    echo "  - $table.$colName ({$result['count']} records)\n";
                    $found = true;
                }
            }
        }
    }
    
    if (!$found) {
        echo "  (No references found)\n";
    }
    
    echo "\n";
}

echo "=== DETAILED REFERENCE MAPPING ===\n\n";

// Manual mapping based on common patterns
$referenceMappings = [
    'customers' => [
        'sales' => 'customer_id',
        'quotations' => 'customer_id',
        'deliveries' => 'customer_id',
        'accounts_receivable' => 'customer_id',
        'customer_product_prices' => 'customer_id'
    ],
    'suppliers' => [
        'purchase_orders' => 'supplier_id',
        'purchase_returns' => 'supplier_id',
        'accounts_payable' => 'supplier_id',
        'supplier_price_history' => 'supplier_id'
    ],
    'products' => [
        'sale_items' => 'product_id',
        'purchase_items' => 'product_id',
        'quotation_items' => 'product_id',
        'stock_movements' => 'product_id',
        'stock_adjustments' => 'product_id',
        'product_units' => 'product_id',
        'product_batches' => 'product_id',
        'customer_product_prices' => 'product_id',
        'supplier_price_history' => 'product_id'
    ],
    'warehouses' => [
        'warehouse_locations' => 'warehouse_id',
        'stock_movements' => 'warehouse_id',
        'stock_transfers' => 'from_warehouse_id',
        'stock_transfers' => 'to_warehouse_id'
    ],
    'employees' => [
        'stock_opnames' => 'employee_id',
        'deliveries' => 'driver_id'
    ],
    'users' => [
        'sales' => 'created_by',
        'audit_logs' => 'user_id'
    ],
    'branches' => [
        'sales' => 'branch_id',
        'warehouses' => 'branch_id'
    ],
    'vehicles' => [
        'deliveries' => 'vehicle_id',
        'vehicle_maintenance' => 'vehicle_id'
    ]
];

foreach ($referenceMappings as $entity => $refs) {
    if (!in_array($entity, $allTables)) {
        continue;
    }
    
    echo "--- $entity ---\n";
    foreach ($refs as $table => $field) {
        if (!in_array($table, $allTables)) {
            echo "  - $table.$field (table not found)\n";
            continue;
        }
        
        // Check if field exists
        $columns = $db->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);
        $fieldExists = false;
        foreach ($columns as $col) {
            if ($col['name'] === $field) {
                $fieldExists = true;
                break;
            }
        }
        
        if (!$fieldExists) {
            echo "  - $table.$field (field not found)\n";
            continue;
        }
        
        // Count references
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM $table WHERE $field IS NOT NULL");
        $stmt->execute();
        $result = $stmt->fetch();
        
        echo "  - $table.$field ({$result['count']} records)\n";
    }
    echo "\n";
}
