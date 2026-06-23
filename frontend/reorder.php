<?php
require_once 'config.php';

$resp = apiCall('/reorder/suggestions');
$suggestions = $resp['body']['data'] ?? [];
$total = $resp['body']['total'] ?? 0;

renderHead('Reorder AI Suggestions');
renderNav('reorder');
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Reorder AI Suggestions</h1>
        <span class="badge bg-warning text-dark fs-6"><?= $total ?> items need reorder</span>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-lightbulb"></i> AI menganalisis pergerakan stok 30 hari terakhir untuk memprediksi kebutuhan reorder.
        Prioritas: <span class="badge bg-danger">Critical</span> <span class="badge bg-warning text-dark">High</span> <span class="badge bg-info">Medium</span> <span class="badge bg-secondary">Low</span>
    </div>

    <div class="card"><div class="card-body">
        <table class="table table-striped" id="reorderTable">
            <thead><tr><th>Priority</th><th>Code</th><th>Product</th><th>Current Stock</th><th>Avg Daily Usage</th><th>Days of Supply</th><th>Suggested Order Qty</th><th>Reason</th></tr></thead>
            <tbody>
            <?php if (count($suggestions) > 0): ?>
                <?php foreach ($suggestions as $s): ?>
                <tr>
                    <td><span class="badge bg-<?= $s['priority']==='critical'?'danger':($s['priority']==='high'?'warning text-dark':($s['priority']==='medium'?'info':'secondary')) ?>"><?= ucfirst($s['priority']) ?></span></td>
                    <td><?= htmlspecialchars($s['product_code']) ?></td>
                    <td><?= htmlspecialchars($s['product_name']) ?></td>
                    <td class="<?= $s['current_stock'] <= 0 ? 'text-danger fw-bold' : '' ?>"><?= $s['current_stock'] ?></td>
                    <td><?= number_format($s['avg_daily_usage'], 2) ?></td>
                    <td class="<?= $s['days_of_supply'] < 7 ? 'text-danger' : ($s['days_of_supply'] < 14 ? 'text-warning' : '') ?>"><?= $s['days_of_supply'] == 999 ? 'N/A' : $s['days_of_supply'] ?></td>
                    <td class="fw-bold"><?= $s['suggested_order_qty'] ?></td>
                    <td><small class="text-muted"><?= htmlspecialchars($s['reason']) ?></small></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" class="text-center text-muted">All products are well-stocked. No reorder needed.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div></div>
</div>
<?php renderFoot(); ?>
