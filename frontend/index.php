<?php
require_once __DIR__ . '/config.php';

// Pastikan user sudah login dan memiliki akses dashboard
requireLogin();
requirePermission('view_dashboard');

$user = currentUser();
$d = db();

// Dashboard berbeda berdasarkan role
$isSuperAdmin = $user['role_slug'] === 'super_admin';

if ($isSuperAdmin) {
    // Super Admin Dashboard - Platform Level Metrics
    $tenantCount = $d->query('SELECT COUNT(*) FROM tenants')->fetchColumn();
    $activeTenants = $d->query("SELECT COUNT(*) FROM tenants WHERE status = 'active'")->fetchColumn();
    $trialTenants = $d->query("SELECT COUNT(*) FROM tenants WHERE status = 'trial'")->fetchColumn();
    $suspendedTenants = $d->query("SELECT COUNT(*) FROM tenants WHERE status = 'suspended'")->fetchColumn();
    
    $userCount = $d->query('SELECT COUNT(*) FROM users')->fetchColumn();
    
    // Tenant growth chart (last 7 days)
    $chartLabels = [];
    $chartData = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $stmt = $d->prepare("SELECT COUNT(*) FROM tenants WHERE DATE(created_at) = ?");
        $stmt->execute([$date]);
        $count = $stmt->fetchColumn();
        $chartLabels[] = date('d/m', strtotime($date));
        $chartData[] = (int)$count;
    }
} else {
    // Tenant Dashboard - Tenant Level Metrics
    $tenantId = $user['tenant_id'];
    $branchId = $user['branch_id'] ?? null;

    // Cek apakah periode bulan ini sudah di-lock
    $periodLocked = false;
    $nowYear = (int)date('Y');
    $nowMonth = (int)date('n');
    $stmt = $d->prepare("SELECT status FROM period_closings WHERE period_year = ? AND period_month = ? AND status = 'closed' AND tenant_id = ?");
    $stmt->execute([$nowYear, $nowMonth, $tenantId]);
    $periodLocked = (bool)$stmt->fetchColumn();

    $stmt = $d->prepare("SELECT COUNT(*) FROM products WHERE tenant_id = ?");
    $stmt->execute([$tenantId]);
    $productCount = $stmt->fetchColumn();
    $stmt = $d->prepare("SELECT COUNT(*) FROM customers WHERE tenant_id = ?");
    $stmt->execute([$tenantId]);
    $customerCount = $stmt->fetchColumn();
    $stmt = $d->prepare("SELECT COUNT(*) FROM products WHERE tenant_id = ? AND CAST(min_stock AS REAL) > 0 AND is_active = 1");
    $stmt->execute([$tenantId]);
    $lowStockCount = $stmt->fetchColumn();

    $todaySales = 0;
    $todayRevenue = 0;
    $todayParams = [$tenantId];
    $todaySql = "SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM sales WHERE tenant_id = ? AND sale_date = date('now') AND status != 'voided'";
    if ($branchId) {
        $todaySql .= " AND branch_id = ?";
        $todayParams[] = $branchId;
    }
    $stmt = $d->prepare($todaySql);
    $stmt->execute($todayParams);
    $row = $stmt->fetch();
    $todaySales = $row['cnt'] ?? 0;
    $todayRevenue = $row['rev'] ?? 0;

    $monthlyRevenue = 0;
    $monthlySales = 0;
    $monthlyParams = [$tenantId];
    $monthlySql = "SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM sales WHERE tenant_id = ? AND sale_date >= date('now','start of month') AND status != 'voided'";
    if ($branchId) {
        $monthlySql .= " AND branch_id = ?";
        $monthlyParams[] = $branchId;
    }
    $stmt = $d->prepare($monthlySql);
    $stmt->execute($monthlyParams);
    $row = $stmt->fetch();
    $monthlySales = $row['cnt'] ?? 0;
    $monthlyRevenue = $row['rev'] ?? 0;

    $chartLabels = [];
    $chartData = [];
    $chartParams = [$tenantId];
    $chartSql = "SELECT sale_date, COALESCE(SUM(total),0) as rev FROM sales WHERE tenant_id = ? AND sale_date >= date('now','-6 days') AND status != 'voided' GROUP BY sale_date ORDER BY sale_date";
    if ($branchId) {
        $chartSql .= " AND branch_id = ?";
        $chartParams[] = $branchId;
    }
    $stmt = $d->prepare($chartSql);
    $stmt->execute($chartParams);
    foreach ($stmt->fetchAll() as $row) {
        $chartLabels[] = date('d/m', strtotime($row['sale_date']));
        $chartData[] = (float)$row['rev'];
    }
}
?>
<?php renderHead('Beranda - Panglong ERP'); ?>
<?php renderNav('dashboard'); ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1>Beranda</h1>
                <p class="text-muted mb-0">Selamat datang, <strong><?= htmlspecialchars($user['full_name']) ?></strong> — <?= date('d F Y', strtotime(date('Y-m-d'))) ?></p>
            </div>
            <div class="text-end">
                <span class="badge bg-primary"><?= htmlspecialchars(ucfirst($user['role_slug'])) ?></span>
                <?php if (!$isSuperAdmin && $periodLocked): ?>
                <span class="badge bg-danger">Periode Terkunci</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$isSuperAdmin && $periodLocked): ?>
        <div class="alert alert-warning alert-dismissible fade show">
            <i class="bi bi-lock-fill"></i> <strong>Periode <?= $nowYear ?>-<?= str_pad($nowMonth, 2, '0', STR_PAD_LEFT) ?> sedang terkunci.</strong> Transaksi pada periode ini tidak dapat ditambah atau diubah. 
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($isSuperAdmin): ?>
        <!-- Super Admin Dashboard -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Total Tenants</h5>
                        <p class="card-text fs-2"><?= $tenantCount ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Active</h5>
                        <p class="card-text fs-2"><?= $activeTenants ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Trial</h5>
                        <p class="card-text fs-2"><?= $trialTenants ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-danger mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Suspended</h5>
                        <p class="card-text fs-2"><?= $suspendedTenants ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5>Pertumbuhan Tenant (7 Hari Terakhir)</h5></div>
                    <div class="card-body">
                        <canvas id="salesChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5>Ringkasan Platform</h5></div>
                    <div class="card-body">
                        <div class="table-responsive"><table class="table table-sm">
                            <tr><td>Total Tenants</td><td class="text-end fw-bold"><?= $tenantCount ?></td></tr>
                            <tr><td>Total Users</td><td class="text-end fw-bold"><?= $userCount ?></td></tr>
                            <tr><td>Active Tenants</td><td class="text-end fw-bold"><?= $activeTenants ?></td></tr>
                            <tr><td>Trial Tenants</td><td class="text-end fw-bold"><?= $trialTenants ?></td></tr>
                        </table></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header"><h5>Akses Cepat - Platform Owner</h5></div>
                    <div class="card-body">
                        <div class="list-group list-group-horizontal flex-wrap">
                            <a href="tenants.php" class="list-group-item list-group-item-action"><i class="bi bi-buildings"></i> Kelola Tenant</a>
                            <a href="users.php" class="list-group-item list-group-item-action"><i class="bi bi-people"></i> Kelola User</a>
                            <a href="register.php" class="list-group-item list-group-item-action"><i class="bi bi-person-plus"></i> Daftar Tenant Baru</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Tenant Dashboard -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Produk</h5>
                        <p class="card-text fs-2"><?= $productCount ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Pelanggan</h5>
                        <p class="card-text fs-2"><?= $customerCount ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Penjualan Hari Ini</h5>
                        <p class="card-text fs-2"><?= $todaySales ?></p>
                        <p class="card-text small">Rp <?= number_format($todayRevenue, 0, ',', '.') ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Stok Menipis</h5>
                        <p class="card-text fs-2"><?= $lowStockCount ?></p>
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
                        <div class="table-responsive"><table class="table table-sm">
                            <tr><td>Total Penjualan Bulan Ini</td><td class="text-end fw-bold"><?= $monthlySales ?></td></tr>
                            <tr><td>Total Omzet Bulan Ini</td><td class="text-end fw-bold">Rp <?= number_format($monthlyRevenue, 0, ',', '.') ?></td></tr>
                            <tr><td>Produk Stok Menipis</td><td class="text-end fw-bold"><?= $lowStockCount ?></td></tr>
                            <tr><td>Total Produk</td><td class="text-end fw-bold"><?= $productCount ?></td></tr>
                        </table></div>
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
        <?php endif; ?>
    </div>

<script src="assets/js/chart.umd.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
var ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: '<?= $isSuperAdmin ? 'Tenant Baru' : 'Omzet (Rp)' ?>',
            data: <?= json_encode($chartData) ?>,
            backgroundColor: 'rgba(13, 110, 253, 0.5)',
            borderColor: 'rgba(13, 110, 253, 1)',
            borderWidth: 1
        }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});
</script>
</body>
</html>
