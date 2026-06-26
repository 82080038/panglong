<?php
require_once __DIR__ . '/config.php';

// Pastikan user sudah login (requireLogin sudah dipanggil di config.php)
$user = currentUser();
if (!$user) {
    header('Location: login.php?msg=timeout');
    exit;
}

$d = db();
$tenantId = $user['tenant_id'] ?? 1;
$branchId = $user['branch_id'] ?? 1;

// Cek apakah periode bulan ini sudah di-lock
$periodLocked = false;
$nowYear = (int)date('Y');
$nowMonth = (int)date('n');
$stmt = $d->prepare("SELECT status FROM period_closings WHERE period_year = ? AND period_month = ? AND status = 'closed'");
$stmt->execute([$nowYear, $nowMonth]);
$periodLocked = (bool)$stmt->fetchColumn();

$productCount = $d->query('SELECT COUNT(*) FROM products')->fetchColumn();
$customerCount = $d->query('SELECT COUNT(*) FROM customers')->fetchColumn();
$lowStockCount = $d->query("SELECT COUNT(*) FROM products WHERE CAST(min_stock AS REAL) > 0 AND is_active = 1")->fetchColumn();

$todaySales = 0;
$todayRevenue = 0;
$stmt = $d->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM sales WHERE sale_date = date('now') AND status != 'voided'");
$row = $stmt->fetch();
$todaySales = $row['cnt'] ?? 0;
$todayRevenue = $row['rev'] ?? 0;

$monthlyRevenue = 0;
$monthlySales = 0;
$stmt = $d->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM sales WHERE sale_date >= date('now','start of month') AND status != 'voided'");
$row = $stmt->fetch();
$monthlySales = $row['cnt'] ?? 0;
$monthlyRevenue = $row['rev'] ?? 0;

$chartLabels = [];
$chartData = [];
$stmt = $d->query("SELECT sale_date, COALESCE(SUM(total),0) as rev FROM sales WHERE sale_date >= date('now','-6 days') AND status != 'voided' GROUP BY sale_date ORDER BY sale_date");
foreach ($stmt->fetchAll() as $row) {
    $chartLabels[] = date('d/m', strtotime($row['sale_date']));
    $chartData[] = (float)$row['rev'];
}
?>
<?php renderHead('Beranda'); ?>
<?php renderNav('dashboard'); ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1>Beranda</h1>
            <p class="text-muted mb-0">Selamat datang, <strong><?= htmlspecialchars($user['full_name']) ?></strong> — <?= tglIndo(date('Y-m-d'), true) ?></p>
        </div>
        <div class="text-end">
            <span class="badge bg-primary"><?= htmlspecialchars($user['role_name'] ?? 'User') ?></span>
            <?php if ($periodLocked): ?>
            <span class="badge bg-danger" data-bs-toggle="tooltip" data-bs-title="Periode bulan ini sudah ditutup. Transaksi tidak dapat diubah.">Periode Terkunci</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($periodLocked): ?>
    <div class="alert alert-warning alert-dismissible fade show">
        <i class="bi bi-lock-fill"></i> <strong>Periode <?= $nowYear ?>-<?= str_pad($nowMonth, 2, '0', STR_PAD_LEFT) ?> sedang terkunci.</strong> Transaksi pada periode ini tidak dapat ditambah atau diubah. 
        <a href="closing.php" class="alert-link">Buka kembali periode</a> jika diperlukan.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Produk</h5>
                    <p class="card-text fs-2"><?php echo $productCount; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Pelanggan</h5>
                    <p class="card-text fs-2"><?php echo $customerCount; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Penjualan Hari Ini</h5>
                    <p class="card-text fs-2"><?php echo $todaySales; ?></p>
                    <p class="card-text small"><?php echo rupiah($todayRevenue); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">Stok Menipis</h5>
                    <p class="card-text fs-2"><?php echo $lowStockCount; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h5>Penjualan 7 Hari Terakhir</h5></div>
                <div class="card-body">
                    <canvas id="salesChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h5>Ringkasan Bulan Ini</h5></div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><td>Total Penjualan Bulan Ini</td><td class="text-end fw-bold"><?php echo $monthlySales; ?></td></tr>
                        <tr><td>Total Omzet Bulan Ini</td><td class="text-end fw-bold"><?php echo rupiah($monthlyRevenue); ?></td></tr>
                        <tr><td>Produk Stok Menipis</td><td class="text-end fw-bold"><?php echo $lowStockCount; ?></td></tr>
                        <tr><td>Total Produk</td><td class="text-end fw-bold"><?php echo $productCount; ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header"><h5>Akses Cepat</h5></div>
                <div class="card-body">
                    <div class="list-group list-group-horizontal flex-wrap">
                        <a href="products.php" class="list-group-item list-group-item-action"><i class="bi bi-box"></i> Produk</a>
                        <a href="customers.php" class="list-group-item list-group-item-action"><i class="bi bi-people"></i> Pelanggan</a>
                        <a href="sales.php" class="list-group-item list-group-item-action"><i class="bi bi-cart"></i> Penjualan</a>
                        <a href="stock.php" class="list-group-item list-group-item-action"><i class="bi bi-box-seam"></i> Stok</a>
                        <a href="suppliers.php" class="list-group-item list-group-item-action"><i class="bi bi-truck"></i> Supplier</a>
                        <a href="purchase-orders.php" class="list-group-item list-group-item-action"><i class="bi bi-bag-check"></i> PO</a>
                        <a href="reports.php" class="list-group-item list-group-item-action"><i class="bi bi-graph-up"></i> Laporan</a>
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
            label: 'Omzet (Rp)',
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
