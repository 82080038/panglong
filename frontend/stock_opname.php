<?php
require_once 'config.php';
requirePermission('manage_stock_opname');

$d = db();
$user = currentUser();
$tenantId = $user['tenant_id'] ?? null;
$isSuperAdmin = $user['role_slug'] === 'super_admin';
$canApprove = in_array($user['role_slug'], ['owner', 'manager', 'super_admin']);

$viewOpnameId = isset($_GET['view']) ? (int)$_GET['view'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_opname') {
    requireCsrfToken();
    $now = date('Y-m-d H:i:s');
    $opnameDate = $_POST['opname_date'] ?? date('Y-m-d');
    $notes = $_POST['notes'] ?? '';

    if (!empty($_POST['physical_qty'])) {
        $d->beginTransaction();
        try {
            $stmt = $d->prepare("INSERT INTO stock_opnames (tenant_id, opname_date, notes, status, created_by, created_at) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$tenantId, $opnameDate, $notes, 'pending', $user['id'], $now]);
            $opnameId = $d->lastInsertId();

            $itemStmt = $d->prepare("INSERT INTO opname_items (opname_id, product_id, system_qty, physical_qty, difference, created_at, tenant_id) VALUES (?,?,?,?,?,?,?)");
            foreach ($_POST['physical_qty'] as $pid => $qty) {
                if ($qty !== '') {
                    $pid = (int)$pid;
                    $sysQtyStmt = $d->prepare("SELECT COALESCE(SUM(quantity),0) FROM stock_movements WHERE product_id = ?");
                    $sysQtyStmt->execute([$pid]);
                    $sysQty = (float)$sysQtyStmt->fetchColumn();
                    $physQty = (float)$qty;
                    $diff = $physQty - $sysQty;
                    $itemStmt->execute([$opnameId, $pid, $sysQty, $physQty, $diff, $now, $tenantId]);
                }
            }

            $d->commit();
            header('Location: stock_opname.php?msg=created');
            exit;
        } catch (Exception $e) {
            $d->rollBack();
            error_log('Stock opname creation failed: ' . $e->getMessage());
            header('Location: stock_opname.php?msg=error');
            exit;
        }
    }
    header('Location: stock_opname.php?msg=error');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'approve_opname') {
    requireCsrfToken();
    if (!$canApprove) {
        header('Location: stock_opname.php?msg=unauthorized');
        exit;
    }
    $opnameId = (int)($_POST['opname_id'] ?? 0);
    if ($opnameId > 0) {
        $d->beginTransaction();
        try {
            $checkStmt = $d->prepare("SELECT * FROM stock_opnames WHERE id = ? AND status = 'pending'" . ($isSuperAdmin ? "" : " AND (tenant_id = ? OR tenant_id IS NULL)"));
            $isSuperAdmin ? $checkStmt->execute([$opnameId]) : $checkStmt->execute([$opnameId, $tenantId]);
            $opname = $checkStmt->fetch();
            if ($opname) {
                $now = date('Y-m-d H:i:s');
                $itemsStmt = $d->prepare("SELECT * FROM opname_items WHERE opname_id = ?");
                $itemsStmt->execute([$opnameId]);
                $items = $itemsStmt->fetchAll();

                $moveStmt = $d->prepare("INSERT INTO stock_movements (tenant_id, product_id, quantity, unit_id, movement_type, reference_type, reference_id, notes, created_by, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)");
                foreach ($items as $item) {
                    if ((float)$item['difference'] != 0) {
                        $unitStmt = $d->prepare("SELECT id FROM product_units WHERE product_id = ? AND is_base_unit = 1 LIMIT 1");
                        $unitStmt->execute([(int)$item['product_id']]);
                        $unitId = $unitStmt->fetchColumn() ?: 1;
                        $moveStmt->execute([$opname['tenant_id'], $item['product_id'], $item['difference'], $unitId, 'adjustment', 'opname', $opnameId, 'Approval opname #' . $opnameId, $user['id'], $now]);
                    }
                }

                $updateStmt = $d->prepare("UPDATE stock_opnames SET status = 'approved', approved_by = ?, approved_at = ? WHERE id = ?");
                $updateStmt->execute([$user['id'], $now, $opnameId]);
                $d->commit();
                header('Location: stock_opname.php?msg=approved');
                exit;
            }
        } catch (Exception $e) {
            $d->rollBack();
            error_log('Stock opname approval failed: ' . $e->getMessage());
        }
    }
    header('Location: stock_opname.php?msg=error');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_opname') {
    requireCsrfToken();
    $opnameId = (int)($_POST['opname_id'] ?? 0);
    if ($opnameId > 0) {
        $d->beginTransaction();
        try {
            $checkStmt = $d->prepare("SELECT status FROM stock_opnames WHERE id = ?" . ($isSuperAdmin ? "" : " AND (tenant_id = ? OR tenant_id IS NULL)"));
            $isSuperAdmin ? $checkStmt->execute([$opnameId]) : $checkStmt->execute([$opnameId, $tenantId]);
            $opname = $checkStmt->fetch();
            if ($opname && $opname['status'] === 'pending') {
                $d->prepare("DELETE FROM opname_items WHERE opname_id = ?")->execute([$opnameId]);
                $d->prepare("DELETE FROM stock_opnames WHERE id = ?")->execute([$opnameId]);
                $d->commit();
                header('Location: stock_opname.php?msg=deleted');
                exit;
            }
        } catch (Exception $e) {
            $d->rollBack();
            error_log('Stock opname deletion failed: ' . $e->getMessage());
        }
    }
    header('Location: stock_opname.php?msg=error');
    exit;
}

$productSql = "SELECT id, code, name FROM products WHERE is_active = 1";
$productParams = [];
if (!$isSuperAdmin && $tenantId) {
    $productSql .= " AND (tenant_id = ? OR tenant_id IS NULL)";
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

$historySql = "SELECT so.*, u.full_name as created_name, ap.full_name as approved_name FROM stock_opnames so LEFT JOIN users u ON so.created_by = u.id LEFT JOIN users ap ON so.approved_by = ap.id WHERE 1=1";
$historyParams = [];
if (!$isSuperAdmin && $tenantId) {
    $historySql .= " AND (so.tenant_id = ? OR so.tenant_id IS NULL)";
    $historyParams[] = $tenantId;
}
$historySql .= " ORDER BY so.created_at DESC LIMIT 50";
$historyStmt = $d->prepare($historySql);
$historyStmt->execute($historyParams);
$opnames = $historyStmt->fetchAll();

$viewOpname = null;
$viewItems = [];
if ($viewOpnameId > 0) {
    $viewStmt = $d->prepare("SELECT so.*, u.full_name as created_name, ap.full_name as approved_name FROM stock_opnames so LEFT JOIN users u ON so.created_by = u.id LEFT JOIN users ap ON so.approved_by = ap.id WHERE so.id = ?" . ($isSuperAdmin ? "" : " AND (so.tenant_id = ? OR so.tenant_id IS NULL)"));
    $isSuperAdmin ? $viewStmt->execute([$viewOpnameId]) : $viewStmt->execute([$viewOpnameId, $tenantId]);
    $viewOpname = $viewStmt->fetch();
    if ($viewOpname) {
        $itemsStmt = $d->prepare("SELECT oi.*, p.code, p.name FROM opname_items oi JOIN products p ON oi.product_id = p.id WHERE oi.opname_id = ?");
        $itemsStmt->execute([$viewOpnameId]);
        $viewItems = $itemsStmt->fetchAll();
    }
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
    <?php elseif ($msg === 'approved'): ?>
        <div class="alert alert-success alert-dismissible fade show">Opname berhasil disetujui dan stok telah disesuaikan. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php elseif ($msg === 'deleted'): ?>
        <div class="alert alert-success alert-dismissible fade show">Opname berhasil dihapus. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php elseif ($msg === 'error'): ?>
        <div class="alert alert-danger alert-dismissible fade show">Terjadi kesalahan. Silakan coba lagi. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php elseif ($msg === 'unauthorized'): ?>
        <div class="alert alert-warning alert-dismissible fade show">Anda tidak memiliki izin untuk menyetujui opname. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <?php if ($viewOpname): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Detail Opname #<?= $viewOpname['id'] ?></h5>
                <a href="stock_opname.php" class="btn btn-sm btn-secondary">Kembali</a>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3"><strong>Tanggal:</strong> <?= tglIndo($viewOpname['opname_date']) ?></div>
                    <div class="col-md-3"><strong>Status:</strong> <span class="badge bg-<?= $viewOpname['status']==='approved'?'success':'warning text-dark' ?>"><?= ucfirst($viewOpname['status']) ?></span></div>
                    <div class="col-md-3"><strong>Dibuat oleh:</strong> <?= htmlspecialchars($viewOpname['created_name'] ?? '-') ?></div>
                    <div class="col-md-3"><strong>Disetujui oleh:</strong> <?= htmlspecialchars($viewOpname['approved_name'] ?? '-') ?></div>
                </div>
                <p><strong>Catatan:</strong> <?= htmlspecialchars($viewOpname['notes'] ?: '-') ?></p>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead><tr><th>Kode</th><th>Nama Produk</th><th>Jml Sistem</th><th>Jml Fisik</th><th>Selisih</th></tr></thead>
                        <tbody>
                            <?php foreach ($viewItems as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['code']) ?></td>
                                    <td><?= htmlspecialchars($item['name']) ?></td>
                                    <td><?= number_format($item['system_qty'], 3) ?></td>
                                    <td><?= number_format($item['physical_qty'], 3) ?></td>
                                    <td class="<?= $item['difference'] > 0 ? 'text-success' : ($item['difference'] < 0 ? 'text-danger' : '') ?>"><?= ($item['difference'] > 0 ? '+' : '') . number_format($item['difference'], 3) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-body">
                <p class="text-muted">Stock opname (stock take) compares system stock with physical count. Enter physical quantities and submit to create an opname for approval.</p>
                <form method="POST" action="stock_opname.php">
                    <input type="hidden" name="action" value="create_opname">
                    <div class="row mb-3">
                        <div class="col-md-3"><label class="form-label">Tanggal Opname</label><input type="date" class="form-control" name="opname_date" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Catatan</label><input type="text" class="form-control" name="notes" placeholder="Optional notes"></div>
                    </div>
                    <div class="table-responsive"><table class="table table-striped" id="opnameTable">
                        <thead><tr><th>Kode Produk</th><th>Nama Produk</th><th>Jml Sistem</th><th>Jml Fisik</th><th>Selisih</th></tr></thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                                <?php $sysQty = $stockData[$p['id']] ?? 0; ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['code']) ?></td>
                                    <td><?= htmlspecialchars($p['name']) ?></td>
                                    <td><?= number_format($sysQty, 3) ?></td>
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

        <div class="card">
            <div class="card-header"><h5 class="mb-0">Riwayat Opname</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead><tr><th>ID</th><th>Tanggal</th><th>Catatan</th><th>Status</th><th>Dibuat</th><th>Disetujui</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php foreach ($opnames as $op): ?>
                                <tr>
                                    <td>#<?= $op['id'] ?></td>
                                    <td><?= tglIndo($op['opname_date']) ?></td>
                                    <td><?= htmlspecialchars($op['notes'] ?: '-') ?></td>
                                    <td><span class="badge bg-<?= $op['status']==='approved'?'success':'warning text-dark' ?>"><?= ucfirst($op['status']) ?></span></td>
                                    <td><?= htmlspecialchars($op['created_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($op['approved_name'] ?? '-') ?></td>
                                    <td>
                                        <a href="stock_opname.php?view=<?= $op['id'] ?>" class="btn btn-sm btn-info">Detail</a>
                                        <?php if ($op['status'] === 'pending' && $canApprove): ?>
                                            <form method="POST" action="stock_opname.php" class="d-inline" onsubmit="return confirm('Setujui opname ini? Stok akan disesuaikan.');">
                                                <input type="hidden" name="action" value="approve_opname">
                                                <input type="hidden" name="opname_id" value="<?= $op['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success">Setujui</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($op['status'] === 'pending'): ?>
                                            <form method="POST" action="stock_opname.php" class="d-inline" onsubmit="return confirm('Hapus opname ini?');">
                                                <input type="hidden" name="action" value="delete_opname">
                                                <input type="hidden" name="opname_id" value="<?= $op['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function calcDiff(input, sysQty) {
    const physQty = parseFloat(input.value) || 0;
    const diff = physQty - sysQty;
    const cell = input.closest('tr').querySelector('.diff-cell');
    cell.textContent = diff > 0 ? '+' + diff.toFixed(3) : diff.toFixed(3);
    cell.className = 'diff-cell ' + (diff > 0 ? 'text-success' : (diff < 0 ? 'text-danger' : ''));
}
</script>
<?php renderFoot(); ?>
