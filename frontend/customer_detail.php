<?php
require_once 'config.php';

$id = $_GET['id'] ?? 0;
if (!$id) { header('Location: customers.php'); exit; }

$d = db();

$stmt = $d->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();
if (!$customer) { header('Location: customers.php?msg=notfound'); exit; }

$stmt = $d->prepare("SELECT * FROM sales WHERE customer_id = ? ORDER BY id DESC LIMIT 50");
$stmt->execute([$id]);
$sales = $stmt->fetchAll();

renderHead('Customer Detail - ' . $customer['name']);
renderNav('customers');
?>
<div class="container mt-4">
    <a href="customers.php" class="btn btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Back</a>
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h4 class="mb-0"><?= htmlspecialchars($customer['name']) ?></h4></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Phone:</strong> <?= htmlspecialchars($customer['phone'] ?? '-') ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($customer['email'] ?? '-') ?></p>
                            <p><strong>Address:</strong> <?= htmlspecialchars($customer['address'] ?? '-') ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Limit Kredit:</strong> <?= rupiah($customer['credit_limit'] ?? 0) ?></p>
                            <p><strong>Syarat Pembayaran:</strong> <?= $customer['payment_terms'] ?? 30 ?> hari</p>
                            <p><strong>Skor Kredit:</strong> <span class="badge bg-info"><?= $customer['credit_score'] ?? 'C' ?></span></p>
                            <p><strong>Status:</strong> <span class="badge bg-<?= $customer['is_active'] ? 'success' : 'danger' ?>"><?= $customer['is_active'] ? 'Active' : 'Inactive' ?></span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body text-center">
                    <h6>Piutang Outstanding</h6>
                    <?php
                    $outstanding = 0;
                    foreach ($sales as $s) {
                        if ($s['payment_status'] !== 'paid') {
                            $outstanding += $s['total'];
                        }
                    }
                    ?>
                    <h3 class="text-danger">Rp <?= number_format($outstanding, 0) ?></h3>
                    <hr>
                    <h6>Total Penjualan</h6>
                    <h4><?= count($sales) ?></h4>
                    <hr>
                    <h6>Total Omzet</h6>
                    <?php
                    $totalOmzet = 0;
                    foreach ($sales as $s) {
                        $totalOmzet += $s['total'];
                    }
                    ?>
                    <h4>Rp <?= number_format($totalOmzet, 0) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5>Purchase History</h5></div>
        <div class="card-body">
            <div class="table-responsive"><table class="table table-striped">
                <thead><tr><th>Invoice</th><th>Tanggal</th><th>Total</th><th>Payment</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php if (count($sales) > 0): ?>
                        <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td><?= htmlspecialchars($sale['invoice_no']) ?></td>
                            <td><?= htmlspecialchars($sale['sale_date']) ?></td>
                            <td><?= rupiah($sale['total']) ?></td>
                            <td><span class="badge bg-<?= $sale['payment_status']==='paid'?'success':($sale['payment_status']==='partial'?'warning':'danger') ?>"><?= ucfirst($sale['payment_status']) ?></span></td>
                            <td><span class="badge bg-<?= $sale['status']==='completed'?'success':($sale['status']==='voided'?'danger':'secondary') ?>"><?= ucfirst($sale['status']) ?></span></td>
                            <td><a href="print_nota.php?id=<?= $sale['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i></a></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted">No sales found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>
<?php renderFoot(); ?>
