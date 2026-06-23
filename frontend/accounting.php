<?php
require_once 'config.php';

$tab = $_GET['tab'] ?? 'trial_balance';
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$asOfDate = $_GET['as_of_date'] ?? date('Y-m-d');

$endpoints = [
    'trial_balance' => '/accounting/trial-balance?date_from=' . $dateFrom . '&date_to=' . $dateTo,
    'balance_sheet' => '/accounting/balance-sheet?as_of_date=' . $asOfDate,
    'income_statement' => '/accounting/income-statement?date_from=' . $dateFrom . '&date_to=' . $dateTo,
    'general_ledger' => '/accounting/general-ledger?date_from=' . $dateFrom . '&date_to=' . $dateTo,
    'journal_entries' => '/accounting/journal-entries?date_from=' . $dateFrom . '&date_to=' . $dateTo,
    'chart_of_accounts' => '/accounting/chart-of-accounts',
];

$resp = apiCall($endpoints[$tab] ?? $endpoints['trial_balance']);
$data = $resp['body']['data'] ?? [];

renderHead('Accounting');
renderNav('accounting');
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Accounting</h1>
        <div class="btn-group">
            <button class="btn btn-outline-success btn-sm" onclick="exportCSV()"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</button>
            <button class="btn btn-outline-danger btn-sm" onclick="exportPDF()"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link <?= $tab==='trial_balance'?'active':'' ?>" href="?tab=trial_balance">Trial Balance</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='balance_sheet'?'active':'' ?>" href="?tab=balance_sheet">Balance Sheet</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='income_statement'?'active':'' ?>" href="?tab=income_statement">Income Statement</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='general_ledger'?'active':'' ?>" href="?tab=general_ledger">General Ledger</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='journal_entries'?'active':'' ?>" href="?tab=journal_entries">Journal Entries</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='chart_of_accounts'?'active':'' ?>" href="?tab=chart_of_accounts">Chart of Accounts</a></li>
    </ul>

    <?php if (in_array($tab, ['trial_balance','income_statement','general_ledger','journal_entries'])): ?>
    <form method="get" class="row g-2 mb-3">
        <input type="hidden" name="tab" value="<?= $tab ?>">
        <div class="col-md-3"><input type="date" class="form-control" name="date_from" value="<?= $dateFrom ?>"></div>
        <div class="col-md-3"><input type="date" class="form-control" name="date_to" value="<?= $dateTo ?>"></div>
        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Filter</button></div>
    </form>
    <?php elseif ($tab === 'balance_sheet'): ?>
    <form method="get" class="row g-2 mb-3">
        <input type="hidden" name="tab" value="balance_sheet">
        <div class="col-md-3"><input type="date" class="form-control" name="as_of_date" value="<?= $asOfDate ?>"></div>
        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Filter</button></div>
    </form>
    <?php endif; ?>

    <div class="card"><div class="card-body">
        <?php if ($tab === 'trial_balance'): ?>
            <h5>Trial Balance (<?= $data['date_from'] ?? '' ?> to <?= $data['date_to'] ?? '' ?>)</h5>
            <table class="table table-sm"><thead><tr><th>Code</th><th>Account</th><th>Type</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead><tbody>
            <?php foreach (($data['accounts'] ?? []) as $a): ?>
            <tr><td><?= $a['code'] ?></td><td><?= htmlspecialchars($a['name']) ?></td><td><?= ucfirst($a['type']) ?></td><td class="text-end">Rp <?= number_format($a['debit'], 0) ?></td><td class="text-end">Rp <?= number_format($a['credit'], 0) ?></td></tr>
            <?php endforeach; ?>
            </tbody><tfoot><tr class="table-dark"><td colspan="3"><strong>Total</strong></td><td class="text-end"><strong>Rp <?= number_format($data['total_debit'] ?? 0, 0) ?></strong></td><td class="text-end"><strong>Rp <?= number_format($data['total_credit'] ?? 0, 0) ?></strong></td></tr></tfoot></table>
            <?php if (($data['is_balanced'] ?? false)): ?><div class="alert alert-success">Balanced</div><?php else: ?><div class="alert alert-warning">Not balanced</div><?php endif; ?>

        <?php elseif ($tab === 'balance_sheet'): ?>
            <h5>Balance Sheet as of <?= $data['as_of_date'] ?? '' ?></h5>
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary">ASSETS</h6>
                    <table class="table table-sm"><tbody>
                    <?php foreach (($data['assets']['current'] ?? []) as $a): ?>
                    <tr><td><?= $a['name'] ?></td><td class="text-end">Rp <?= number_format($a['balance'], 0) ?></td></tr>
                    <?php endforeach; ?>
                    <?php foreach (($data['assets']['fixed'] ?? []) as $a): ?>
                    <tr><td><?= $a['name'] ?></td><td class="text-end">Rp <?= number_format($a['balance'], 0) ?></td></tr>
                    <?php endforeach; ?>
                    <tr class="table-primary"><td><strong>Total Assets</strong></td><td class="text-end"><strong>Rp <?= number_format($data['assets']['total'] ?? 0, 0) ?></strong></td></tr>
                    </tbody></table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-danger">LIABILITIES</h6>
                    <table class="table table-sm"><tbody>
                    <?php foreach (($data['liabilities']['current'] ?? []) as $l): ?>
                    <tr><td><?= $l['name'] ?></td><td class="text-end">Rp <?= number_format($l['balance'], 0) ?></td></tr>
                    <?php endforeach; ?>
                    <tr class="table-danger"><td><strong>Total Liabilities</strong></td><td class="text-end"><strong>Rp <?= number_format($data['liabilities']['total'] ?? 0, 0) ?></strong></td></tr>
                    </tbody></table>
                    <h6 class="text-success">EQUITY</h6>
                    <table class="table table-sm"><tbody>
                    <?php foreach (($data['equity']['items'] ?? []) as $e): ?>
                    <tr><td><?= $e['name'] ?></td><td class="text-end">Rp <?= number_format($e['balance'], 0) ?></td></tr>
                    <?php endforeach; ?>
                    <tr class="table-success"><td><strong>Total Equity</strong></td><td class="text-end"><strong>Rp <?= number_format($data['equity']['total'] ?? 0, 0) ?></strong></td></tr>
                    <tr class="table-dark"><td><strong>Total L+E</strong></td><td class="text-end"><strong>Rp <?= number_format($data['total_liabilities_equity'] ?? 0, 0) ?></strong></td></tr>
                    </tbody></table>
                </div>
            </div>

        <?php elseif ($tab === 'income_statement'): ?>
            <h5>Income Statement (<?= $data['date_from'] ?? '' ?> to <?= $data['date_to'] ?? '' ?>)</h5>
            <h6 class="text-success">REVENUE</h6>
            <table class="table table-sm"><tbody>
            <?php foreach (($data['revenues'] ?? []) as $r): ?>
            <tr><td><?= $r['name'] ?></td><td class="text-end">Rp <?= number_format($r['amount'], 0) ?></td></tr>
            <?php endforeach; ?>
            <tr class="table-success"><td><strong>Total Revenue</strong></td><td class="text-end"><strong>Rp <?= number_format($data['total_revenue'] ?? 0, 0) ?></strong></td></tr>
            </tbody></table>
            <h6 class="text-danger">EXPENSES</h6>
            <table class="table table-sm"><tbody>
            <?php foreach (($data['expenses'] ?? []) as $e): ?>
            <tr><td><?= $e['name'] ?></td><td class="text-end">Rp <?= number_format($e['amount'], 0) ?></td></tr>
            <?php endforeach; ?>
            <tr class="table-danger"><td><strong>Total Expenses</strong></td><td class="text-end"><strong>Rp <?= number_format($data['total_expense'] ?? 0, 0) ?></strong></td></tr>
            </tbody></table>
            <div class="card bg-<?= ($data['net_income'] ?? 0) > 0 ? 'success' : 'danger' ?> text-white"><div class="card-body"><h5>Net Income: Rp <?= number_format($data['net_income'] ?? 0, 0) ?></h5></div></div>

        <?php elseif ($tab === 'general_ledger'): ?>
            <h5>General Ledger</h5>
            <table class="table table-sm"><thead><tr><th>Date</th><th>Journal No</th><th>Description</th><th>Account</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th></tr></thead><tbody>
            <?php foreach (($data ?? []) as $l): ?>
            <tr><td><?= htmlspecialchars($l['date']) ?></td><td><?= htmlspecialchars($l['journal_no']) ?></td><td><?= htmlspecialchars($l['description']) ?></td><td><?= htmlspecialchars($l['account_code']) ?> - <?= htmlspecialchars($l['account_name']) ?></td><td class="text-end">Rp <?= number_format($l['debit'], 0) ?></td><td class="text-end">Rp <?= number_format($l['credit'], 0) ?></td><td class="text-end">Rp <?= number_format($l['balance'], 0) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>

        <?php elseif ($tab === 'journal_entries'): ?>
            <h5>Journal Entries</h5>
            <?php if (isset($data['data'])): foreach ($data['data'] as $je): ?>
            <div class="card mb-2"><div class="card-body">
                <h6><?= htmlspecialchars($je['journal_no']) ?> - <?= htmlspecialchars($je['description']) ?> <span class="badge bg-secondary"><?= htmlspecialchars($je['status']) ?></span></h6>
                <p class="text-muted small mb-1"><?= htmlspecialchars($je['entry_date']) ?> | By: <?= htmlspecialchars($je['creator']['name'] ?? 'N/A') ?></p>
                <table class="table table-sm mb-0"><thead><tr><th>Account</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead><tbody>
                <?php foreach ($je['lines'] as $line): ?>
                <tr><td><?= htmlspecialchars($line['account']['code']) ?> - <?= htmlspecialchars($line['account']['name']) ?></td><td class="text-end">Rp <?= number_format($line['debit'], 0) ?></td><td class="text-end">Rp <?= number_format($line['credit'], 0) ?></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            </div></div>
            <?php endforeach; else: ?>
            <p class="text-muted">No journal entries found.</p>
            <?php endif; ?>

        <?php elseif ($tab === 'chart_of_accounts'): ?>
            <h5>Chart of Accounts</h5>
            <table class="table table-sm"><thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Subtype</th><th>Status</th></tr></thead><tbody>
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
