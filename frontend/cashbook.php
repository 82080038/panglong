<?php
require_once 'config.php';

$d = db();
$user = currentUser();
$tenantId = $user['tenant_id'] ?? null;
$isSuperAdmin = $user['role_slug'] === 'super_admin';

$transactionSql = "SELECT * FROM cash_transactions";
if (!$isSuperAdmin && $tenantId) {
    $transactionSql .= " WHERE tenant_id = $tenantId";
}
$transactionSql .= " ORDER BY id DESC LIMIT 50";
$transactions = $d->query($transactionSql)->fetchAll();

$bankStmtSql = "SELECT * FROM bank_statements";
if (!$isSuperAdmin && $tenantId) {
    $bankStmtSql .= " WHERE tenant_id = $tenantId";
}
$bankStmtSql .= " ORDER BY transaction_date DESC LIMIT 50";
$bankStatements = $d->query($bankStmtSql)->fetchAll();

$totalInSql = "SELECT COALESCE(SUM(amount),0) as total FROM cash_transactions WHERE type='in'";
if (!$isSuperAdmin && $tenantId) {
    $totalInSql .= " AND tenant_id = $tenantId";
}
$totalIn = $d->query($totalInSql)->fetchColumn();

$totalOutSql = "SELECT COALESCE(SUM(amount),0) as total FROM cash_transactions WHERE type='out'";
if (!$isSuperAdmin && $tenantId) {
    $totalOutSql .= " AND tenant_id = $tenantId";
}
$totalOut = $d->query($totalOutSql)->fetchColumn();
$balance = $totalIn - $totalOut;

