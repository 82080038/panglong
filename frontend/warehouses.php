<?php
require_once 'config.php';
requirePermission('manage_warehouses');

$d = db();
$user = currentUser();
$tenantId = $user['tenant_id'] ?? null;
$branchId = $user['branch_id'] ?? null;
$isSuperAdmin = $user['role_slug'] === 'super_admin';

$warehouseSql = "SELECT * FROM warehouses";
$warehouseParams = [];
if (!$isSuperAdmin && $tenantId) {
    $warehouseSql .= " WHERE tenant_id = ?";
    $warehouseParams[] = $tenantId;
    if ($branchId) {
        $warehouseSql .= " AND branch_id = ?";
        $warehouseParams[] = $branchId;
    }
}
$warehouseSql .= " ORDER BY id";
$warehouseStmt = $d->prepare($warehouseSql);
$warehouseStmt->execute($warehouseParams);
$warehouses = $warehouseStmt->fetchAll();

$transferSql = "SELECT * FROM stock_transfers";
$transferParams = [];
if (!$isSuperAdmin && $tenantId) {
    $transferSql .= " WHERE tenant_id = ?";
    $transferParams[] = $tenantId;
}
$transferSql .= " ORDER BY id DESC LIMIT 50";
$transferStmt = $d->prepare($transferSql);
$transferStmt->execute($transferParams);
$transfers = $transferStmt->fetchAll();

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    if (($_POST['action'] ?? '') === 'create_warehouse') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO warehouses (code, name, address, phone, is_active, created_at, updated_at, tenant_id, branch_id) VALUES (?,?,?,?,1,?,?,?,?)");
        $stmt->execute([$_POST['code'], $_POST['name'], $_POST['address'] ?? null, $_POST['phone'] ?? null, $now, $now, $tenantId, $branchId]);
        header('Location: warehouses.php?msg=created');
        exit;
    } elseif (($_POST['action'] ?? '') === 'create_transfer') {
        $now = date('Y-m-d H:i:s');
        $transferNo = 'TR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $stmt = $d->prepare("INSERT INTO stock_transfers (transfer_no, transfer_date, from_warehouse_id, to_warehouse_id, status, notes, created_by, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$transferNo, $_POST['transfer_date'], (int)$_POST['from_warehouse_id'], (int)$_POST['to_warehouse_id'], 'pending', $_POST['notes'] ?? null, $user['id'], $now, $now, $tenantId]);
        $transferId = $d->lastInsertId();
        if (!empty($_POST['product_id'])) {
            foreach ($_POST['product_id'] as $i => $pid) {
                if ($pid && $_POST['quantity'][$i] > 0) {
                    $stmt = $d->prepare("INSERT INTO stock_transfer_items (transfer_id, product_id, quantity) VALUES (?,?,?)");
                    $stmt->execute([$transferId, (int)$pid, (float)$_POST['quantity'][$i]]);
                }
            }
        }
        header('Location: warehouses.php?msg=transferred');
        exit;
    } elseif (($_POST['action'] ?? '') === 'create_location') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO warehouse_locations (warehouse_id, code, name, zone_type, aisle, level, max_weight_kg, capacity_m2, is_active, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,1,?,?)");
        $stmt->execute([(int)$_POST['warehouse_id'], $_POST['code'], $_POST['name'], $_POST['zone_type'] ?? 'rack', $_POST['aisle'] ?? null, $_POST['level'] ?? null, (float)($_POST['max_weight_kg'] ?? 0), (float)($_POST['capacity_m2'] ?? 0), $now, $now]);
        header('Location: warehouses.php?msg=location_created');
        exit;
    }
}

// Fetch locations for each warehouse
$locationStmt = $d->prepare("SELECT * FROM warehouse_locations WHERE warehouse_id = ? ORDER BY code");
foreach ($warehouses as $i => $w) {
    $locationStmt->execute([$w['id']]);
    $warehouses[$i]['locations'] = $locationStmt->fetchAll();
}

