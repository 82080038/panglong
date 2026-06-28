<?php
require_once 'config.php';
requirePermission('manage_stock_transfers');

$d = db();
$user = currentUser();
$tenantId = $user['tenant_id'] ?? null;
$branchId = $user['branch_id'] ?? null;
$isSuperAdmin = $user['role_slug'] === 'super_admin';

$warehouseSql = "SELECT id, name FROM warehouses";
$warehouseParams = [];
if (!$isSuperAdmin && $tenantId) {
    $warehouseSql .= " WHERE tenant_id = ?";
    $warehouseParams[] = $tenantId;
    if ($branchId) {
        $warehouseSql .= " AND branch_id = ?";
        $warehouseParams[] = $branchId;
    }
}
$warehouseSql .= " ORDER BY name";
$warehouseStmt = $d->prepare($warehouseSql);
$warehouseStmt->execute($warehouseParams);
$warehouses = $warehouseStmt->fetchAll();

$productSql = "SELECT id, code, name FROM products WHERE is_active = 1";
$productParams = [];
if (!$isSuperAdmin && $tenantId) {
    $productSql .= " AND tenant_id = ?";
    $productParams[] = $tenantId;
}
$productSql .= " ORDER BY name LIMIT 200";
$productStmt = $d->prepare($productSql);
$productStmt->execute($productParams);
$products = $productStmt->fetchAll();

$transferSql = "SELECT st.*, wf.name as from_warehouse, wt.name as to_warehouse FROM stock_transfers st LEFT JOIN warehouses wf ON st.from_warehouse_id = wf.id LEFT JOIN warehouses wt ON st.to_warehouse_id = wt.id";
$transferParams = [];
if (!$isSuperAdmin && $tenantId) {
    $transferSql .= " WHERE st.tenant_id = ?";
    $transferParams[] = $tenantId;
}
$transferSql .= " ORDER BY st.id DESC LIMIT 20";
$transferStmt = $d->prepare($transferSql);
$transferStmt->execute($transferParams);
$transfers = $transferStmt->fetchAll();

renderHead('Stock Transfers');
renderNav('stock-transfers');
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Mutasi Stok</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#trModal" onclick="resetTRForm()"><i class="bi bi-plus-circle"></i> Mutasi Baru</button>
    </div>
    <div class="card"><div class="card-body">
        <div class="table-responsive"><table class="table table-striped">
            <thead><tr><th>Transfer No</th><th>Tanggal</th><th>From</th><th>To</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php foreach ($transfers as $tr): ?>
                <tr>
                    <td><?= htmlspecialchars($tr['transfer_no']) ?></td>
                    <td><?= htmlspecialchars($tr['transfer_date']) ?></td>
                    <td><?= htmlspecialchars($tr['from_warehouse'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($tr['to_warehouse'] ?? '-') ?></td>
                    <td><span class="badge bg-<?= $tr['status']==='completed'?'success':($tr['status']==='in_transit'?'info':'warning') ?>"><?= ucfirst(str_replace('_',' ',$tr['status'])) ?></span></td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="viewTR(<?= $tr['id'] ?>)"><i class="bi bi-eye"></i></button>
                        <?php if ($tr['status'] === 'pending'): ?>
                        <button class="btn btn-sm btn-success" onclick="completeTR(<?= $tr['id'] ?>)"><i class="bi bi-check"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
    </div></div>
</div>

<div class="modal fade" id="trModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">New Stock Transfer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row mb-3">
            <div class="col-md-4"><label class="form-label">Dari Gudang *</label><select class="form-select" id="trFrom" required><?php foreach ($warehouses as $w): ?><option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">Ke Gudang *</label><select class="form-select" id="trTo" required><?php foreach ($warehouses as $w): ?><option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">Transfer Date</label><input type="date" class="form-control" id="trDate" value="<?= date('Y-m-d') ?>"></div>
        </div>
        <div class="mb-3"><label class="form-label">Catatan</label><input type="text" class="form-control" id="trNotes"></div>
        <div id="trItemsContainer"></div>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addTRItem()"><i class="bi bi-plus"></i> Add Item</button>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" onclick="submitTR()">Create Transfer</button></div>
</div></div></div>

<script>
const productsJson = <?= json_encode($products) ?>;

function resetTRForm() { document.getElementById('trItemsContainer').innerHTML = ''; addTRItem(); }

function addTRItem() {
    const opts = productsJson.map(p => `<option value="${p.id}">${p.code} - ${p.name}</option>`).join('');
    const div = document.createElement('div');
    div.className = 'row mb-2 tr-item';
    div.innerHTML = `<div class="col-md-8"><select class="form-select form-select-sm">${opts}</select></div>
        <div class="col-md-3"><input type="number" class="form-control form-control-sm" placeholder="Qty" step="0.001" min="0.001" value="1"></div>
        <div class="col-md-1"><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.tr-item').remove()"><i class="bi bi-trash"></i></button></div>`;
    document.getElementById('trItemsContainer').appendChild(div);
}

function submitTR() {
    const fromId = parseInt(document.getElementById('trFrom').value);
    const toId = parseInt(document.getElementById('trTo').value);
    if (fromId === toId) { alert('From and To must be different'); return; }
    const items = [];
    document.querySelectorAll('.tr-item').forEach(row => { items.push({ product_id: parseInt(row.querySelector('select').value), quantity: parseFloat(row.querySelector('input').value) }); });
    fetch(API_URL+'?endpoint=stock-transfers', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ from_warehouse_id: fromId, to_warehouse_id: toId, transfer_date: document.getElementById('trDate').value, notes: document.getElementById('trNotes').value, items }) })
    .then(r=>r.json()).then(res=>{ if(res.success){alert('Transfer created: '+res.data.transfer_no); location.reload();} else alert('Kesalahan: '+res.message); });
}

function viewTR(id) { fetch(`${API_URL}?endpoint=stock-transfers&id=${id}`).then(r=>r.json()).then(res=>{ if(res.success){ const tr=res.data; let msg=`${tr.transfer_no}\nFrom: ${tr.from_warehouse}\nTo: ${tr.to_warehouse}\nStatus: ${tr.status}\nItems:\n`; tr.items?.forEach(i=>{ msg+=`  ${i.product_name||''} - ${i.quantity}\n`; }); alert(msg); } }); }
function completeTR(id) { if(!confirm('Complete transfer? Stock will be moved.'))return; fetch(`${API_URL}?endpoint=stock-transfers&id=${id}`,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({status:'completed'})}).then(r=>r.json()).then(res=>{if(res.success)location.reload();else alert(res.message);}); }
</script>
<?php renderFoot(); ?>
