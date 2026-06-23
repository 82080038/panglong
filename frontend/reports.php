<?php
require_once 'config.php';

$tab = $_GET['tab'] ?? 'daily';
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

$endpoints = [
    'daily' => '/reports/sales/daily',
    'monthly' => '/reports/sales/monthly',
    'byproduct' => '/reports/sales/by-product?date_from=' . $dateFrom . '&date_to=' . $dateTo,
    'bycustomer' => '/reports/sales/by-customer?date_from=' . $dateFrom . '&date_to=' . $dateTo,
    'lowstock' => '/reports/inventory/low-stock',
    'stockmovement' => '/reports/inventory/stock-movement?date_from=' . $dateFrom . '&date_to=' . $dateTo,
    'deadstock' => '/reports/inventory/dead-stock',
    'stockvaluation' => '/reports/inventory/stock-valuation',
    'araging' => '/reports/accounts/receivable/aging',
    'apaging' => '/reports/accounts/payable/aging',
    'profitloss' => '/reports/profit-loss?date_from=' . $dateFrom . '&date_to=' . $dateTo,
];

$resp = apiCall($endpoints[$tab] ?? $endpoints['daily']);
$reportData = $resp['body']['data'] ?? [];

renderHead('Reports');
renderNav('reports');
?>
<div class="container mt-4">
    <h1>Reports</h1>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link <?= $tab==='daily'?'active':'' ?>" href="?tab=daily">Daily Sales</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='monthly'?'active':'' ?>" href="?tab=monthly">Monthly Sales</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='byproduct'?'active':'' ?>" href="?tab=byproduct">Sales by Product</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='bycustomer'?'active':'' ?>" href="?tab=bycustomer">Sales by Customer</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='profitloss'?'active':'' ?>" href="?tab=profitloss">Profit/Loss</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='lowstock'?'active':'' ?>" href="?tab=lowstock">Low Stock</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='stockmovement'?'active':'' ?>" href="?tab=stockmovement">Stock Movement</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='deadstock'?'active':'' ?>" href="?tab=deadstock">Dead Stock</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='stockvaluation'?'active':'' ?>" href="?tab=stockvaluation">Stock Valuation</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='araging'?'active':'' ?>" href="?tab=araging">AR Aging</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='apaging'?'active':'' ?>" href="?tab=apaging">AP Aging</a></li>
    </ul>

    <?php if (in_array($tab, ['byproduct','bycustomer','stockmovement','profitloss'])): ?>
    <form method="get" class="row g-2 mb-3">
        <input type="hidden" name="tab" value="<?= $tab ?>">
        <div class="col-md-3"><input type="date" class="form-control" name="date_from" value="<?= $dateFrom ?>"></div>
        <div class="col-md-3"><input type="date" class="form-control" name="date_to" value="<?= $dateTo ?>"></div>
        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Filter</button></div>
    </form>
    <?php endif; ?>

    <div class="card"><div class="card-body">
        <?php if ($tab === 'daily'): ?>
            <h5>Daily Sales - <?= htmlspecialchars($reportData['date'] ?? '') ?></h5>
            <div class="row mb-3">
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>Total Sales</small><h4><?= $reportData['total_sales'] ?? 0 ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>Revenue</small><h4>Rp <?= number_format($reportData['total_revenue'] ?? 0, 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>Cash</small><h4>Rp <?= number_format($reportData['total_cash'] ?? 0, 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>Credit</small><h4>Rp <?= number_format($reportData['total_credit'] ?? 0, 0) ?></h4></div></div></div>
            </div>
            <?php if (!empty($reportData['items'])): ?>
            <table class="table table-sm"><thead><tr><th>Product</th><th>Qty Sold</th><th>Revenue</th></tr></thead><tbody>
                <?php foreach ($reportData['items'] as $item): ?>
                <tr><td><?= htmlspecialchars($item['product_name']) ?></td><td><?= $item['quantity_sold'] ?></td><td>Rp <?= number_format($item['revenue'], 0) ?></td></tr>
                <?php endforeach; ?>
            </tbody></table>
            <?php endif; ?>

        <?php elseif ($tab === 'monthly'): ?>
            <h5>Monthly Sales - <?= $reportData['year'] ?? '' ?>/<?= $reportData['month'] ?? '' ?></h5>
            <div class="row mb-3">
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>Total Sales</small><h4><?= $reportData['total_sales'] ?? 0 ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>Revenue</small><h4>Rp <?= number_format($reportData['total_revenue'] ?? 0, 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>Cash</small><h4>Rp <?= number_format($reportData['total_cash'] ?? 0, 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>Credit</small><h4>Rp <?= number_format($reportData['total_credit'] ?? 0, 0) ?></h4></div></div></div>
            </div>

        <?php elseif ($tab === 'byproduct'): ?>
            <table class="table table-sm"><thead><tr><th>Product</th><th>Qty Sold</th><th>Revenue</th><th>Profit</th></tr></thead><tbody>
            <?php foreach ($reportData as $r): ?>
            <tr><td><?= htmlspecialchars($r['product_name']) ?></td><td><?= $r['quantity_sold'] ?></td><td>Rp <?= number_format($r['revenue'], 0) ?></td><td>Rp <?= number_format($r['profit'], 0) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>

        <?php elseif ($tab === 'bycustomer'): ?>
            <table class="table table-sm"><thead><tr><th>Customer</th><th>Total Sales</th><th>Revenue</th><th>Paid</th><th>Unpaid</th></tr></thead><tbody>
            <?php foreach ($reportData as $r): ?>
            <tr><td><?= htmlspecialchars($r['customer_name']) ?></td><td><?= $r['total_sales'] ?></td><td>Rp <?= number_format($r['total_revenue'], 0) ?></td><td>Rp <?= number_format($r['total_paid'], 0) ?></td><td>Rp <?= number_format($r['total_unpaid'], 0) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>

        <?php elseif ($tab === 'profitloss'): ?>
            <div class="row mb-3">
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>Revenue</small><h4>Rp <?= number_format($reportData['revenue'] ?? 0, 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>COGS</small><h4>Rp <?= number_format($reportData['cogs'] ?? 0, 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-<?= ($reportData['gross_profit'] ?? 0) > 0 ? 'success' : 'danger' ?> text-white"><div class="card-body"><small>Gross Profit</small><h4>Rp <?= number_format($reportData['gross_profit'] ?? 0, 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-<?= ($reportData['net_profit'] ?? 0) > 0 ? 'success' : 'danger' ?> text-white"><div class="card-body"><small>Net Profit</small><h4>Rp <?= number_format($reportData['net_profit'] ?? 0, 0) ?></h4></div></div></div>
            </div>
            <p>Period: <?= $reportData['date_from'] ?? '' ?> to <?= $reportData['date_to'] ?? '' ?> | Total Sales: <?= $reportData['total_sales'] ?? 0 ?> | Tax: Rp <?= number_format($reportData['tax'] ?? 0, 0) ?></p>

        <?php elseif ($tab === 'lowstock'): ?>
            <table class="table table-sm"><thead><tr><th>Code</th><th>Product</th><th>Current</th><th>Min</th><th>Shortage</th></tr></thead><tbody>
            <?php foreach ($reportData as $r): ?>
            <tr><td><?= htmlspecialchars($r['product_code']) ?></td><td><?= htmlspecialchars($r['product_name']) ?></td><td><?= $r['current_stock'] ?></td><td><?= $r['min_stock'] ?></td><td class="text-danger"><?= $r['shortage'] ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>

        <?php elseif ($tab === 'stockmovement'): ?>
            <table class="table table-sm"><thead><tr><th>Date</th><th>Product</th><th>Qty</th><th>Type</th><th>Notes</th></tr></thead><tbody>
            <?php foreach ($reportData as $r): ?>
            <tr><td><?= $r['date'] ?></td><td><?= htmlspecialchars($r['product_name']) ?></td><td class="<?= $r['quantity'] > 0 ? 'text-success' : 'text-danger' ?>"><?= $r['quantity'] ?></td><td><?= $r['movement_type'] ?></td><td><?= htmlspecialchars($r['notes'] ?? '') ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>

        <?php elseif ($tab === 'deadstock'): ?>
            <table class="table table-sm"><thead><tr><th>Code</th><th>Product</th><th>Stock</th><th>Stock Value</th><th>Days Inactive</th></tr></thead><tbody>
            <?php foreach ($reportData as $r): ?>
            <tr><td><?= htmlspecialchars($r['product_code']) ?></td><td><?= htmlspecialchars($r['product_name']) ?></td><td><?= $r['current_stock'] ?></td><td>Rp <?= number_format($r['stock_value'], 0) ?></td><td><?= $r['days_inactive'] ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>

        <?php elseif ($tab === 'stockvaluation'): ?>
            <div class="row mb-3">
                <div class="col-md-4"><div class="card bg-light"><div class="card-body"><small>Total Stock Value</small><h4>Rp <?= number_format($reportData['total_stock_value'] ?? 0, 0) ?></h4></div></div></div>
                <div class="col-md-4"><div class="card bg-light"><div class="card-body"><small>Products in Stock</small><h4><?= $reportData['total_products'] ?? 0 ?></h4></div></div></div>
            </div>
            <table class="table table-sm"><thead><tr><th>Code</th><th>Product</th><th>Stock Qty</th><th>Avg Cost</th><th>Stock Value</th><th>Sell Price</th><th>Potential Revenue</th></tr></thead><tbody>
            <?php foreach (($reportData['items'] ?? []) as $r): ?>
            <tr><td><?= htmlspecialchars($r['product_code']) ?></td><td><?= htmlspecialchars($r['product_name']) ?></td><td><?= $r['current_stock'] ?></td><td>Rp <?= number_format($r['avg_cost'], 0) ?></td><td>Rp <?= number_format($r['stock_value'], 0) ?></td><td>Rp <?= number_format($r['sell_price'], 0) ?></td><td>Rp <?= number_format($r['potential_revenue'], 0) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>

        <?php elseif ($tab === 'araging'): ?>
            <div class="row mb-3">
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>0-30 days</small><h4>Rp <?= number_format($reportData['0_30_days'] ?? 0, 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>31-60 days</small><h4>Rp <?= number_format($reportData['31_60_days'] ?? 0, 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>61-90 days</small><h4>Rp <?= number_format($reportData['61_90_days'] ?? 0, 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-danger text-white"><div class="card-body"><small>Over 90 days</small><h4>Rp <?= number_format($reportData['over_90_days'] ?? 0, 0) ?></h4></div></div></div>
            </div>
            <p><strong>Total Outstanding: Rp <?= number_format($reportData['total_outstanding'] ?? 0, 0) ?></strong></p>
            <?php if (!empty($reportData['details'])): ?>
            <table class="table table-sm"><thead><tr><th>Customer</th><th>Outstanding</th><th>Days Overdue</th></tr></thead><tbody>
            <?php foreach ($reportData['details'] as $d): ?>
            <tr><td><?= htmlspecialchars($d['customer_name']) ?></td><td>Rp <?= number_format($d['outstanding'], 0) ?></td><td class="<?= $d['days_overdue'] > 60 ? 'text-danger' : '' ?>"><?= $d['days_overdue'] ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
            <?php endif; ?>

        <?php elseif ($tab === 'apaging'): ?>
            <div class="row mb-3">
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>0-30 days</small><h4>Rp <?= number_format($reportData['0_30_days'] ?? 0, 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>31-60 days</small><h4>Rp <?= number_format($reportData['31_60_days'] ?? 0, 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-light"><div class="card-body"><small>61-90 days</small><h4>Rp <?= number_format($reportData['61_90_days'] ?? 0, 0) ?></h4></div></div></div>
                <div class="col-md-3"><div class="card bg-danger text-white"><div class="card-body"><small>Over 90 days</small><h4>Rp <?= number_format($reportData['over_90_days'] ?? 0, 0) ?></h4></div></div></div>
            </div>
            <p><strong>Total Outstanding: Rp <?= number_format($reportData['total_outstanding'] ?? 0, 0) ?></strong></p>
            <?php if (!empty($reportData['details'])): ?>
            <table class="table table-sm"><thead><tr><th>Supplier</th><th>Outstanding</th><th>Days Overdue</th></tr></thead><tbody>
            <?php foreach ($reportData['details'] as $d): ?>
            <tr><td><?= htmlspecialchars($d['supplier_name']) ?></td><td>Rp <?= number_format($d['outstanding'], 0) ?></td><td class="<?= $d['days_overdue'] > 60 ? 'text-danger' : '' ?>"><?= $d['days_overdue'] ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
            <?php endif; ?>

        <?php endif; ?>
    </div></div>
</div>
<?php renderFoot(); ?>