renderHead('Cash Book');
renderNav('cashbook');
?>
<div class="container mt-4">
    <h1>Kas Buku</h1>
    <div class="row mb-4">
        <div class="col-md-4"><div class="card bg-success text-white"><div class="card-body"><h6>Total In</h6><h3><?= rupiah($totalIn) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card bg-danger text-white"><div class="card-body"><h6>Total Out</h6><h3><?= rupiah($totalOut) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card bg-primary text-white"><div class="card-body"><h6>Saldo</h6><h3><?= rupiah($balance) ?></h3></div></div></div>
    </div>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#cashTx">Transaksi Kas</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#bankStmt">Rekening Koran</a></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="cashTx">
            <button class="btn btn-primary btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#ctModal"><i class="bi bi-plus"></i> New Transaction</button>
            <div class="card"><div class="card-body">
                <div class="table-responsive"><table class="table table-striped">
                    <thead><tr><th>No</th><th>Tanggal</th><th>Type</th><th>Akun</th><th>Amount</th><th>Deskripsi</th><th>Category</th></tr></thead>
                    <tbody>
                        <?php foreach ($transactions as $ct): ?>
                        <tr>
                            <td><?= htmlspecialchars($ct['transaction_no'] ?? '') ?></td>
                            <td><?= tglJamIndo($ct['transaction_date']) ?></td>
                            <td><span class="badge bg-<?= $ct['type']==='in'?'success':'danger' ?>"><?= ucfirst($ct['type']) ?></span></td>
                            <td><?= ucfirst($ct['account_type'] ?? 'cash') ?></td>
                            <td><?= rupiah($ct['amount']) ?></td>
                            <td><?= htmlspecialchars($ct['description'] ?? '') ?></td>
                            <td><?= htmlspecialchars($ct['category'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table></div>
            </div></div>
        </div>
        <div class="tab-pane fade" id="bankStmt">
            <button class="btn btn-primary btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#bsModal"><i class="bi bi-plus"></i> Add Bank Statement</button>
            <div class="card"><div class="card-body">
                <div class="table-responsive"><table class="table table-striped">
                    <thead><tr><th>Tanggal</th><th>Akun</th><th>Deskripsi</th><th>Debit</th><th>Kredit</th><th>Saldo</th><th>Rekonsiliasi</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php foreach ($bankStatements as $bs): ?>
                        <tr>
                            <td><?= tglJamIndo($bs['transaction_date']) ?></td>
                            <td><?= htmlspecialchars($bs['bank_account']) ?></td>
                            <td><?= htmlspecialchars($bs['description'] ?? '') ?></td>
                            <td><?= rupiah($bs['debit']) ?></td>
                            <td><?= rupiah($bs['credit']) ?></td>
                            <td><?= rupiah($bs['balance']) ?></td>
                            <td><span class="badge bg-<?= $bs['reconciliation_status']==='reconciled'?'success':'warning' ?>"><?= $bs['reconciliation_status'] === 'reconciled' ? 'Terekonsiliasi' : 'Pending' ?></span></td>
                            <td><?php if ($bs['reconciliation_status'] !== 'reconciled'): ?><button class="btn btn-sm btn-success" onclick="reconcileBS(<?= $bs['id'] ?>)"><i class="bi bi-check"></i></button><?php endif; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table></div>
            </div></div>
        </div>
    </div>
</div>

<div class="modal fade" id="ctModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">New Cash Transaction</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Type</label><select class="form-select" id="ctType"><option value="in">Cash In</option><option value="out">Cash Out</option></select></div>
        <div class="mb-3"><label class="form-label">Jenis Akun</label><select class="form-select" id="ctAccount"><option value="cash">Tunai</option><option value="bank">Bank</option></select></div>
        <div class="mb-3"><label class="form-label">Amount</label><input type="number" class="form-control" id="ctAmount" min="0" required></div>
        <div class="mb-3"><label class="form-label">Deskripsi</label><input type="text" class="form-control" id="ctDesc"></div>
        <div class="mb-3"><label class="form-label">Category</label><input type="text" class="form-control" id="ctCategory"></div>
        <div class="mb-3"><label class="form-label">Recipient</label><input type="text" class="form-control" id="ctRecipient"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" onclick="submitCT()">Simpan</button></div>
</div></div></div>

<div class="modal fade" id="bsModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Add Bank Statement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Bank Account</label><input type="text" class="form-control" id="bsAccount" required></div>
        <div class="mb-3"><label class="form-label">Tanggal Transaksi</label><input type="date" class="form-control" id="bsDate" value="<?= date('Y-m-d') ?>"></div>
        <div class="mb-3"><label class="form-label">Deskripsi</label><input type="text" class="form-control" id="bsDesc"></div>
        <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">Debit</label><input type="number" class="form-control" id="bsDebit" value="0" min="0"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Kredit</label><input type="number" class="form-control" id="bsCredit" value="0" min="0"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Saldo</label><input type="number" class="form-control" id="bsBalance" value="0"></div>
        </div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" onclick="submitBS()">Simpan</button></div>
</div></div></div>

<script>
function submitCT() {
    fetch(API_URL+'?endpoint=cash-transactions', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ type: document.getElementById('ctType').value, account_type: document.getElementById('ctAccount').value, amount: parseFloat(document.getElementById('ctAmount').value), description: document.getElementById('ctDesc').value, category: document.getElementById('ctCategory').value, recipient: document.getElementById('ctRecipient').value }) })
    .then(r=>r.json()).then(res=>{ if(res.success) location.reload(); else alert(res.message); });
}
function submitBS() {
    fetch(API_URL+'?endpoint=bank-statements', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ bank_account: document.getElementById('bsAccount').value, transaction_date: document.getElementById('bsDate').value, description: document.getElementById('bsDesc').value, debit: parseFloat(document.getElementById('bsDebit').value), credit: parseFloat(document.getElementById('bsCredit').value), balance: parseFloat(document.getElementById('bsBalance').value) }) })
    .then(r=>r.json()).then(res=>{ if(res.success) location.reload(); else alert(res.message); });
}
function reconcileBS(id) { if(!confirm('Reconcile?'))return; fetch(`${API_URL}?endpoint=bank-statements&id=${id}`,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({})}).then(r=>r.json()).then(res=>{if(res.success)location.reload();}); }
</script>
<?php renderFoot(); ?>
