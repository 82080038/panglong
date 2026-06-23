<?php
require_once 'config.php';

$warehousesResp = apiCall('/warehouses');
$warehouses = $warehousesResp['body']['data'] ?? [];

$transfersResp = apiCall('/warehouses/transfers');
$transfers = $transfersResp['body']['data']['data'] ?? ($transfersResp['body']['data'] ?? []);

$productsResp = apiCall('/products?per_page=100');
$products = $productsResp['body']['data'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'create_warehouse') {
        $result = apiCall('/warehouses', 'POST', [
            'code' => $_POST['code'],
            'name' => $_POST['name'],
            'address' => $_POST['address'] ?? null,
            'phone' => $_POST['phone'] ?? null,
        ]);
        header('Location: warehouses.php?msg=' . ($result['code'] === 201 ? 'created' : 'error'));
        exit;
    } elseif (($_POST['action'] ?? '') === 'create_transfer') {
        $items = [];
        if (!empty($_POST['product_id'])) {
            foreach ($_POST['product_id'] as $i => $pid) {
                if ($pid && $_POST['quantity'][$i] > 0) {
                    $items[] = ['product_id' => (int)$pid, 'quantity' => (float)$_POST['quantity'][$i]];
                }
            }
        }
        if (count($items) > 0) {
            $result = apiCall('/warehouses/transfer', 'POST', [
                'transfer_date' => $_POST['transfer_date'],
                'from_warehouse_id' => (int)$_POST['from_warehouse_id'],
                'to_warehouse_id' => (int)$_POST['to_warehouse_id'],
                'items' => $items,
                'notes' => $_POST['notes'] ?? null,
            ]);
            header('Location: warehouses.php?msg=' . ($result['code'] === 201 ? 'transferred' : 'error'));
            exit;
        }
        header('Location: warehouses.php?msg=error');
        exit;
    }
}

$msg = $_GET['msg'] ?? '';
renderHead('Warehouses');
renderNav('warehouses');
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Warehouses</h1>
        <div class="btn-group">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#whModal"><i class="bi bi-plus"></i> Add Warehouse</button>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#transferModal"><i class="bi bi-arrow-left-right"></i> Transfer Stock</button>
        </div>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msg==='error'?'danger':'success' ?> alert-dismissible fade show">
        <?= $msg==='created'?'Warehouse created':($msg==='transferred'?'Stock transferred successfully':'Error occurred') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card mb-3"><div class="card-header"><h5 class="mb-0">Warehouses</h5></div><div class="card-body">
        <table class="table table-striped"><thead><tr><th>Code</th><th>Name</th><th>Address</th><th>Phone</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($warehouses as $w): ?>
        <tr><td><?= htmlspecialchars($w['code']) ?></td><td><?= htmlspecialchars($w['name']) ?></td><td><?= htmlspecialchars($w['address'] ?? '-') ?></td><td><?= htmlspecialchars($w['phone'] ?? '-') ?></td><td><span class="badge bg-success">Active</span></td></tr>
        <?php endforeach; ?>
        <?php if (empty($warehouses)): ?><tr><td colspan="5" class="text-center text-muted">No warehouses</td></tr><?php endif; ?>
        </tbody></table>
    </div></div>

    <div class="card"><div class="card-header"><h5 class="mb-0">Stock Transfers</h5></div><div class="card-body">
        <table class="table table-striped"><thead><tr><th>Transfer No</th><th>Date</th><th>From</th><th>To</th><th>Items</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($transfers as $t): ?>
        <tr><td><?= htmlspecialchars($t['transfer_no']) ?></td><td><?= $t['transfer_date'] ?></td><td><?= htmlspecialchars($t['from_warehouse']['name'] ?? '') ?></td><td><?= htmlspecialchars($t['to_warehouse']['name'] ?? '') ?></td><td><?= count($t['items'] ?? []) ?></td><td><span class="badge bg-info"><?= $t['status'] ?></span></td></tr>
        <?php endforeach; ?>
        <?php if (empty($transfers)): ?><tr><td colspan="6" class="text-center text-muted">No transfers yet</td></tr><?php endif; ?>
        </tbody></table>
    </div></div>
</div>

<div class="modal fade" id="whModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="warehouses.php"><input type="hidden" name="action" value="create_warehouse">
        <div class="modal-header"><h5 class="modal-title">Add Warehouse</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Code *</label><input type="text" class="form-control" name="code" required></div>
            <div class="mb-3"><label class="form-label">Name *</label><input type="text" class="form-control" name="name" required></div>
            <div class="mb-3"><label class="form-label">Address</label><textarea class="form-control" name="address"></textarea></div>
            <div class="mb-3"><label class="form-label">Phone</label><input type="text" class="form-control" name="phone"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
    </form>
</div></div></div>

<div class="modal fade" id="transferModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST" action="warehouses.php"><input type="hidden" name="action" value="create_transfer">
        <div class="modal-header"><h5 class="modal-title">Transfer Stock</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row mb-3">
                <div class="col-md-4"><label class="form-label">Date</label><input type="date" class="form-control" name="transfer_date" value="<?= date('Y-m-d') ?>" required></div>
                <div class="col-md-4"><label class="form-label">From Warehouse</label><select class="form-select" name="from_warehouse_id" required>
                    <?php foreach ($warehouses as $w): ?><option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option><?php endforeach; ?>
                </select></div>
                <div class="col-md-4"><label class="form-label">To Warehouse</label><select class="form-select" name="to_warehouse_id" required>
                    <?php foreach ($warehouses as $w): ?><option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option><?php endforeach; ?>
                </select></div>
            </div>
            <div class="mb-3"><label class="form-label">Notes</label><input type="text" class="form-control" name="notes"></div>
            <table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th></tr></thead><tbody id="transferItems">
            <tr><td><select class="form-select form-select-sm" name="product_id[]"><option value="">Select...</option><?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['code'] . ' - ' . $p['name']) ?></option><?php endforeach; ?></select></td>
            <td><input type="number" class="form-control form-control-sm" name="quantity[]" step="0.001" min="0.001" style="width:100px"></td></tr>
            </tbody></table>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addTransferRow()"><i class="bi bi-plus"></i> Add Row</button>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Transfer</button></div>
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
