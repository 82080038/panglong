<?php
require_once 'config.php';

$d = db();
$user = currentUser();
$tenantId = $user['tenant_id'] ?? null;
$isSuperAdmin = $user['role_slug'] === 'super_admin';

$assetSql = "SELECT * FROM fixed_assets";
if (!$isSuperAdmin && $tenantId) {
    $assetSql .= " WHERE tenant_id = $tenantId";
}
$assetSql .= " ORDER BY id DESC LIMIT 50";
$assets = $d->query($assetSql)->fetchAll();

$totalCostSql = "SELECT COALESCE(SUM(acquisition_cost),0) FROM fixed_assets WHERE status='active'";
if (!$isSuperAdmin && $tenantId) {
    $totalCostSql .= " AND tenant_id = $tenantId";
}
$totalCost = $d->query($totalCostSql)->fetchColumn();

$totalDepSql = "SELECT COALESCE(SUM(accumulated_depreciation),0) FROM fixed_assets WHERE status='active'";
if (!$isSuperAdmin && $tenantId) {
    $totalDepSql .= " AND tenant_id = $tenantId";
}
$totalDep = $d->query($totalDepSql)->fetchColumn();

$totalBookSql = "SELECT COALESCE(SUM(book_value),0) FROM fixed_assets WHERE status='active'";
if (!$isSuperAdmin && $tenantId) {
    $totalBookSql .= " AND tenant_id = $tenantId";
}
$totalBook = $d->query($totalBookSql)->fetchColumn();

renderHead('Fixed Assets');
renderNav('fixed-assets');
?>
<div class="container mt-4">
    <h1>Aset Tetap</h1>
    <div class="row mb-4">
        <div class="col-md-4"><div class="card bg-info text-white"><div class="card-body"><h6>Harga Perolehan</h6><h3><?= rupiah($totalCost) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card bg-warning text-white"><div class="card-body"><h6>Akumulasi Penyusutan</h6><h3><?= rupiah($totalDep) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card bg-primary text-white"><div class="card-body"><h6>Book Value</h6><h3><?= rupiah($totalBook) ?></h3></div></div></div>
    </div>
    <div class="d-flex justify-content-between mb-3">
        <h5>Asset List</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#faModal"><i class="bi bi-plus-circle"></i> New Asset</button>
    </div>
    <div class="card"><div class="card-body">
        <div class="table-responsive"><table class="table table-striped">
            <thead><tr><th>Kode</th><th>Nama</th><th>Category</th><th>Harga Perolehan</th><th>Monthly Dep.</th><th>Accum. Dep.</th><th>Book Value</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php foreach ($assets as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['asset_code']) ?></td>
                    <td><?= htmlspecialchars($a['name']) ?></td>
                    <td><?= htmlspecialchars($a['category'] ?? '-') ?></td>
                    <td><?= rupiah($a['acquisition_cost']) ?></td>
                    <td><?= rupiah($a['monthly_depreciation']) ?></td>
                    <td><?= rupiah($a['accumulated_depreciation']) ?></td>
                    <td><?= rupiah($a['book_value']) ?></td>
                    <td><span class="badge bg-<?= $a['status']==='active'?'success':'secondary' ?>"><?= $a['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?></span></td>
                    <td>
                        <?php if ($a['status'] === 'active'): ?>
                        <button class="btn btn-sm btn-warning" onclick="depreciate(<?= $a['id'] ?>)" title="Run Penyusutan Bulanan"><i class="bi bi-arrow-repeat"></i></button>
                        <button class="btn btn-sm btn-info" onclick="viewAsset(<?= $a['id'] ?>)"><i class="bi bi-eye"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
    </div></div>
</div>

<div class="modal fade" id="faModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">New Fixed Asset</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Name *</label><input type="text" class="form-control" id="faName" required></div>
        <div class="mb-3"><label class="form-label">Category</label><select class="form-select" id="faCategory"><option value="equipment">Equipment</option><option value="vehicle">Vehicle</option><option value="building">Building</option><option value="furniture">Furniture</option><option value="it">IT Equipment</option></select></div>
        <div class="mb-3"><label class="form-label">Serial No</label><input type="text" class="form-control" id="faSerial"></div>
        <div class="mb-3"><label class="form-label">Plate No</label><input type="text" class="form-control" id="faPlate"></div>
        <div class="mb-3"><label class="form-label">Acquisition Date</label><input type="date" class="form-control" id="faDate" value="<?= date('Y-m-d') ?>"></div>
        <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">Harga Perolehan</label><input type="number" class="form-control" id="faCost" min="0" required></div>
            <div class="col-md-4 mb-3"><label class="form-label">Nilai Residu</label><input type="number" class="form-control" id="faSalvage" value="0" min="0"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Life (months)</label><input type="number" class="form-control" id="faLife" value="60" min="1"></div>
        </div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" onclick="submitFA()">Simpan</button></div>
</div></div></div>

<script>
function submitFA() {
    fetch(API_URL+'?endpoint=fixed-assets', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ name: document.getElementById('faName').value, category: document.getElementById('faCategory').value, serial_no: document.getElementById('faSerial').value, plate_no: document.getElementById('faPlate').value, acquisition_date: document.getElementById('faDate').value, acquisition_cost: parseFloat(document.getElementById('faCost').value), salvage_value: parseFloat(document.getElementById('faSalvage').value), useful_life_months: parseInt(document.getElementById('faLife').value) }) })
    .then(r=>r.json()).then(res=>{ if(res.success) location.reload(); else alert(res.message); });
}
function depreciate(id) {
    if(!confirm('Run monthly depreciation?'))return;
    fetch(`${API_URL}?endpoint=fixed-assets&id=${id}`,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'depreciate'})})
    .then(r=>r.json()).then(res=>{ if(res.success){alert('Depreciation: Rp '+Number(res.data.depreciation).toLocaleString()+'\nNew Book Value: Rp '+Number(res.data.book_value).toLocaleString()); location.reload();} else alert(res.message); });
}
function viewAsset(id) { fetch(`${API_URL}?endpoint=fixed-assets&id=${id}`).then(r=>r.json()).then(res=>{ if(res.success){ const a=res.data; let msg=`${a.asset_code}\n${a.name}\nCost: Rp ${Number(a.acquisition_cost).toLocaleString()}\nDepreciation: Rp ${Number(a.accumulated_depreciation).toLocaleString()}\nBook Value: Rp ${Number(a.book_value).toLocaleString()}\nDepreciation History:\n`; a.depreciations?.forEach(d=>{ msg+=`  ${d.depreciation_date}: Rp ${Number(d.amount).toLocaleString()}\n`; }); alert(msg); } }); }
</script>
<?php renderFoot(); ?>
