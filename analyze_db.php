<?php
$db = new PDO('sqlite:database/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get all tables
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

echo "=== DATABASE TABLES ===\n";
foreach ($tables as $table) {
    echo "- $table\n";
}

echo "\n=== REFERENCE TABLES ANALYSIS ===\n";

$referenceTables = [
    'categories',
    'warehouse_locations',
    'payment_methods',
    'unit_measurements',
    'tax_rates',
    'delivery_methods',
    'status_codes',
    'customer_groups',
    'supplier_groups'
];

foreach ($referenceTables as $refTable) {
    if (in_array($refTable, $tables)) {
        echo "\n--- $refTable ---\n";
        
        // Get table structure
        $columns = $db->query("PRAGMA table_info($refTable)")->fetchAll(PDO::FETCH_ASSOC);
        echo "Columns:\n";
        foreach ($columns as $col) {
            echo "  - {$col['name']} ({$col['type']})\n";
        }
        
        // Check for foreign keys
        $fks = $db->query("PRAGMA foreign_key_list($refTable)")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($fks)) {
            echo "  No foreign keys defined\n";
        } else {
            echo "  Foreign keys:\n";
            foreach ($fks as $fk) {
                echo "    - {$fk['from']} -> {$fk['table']}.{$fk['to']}\n";
            }
        }
        
        // Check which tables reference this table
        echo "  Referenced by:\n";
        $found = false;
        foreach ($tables as $table) {
            if ($table === $refTable) continue;
            $fks = $db->query("PRAGMA foreign_key_list($table)")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($fks as $fk) {
                if ($fk['table'] === $refTable) {
                    echo "    - $table.{$fk['from']} -> $refTable.{$fk['to']}\n";
                    $found = true;
                }
            }
        }
        if (!$found) {
            echo "    (No foreign key references found)\n";
        }
    }
}

echo "\n=== PRODUCTS TABLE REFERENCES ===\n";
$columns = $db->query("PRAGMA table_info(products)")->fetchAll(PDO::FETCH_ASSOC);
echo "Columns:\n";
foreach ($columns as $col) {
    echo "  - {$col['name']} ({$col['type']})\n";
}

$fks = $db->query("PRAGMA foreign_key_list(products)")->fetchAll(PDO::FETCH_ASSOC);
if (empty($fks)) {
    echo "No foreign keys defined\n";
} else {
    echo "Foreign keys:\n";
    foreach ($fks as $fk) {
        echo "  - {$fk['from']} -> {$fk['table']}.{$fk['to']}\n";
    }
}

echo "\n=== CUSTOMERS TABLE REFERENCES ===\n";
$columns = $db->query("PRAGMA table_info(customers)")->fetchAll(PDO::FETCH_ASSOC);
echo "Columns:\n";
foreach ($columns as $col) {
    echo "  - {$col['name']} ({$col['type']})\n";
}

$fks = $db->query("PRAGMA foreign_key_list(customers)")->fetchAll(PDO::FETCH_ASSOC);
if (empty($fks)) {
    echo "No foreign keys defined\n";
} else {
    echo "Foreign keys:\n";
    foreach ($fks as $fk) {
        echo "  - {$fk['from']} -> {$fk['table']}.{$fk['to']}\n";
    }
}

echo "\n=== SUPPLIERS TABLE REFERENCES ===\n";
$columns = $db->query("PRAGMA table_info(suppliers)")->fetchAll(PDO::FETCH_ASSOC);
echo "Columns:\n";
foreach ($columns as $col) {
    echo "  - {$col['name']} ({$col['type']})\n";
}

$fks = $db->query("PRAGMA foreign_key_list(suppliers)")->fetchAll(PDO::FETCH_ASSOC);
if (empty($fks)) {
    echo "No foreign keys defined\n";
} else {
    echo "Foreign keys:\n";
    foreach ($fks as $fk) {
        echo "  - {$fk['from']} -> {$fk['table']}.{$fk['to']}\n";
    }
}

echo "\n=== SALES TABLE REFERENCES ===\n";
$columns = $db->query("PRAGMA table_info(sales)")->fetchAll(PDO::FETCH_ASSOC);
echo "Columns:\n";
foreach ($columns as $col) {
    echo "  - {$col['name']} ({$col['type']})\n";
}

$fks = $db->query("PRAGMA foreign_key_list(sales)")->fetchAll(PDO::FETCH_ASSOC);
if (empty($fks)) {
    echo "No foreign keys defined\n";
} else {
    echo "Foreign keys:\n";
    foreach ($fks as $fk) {
        echo "  - {$fk['from']} -> {$fk['table']}.{$fk['to']}\n";
    }
}
