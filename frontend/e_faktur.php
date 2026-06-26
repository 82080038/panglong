<?php
require_once 'config.php';

$d = db();

$faktur = $d->query("SELECT * FROM e_faktur ORDER BY transaction_date DESC LIMIT 50")->fetchAll();
$totalKeluaran = $d->query("SELECT COALESCE(SUM(dpp),0) as dpp, COALESCE(SUM(ppn),0) as ppn FROM e_faktur WHERE faktur_type='keluaran'")->fetch();
$totalMasukan = $d->query("SELECT COALESCE(SUM(dpp),0) as dpp, COALESCE(SUM(ppn),0) as ppn FROM e_faktur WHERE faktur_type='masukan'")->fetch();

renderHead('e-Faktur');
renderNav('e-faktur');
?>
<div class="container mt-4">
    <h1>e-Faktur <span data-bs-toggle="tooltip" data-bs-title="e-Faktur: Faktur Pajak elektronik wajib dilaporkan ke DJP. PPN: Pajak Pertambahan Nilai.">(PPN)</span></h1>
    <div class="row mb-4">
        <div class="col-md-3"><div class="card bg-info text-white"><div class="card-body"><h6>PPN Keluaran <span data-bs-toggle="tooltip" data-bs-title="PPN Keluaran: Pajak Pertambahan Nilai yang dipungut saat penjualan. DPP: Dasar Pengenaan Pajak (harga sebelum PPN).">(DPP)</span></h6><h5><?= rupiah($totalKeluaran['dpp']) ?></h5></div></div></div>
        <div class="col-md-3"><div class="card bg-success text-white"><div class="card-body"><h6>PPN Keluaran</h6><h5><?= rupiah($totalKeluaran['ppn']) ?></h5></div></div></div>
        <div class="col-md-3"><div class="card bg-warning text-white"><div class="card-body"><h6>PPN Masukan <span data-bs-toggle="tooltip" data-bs-title="PPN Masukan: Pajak Pertambahan Nilai yang dibayar saat pembelian. DPP: Dasar Pengenaan Pajak (harga sebelum PPN).">(DPP)</span></h6><h5><?= rupiah($totalMasukan['dpp']) ?></h5></div></div></div>
        <div class="col-md-3"><div class="card bg-danger text-white"><div class="card-body"><h6>PPN Masukan</h6><h5><?= rupiah($totalMasukan['ppn']) ?></h5></div></div></div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6"><h5>Net PPN: <?= rupiah($totalKeluaran['ppn'] - $totalMasukan['ppn']) ?></h5></div>
        <div class="col-md-6 text-end">
            <a href="ajax.php?endpoint=e-faktur&export=csv" class="btn btn-success btn-sm"><i class="bi bi-download"></i> Ekspor CSV (Format DJP)</a>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#fakturModal"><i class="bi bi-plus"></i> Tambah Faktur</button>
        </div>
    </div>
    <div class="card"><div class="card-body">
        <table class="table table-striped">
            <thead><tr><th>Nomor Faktur</th><th>Type</th><th>Tanggal</th><th>Nama Pihak</th><th>NPWP</th><th>DPP</th><th>PPN</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php foreach ($faktur as $f): ?>
                <tr>
                    <td><?= htmlspecialchars($f['faktur_no']) ?></td>
                    <td><span class="badge bg-<?= $f['faktur_type']==='keluaran'?'success':'warning' ?>"><?= ucfirst($f['faktur_type']) ?></span></td>
                    <td><?= tglIndo($f['transaction_date']) ?></td>
                    <td><?= htmlspecialchars($f['counterparty_name']) ?></td>
                    <td><?= htmlspecialchars($f['counterparty_npwp'] ?? '-') ?></td>
                    <td><?= rupiah($f['dpp']) ?></td>
                    <td><?= rupiah($f['ppn']) ?></td>
                    <td><span class="badge bg-<?= $f['export_status']==='exported'?'success':'secondary' ?>"><?= $f['export_status'] === 'exported' ? 'Terekspor' : 'Pending' ?></span></td>
                    <td><?php if ($f['export_status'] !== 'exported'): ?><button class="btn btn-sm btn-success" onclick="markExported(<?= $f['id'] ?>)"><i class="bi bi-check"></i></button><?php endif; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
</div>

<div class="modal fade" id="fakturModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Add e-Faktur</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Type *</label><select class="form-select" id="fType"><option value="keluaran">Keluaran (Penjualan)</option><option value="masukan">Masukan (Pembelian)</option></select></div>
        <div class="mb-3"><label class="form-label">Transaction Date</label><input type="date" class="form-control" id="fDate" value="<?= date('Y-m-d') ?>"></div>
        <div class="mb-3"><label class="form-label">Nama Pihak Name *</label><input type="text" class="form-control" id="fName" required></div>
        <div class="mb-3"><label class="form-label">NPWP</label><input type="text" class="form-control" id="fNpwp" placeholder="01.234.567.8-901.000"></div>
        <div class="mb-3"><label class="form-label">DPP *</label><input type="number" class="form-control" id="fDpp" min="0" required><small class="text-muted">PPN 11% akan dihitung otomatis</small></div>
        <div class="mb-3"><label class="form-label">Description</label><input type="text" class="form-control" id="fDesc"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" onclick="submitFaktur()">Simpan</button></div>
</div></div></div>

<script>
function submitFaktur() {
    fetch(API_URL+'?endpoint=e-faktur', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ faktur_type: document.getElementById('fType').value, transaction_date: document.getElementById('fDate').value, counterparty_name: document.getElementById('fName').value, counterparty_npwp: document.getElementById('fNpwp').value, dpp: parseFloat(document.getElementById('fDpp').value), description: document.getElementById('fDesc').value }) })
    .then(r=>r.json()).then(res=>{ if(res.success) location.reload(); else alert(res.message); });
}
function markExported(id) { if(!confirm('Mark as exported?'))return; fetch(`${API_URL}?endpoint=e-faktur&id=${id}`,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({})}).then(r=>r.json()).then(res=>{if(res.success)location.reload();}); }
</script>
<?php renderFoot(); ?>
