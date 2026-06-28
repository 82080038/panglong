<?php
require_once __DIR__ . '/config.php';
requirePermission('manage_stock');

$d = db();
$user = currentUser();
$tenantId = $user['tenant_id'] ?? null;
$isSuperAdmin = $user['role_slug'] === 'super_admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'adjustment') {
        $now = date('Y-m-d H:i:s');
        $pid = (int)$_POST['product_id'];

        $unitStmt = $d->prepare("SELECT id FROM product_units WHERE product_id = ? AND is_base_unit = 1 LIMIT 1");
        $unitStmt->execute([$pid]);
        $unitId = $unitStmt->fetchColumn() ?: 1;

        $stmt = $d->prepare("INSERT INTO stock_movements (tenant_id, product_id, quantity, unit_id, movement_type, reference_type, notes, created_by, created_at) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $tenantId, $pid, (float)$_POST['quantity'],
            $unitId, $_POST['adjustment_type'], 'manual_adjustment',
            $_POST['reason'], $user['id'], $now
        ]);
        header('Location: stock.php?msg=adjustment_created');
        exit;
    }
}

$stockSql = "SELECT p.id, p.name as product_name, p.code as product_code, p.min_stock, p.max_stock,
    COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) as current_stock,
    pu.unit_name as base_unit
    FROM products p LEFT JOIN product_units pu ON pu.product_id = p.id AND pu.is_base_unit = 1
    WHERE p.is_active = 1";
$stockParams = [];
if (!$isSuperAdmin && $tenantId) {
    $stockSql .= " AND (p.tenant_id = ? OR p.tenant_id IS NULL)";
    $stockParams[] = $tenantId;
}
$stockSql .= " ORDER BY p.id DESC LIMIT 200";
$stockStmt = $d->prepare($stockSql);
$stockStmt->execute($stockParams);
$stockItems = $stockStmt->fetchAll();

$adjParams = [];
$adjSql = "SELECT * FROM adjustment_types WHERE is_active = 1";
if (!$isSuperAdmin && $tenantId) {
    $adjSql .= " AND (tenant_id = ? OR tenant_id IS NULL)";
    $adjParams[] = $tenantId;
}
$adjSql .= " ORDER BY name";
$adjStmt = $d->prepare($adjSql);
$adjStmt->execute($adjParams);
$adjustmentTypes = $adjStmt->fetchAll();

foreach ($stockItems as &$item) {
    $item['status'] = 'normal';
    if ((float)$item['current_stock'] <= (float)$item['min_stock'] && (float)$item['min_stock'] > 0) $item['status'] = 'low_stock';
    elseif ((float)$item['current_stock'] >= (float)$item['max_stock'] && (float)$item['max_stock'] > 0) $item['status'] = 'overstock';
}

$productSql = "SELECT id, name, code FROM products WHERE is_active = 1";
$productParams = [];
if (!$isSuperAdmin && $tenantId) {
    $productSql .= " AND (tenant_id = ? OR tenant_id IS NULL)";
    $productParams[] = $tenantId;
}
$productSql .= " ORDER BY name LIMIT 200";
$productStmt = $d->prepare($productSql);
$productStmt->execute($productParams);
$products = $productStmt->fetchAll();

$msg = $_GET['msg'] ?? '';
$errMsg = $_GET['err'] ?? '';
?>
<?php renderHead('Stok'); ?>
<?php renderNav('stock'); ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Inventaris Stok</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adjustModal">
            <i class="bi bi-plus"></i> Penyesuaian Stok
        </button>
    </div>

    <?php if ($msg === 'adjustment_created'): ?>
        <div class="alert alert-success alert-dismissible fade show">Penyesuaian stok dibuat (menunggu persetujuan). <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php elseif ($msg === 'error'): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($errMsg); ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive"><table class="table table-striped">
                <thead><tr><th>Produk</th><th>Kode</th><th>Current Stock</th><th>Unit</th><th>Min</th><th>Max</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (is_array($stockItems)): ?>
                        <?php foreach ($stockItems as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['product_code']); ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($item['current_stock']); ?></td>
                                <td><?php echo htmlspecialchars($item['base_unit']); ?></td>
                                <td><?php echo htmlspecialchars($item['min_stock']); ?></td>
                                <td><?php echo htmlspecialchars($item['max_stock']); ?></td>
                                <td>
                                    <?php
                                    $statusClass = 'bg-success';
                                    $statusLabel = 'Normal';
                                    if ($item['status'] === 'low_stock') { $statusClass = 'bg-danger'; $statusLabel = 'Low Stock'; }
                                    elseif ($item['status'] === 'overstock') { $statusClass = 'bg-warning'; $statusLabel = 'Overstock'; }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">No stock data found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>

<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="stock.php">
                <input type="hidden" name="action" value="adjustment">
                <div class="modal-header"><h5 class="modal-title">Penyesuaian Stok</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Product *</label>
                        <select name="product_id" class="form-select" required>
                            <option value="">Select Product</option>
                            <?php if (is_array($products)): ?>
                                <?php foreach ($products as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?> (<?php echo htmlspecialchars($p['code']); ?>)</option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis Penyesuaian *</label>
                        <select name="adjustment_type" class="form-select" required>
                            <option value="">Pilih Jenis</option>
                            <?php if (is_array($adjustmentTypes)): ?>
                                <?php foreach ($adjustmentTypes as $at): ?>
                                    <option value="<?php echo htmlspecialchars($at['code']); ?>"><?php echo htmlspecialchars($at['name']); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity (use negative for reduction) *</label>
                        <input type="number" name="quantity" class="form-control" step="0.001" required>
                    </div>
                    <div class="mb-3"><label class="form-label">Reason *</label><textarea name="reason" class="form-control" rows="3" required></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Kirim</button></div>
            </form>
        </div>
    </div>
</div>
<?php renderFoot(); ?>
