<?php
require_once 'config.php';
requirePermission('view_reports');

$d = db();
$user = currentUser();
$tenantId = $user['tenant_id'] ?? null;
$isSuperAdmin = $user['role_slug'] === 'super_admin';

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$branchId = $user['branch_id'] ?? null;

function runCashFlowSum($d, $sql, $params, $whereAdded, $tenantId, $branchId, $isSuperAdmin) {
    if (!$isSuperAdmin && $tenantId) {
        $sql .= $whereAdded ? " AND tenant_id = ?" : " WHERE tenant_id = ?";
        $params[] = $tenantId;
        if ($branchId) {
            $sql .= " AND branch_id = ?";
            $params[] = $branchId;
        }
    }
    $stmt = $d->prepare($sql);
    $stmt->execute($params);
    return (float)$stmt->fetchColumn();
}

function runSimpleSum($d, $sql, $params, $whereAdded, $tenantId, $isSuperAdmin) {
    if (!$isSuperAdmin && $tenantId) {
        $sql .= $whereAdded ? " AND tenant_id = ?" : " WHERE tenant_id = ?";
        $params[] = $tenantId;
    }
    $stmt = $d->prepare($sql);
    $stmt->execute($params);
    return (float)$stmt->fetchColumn();
}

// Operating: cash transactions
$operatingIn = runCashFlowSum($d, "SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='in' AND account_type='cash' AND transaction_date BETWEEN ? AND ?", [$startDate, $endDate], true, $tenantId, $branchId, $isSuperAdmin);
$operatingOut = runCashFlowSum($d, "SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='out' AND account_type='cash' AND transaction_date BETWEEN ? AND ?", [$startDate, $endDate], true, $tenantId, $branchId, $isSuperAdmin);

