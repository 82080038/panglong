<?php
require_once 'config.php';

$d = db();
$user = currentUser();
$tenantId = $user['tenant_id'] ?? null;
$isSuperAdmin = $user['role_slug'] === 'super_admin';

$tab = $_GET['tab'] ?? 'daily';
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

$reportData = [];

if ($tab === 'daily') {
    $dailySql = "SELECT COUNT(*) as total_sales, COALESCE(SUM(total),0) as total_revenue, COALESCE(SUM(CASE WHEN payment_method='cash' THEN total ELSE 0 END),0) as total_cash, COALESCE(SUM(CASE WHEN payment_method='credit' THEN total ELSE 0 END),0) as total_credit FROM sales WHERE sale_date = date('now') AND status != 'voided'";
    if (!$isSuperAdmin && $tenantId) {
        $dailySql .= " AND tenant_id = $tenantId";
    }
    $stmt = $d->query($dailySql);
    $reportData = $stmt->fetch();
    $reportData['date'] = date('Y-m-d');
} elseif ($tab === 'monthly') {
    $monthlySql = "SELECT COUNT(*) as total_sales, COALESCE(SUM(total),0) as total_revenue, COALESCE(SUM(CASE WHEN payment_method='cash' THEN total ELSE 0 END),0) as total_cash, COALESCE(SUM(CASE WHEN payment_method='credit' THEN total ELSE 0 END),0) as total_credit FROM sales WHERE sale_date >= date('now','start of month') AND status != 'voided'";
    if (!$isSuperAdmin && $tenantId) {
        $monthlySql .= " AND tenant_id = $tenantId";
    }
    $stmt = $d->query($monthlySql);
    $reportData = $stmt->fetch();
    $reportData['year'] = date('Y');
    $reportData['month'] = date('m');
} elseif ($tab === 'byproduct') {
    $byProductSql = "SELECT p.name as product_name, SUM(si.quantity) as quantity_sold, SUM(si.subtotal) as revenue, SUM((si.subtotal - (si.quantity * p.buy_price))) as profit FROM sale_items si JOIN sales s ON si.sale_id = s.id JOIN products p ON si.product_id = p.id WHERE s.sale_date >= ? AND s.sale_date <= ? AND s.status != 'voided'";
    if (!$isSuperAdmin && $tenantId) {
        $byProductSql .= " AND s.tenant_id = $tenantId";
    }
    $byProductSql .= " GROUP BY p.id ORDER BY revenue DESC";
    $stmt = $d->prepare($byProductSql);
    $stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
    $reportData = $stmt->fetchAll();
} elseif ($tab === 'bycustomer') {
    $byCustomerSql = "SELECT c.name as customer_name, COUNT(s.id) as total_sales, SUM(s.total) as total_revenue, 0 as total_paid, SUM(s.total) as total_unpaid FROM sales s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.sale_date >= ? AND s.sale_date <= ? AND s.status != 'voided'";
    if (!$isSuperAdmin && $tenantId) {
        $byCustomerSql .= " AND s.tenant_id = $tenantId";
    }
    $byCustomerSql .= " GROUP BY c.id ORDER BY total_revenue DESC";
    $stmt = $d->prepare($byCustomerSql);
    $stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
    $reportData = $stmt->fetchAll();
} elseif ($tab === 'profitloss') {
    $profitLossSql = "SELECT COALESCE(SUM(s.total),0) as revenue, COALESCE(SUM(si.quantity * p.buy_price),0) as cogs, COALESCE(SUM(s.total),0) - COALESCE(SUM(si.quantity * p.buy_price),0) as gross_profit, COALESCE(SUM(s.tax),0) as tax, COUNT(s.id) as total_sales FROM sales s LEFT JOIN sale_items si ON si.sale_id = s.id LEFT JOIN products p ON si.product_id = p.id WHERE s.sale_date >= ? AND s.sale_date <= ? AND s.status != 'voided'";
    if (!$isSuperAdmin && $tenantId) {
        $profitLossSql .= " AND s.tenant_id = $tenantId";
    }
    $stmt = $d->prepare($profitLossSql);
    $stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
    $reportData = $stmt->fetch();
    $reportData['net_profit'] = (float)($reportData['gross_profit'] ?? 0);
    $reportData['date_from'] = $dateFrom;
    $reportData['date_to'] = $dateTo;
} elseif ($tab === 'lowstock') {
    $lowStockSql = "SELECT p.code as product_code, p.name as product_name, COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) as current_stock, p.min_stock, (p.min_stock - COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0)) as shortage FROM products p WHERE p.is_active=1 AND CAST(p.min_stock AS REAL) > 0 AND COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) <= CAST(p.min_stock AS REAL)";
    if (!$isSuperAdmin && $tenantId) {
        $lowStockSql .= " AND p.tenant_id = $tenantId";
    }
    $reportData = $d->query($lowStockSql)->fetchAll();
} elseif ($tab === 'stockmovement') {
    try {
        $stockMovementSql = "SELECT sm.created_at as date, sm.product_id, sm.quantity, sm.movement_type, sm.notes FROM stock_movements sm";
        if (!$isSuperAdmin && $tenantId) {
            $stockMovementSql .= " JOIN products p ON sm.product_id = p.id WHERE p.tenant_id = $tenantId";
        }
        $stockMovementSql .= " ORDER BY sm.created_at DESC LIMIT 200";
        $reportData = $d->query($stockMovementSql)->fetchAll();
    } catch (Exception $e) {
        $reportData = [];
    }
} elseif ($tab === 'deadstock') {
    $deadStockSql = "SELECT p.code as product_code, p.name as product_name, COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) as current_stock, (COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) * p.buy_price) as stock_value, CAST((julianday('now') - julianday(p.updated_at)) AS INTEGER) as days_inactive FROM products p WHERE p.is_active=1";
    if (!$isSuperAdmin && $tenantId) {
        $deadStockSql .= " AND p.tenant_id = $tenantId";
    }
    $deadStockSql .= " ORDER BY days_inactive DESC";
    $reportData = $d->query($deadStockSql)->fetchAll();
} elseif ($tab === 'stockvaluation') {
    $stockValuationSql = "SELECT p.code as product_code, p.name as product_name, COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) as current_stock, p.buy_price as avg_cost, (COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) * p.buy_price) as stock_value, p.sell_price, (COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) * p.sell_price) as potential_revenue FROM products p WHERE p.is_active=1";
    if (!$isSuperAdmin && $tenantId) {
        $stockValuationSql .= " AND p.tenant_id = $tenantId";
    }
    $items = $d->query($stockValuationSql)->fetchAll();
    $totalValue = 0;
    foreach ($items as $i) {
        $totalValue += (float)$i['stock_value'];
    }
    $reportData = ['total_stock_value' => $totalValue, 'total_products' => count($items), 'items' => $items];
} elseif ($tab === 'araging') {
    $arAgingSql = "SELECT c.name as customer_name, s.total - COALESCE((SELECT SUM(sp.amount) FROM sale_payments sp WHERE sp.sale_id=s.id),0) as outstanding, CAST(julianday('now') - julianday(s.sale_date) AS INTEGER) as days_overdue FROM sales s JOIN customers c ON s.customer_id = c.id WHERE s.payment_status != 'paid' AND s.status != 'voided'";
    if (!$isSuperAdmin && $tenantId) {
        $arAgingSql .= " AND s.tenant_id = $tenantId";
    }
    $arAgingSql .= " ORDER BY days_overdue DESC";
    $details = $d->query($arAgingSql)->fetchAll();
    $reportData = ['0_30_days' => 0, '31_60_days' => 0, '61_90_days' => 0, 'over_90_days' => 0, 'total_outstanding' => 0, 'details' => $details];
    foreach ($details as $dt) {
        $out = (float)$dt['outstanding'];
        $reportData['total_outstanding'] += $out;
        if ($dt['days_overdue'] <= 30) $reportData['0_30_days'] += $out;
        elseif ($dt['days_overdue'] <= 60) $reportData['31_60_days'] += $out;
        elseif ($dt['days_overdue'] <= 90) $reportData['61_90_days'] += $out;
        else $reportData['over_90_days'] += $out;
    }
} elseif ($tab === 'apaging') {
    $reportData = ['0_30_days' => 0, '31_60_days' => 0, '61_90_days' => 0, 'over_90_days' => 0, 'total_outstanding' => 0, 'details' => []];
}

