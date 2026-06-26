<?php
require_once 'config.php';

$d = db();

$tab = $_GET['tab'] ?? 'trial_balance';
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$asOfDate = $_GET['as_of_date'] ?? date('Y-m-d');

$data = [];

if ($tab === 'trial_balance') {
    $accounts = $d->query("SELECT * FROM chart_of_accounts WHERE is_active = 1 ORDER BY code")->fetchAll();
    $totalDebit = 0; $totalCredit = 0;
    foreach ($accounts as &$a) {
        $a['debit'] = 0; $a['credit'] = 0;
    }
    $data = ['accounts' => $accounts, 'total_debit' => $totalDebit, 'total_credit' => $totalCredit, 'is_balanced' => true, 'date_from' => $dateFrom, 'date_to' => $dateTo];
} elseif ($tab === 'chart_of_accounts') {
    $data = $d->query("SELECT * FROM chart_of_accounts ORDER BY code")->fetchAll();
} elseif ($tab === 'balance_sheet') {
    $data = ['as_of_date' => $asOfDate, 'assets' => ['current' => [], 'fixed' => [], 'total' => 0], 'liabilities' => ['current' => [], 'total' => 0], 'equity' => ['items' => [], 'total' => 0], 'total_liabilities_equity' => 0];
} elseif ($tab === 'income_statement') {
    $data = ['date_from' => $dateFrom, 'date_to' => $dateTo, 'revenues' => [], 'total_revenue' => 0, 'expenses' => [], 'total_expense' => 0, 'net_income' => 0];
} elseif ($tab === 'general_ledger') {
    $data = [];
} elseif ($tab === 'journal_entries') {
    $data = ['data' => []];
}

