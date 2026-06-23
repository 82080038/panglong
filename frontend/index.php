<?php
require_once __DIR__ . '/config.php';

$stats = apiCall('/reports/sales/daily');
$products = apiCall('/products');
$customers = apiCall('/customers');
$lowStock = apiCall('/reports/inventory/low-stock');

$todaySales = $stats['body']['data']['total_sales'] ?? 0;
$todayRevenue = $stats['body']['data']['total_revenue'] ?? 0;
$productCount = $products['body']['meta']['total'] ?? count($products['body']['data'] ?? []);
$customerCount = $customers['body']['meta']['total'] ?? count($customers['body']['data'] ?? []);
$lowStockCount = count($lowStock['body']['data'] ?? []);

$monthlyStats = apiCall('/reports/sales/monthly');
$dailyBreakdown = $monthlyStats['body']['data']['daily_breakdown'] ?? [];
$monthlyRevenue = $monthlyStats['body']['data']['total_revenue'] ?? 0;
$monthlySales = $monthlyStats['body']['data']['total_sales'] ?? 0;

$chartLabels = [];
$chartData = [];
foreach (array_slice($dailyBreakdown, -7, 7, true) as $date => $data) {
    $chartLabels[] = date('d/m', strtotime($date));
    $chartData[] = $data['revenue'] ?? 0;
}
?>
<?php renderHead('Panglong ERP - Dashboard'); ?>
<?php renderNav('dashboard'); ?>

<div class="container mt-4">
    <h1>Dashboard</h1>
    
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Products</h5>
                    <p class="card-text fs-2"><?php echo $productCount; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Customers</h5>
                    <p class="card-text fs-2"><?php echo $customerCount; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Sales Today</h5>
                    <p class="card-text fs-2"><?php echo $todaySales; ?></p>
                    <p class="card-text small">Rp <?php echo number_format($todayRevenue, 0, ',', '.'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">Stock Low</h5>
                    <p class="card-text fs-2"><?php echo $lowStockCount; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h5>Weekly Sales Revenue</h5></div>
                <div class="card-body">
                    <canvas id="salesChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h5>Monthly Summary</h5></div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><td>Total Sales This Month</td><td class="text-end fw-bold"><?php echo $monthlySales; ?></td></tr>
                        <tr><td>Total Revenue This Month</td><td class="text-end fw-bold">Rp <?php echo number_format($monthlyRevenue, 0, ',', '.'); ?></td></tr>
                        <tr><td>Low Stock Products</td><td class="text-end fw-bold"><?php echo $lowStockCount; ?></td></tr>
                        <tr><td>Total Products</td><td class="text-end fw-bold"><?php echo $productCount; ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header"><h5>Quick Links</h5></div>
                <div class="card-body">
                    <div class="list-group list-group-horizontal flex-wrap">
                        <a href="products.php" class="list-group-item list-group-item-action"><i class="bi bi-box"></i> Products</a>
                        <a href="customers.php" class="list-group-item list-group-item-action"><i class="bi bi-people"></i> Customers</a>
                        <a href="sales.php" class="list-group-item list-group-item-action"><i class="bi bi-cart"></i> Sales</a>
                        <a href="stock.php" class="list-group-item list-group-item-action"><i class="bi bi-box-seam"></i> Stock</a>
                        <a href="suppliers.php" class="list-group-item list-group-item-action"><i class="bi bi-truck"></i> Suppliers</a>
                        <a href="purchase-orders.php" class="list-group-item list-group-item-action"><i class="bi bi-bag-check"></i> Purchase Orders</a>
                        <a href="reports.php" class="list-group-item list-group-item-action"><i class="bi bi-graph-up"></i> Reports</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
var ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chartLabels); ?>,
        datasets: [{
            label: 'Revenue (Rp)',
            data: <?php echo json_encode($chartData); ?>,
            backgroundColor: 'rgba(13, 110, 253, 0.5)',
            borderColor: 'rgba(13, 110, 253, 1)',
            borderWidth: 1
        }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});
</script>
<?php renderFoot(); ?>