renderHead('Reports');
renderNav('reports');
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Laporan</h1>
        <div class="btn-group">
            <button class="btn btn-outline-success btn-sm" onclick="exportCSV()"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</button>
            <button class="btn btn-outline-danger btn-sm" onclick="exportPDF()"><i class="bi bi-file-earmark-pdf"></i> Export PDF</button>
        </div>
    </div>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link <?= $tab==='daily'?'active':'' ?>" href="?tab=daily">Penjualan Harian</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='monthly'?'active':'' ?>" href="?tab=monthly">Penjualan Bulanan</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='byproduct'?'active':'' ?>" href="?tab=byproduct">Sales by Product</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='bycustomer'?'active':'' ?>" href="?tab=bycustomer">Penjualan per Pelanggan</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='profitloss'?'active':'' ?>" href="?tab=profitloss">Profit/Loss</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='lowstock'?'active':'' ?>" href="?tab=lowstock">Stok Menipis</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='stockmovement'?'active':'' ?>" href="?tab=stockmovement">Stock Movement</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='deadstock'?'active':'' ?>" href="?tab=deadstock">Dead Stock</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='stockvaluation'?'active':'' ?>" href="?tab=stockvaluation">Stock Valuation</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='araging'?'active':'' ?>" href="?tab=araging">Piutang Jatuh Tempo</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='apaging'?'active':'' ?>" href="?tab=apaging">AP Aging</a></li>
    </ul>

    <?php if (in_array($tab, ['byproduct','bycustomer','stockmovement','profitloss'])): ?>
    <form method="get" class="row g-2 mb-3">
        <input type="hidden" name="tab" value="<?= $tab ?>">
        <div class="col-md-3"><input type="date" class="form-control" name="date_from" value="<?= $dateFrom ?>"></div>
        <div class="col-md-3"><input type="date" class="form-control" name="date_to" value="<?= $dateTo ?>"></div>
        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Saring</button></div>
    </form>
    <?php endif; ?>

    <div class="card"><div class="card-body">
        <?php if ($tab === 'daily'): ?>
            <h5>Penjualan Harian - <?= tglIndo($reportData['date'] ?? '') ?></h5>
            <div class="row mb-3">
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>Total Sales</small><h4><?= $reportData['total_sales'] ?? 0 ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>Revenue</small><h4><?= rupiah($reportData['total_revenue'] ?? 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>Tunai</small><h4><?= rupiah($reportData['total_cash'] ?? 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>Kredit</small><h4><?= rupiah($reportData['total_credit'] ?? 0) ?></h4></div></div></div>
            </div>
            <?php if (!empty($reportData['items'])): ?>
            <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Produk</th><th>Qty Sold</th><th>Revenue</th></tr></thead><tbody>
                <?php foreach ($reportData['items'] as $item): ?>
                <tr><td><?= htmlspecialchars($item['product_name']) ?></td><td><?= $item['quantity_sold'] ?></td><td><?= rupiah($item['revenue']) ?></td></tr>
                <?php endforeach; ?>
            </tbody></table></div>
            <?php endif; ?>

        <?php elseif ($tab === 'monthly'): ?>
            <h5>Penjualan Bulanan - <?= $reportData['year'] ?? '' ?>/<?= $reportData['month'] ?? '' ?></h5>
            <div class="row mb-3">
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>Total Sales</small><h4><?= $reportData['total_sales'] ?? 0 ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>Revenue</small><h4><?= rupiah($reportData['total_revenue'] ?? 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>Tunai</small><h4><?= rupiah($reportData['total_cash'] ?? 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>Kredit</small><h4><?= rupiah($reportData['total_credit'] ?? 0) ?></h4></div></div></div>
            </div>

        <?php elseif ($tab === 'byproduct'): ?>
            <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Produk</th><th>Qty Sold</th><th>Revenue</th><th>Profit</th></tr></thead><tbody>
            <?php foreach ($reportData as $r): ?>
            <tr><td><?= htmlspecialchars($r['product_name']) ?></td><td><?= $r['quantity_sold'] ?></td><td><?= rupiah($r['revenue']) ?></td><td><?= rupiah($r['profit']) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>

        <?php elseif ($tab === 'bycustomer'): ?>
            <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Pelanggan</th><th>Total Sales</th><th>Revenue</th><th>Paid</th><th>Unpaid</th></tr></thead><tbody>
            <?php foreach ($reportData as $r): ?>
            <tr><td><?= htmlspecialchars($r['customer_name']) ?></td><td><?= $r['total_sales'] ?></td><td><?= rupiah($r['total_revenue']) ?></td><td><?= rupiah($r['total_paid']) ?></td><td><?= rupiah($r['total_unpaid']) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>

        <?php elseif ($tab === 'profitloss'): ?>
            <div class="row mb-3">
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>Revenue</small><h4><?= rupiah($reportData['revenue'] ?? 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>COGS</small><h4><?= rupiah($reportData['cogs'] ?? 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-<?= ($reportData['gross_profit'] ?? 0) > 0 ? 'success' : 'danger' ?> text-white"><div class="card-body"><small>Gross Profit</small><h4><?= rupiah($reportData['gross_profit'] ?? 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-<?= ($reportData['net_profit'] ?? 0) > 0 ? 'success' : 'danger' ?> text-white"><div class="card-body"><small>Net Profit</small><h4><?= rupiah($reportData['net_profit'] ?? 0) ?></h4></div></div></div>
            </div>
            <p>Period: <?= $reportData['date_from'] ?? '' ?> to <?= $reportData['date_to'] ?? '' ?> | Total Sales: <?= $reportData['total_sales'] ?? 0 ?> | Tax: <?= rupiah($reportData['tax'] ?? 0) ?></p>

        <?php elseif ($tab === 'lowstock'): ?>
            <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Kode</th><th>Produk</th><th>Current</th><th>Min</th><th>Shortage</th></tr></thead><tbody>
            <?php foreach ($reportData as $r): ?>
            <tr><td><?= htmlspecialchars($r['product_code']) ?></td><td><?= htmlspecialchars($r['product_name']) ?></td><td><?= $r['current_stock'] ?></td><td><?= $r['min_stock'] ?></td><td class="text-danger"><?= $r['shortage'] ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>

        <?php elseif ($tab === 'stockmovement'): ?>
            <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Tanggal</th><th>Product ID</th><th>Qty</th><th>Type</th><th>Catatan</th></tr></thead><tbody>
            <?php foreach ($reportData as $r): ?>
            <tr><td><?= tglIndo($r['date']) ?></td><td><?= $r['product_id'] ?></td><td class="<?= $r['quantity'] > 0 ? 'text-success' : 'text-danger' ?>"><?= $r['quantity'] ?></td><td><?= $r['movement_type'] === 'in' ? 'Masuk' : ($r['movement_type'] === 'out' ? 'Keluar' : 'Penyesuaian') ?></td><td><?= htmlspecialchars($r['notes'] ?? '') ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>

        <?php elseif ($tab === 'deadstock'): ?>
            <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Kode</th><th>Produk</th><th>Stok</th><th>Stock Value</th><th>Days Inactive</th></tr></thead><tbody>
            <?php foreach ($reportData as $r): ?>
            <tr><td><?= htmlspecialchars($r['product_code']) ?></td><td><?= htmlspecialchars($r['product_name']) ?></td><td><?= $r['current_stock'] ?></td><td><?= rupiah($r['stock_value']) ?></td><td><?= $r['days_inactive'] ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>

        <?php elseif ($tab === 'stockvaluation'): ?>
            <div class="row mb-3">
                <div class="col-md-4"><div class="card bg-light"><div class="card-body"><small>Total Stock Value</small><h4><?= rupiah($reportData['total_stock_value'] ?? 0) ?></h4></div></div></div>
                <div class="col-md-4"><div class="card bg-light"><div class="card-body"><small>Products in Stock</small><h4><?= $reportData['total_products'] ?? 0 ?></h4></div></div></div>
            </div>
            <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Kode</th><th>Produk</th><th>Stock Qty</th><th>Avg Cost</th><th>Stock Value</th><th>Sell Price</th><th>Potential Revenue</th></tr></thead><tbody>
            <?php foreach (($reportData['items'] ?? []) as $r): ?>
            <tr><td><?= htmlspecialchars($r['product_code']) ?></td><td><?= htmlspecialchars($r['product_name']) ?></td><td><?= $r['current_stock'] ?></td><td><?= rupiah($r['avg_cost']) ?></td><td><?= rupiah($r['stock_value']) ?></td><td><?= rupiah($r['sell_price']) ?></td><td><?= rupiah($r['potential_revenue']) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>

        <?php elseif ($tab === 'araging'): ?>
            <div class="row mb-3">
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>0-30 days</small><h4><?= rupiah($reportData['0_30_days'] ?? 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>31-60 days</small><h4><?= rupiah($reportData['31_60_days'] ?? 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>61-90 days</small><h4><?= rupiah($reportData['61_90_days'] ?? 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-danger text-white"><div class="card-body"><small>Over 90 days</small><h4><?= rupiah($reportData['over_90_days'] ?? 0) ?></h4></div></div></div>
            </div>
            <p><strong>Total Outstanding: <?= rupiah($reportData['total_outstanding'] ?? 0) ?></strong></p>
            <?php if (!empty($reportData['details'])): ?>
            <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Pelanggan</th><th>Outstanding</th><th>Days Overdue</th></tr></thead><tbody>
            <?php foreach ($reportData['details'] as $d): ?>
            <tr><td><?= htmlspecialchars($d['customer_name']) ?></td><td><?= rupiah($d['outstanding']) ?></td><td class="<?= $d['days_overdue'] > 60 ? 'text-danger' : '' ?>"><?= $d['days_overdue'] ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
            <?php endif; ?>

        <?php elseif ($tab === 'apaging'): ?>
            <div class="row mb-3">
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>0-30 days</small><h4><?= rupiah($reportData['0_30_days'] ?? 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>31-60 days</small><h4><?= rupiah($reportData['31_60_days'] ?? 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>61-90 days</small><h4><?= rupiah($reportData['61_90_days'] ?? 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-danger text-white"><div class="card-body"><small>Over 90 days</small><h4><?= rupiah($reportData['over_90_days'] ?? 0) ?></h4></div></div></div>
            </div>
            <p><strong>Total Outstanding: <?= rupiah($reportData['total_outstanding'] ?? 0) ?></strong></p>
            <?php if (!empty($reportData['details'])): ?>
            <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Supplier</th><th>Outstanding</th><th>Days Overdue</th></tr></thead><tbody>
            <?php foreach ($reportData['details'] as $d): ?>
            <tr><td><?= htmlspecialchars($d['supplier_name']) ?></td><td><?= rupiah($d['outstanding']) ?></td><td class="<?= $d['days_overdue'] > 60 ? 'text-danger' : '' ?>"><?= $d['days_overdue'] ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
            <?php endif; ?>

        <?php endif; ?>
    </div></div>
</div>

<script>
function exportCSV() {
    const tables = document.querySelectorAll('.card-body table');
    if (!tables.length) { alert('No table to export'); return; }
    const table = tables[0];
    let csv = [];
    table.querySelectorAll('tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('th, td').forEach(cell => {
            let text = cell.textContent.trim().replace(/Rp\s/g, '').replace(/,/g, '');
            text = text.replace(/"/g, '""');
            row.push('"' + text + '"');
        });
        csv.push(row.join(','));
    });
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'report_<?= $tab ?>_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
}

function exportPDF() {
    window.print();
}
</script>
<style>
@media print {
    .navbar, .nav-tabs, .btn-group, .btn { display: none !important; }
    .card { border: none !important; }
    body { font-size: 12px; }
}
</style>
<?php renderFoot(); ?>
