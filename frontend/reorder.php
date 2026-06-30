<?php
require_once 'config.php';
requirePermission('view_reorder');

$d = db();
$user = currentUser();
$tenantId = $user['tenant_id'] ?? null;
$isSuperAdmin = $user['role_slug'] === 'super_admin';

$productParams = [];
$productSql = "SELECT id, code, name, min_stock, max_stock, buy_price FROM products WHERE is_active = 1";
if (!$isSuperAdmin && $tenantId) {
    $productSql .= " AND tenant_id = ?";
    $productParams[] = $tenantId;
}
$productSql .= " ORDER BY name LIMIT 200";
$productStmt = $d->prepare($productSql);
$productStmt->execute($productParams);
$products = $productStmt->fetchAll();

$supplierSql = "SELECT id, name FROM suppliers";
$supplierParams = [];
if (!$isSuperAdmin && $tenantId) {
    $supplierSql .= " WHERE tenant_id = ?";
    $supplierParams[] = $tenantId;
}
$supplierSql .= " ORDER BY name";
$supplierStmt = $d->prepare($supplierSql);
$supplierStmt->execute($supplierParams);
$suppliers = $supplierStmt->fetchAll();

$suggestions = [];
foreach ($products as $p) {
    $stmt = $d->prepare("SELECT COALESCE(SUM(quantity),0) as qty FROM stock_movements WHERE product_id = ?");
    $stmt->execute([$p['id']]);
    $currentStock = (float)$stmt->fetchColumn();

    $soldParams = [$p['id']];
    $soldSql = "SELECT COALESCE(SUM(si.quantity),0) as total_sold FROM sale_items si JOIN sales s ON si.sale_id = s.id WHERE si.product_id = ? AND s.sale_date >= date('now','-30 days') AND s.status != 'voided'";
    if (!$isSuperAdmin && $tenantId) {
        $soldSql .= " AND s.tenant_id = ?";
        $soldParams[] = $tenantId;
    }
    $stmt = $d->prepare($soldSql);
    $stmt->execute($soldParams);
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
            'product_id' => $p['id'],
            'priority' => $priority,
            'product_code' => $p['code'],
            'product_name' => $p['name'],
            'current_stock' => $currentStock,
            'avg_daily_usage' => round($avgDaily, 2),
            'days_of_supply' => $daysOfSupply,
            'suggested_order_qty' => (int)$suggestedQty,
            'unit_price' => (float)($p['buy_price'] ?? 0),
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

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-3 align-items-end">
            <div>
                <label class="form-label">Supplier</label>
                <select id="poSupplier" class="form-select">
                    <option value="">Pilih Supplier</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Tanggal PO</label>
                <input type="date" id="poDate" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <button class="btn btn-primary" onclick="createPOFromReorder()">
                <i class="bi bi-cart-plus"></i> Buat PO dari Terpilih
            </button>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-lightbulb"></i> AI menganalisis pergerakan stok 30 hari terakhir untuk memprediksi kebutuhan reorder.
        Prioritas: <span class="badge bg-danger">Critical</span> <span class="badge bg-warning text-dark">High</span> <span class="badge bg-info">Medium</span> <span class="badge bg-secondary">Low</span>
    </div>

    <div class="card"><div class="card-body">
        <div class="table-responsive"><table class="table table-striped" id="reorderTable">
            <thead><tr><th><input type="checkbox" id="selectAllReorder" onclick="toggleSelectAllReorder()"></th><th>Priority</th><th>Kode</th><th>Product</th><th>Stok Saat Ini</th><th>Avg Daily Usage</th><th>Hari Persediaan</th><th>Suggested Order Qty</th><th>Reason</th></tr></thead>
            <tbody>
            <?php if (count($suggestions) > 0): ?>
                <?php foreach ($suggestions as $s): ?>
                <tr class="reorder-row" data-product-id="<?= $s['product_id'] ?>" data-product-name="<?= htmlspecialchars($s['product_name']) ?>" data-suggested-qty="<?= $s['suggested_order_qty'] ?>" data-unit-price="<?= $s['unit_price'] ?>">
                    <td><input type="checkbox" class="reorder-checkbox form-check-input"></td>
                    <td><span class="badge bg-<?= $s['priority']==='critical'?'danger':($s['priority']==='high'?'warning text-dark':($s['priority']==='medium'?'info':'secondary')) ?>"><?= ucfirst($s['priority']) ?></span></td>
                    <td><?= htmlspecialchars($s['product_code']) ?></td>
                    <td><?= htmlspecialchars($s['product_name']) ?></td>
                    <td class="<?= $s['current_stock'] <= 0 ? 'text-danger fw-bold' : '' ?>"><?= $s['current_stock'] ?></td>
                    <td><?= number_format($s['avg_daily_usage'], 2) ?></td>
                    <td class="<?= $s['days_of_supply'] < 7 ? 'text-danger' : ($s['days_of_supply'] < 14 ? 'text-warning' : '') ?>"><?= $s['days_of_supply'] == 999 ? 'N/A' : $s['days_of_supply'] ?></td>
                    <td class="fw-bold"><input type="number" class="form-control form-control-sm reorder-qty" style="width:80px" value="<?= $s['suggested_order_qty'] ?>" min="1" step="1"></td>
                    <td><small class="text-muted"><?= htmlspecialchars($s['reason']) ?></small></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="9" class="text-center text-muted">All products are well-stocked. No reorder needed.</td></tr>
            <?php endif; ?>
            </tbody>
        </table></div>
    </div></div>
</div>
<script>
function toggleSelectAllReorder() {
    const checked = document.getElementById('selectAllReorder').checked;
    document.querySelectorAll('.reorder-checkbox').forEach(function(cb) {
        cb.checked = checked;
    });
}

function createPOFromReorder() {
    const supplierId = document.getElementById('poSupplier').value;
    if (!supplierId) { alert('Pilih supplier terlebih dahulu'); return; }

    const items = [];
    document.querySelectorAll('.reorder-row').forEach(function(row) {
        const cb = row.querySelector('.reorder-checkbox');
        if (cb && cb.checked) {
            const qtyInput = row.querySelector('.reorder-qty');
            const qty = parseFloat(qtyInput.value) || 0;
            if (qty > 0) {
                items.push({
                    product_id: parseInt(row.dataset.productId),
                    quantity: qty,
                    unit_id: 1,
                    unit_price: parseFloat(row.dataset.unitPrice) || 0
                });
            }
        }
    });

    if (items.length === 0) { alert('Pilih minimal 1 item untuk reorder'); return; }

    const data = {
        supplier_id: parseInt(supplierId),
        po_date: document.getElementById('poDate').value,
        payment_method: 'credit',
        notes: 'Auto-generated from Reorder AI',
        items: items
    };

    fetch(API_URL + '?endpoint=purchase-orders', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(function(r) { return r.json(); }).then(function(res) {
        if (res.success) {
            window.location.href = 'purchase-orders.php?po_created=' + encodeURIComponent(res.data.po_number);
        } else {
            alert('Gagal membuat PO: ' + res.message);
        }
    }).catch(function(err) {
        alert('Error: ' + err.message);
    });
}
</script>
<?php renderFoot(); ?>
