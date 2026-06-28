<?php
require_once 'config.php';
requirePermission('manage_stock_opname');

$d = db();
$user = currentUser();
$tenantId = $user['tenant_id'] ?? null;
$isSuperAdmin = $user['role_slug'] === 'super_admin';

$productSql = "SELECT id, code, name FROM products WHERE is_active = 1";
$productParams = [];
if (!$isSuperAdmin && $tenantId) {
    $productSql .= " AND tenant_id = ?";
    $productParams[] = $tenantId;
}
$productSql .= " ORDER BY name LIMIT 100";
$productStmt = $d->prepare($productSql);
$productStmt->execute($productParams);
$products = $productStmt->fetchAll();

$stockData = [];
foreach ($products as $p) {
    $stmt = $d->prepare("SELECT COALESCE(SUM(quantity),0) as qty FROM stock_movements WHERE product_id = ?");
    $stmt->execute([$p['id']]);
    $stockData[$p['id']] = $stmt->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_opname') {
    $now = date('Y-m-d H:i:s');
    $opnameNo = 'OP-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    if (!empty($_POST['physical_qty'])) {
        foreach ($_POST['physical_qty'] as $pid => $qty) {
            if ($qty !== '') {
                $sysQty = $stockData[$pid] ?? 0;
                $diff = (float)$qty - (float)$sysQty;
                $stmt = $d->prepare("INSERT INTO stock_movements (product_id, quantity, movement_type, notes, created_at) VALUES (?,?,?,?,?)");
                $stmt->execute([(int)$pid, $diff, 'physical_count', 'Stok opname ' . $opnameNo, $now]);
            }
        }
        header('Location: stock_opname.php?msg=created');
        exit;
    }
    header('Location: stock_opname.php?msg=error');
    exit;
}

$msg = $_GET['msg'] ?? '';

renderHead('Stok Opname');
renderNav('stock-opname');
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Stok Opname</h1>
    </div>

    <?php if ($msg === 'created'): ?>
        <div class="alert alert-success alert-dismissible fade show">Opname dibuat (menunggu persetujuan). <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php elseif ($msg === 'error'): ?>
        <div class="alert alert-danger alert-dismissible fade show">Error creating opname. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <p class="text-muted">Stock opname (stock take) compares system stock with physical count. Enter physical quantities and submit to create an opname for approval.</p>
            <form method="POST" action="stock_opname.php">
                <input type="hidden" name="action" value="create_opname">
                <div class="row mb-3">
                    <div class="col-md-3"><label class="form-label">Tanggal Opname</label><input type="date" class="form-control" name="opname_date" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Catatan</label><input type="text" class="form-control" name="notes" placeholder="Optional notes"></div>
                </div>
                <div class="table-responsive"><table class="table table-striped">
                    <thead><tr><th>Kode Produk</th><th>Nama Produk</th><th>Jml Sistem</th><th>Jml Fisik</th><th>Selisih</th></tr></thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                            <?php $sysQty = $stockData[$p['id']] ?? 0; ?>
                            <tr>
                                <td><?= htmlspecialchars($p['code']) ?></td>
                                <td><?= htmlspecialchars($p['name']) ?></td>
                                <td><?= $sysQty ?></td>
                                <td><input type="number" class="form-control form-control-sm" name="physical_qty[<?= $p['id'] ?>]" style="width:100px" placeholder="0" step="0.001" oninput="calcDiff(this, <?= $sysQty ?>)"></td>
                                <td class="diff-cell">-</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table></div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Buat Opname</button>
            </form>
        </div>
    </div>
</div>

<script>
function calcDiff(input, sysQty) {
    const physQty = parseFloat(input.value) || 0;
    const diff = physQty - sysQty;
    const cell = input.closest('tr').querySelector('.diff-cell');
    cell.textContent = diff > 0 ? '+' + diff : diff;
    cell.className = 'diff-cell ' + (diff > 0 ? 'text-success' : (diff < 0 ? 'text-danger' : ''));
}
</script>
<?php renderFoot(); ?>
