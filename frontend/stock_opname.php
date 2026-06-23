<?php
require_once 'config.php';

$productsResp = apiCall('/products?per_page=100');
$products = $productsResp['body']['data'] ?? [];

// Pre-load stock data server-side to avoid CORS
$stockData = [];
foreach ($products as $p) {
    $sr = apiCall('/stock/' . $p['id']);
    $stockData[$p['id']] = $sr['body']['data']['current_stock'] ?? ($sr['body']['data'] ?? 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_opname') {
    $items = [];
    if (!empty($_POST['physical_qty'])) {
        foreach ($_POST['physical_qty'] as $pid => $qty) {
            if ($qty !== '') {
                $items[] = ['product_id' => (int)$pid, 'physical_qty' => (float)$qty];
            }
        }
    }
    if (count($items) > 0) {
        $data = [
            'opname_date' => $_POST['opname_date'],
            'notes' => $_POST['notes'] ?? null,
            'items' => $items,
        ];
        $result = apiCall('/stock/opnames', 'POST', $data);
        if ($result['code'] === 201 || ($result['body']['success'] ?? false)) {
            header('Location: stock_opname.php?msg=created');
            exit;
        }
    }
    header('Location: stock_opname.php?msg=error');
    exit;
}

$msg = $_GET['msg'] ?? '';

renderHead('Stock Opname');
renderNav('stock-opname');
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Stock Opname</h1>
    </div>

    <?php if ($msg === 'created'): ?>
        <div class="alert alert-success alert-dismissible fade show">Opname created (pending approval). <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php elseif ($msg === 'error'): ?>
        <div class="alert alert-danger alert-dismissible fade show">Error creating opname. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <p class="text-muted">Stock opname (stock take) compares system stock with physical count. Enter physical quantities and submit to create an opname for approval.</p>
            <form method="POST" action="stock_opname.php">
                <input type="hidden" name="action" value="create_opname">
                <div class="row mb-3">
                    <div class="col-md-3"><label class="form-label">Opname Date</label><input type="date" class="form-control" name="opname_date" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Notes</label><input type="text" class="form-control" name="notes" placeholder="Optional notes"></div>
                </div>
                <table class="table table-striped">
                    <thead><tr><th>Product Code</th><th>Product Name</th><th>System Qty</th><th>Physical Qty</th><th>Difference</th></tr></thead>
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
                </table>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Create Opname</button>
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
