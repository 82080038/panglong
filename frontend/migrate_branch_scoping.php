<?php
require_once __DIR__ . '/db.php';
if (PHP_SAPI !== 'cli') {
    require_once __DIR__ . '/auth.php';
    requirePermission('manage_settings');
}

$d = db();

try {
    $d->beginTransaction();

    // Get all tenants
    $tenants = $d->query('SELECT id, name FROM tenants ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tenants as $tenant) {
        $tenantId = $tenant['id'];

        // Create default branch if not exists for this tenant
        $stmt = $d->prepare('SELECT id FROM branches WHERE tenant_id = ? ORDER BY id LIMIT 1');
        $stmt->execute([$tenantId]);
        $branchId = $stmt->fetchColumn();

        if (!$branchId) {
            $now = date('Y-m-d H:i:s');
            $stmt = $d->prepare('INSERT INTO branches (tenant_id, code, name, address, phone, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$tenantId, 'MAIN', 'Cabang Utama - ' . $tenant['name'], null, null, 1, $now, $now]);
            $branchId = $d->lastInsertId();
        }

        // Update existing records with null branch_id
        foreach (['sales', 'purchase_orders', 'warehouses', 'users', 'cash_transactions', 'fixed_assets'] as $table) {
            $stmt = $d->prepare('UPDATE ' . $table . ' SET branch_id = ? WHERE tenant_id = ? AND branch_id IS NULL');
            $stmt->execute([$branchId, $tenantId]);
        }

        // employees may not have tenant_id; use branch_id only when tenant_id is present
        try {
            $stmt = $d->prepare('UPDATE employees SET branch_id = ? WHERE tenant_id = ? AND branch_id IS NULL');
            $stmt->execute([$branchId, $tenantId]);
        } catch (Exception $e) {
            // employees may lack tenant_id column
        }
    }

    $d->commit();
    echo "Branch scoping migration completed successfully.\n";
} catch (Exception $e) {
    if ($d->inTransaction()) {
        $d->rollBack();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
