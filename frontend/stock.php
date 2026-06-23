<?php
require_once __DIR__ . '/config.php';

$stock = apiCall('/stock');
$products = apiCall('/products');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'adjustment') {
        $data = [
            'product_id' => (int)$_POST['product_id'],
            'quantity' => (float)$_POST['quantity'],
            'adjustment_type' => $_POST['adjustment_type'],
            'reason' => $_POST['reason'],
        ];
        $result = apiCall('/stock/adjustments', 'POST', $data);
        if ($result['code'] === 201) {
            header('Location: stock.php?msg=adjustment_created');
            exit;
        } else {
            $err = $result['body']['message'] ?? 'Failed';
            header('Location: stock.php?msg=error&err=' . urlencode($err));
            exit;
        }
    }
}

$msg = $_GET['msg'] ?? '';
$errMsg = $_GET['err'] ?? '';
?>
<?php renderHead('Stock - Panglong ERP'); ?>
<?php renderNav('stock'); ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Stock Inventory</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adjustModal">
            <i class="bi bi-plus"></i> Stock Adjustment
        </button>
    </div>

    <?php if ($msg === 'adjustment_created'): ?>
        <div class="alert alert-success alert-dismissible fade show">Stock adjustment created (pending approval). <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php elseif ($msg === 'error'): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($errMsg); ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead><tr><th>Product</th><th>Code</th><th>Current Stock</th><th>Unit</th><th>Min</th><th>Max</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (isset($stock['body']['data']) && is_array($stock['body']['data'])): ?>
                        <?php foreach ($stock['body']['data'] as $item): ?>
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
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="stock.php">
                <input type="hidden" name="action" value="adjustment">
                <div class="modal-header"><h5 class="modal-title">Stock Adjustment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Product *</label>
                        <select name="product_id" class="form-select" required>
                            <option value="">Select Product</option>
                            <?php if (isset($products['body']['data'])): ?>
                                <?php foreach ($products['body']['data'] as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?> (<?php echo htmlspecialchars($p['code']); ?>)</option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adjustment Type *</label>
                        <select name="adjustment_type" class="form-select" required>
                            <option value="physical_count">Physical Count</option>
                            <option value="damage">Damage</option>
                            <option value="loss">Loss</option>
                            <option value="theft">Theft</option>
                            <option value="correction">Correction</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity (use negative for reduction) *</label>
                        <input type="number" name="quantity" class="form-control" step="0.001" required>
                    </div>
                    <div class="mb-3"><label class="form-label">Reason *</label><textarea name="reason" class="form-control" rows="3" required></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Submit</button></div>
            </form>
        </div>
    </div>
</div>
<?php renderFoot(); ?>
