<?php
/**
 * Tenant Isolation Audit
 * Checks for potential tenant isolation issues across key tables and pages.
 */
require_once __DIR__ . '/../frontend/db.php';
require_once __DIR__ . '/../frontend/auth.php';

$d = db();
$report = [
    'checks' => [],
    'issues' => [],
    'summary' => ['total_checks' => 0, 'failed_checks' => 0]
];

function addCheck(&$report, $name, $passed, $details = '') {
    $report['checks'][] = ['name' => $name, 'passed' => $passed, 'details' => $details];
    $report['summary']['total_checks']++;
    if (!$passed) $report['summary']['failed_checks']++;
}

// 1. Check for tenant orphan records in child tables
$tenantTables = [
    'products', 'customers', 'suppliers', 'warehouses', 'sales', 'purchase_orders',
    'users', 'stock_movements', 'branches', 'period_closings', 'categories', 'unit_measurements'
];

foreach ($tenantTables as $table) {
    $stmt = $d->prepare("SELECT COUNT(*) FROM {$table} t LEFT JOIN tenants tn ON t.tenant_id = tn.id WHERE t.tenant_id IS NOT NULL AND tn.id IS NULL");
    $stmt->execute();
    $count = (int)$stmt->fetchColumn();
    addCheck($report, "{$table} orphan tenant records", $count === 0, "{$count} orphan records");
}

// 2. Check super_admin users have tenant_id NULL
$stmt = $d->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.slug = 'super_admin' AND u.tenant_id IS NOT NULL");
$stmt->execute();
$count = (int)$stmt->fetchColumn();
addCheck($report, "super_admin users have NULL tenant_id", $count === 0, "{$count} super_admin with tenant_id");

// 3. Check non-super_admin users have tenant_id
$stmt = $d->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.slug != 'super_admin' AND u.tenant_id IS NULL");
$stmt->execute();
$count = (int)$stmt->fetchColumn();
addCheck($report, "non-super_admin users have tenant_id", $count === 0, "{$count} users without tenant_id");

// 4. Check sale_items belong to same tenant as sales
$stmt = $d->prepare("SELECT COUNT(*) FROM sale_items si JOIN sales s ON si.sale_id = s.id WHERE si.tenant_id IS NOT NULL AND si.tenant_id != s.tenant_id");
$stmt->execute();
$count = (int)$stmt->fetchColumn();
addCheck($report, "sale_items tenant matches sales", $count === 0, "{$count} mismatched");

// 5. Check purchase_items belong to same tenant as purchase_orders
$stmt = $d->prepare("SELECT COUNT(*) FROM purchase_items pi JOIN purchase_orders po ON pi.po_id = po.id WHERE pi.tenant_id IS NOT NULL AND pi.tenant_id != po.tenant_id");
$stmt->execute();
$count = (int)$stmt->fetchColumn();
addCheck($report, "purchase_items tenant matches purchase_orders", $count === 0, "{$count} mismatched");

// 6. Check for tenant_id=1 (legacy/invalid) records
$stmt = $d->prepare("SELECT COUNT(*) FROM tenants WHERE id = 1");
$stmt->execute();
$tenant1Exists = (int)$stmt->fetchColumn() > 0;
if (!$tenant1Exists) {
    foreach ($tenantTables as $table) {
        $stmt = $d->prepare("SELECT COUNT(*) FROM {$table} WHERE tenant_id = 1");
        $stmt->execute();
        $count = (int)$stmt->fetchColumn();
        addCheck($report, "{$table} has no tenant_id=1 orphan", $count === 0, "{$count} records with tenant_id=1");
    }
}

// Output report
$reportPath = __DIR__ . '/../docs/tenant_isolation_report.json';
file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));

echo "Tenant Isolation Audit Complete\n";
echo "Total checks: {$report['summary']['total_checks']}\n";
echo "Failed checks: {$report['summary']['failed_checks']}\n";
echo "Report saved to: {$reportPath}\n";

if ($report['summary']['failed_checks'] > 0) {
    foreach ($report['checks'] as $check) {
        if (!$check['passed']) {
            echo "FAIL: {$check['name']} - {$check['details']}\n";
        }
    }
    exit(1);
}
exit(0);
