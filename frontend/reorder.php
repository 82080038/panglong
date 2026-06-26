<?php
require_once 'config.php';

$d = db();

$products = $d->query("SELECT id, code, name, min_stock, max_stock, buy_price FROM products WHERE is_active = 1 ORDER BY name LIMIT 200")->fetchAll();

$suggestions = [];
foreach ($products as $p) {
    $stmt = $d->prepare("SELECT COALESCE(SUM(quantity),0) as qty FROM stock_movements WHERE product_id = ?");
    $stmt->execute([$p['id']]);
    $currentStock = (float)$stmt->fetchColumn();

    $stmt = $d->prepare("SELECT COALESCE(SUM(si.quantity),0) as total_sold FROM sale_items si JOIN sales s ON si.sale_id = s.id WHERE si.product_id = ? AND s.sale_date >= date('now','-30 days') AND s.status != 'voided'");
    $stmt->execute([$p['id']]);
    $totalSold30 = (float)$stmt->fetchColumn();
    $avgDaily = $totalSold30 / 30;

    $daysOfSupply = $avgDaily > 0 ? (int)($currentStock / $avgDaily) : 999;
    $minStock = (float)($p['min_stock'] ?? 0);
    $maxStock = (float)($p['max_stock'] ?? 0);

    if ($currentStock <= $minStock || $daysOfSupply < 14) {
        $suggestedQty = max(0, $maxStock - $currentStock);
        if ($suggestedQty <= 0 && $currentStock <= $minStock) $suggestedQty = $minStock * 2;

        $priority = 'low';
        $reason = 'Below minimum stock';
        if ($currentStock <= 0) { $priority = 'critical'; $reason = 'Out of stock'; }
        elseif ($daysOfSupply < 7) { $priority = 'critical'; $reason = 'Less than 7 days supply'; }
        elseif ($daysOfSupply < 14) { $priority = 'high'; $reason = 'Less than 14 days supply'; }
        elseif ($currentStock <= $minStock) { $priority = 'medium'; $reason = 'Below minimum stock'; }

        $suggestions[] = [
            'priority' => $priority,
            'product_code' => $p['code'],
            'product_name' => $p['name'],
            'current_stock' => $currentStock,
            'avg_daily_usage' => round($avgDaily, 2),
            'days_of_supply' => $daysOfSupply,
            'suggested_order_qty' => (int)$suggestedQty,
            'reason' => $reason
        ];
    }
}

usort($suggestions, function($a, $b) {
    $order = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
    return $order[$a['priority']] <=> $order[$b['priority']];
});

$total = count($suggestions);

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
            <thead><tr><th>Priority</th><th>Kode</th><th>Product</th><th>Stok Saat Ini</th><th>Avg Daily Usage</th><th>Hari Persediaan</th><th>Suggested Order Qty</th><th>Reason</th></tr></thead>
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