$msg = $_GET['msg'] ?? '';
renderHead('Warehouses');
renderNav('warehouses');
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Gudang</h1>
        <div class="btn-group">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#whModal"><i class="bi bi-plus"></i> Add Warehouse</button>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#transferModal"><i class="bi bi-arrow-left-right"></i> Transfer Stock</button>
        </div>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msg==='error'?'danger':'success' ?> alert-dismissible fade show">
        <?= $msg==='created'?'Gudang dibuat':($msg==='transferred'?'Mutasi stok berhasil':($msg==='location_created'?'Lokasi gudang dibuat':'Terjadi kesalahan')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card mb-3"><div class="card-header"><h5 class="mb-0">Gudang</h5></div><div class="card-body">
        <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Kode</th><th>Nama</th><th>Alamat</th><th>Telepon</th><th>Lokasi</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($warehouses as $w): ?>
        <tr><td><?= htmlspecialchars($w['code']) ?></td><td><?= htmlspecialchars($w['name']) ?></td><td><?= htmlspecialchars($w['address'] ?? '-') ?></td><td><?= htmlspecialchars($w['phone'] ?? '-') ?></td><td><button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#locationModal<?= $w['id'] ?>"><i class="bi bi-geo-alt"></i> <?= count($w['locations'] ?? []) ?> Lokasi</button></td><td><span class="badge bg-success">Aktif</span></td></tr>
        <?php endforeach; ?>
        <?php if (empty($warehouses)): ?><tr><td colspan="6" class="text-center text-muted">No warehouses</td></tr><?php endif; ?>
        </tbody></table></div>
    </div></div>

    <?php foreach ($warehouses as $w): ?>
    <div class="modal fade" id="locationModal<?= $w['id'] ?>" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Lokasi Gudang: <?= htmlspecialchars($w['name']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="table-responsive mb-3"><table class="table table-sm table-striped"><thead><tr><th>Kode</th><th>Nama</th><th>Zona</th><th>Gang</th><th>Level</th><th>Max Berat</th><th>Kapasitas</th></tr></thead><tbody>
            <?php foreach (($w['locations'] ?? []) as $loc): ?>
            <tr><td><?= htmlspecialchars($loc['code']) ?></td><td><?= htmlspecialchars($loc['name']) ?></td><td><?= htmlspecialchars($loc['zone_type']) ?></td><td><?= htmlspecialchars($loc['aisle'] ?? '-') ?></td><td><?= htmlspecialchars($loc['level'] ?? '-') ?></td><td><?= $loc['max_weight_kg'] ?></td><td><?= $loc['capacity_m2'] ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($w['locations'])): ?><tr><td colspan="7" class="text-center text-muted">Belum ada lokasi</td></tr><?php endif; ?>
            </tbody></table></div>
            <h6>Tambah Lokasi</h6>
            <form method="POST" action="warehouses.php"><input type="hidden" name="action" value="create_location"><input type="hidden" name="warehouse_id" value="<?= $w['id'] ?>">
                <div class="row g-2">
                    <div class="col-md-3"><input type="text" class="form-control" name="code" placeholder="Kode" required></div>
                    <div class="col-md-3"><input type="text" class="form-control" name="name" placeholder="Nama" required></div>
                    <div class="col-md-2"><select class="form-select" name="zone_type"><option value="rack">Rack</option><option value="floor">Floor</option><option value="bin">Bin</option><option value="pallet">Pallet</option></select></div>
                    <div class="col-md-1"><input type="text" class="form-control" name="aisle" placeholder="Gang"></div>
                    <div class="col-md-1"><input type="text" class="form-control" name="level" placeholder="Level"></div>
                    <div class="col-md-1"><input type="number" class="form-control" name="max_weight_kg" placeholder="Berat" step="0.01" min="0"></div>
                    <div class="col-md-1"><input type="number" class="form-control" name="capacity_m2" placeholder="m2" step="0.01" min="0"></div>
                </div>
                <div class="mt-2"><button type="submit" class="btn btn-sm btn-primary">Simpan Lokasi</button></div>
            </form>
        </div>
    </div></div></div>
    <?php endforeach; ?>

    <div class="card"><div class="card-header"><h5 class="mb-0">Stock Transfers</h5></div><div class="card-body">
        <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Transfer No</th><th>Tanggal</th><th>From</th><th>To</th><th>Items</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($transfers as $t): ?>
        <tr><td><?= htmlspecialchars($t['transfer_no']) ?></td><td><?= tglIndo($t['transfer_date']) ?></td><td><?= htmlspecialchars($t['from_warehouse']['name'] ?? '') ?></td><td><?= htmlspecialchars($t['to_warehouse']['name'] ?? '') ?></td><td><?= count($t['items'] ?? []) ?></td><td><span class="badge bg-info"><?= $t['status'] === 'completed' ? 'Selesai' : ($t['status'] === 'in_transit' ? 'Dalam Pengiriman' : 'Pending') ?></span></td></tr>
        <?php endforeach; ?>
        <?php if (empty($transfers)): ?><tr><td colspan="6" class="text-center text-muted">No transfers yet</td></tr><?php endif; ?>
        </tbody></table></div>
    </div></div>
</div>

<div class="modal fade" id="whModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="warehouses.php"><input type="hidden" name="action" value="create_warehouse">
        <div class="modal-header"><h5 class="modal-title">Add Warehouse</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Code *</label><input type="text" class="form-control" name="code" required></div>
            <div class="mb-3"><label class="form-label">Name *</label><input type="text" class="form-control" name="name" required></div>
            <div class="mb-3"><label class="form-label">Alamat</label><textarea class="form-control" name="address"></textarea></div>
            <div class="mb-3"><label class="form-label">Telepon</label><input type="text" class="form-control" name="phone"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
    </form>
</div></div></div>

<div class="modal fade" id="transferModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST" action="warehouses.php"><input type="hidden" name="action" value="create_transfer">
        <div class="modal-header"><h5 class="modal-title">Transfer Stock</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row mb-3">
                <div class="col-md-4"><label class="form-label">Tanggal</label><input type="date" class="form-control" name="transfer_date" value="<?= date('Y-m-d') ?>" required></div>
                <div class="col-md-4"><label class="form-label">From Warehouse</label><select class="form-select" name="from_warehouse_id" required>
                    <?php foreach ($warehouses as $w): ?><option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option><?php endforeach; ?>
                </select></div>
                <div class="col-md-4"><label class="form-label">To Warehouse</label><select class="form-select" name="to_warehouse_id" required>
                    <?php foreach ($warehouses as $w): ?><option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option><?php endforeach; ?>
                </select></div>
            </div>
            <div class="mb-3"><label class="form-label">Catatan</label><input type="text" class="form-control" name="notes"></div>
            <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Produk</th><th>Qty</th></tr></thead><tbody id="transferItems">
            <tr><td><select class="form-select form-select-sm" name="product_id[]"><option value="">Select...</option><?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['code'] . ' - ' . $p['name']) ?></option><?php endforeach; ?></select></td>
            <td><input type="number" class="form-control form-control-sm" name="quantity[]" step="0.001" min="0.001" style="width:100px"></td></tr>
            </tbody></table></div>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addTransferRow()"><i class="bi bi-plus"></i> Add Row</button>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-success">Transfer</button></div>
    </form>
</div></div></div>

<script>
function addTransferRow(){
    const tb=document.getElementById('transferItems');
    const tr=tb.insertRow();
    tr.innerHTML=`<td><select class="form-select form-select-sm" name="product_id[]"><option value="">Select...</option><?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['code'] . ' - ' . $p['name']) ?></option><?php endforeach; ?></select></td><td><input type="number" class="form-control form-control-sm" name="quantity[]" step="0.001" min="0.001" style="width:100px"></td>`;
}
</script>
<?php renderFoot(); ?>