$salesCash = runSimpleSum($d, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_date BETWEEN ? AND ?", [$startDate, $endDate], true, $tenantId, $isSuperAdmin);
$purchaseCash = runSimpleSum($d, "SELECT COALESCE(SUM(amount),0) FROM purchase_payments WHERE payment_date BETWEEN ? AND ?", [$startDate, $endDate], true, $tenantId, $isSuperAdmin);

// Investing
$assetPurchases = runCashFlowSum($d, "SELECT COALESCE(SUM(acquisition_cost),0) FROM fixed_assets WHERE acquisition_date BETWEEN ? AND ?", [$startDate, $endDate], true, $tenantId, $branchId, $isSuperAdmin);

// Financing
$financingIn = runCashFlowSum($d, "SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='in' AND category LIKE ? AND transaction_date BETWEEN ? AND ?", ['%loan%', $startDate, $endDate], true, $tenantId, $branchId, $isSuperAdmin);
$financingOut = runCashFlowSum($d, "SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='out' AND category LIKE ? AND transaction_date BETWEEN ? AND ?", ['%loan%', $startDate, $endDate], true, $tenantId, $branchId, $isSuperAdmin);

$operatingNet = $operatingIn + $salesCash - $operatingOut - $purchaseCash;
$investingNet = -$assetPurchases;
$financingNet = $financingIn - $financingOut;
$netChange = $operatingNet + $investingNet + $financingNet;

$beginningIn = runCashFlowSum($d, "SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='in' AND transaction_date < ?", [$startDate], true, $tenantId, $branchId, $isSuperAdmin);
$beginningOut = runCashFlowSum($d, "SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='out' AND transaction_date < ?", [$startDate], true, $tenantId, $branchId, $isSuperAdmin);
$beginningCash = $beginningIn - $beginningOut;
$endingCash = $beginningCash + $netChange;

renderHead('Laporan Arus Kas');
renderNav('cash_flow');
?>

<div class="container-fluid mt-3">
    <h4><i class="bi bi-cash-coin"></i> Laporan Arus Kas</h4>

    <form class="row g-2 mb-3" method="GET">
        <div class="col-auto">
            <label class="form-label small">Tanggal Mulai</label>
            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $startDate ?>">
        </div>
        <div class="col-auto">
            <label class="form-label small">Tanggal Akhir</label>
            <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $endDate ?>">
        </div>
        <div class="col-auto align-self-end">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Filter</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportCSV()"><i class="bi bi-download"></i> CSV</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
        </div>
    </form>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header bg-light"><strong>Aktivitas Operasi</strong></div>
                <div class="card-body">
                    <div class="table-responsive"><table class="table table-sm table-borderless">
                        <tr>
                            <td>Kas diterima dari pelanggan</td>
                            <td class="text-end text-success"><?= rupiah($operatingIn + $salesCash) ?></td>
                        </tr>
                        <tr>
                            <td>Kas dibayar ke supplier</td>
                            <td class="text-end text-danger">(<?= rupiah($operatingOut + $purchaseCash) ?>)</td>
                        </tr>
                        <tr class="table-light fw-bold">
                            <td>Kas Bersih dari Operasi</td>
                            <td class="text-end <?= $operatingNet >= 0 ? 'text-success' : 'text-danger' ?>"><?= rupiah($operatingNet) ?></td>
                        </tr>
                    </table></div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-light"><strong>Aktivitas Investasi</strong></div>
                <div class="card-body">
                    <div class="table-responsive"><table class="table table-sm table-borderless">
                        <tr>
                            <td>Pembelian aset tetap</td>
                            <td class="text-end text-danger">(<?= rupiah($assetPurchases) ?>)</td>
                        </tr>
                        <tr class="table-light fw-bold">
                            <td>Kas Bersih dari Investasi</td>
                            <td class="text-end <?= $investingNet >= 0 ? 'text-success' : 'text-danger' ?>"><?= rupiah($investingNet) ?></td>
                        </tr>
                    </table></div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-light"><strong>Aktivitas Pendanaan</strong></div>
                <div class="card-body">
                    <div class="table-responsive"><table class="table table-sm table-borderless">
                        <tr>
                            <td>Penerimaan pinjaman</td>
                            <td class="text-end text-success"><?= rupiah($financingIn) ?></td>
                        </tr>
                        <tr>
                            <td>Pembayaran pinjaman</td>
                            <td class="text-end text-danger">(<?= rupiah($financingOut) ?>)</td>
                        </tr>
                        <tr class="table-light fw-bold">
                            <td>Kas Bersih dari Pendanaan</td>
                            <td class="text-end <?= $financingNet >= 0 ? 'text-success' : 'text-danger' ?>"><?= rupiah($financingNet) ?></td>
                        </tr>
                    </table></div>
                </div>
            </div>

            <div class="card border-primary">
                <div class="card-header bg-primary text-white"><strong>Perubahan Kas Bersih</strong></div>
                <div class="card-body">
                    <div class="table-responsive"><table class="table table-sm table-borderless">
                        <tr>
                            <td>Saldo Kas Awal</td>
                            <td class="text-end"><?= rupiah($beginningCash) ?></td>
                        </tr>
                        <tr>
                            <td>Perubahan Kas Bersih</td>
                            <td class="text-end fw-bold <?= $netChange >= 0 ? 'text-success' : 'text-danger' ?>"><?= rupiah($netChange) ?></td>
                        </tr>
                        <tr class="table-primary fw-bold">
                            <td>Saldo Kas Akhir</td>
                            <td class="text-end"><?= rupiah($endingCash) ?></td>
                        </tr>
                    </table></div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><strong>Summary</strong></div>
                <div class="card-body">
                    <div class="mb-2">
                        <span class="text-muted small">Operating</span><br>
                        <span class="fs-5 fw-bold <?= $operatingNet >= 0 ? 'text-success' : 'text-danger' ?>"><?= rupiah($operatingNet) ?></span>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted small">Investing</span><br>
                        <span class="fs-5 fw-bold <?= $investingNet >= 0 ? 'text-success' : 'text-danger' ?>"><?= rupiah($investingNet) ?></span>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted small">Financing</span><br>
                        <span class="fs-5 fw-bold <?= $financingNet >= 0 ? 'text-success' : 'text-danger' ?>"><?= rupiah($financingNet) ?></span>
                    </div>
                    <hr>
                    <div>
                        <span class="text-muted small">Perubahan Kas Bersih</span><br>
                        <span class="fs-4 fw-bold <?= $netChange >= 0 ? 'text-success' : 'text-danger' ?>"><?= rupiah($netChange) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php renderFoot(); ?>
<script>
function exportCSV() {
    let csv = 'Section,Amount\n';
    csv += 'Kas Masuk Operasi,Rp <?= $operatingIn + $salesCash ?>\n';
    csv += 'Kas Keluar Operasi,Rp <?= $operatingOut + $purchaseCash ?>\n';
    csv += 'Kas Bersih Operasi,Rp <?= $operatingNet ?>\n';
    csv += 'Kas Bersih Investasi,Rp <?= $investingNet ?>\n';
    csv += 'Kas Masuk Pendanaan,Rp <?= $financingIn ?>\n';
    csv += 'Kas Keluar Pendanaan,Rp <?= $financingOut ?>\n';
    csv += 'Kas Bersih Pendanaan,Rp <?= $financingNet ?>\n';
    csv += 'Perubahan Kas Bersih,Rp <?= $netChange ?>\n';
    csv += 'Saldo Kas Awal,Rp <?= $beginningCash ?>\n';
    csv += 'Saldo Kas Akhir,Rp <?= $endingCash ?>\n';
    const blob = new Blob([csv], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'cash_flow_<?= $startDate ?>_<?= $endDate ?>.csv';
    a.click();
}
</script>
