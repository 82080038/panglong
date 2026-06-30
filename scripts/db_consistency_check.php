<?php
/**
 * Database Consistency Check - Cycle 5
 * Checks for orphan records, negative stock, missing fields, and tenant inconsistencies.
 */

require_once __DIR__ . '/../frontend/db.php';
$d = db();

$checks = [];

function runCheck($d, $name, $sql, $params = []) {
    $stmt = $d->prepare($sql);
    $stmt->execute($params);
    $count = $stmt->fetchColumn();
    return ['name' => $name, 'count' => $count, 'ok' => $count == 0];
}

// Orphan records
$checks[] = runCheck($d, 'sale_items without sale', "SELECT COUNT(*) FROM sale_items si LEFT JOIN sales s ON si.sale_id = s.id WHERE s.id IS NULL");
$checks[] = runCheck($d, 'sale_payments without sale', "SELECT COUNT(*) FROM sale_payments sp LEFT JOIN sales s ON sp.sale_id = s.id WHERE s.id IS NULL");
$checks[] = runCheck($d, 'purchase_items without PO', "SELECT COUNT(*) FROM purchase_items pi LEFT JOIN purchase_orders po ON pi.po_id = po.id WHERE po.id IS NULL");
$checks[] = runCheck($d, 'purchase_payments without PO', "SELECT COUNT(*) FROM purchase_payments pp LEFT JOIN purchase_orders po ON pp.po_id = po.id WHERE po.id IS NULL");
$checks[] = runCheck($d, 'stock_movements without product', "SELECT COUNT(*) FROM stock_movements sm LEFT JOIN products p ON sm.product_id = p.id WHERE p.id IS NULL");
$checks[] = runCheck($d, 'stock_movements without user', "SELECT COUNT(*) FROM stock_movements sm LEFT JOIN users u ON sm.created_by = u.id WHERE sm.created_by IS NOT NULL AND u.id IS NULL");
$checks[] = runCheck($d, 'delivery_items without delivery', "SELECT COUNT(*) FROM delivery_items di LEFT JOIN deliveries d ON di.delivery_id = d.id WHERE d.id IS NULL");
$checks[] = runCheck($d, 'opname_items without opname', "SELECT COUNT(*) FROM opname_items oi LEFT JOIN stock_opnames so ON oi.opname_id = so.id WHERE so.id IS NULL");

// Tenant consistency
$checks[] = runCheck($d, 'products with tenant_id not in tenants', "SELECT COUNT(*) FROM products p LEFT JOIN tenants t ON p.tenant_id = t.id WHERE p.tenant_id IS NOT NULL AND t.id IS NULL");
$checks[] = runCheck($d, 'users with tenant_id not in tenants', "SELECT COUNT(*) FROM users u LEFT JOIN tenants t ON u.tenant_id = t.id WHERE u.tenant_id IS NOT NULL AND t.id IS NULL");
$checks[] = runCheck($d, 'sales with tenant_id not in tenants', "SELECT COUNT(*) FROM sales s LEFT JOIN tenants t ON s.tenant_id = t.id WHERE s.tenant_id IS NOT NULL AND t.id IS NULL");

// Negative stock
$checks[] = runCheck($d, 'products with negative stock', "SELECT COUNT(*) FROM (SELECT product_id, SUM(quantity) as qty FROM stock_movements GROUP BY product_id HAVING qty < 0)");

// Missing required fields
$checks[] = runCheck($d, 'products without name', "SELECT COUNT(*) FROM products WHERE name IS NULL OR name = ''");
$checks[] = runCheck($d, 'products without code', "SELECT COUNT(*) FROM products WHERE code IS NULL OR code = ''");
$checks[] = runCheck($d, 'customers without name', "SELECT COUNT(*) FROM customers WHERE name IS NULL OR name = ''");
$checks[] = runCheck($d, 'suppliers without name', "SELECT COUNT(*) FROM suppliers WHERE name IS NULL OR name = ''");
$checks[] = runCheck($d, 'users without role', "SELECT COUNT(*) FROM users WHERE role_id IS NULL");

// Inconsistent workflow statuses
$checks[] = runCheck($d, 'sales with invalid status', "SELECT COUNT(*) FROM sales WHERE status NOT IN ('draft','pending','completed','paid','voided')");
$checks[] = runCheck($d, 'purchase_orders with invalid status', "SELECT COUNT(*) FROM purchase_orders WHERE status NOT IN ('draft','pending','approved','ordered','received','cancelled','completed')");
$checks[] = runCheck($d, 'deliveries with invalid status', "SELECT COUNT(*) FROM deliveries WHERE status NOT IN ('pending','shipped','delivered','returned','cancelled')");

// Duplicate codes (single-column where composite unique expected)
$checks[] = runCheck($d, 'duplicate product codes across tenants', "SELECT COUNT(*) FROM (SELECT code FROM products WHERE tenant_id IS NOT NULL GROUP BY code HAVING COUNT(DISTINCT tenant_id) > 1)");

// Summary
$failed = array_filter($checks, function($c) { return !$c['ok']; });
echo "=== Database Consistency Report ===\n";
echo "Total checks: " . count($checks) . "\n";
echo "Failed: " . count($failed) . "\n\n";

foreach ($checks as $c) {
    $status = $c['ok'] ? 'OK' : 'FAIL';
    echo sprintf("%-45s %5s  %d\n", $c['name'], $status, $c['count']);
}

// Save JSON report
$reportPath = __DIR__ . '/../docs/db_consistency_report.json';
file_put_contents($reportPath, json_encode($checks, JSON_PRETTY_PRINT));
echo "\nReport saved to: {$reportPath}\n";