renderHead('Accounting');
renderNav('accounting');
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Akuntansi/h1>
        <div class="btn-group">
            <button class="btn btn-outline-success btn-sm" onclick="exportCSV()"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</button>
            <button class="btn btn-outline-danger btn-sm" onclick="exportPDF()"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link <?= $tab==='trial_balance'?'active':'' ?>" href="?tab=trial_balance">Neraca Saldo</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='balance_sheet'?'active':'' ?>" href="?tab=balance_sheet">Neraca</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='income_statement'?'active':'' ?>" href="?tab=income_statement">Laba Rugi</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='general_ledger'?'active':'' ?>" href="?tab=general_ledger">Buku Besar</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='journal_entries'?'active':'' ?>" href="?tab=journal_entries">Journal Entries</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='chart_of_accounts'?'active':'' ?>" href="?tab=chart_of_accounts">Bagan Akun</a></li>
    </ul>

    <?php if (in_array($tab, ['trial_balance','income_statement','general_ledger','journal_entries'])): ?>
    <form method="get" class="row g-2 mb-3">
        <input type="hidden" name="tab" value="<?= $tab ?>">
        <div class="col-md-3"><input type="date" class="form-control" name="date_from" value="<?= $dateFrom ?>"></div>
        <div class="col-md-3"><input type="date" class="form-control" name="date_to" value="<?= $dateTo ?>"></div>
        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Saring</button></div>
    </form>
    <?php elseif ($tab === 'balance_sheet'): ?>
    <form method="get" class="row g-2 mb-3">
        <input type="hidden" name="tab" value="balance_sheet">
        <div class="col-md-3"><input type="date" class="form-control" name="as_of_date" value="<?= $asOfDate ?>"></div>
        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Saring</button></div>
    </form>
    <?php endif; ?>

    <div class="card"><div class="card-body">
        <?php if ($tab === 'trial_balance'): ?>
            <h5>Neraca Saldo (<?= tglIndo($data['date_from'] ?? '') ?> s/d <?= tglIndo($data['date_to'] ?? '') ?>)</h5>
            <table class="table table-sm"><thead><tr><th>Kode</th><th>Akun</th><th>Tipe</th><th class="text-end">Debit</th><th class="text-end">Kredit</th></tr></thead><tbody>
            <?php foreach (($data['accounts'] ?? []) as $a): ?>
            <tr><td><?= $a['code'] ?></td><td><?= htmlspecialchars($a['name']) ?></td><td><?= htmlspecialchars($a['type']) ?></td><td class="text-end"><?= rupiah($a['debit']) ?></td><td class="text-end"><?= rupiah($a['credit']) ?></td></tr>
            <?php endforeach; ?>
            </tbody><tfoot><tr class="table-dark"><td colspan="3"><strong>Total</strong></td><td class="text-end"><strong><?= rupiah($data['total_debit'] ?? 0) ?></strong></td><td class="text-end"><strong><?= rupiah($data['total_credit'] ?? 0) ?></strong></td></tr></tfoot></table>
            <?php if (($data['is_balanced'] ?? false)): ?><div class="alert alert-success">Seimbang</div><?php else: ?><div class="alert alert-warning">Tidak seimbang</div><?php endif; ?>

        <?php elseif ($tab === 'balance_sheet'): ?>
            <h5>Neraca per <?= tglIndo($data['as_of_date'] ?? '') ?></h5>
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary">ASET</h6>
                    <table class="table table-sm"><tbody>
                    <?php foreach (($data['assets']['current'] ?? []) as $a): ?>
                    <tr><td><?= $a['name'] ?></td><td class="text-end"><?= rupiah($a['balance']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php foreach (($data['assets']['fixed'] ?? []) as $a): ?>
                    <tr><td><?= $a['name'] ?></td><td class="text-end"><?= rupiah($a['balance']) ?></td></tr>
                    <?php endforeach; ?>
                    <tr class="table-primary"><td><strong>Total Aset</strong></td><td class="text-end"><strong><?= rupiah($data['assets']['total'] ?? 0) ?></strong></td></tr>
                    </tbody></table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-danger">LIABILITAS</h6>
                    <table class="table table-sm"><tbody>
                    <?php foreach (($data['liabilities']['current'] ?? []) as $l): ?>
                    <tr><td><?= $l['name'] ?></td><td class="text-end"><?= rupiah($l['balance']) ?></td></tr>
                    <?php endforeach; ?>
                    <tr class="table-danger"><td><strong>Total Liabilitas</strong></td><td class="text-end"><strong><?= rupiah($data['liabilities']['total'] ?? 0) ?></strong></td></tr>
                    </tbody></table>
                    <h6 class="text-success">EKUITAS</h6>
                    <table class="table table-sm"><tbody>
                    <?php foreach (($data['equity']['items'] ?? []) as $e): ?>
                    <tr><td><?= $e['name'] ?></td><td class="text-end"><?= rupiah($e['balance']) ?></td></tr>
                    <?php endforeach; ?>
                    <tr class="table-success"><td><strong>Total Ekuitas</strong></td><td class="text-end"><strong><?= rupiah($data['equity']['total'] ?? 0) ?></strong></td></tr>
                    <tr class="table-dark"><td><strong>Total Liabilitas+Ekuitas</strong></td><td class="text-end"><strong><?= rupiah($data['total_liabilities_equity'] ?? 0) ?></strong></td></tr>
                    </tbody></table>
                </div>
            </div>

        <?php elseif ($tab === 'income_statement'): ?>
            <h5>Laba Rugi (<?= tglIndo($data['date_from'] ?? '') ?> s/d <?= tglIndo($data['date_to'] ?? '') ?>)</h5>
            <h6 class="text-success">PENDAPATAN</h6>
            <table class="table table-sm"><tbody>
            <?php foreach (($data['revenues'] ?? []) as $r): ?>
            <tr><td><?= $r['name'] ?></td><td class="text-end"><?= rupiah($r['amount']) ?></td></tr>
            <?php endforeach; ?>
            <tr class="table-success"><td><strong>Total Pendapatan</strong></td><td class="text-end"><strong><?= rupiah($data['total_revenue'] ?? 0) ?></strong></td></tr>
            </tbody></table>
            <h6 class="text-danger">PENGELUARAN</h6>
            <table class="table table-sm"><tbody>
            <?php foreach (($data['expenses'] ?? []) as $e): ?>
            <tr><td><?= $e['name'] ?></td><td class="text-end"><?= rupiah($e['amount']) ?></td></tr>
            <?php endforeach; ?>
            <tr class="table-danger"><td><strong>Total Pengeluaran</strong></td><td class="text-end"><strong><?= rupiah($data['total_expense'] ?? 0) ?></strong></td></tr>
            </tbody></table>
            <div class="card bg-<?= ($data['net_income'] ?? 0) > 0 ? 'success' : 'danger' ?> text-white"><div class="card-body"><h5>Laba Bersih: <?= rupiah($data['net_income'] ?? 0) ?></h5></div></div>

        <?php elseif ($tab === 'general_ledger'): ?>
            <h5>Buku Besar</h5>
            <table class="table table-sm"><thead><tr><th>Tanggal</th><th>No Jurnal</th><th>Deskripsi</th><th>Akun</th><th class="text-end">Debit</th><th class="text-end">Kredit</th><th class="text-end">Saldo</th></tr></thead><tbody>
            <?php foreach (($data ?? []) as $l): ?>
            <tr><td><?= tglIndo($l['date']) ?></td><td><?= htmlspecialchars($l['journal_no']) ?></td><td><?= htmlspecialchars($l['description']) ?></td><td><?= htmlspecialchars($l['account_code']) ?> - <?= htmlspecialchars($l['account_name']) ?></td><td class="text-end"><?= rupiah($l['debit']) ?></td><td class="text-end"><?= rupiah($l['credit']) ?></td><td class="text-end"><?= rupiah($l['balance']) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>

        <?php elseif ($tab === 'journal_entries'): ?>
            <h5>Journal Entries</h5>
            <?php if (isset($data['data'])): foreach ($data['data'] as $je): ?>
            <div class="card mb-2"><div class="card-body">
                <h6><?= htmlspecialchars($je['journal_no']) ?> - <?= htmlspecialchars($je['description']) ?> <span class="badge bg-secondary"><?= htmlspecialchars($je['status']) ?></span></h6>
                <p class="text-muted small mb-1"><?= htmlspecialchars($je['entry_date']) ?> | By: <?= htmlspecialchars($je['creator']['name'] ?? 'N/A') ?></p>
                <table class="table table-sm mb-0"><thead><tr><th>Akun</th><th class="text-end">Debit</th><th class="text-end">Kredit</th></tr></thead><tbody>
                <?php foreach ($je['lines'] as $line): ?>
                <tr><td><?= htmlspecialchars($line['account']['code']) ?> - <?= htmlspecialchars($line['account']['name']) ?></td><td class="text-end"><?= rupiah($line['debit']) ?></td><td class="text-end"><?= rupiah($line['credit']) ?></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            </div></div>
            <?php endforeach; else: ?>
            <p class="text-muted">No journal entries found.</p>
            <?php endif; ?>

        <?php elseif ($tab === 'chart_of_accounts'): ?>
            <h5>Bagan Akun</h5>
            <table class="table table-sm"><thead><tr><th>Kode</th><th>Nama</th><th>Tipe</th><th>Subtype</th><th>Status</th></tr></thead><tbody>
            <?php foreach (($data ?? []) as $a): ?>
            <tr><td><?= $a['code'] ?></td><td><?= htmlspecialchars($a['name']) ?></td><td><span class="badge bg-info"><?= ucfirst($a['type']) ?></span></td><td><?= ucfirst(str_replace('_', ' ', $a['subtype'] ?? '')) ?></td><td><span class="badge bg-<?= $a['is_active']?'success':'danger' ?>"><?= $a['is_active']?'Active':'Inactive' ?></span></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
    </div></div>
</div>
<script>
function exportCSV(){const t=document.querySelector('.card-body table');if(!t){alert('No table');return;}let c=[];t.querySelectorAll('tr').forEach(r=>{let row=[];r.querySelectorAll('th,td').forEach(c2=>{let txt=c2.textContent.trim().replace(/Rp\s/g,'').replace(/,/g,'');row.push('"'+txt.replace(/"/g,'""')+'"')});c.push(row.join(','))});const b=new Blob([c.join('\n')],{type:'text/csv'});const a=document.createElement('a');a.href=URL.createObjectURL(b);a.download='accounting_<?= $tab ?>.csv';a.click()}
function exportPDF(){window.print()}
</script>
<style>@media print{.navbar,.nav-tabs,.btn-group,.btn{display:none!important}.card{border:none!important}body{font-size:12px}}</style>
<?php renderFoot(); ?>
