<?php
require_once 'config.php';

$d = db();
$user = currentUser();
$tenantId = $user['tenant_id'] ?? null;
$isSuperAdmin = $user['role_slug'] === 'super_admin';

$salesReturnSql = "SELECT sr.*, c.name as customer_name, s.invoice_no FROM sales_returns sr LEFT JOIN customers c ON sr.customer_id = c.id LEFT JOIN sales s ON sr.sale_id = s.id";
if (!$isSuperAdmin && $tenantId) {
    $salesReturnSql .= " WHERE sr.tenant_id = $tenantId";
}
$salesReturnSql .= " ORDER BY sr.id DESC LIMIT 20";
$salesReturns = $d->query($salesReturnSql)->fetchAll();

$purchaseReturnSql = "SELECT pr.*, s.name as supplier_name, po.po_number FROM purchase_returns pr LEFT JOIN suppliers s ON pr.supplier_id = s.id LEFT JOIN purchase_orders po ON pr.po_id = po.id";
if (!$isSuperAdmin && $tenantId) {
    $purchaseReturnSql .= " WHERE pr.tenant_id = $tenantId";
}
$purchaseReturnSql .= " ORDER BY pr.id DESC LIMIT 20";
$purchaseReturns = $d->query($purchaseReturnSql)->fetchAll();

$salesSql = "SELECT id, invoice_no FROM sales WHERE status != 'voided'";
if (!$isSuperAdmin && $tenantId) {
    $salesSql .= " AND tenant_id = $tenantId";
}
$salesSql .= " ORDER BY id DESC LIMIT 50";
$sales = $d->query($salesSql)->fetchAll();

$poSql = "SELECT id, po_number FROM purchase_orders";
if (!$isSuperAdmin && $tenantId) {
    $poSql .= " WHERE tenant_id = $tenantId";
}
$poSql .= " ORDER BY id DESC LIMIT 50";
$pos = $d->query($poSql)->fetchAll();

$productSql = "SELECT id, code, name FROM products WHERE is_active = 1";
if (!$isSuperAdmin && $tenantId) {
    $productSql .= " AND tenant_id = $tenantId";
}
$productSql .= " ORDER BY name LIMIT 200";
$products = $d->query($productSql)->fetchAll();

renderHead('Returns');
renderNav('returns');
?>
<div class="container mt-4">
    <h1>Retur</h1>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#salesReturns">Retur Penjualan</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#purchaseReturns">Retur Pembelian</a></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="salesReturns">
            <div class="d-flex justify-content-between mb-3">
                <h5>Retur Penjualan</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#srModal" onclick="resetSRForm()"><i class="bi bi-plus"></i> New Sales Return</button>
            </div>
            <div class="card"><div class="card-body">
                <div class="table-responsive"><table class="table table-striped">
                    <thead><tr><th>Return No</th><th>Invoice</th><th>Customer</th><th>Tanggal</th><th>Refund</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php foreach ($salesReturns as $sr): ?>
                        <tr>
                            <td><?= htmlspecialchars($sr['return_no']) ?></td>
                            <td><?= htmlspecialchars($sr['invoice_no'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($sr['customer_name'] ?? 'Walk-in') ?></td>
                            <td><?= htmlspecialchars($sr['return_date']) ?></td>
                            <td><?= rupiah($sr['total_refund']) ?></td>
                            <td><span class="badge bg-<?= $sr['status']==='approved'?'success':($sr['status']==='rejected'?'danger':'warning') ?>"><?= ucfirst($sr['status']) ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="viewSR(<?= $sr['id'] ?>)"><i class="bi bi-eye"></i></button>
                                <?php if ($sr['status'] === 'pending'): ?>
                                <button class="btn btn-sm btn-success" onclick="approveSR(<?= $sr['id'] ?>)"><i class="bi bi-check"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table></div>
            </div></div>
        </div>
        <div class="tab-pane fade" id="purchaseReturns">
            <div class="d-flex justify-content-between mb-3">
                <h5>Retur Pembelian</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#prModal" onclick="resetPRForm()"><i class="bi bi-plus"></i> New Purchase Return</button>
            </div>
            <div class="card"><div class="card-body">
                <div class="table-responsive"><table class="table table-striped">
                    <thead><tr><th>Return No</th><th>PO</th><th>Supplier</th><th>Tanggal</th><th>Refund</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php foreach ($purchaseReturns as $pr): ?>
                        <tr>
                            <td><?= htmlspecialchars($pr['return_no']) ?></td>
                            <td><?= htmlspecialchars($pr['po_number'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($pr['supplier_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($pr['return_date']) ?></td>
                            <td><?= rupiah($pr['total_refund']) ?></td>
                            <td><span class="badge bg-<?= $pr['status']==='approved'?'success':($pr['status']==='rejected'?'danger':'warning') ?>"><?= ucfirst($pr['status']) ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="viewPR(<?= $pr['id'] ?>)"><i class="bi bi-eye"></i></button>
                                <?php if ($pr['status'] === 'pending'): ?>
                                <button class="btn btn-sm btn-success" onclick="approvePR(<?= $pr['id'] ?>)"><i class="bi bi-check"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table></div>
            </div></div>
        </div>
    </div>
</div>

<div class="modal fade" id="srModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">New Sales Return</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Sale Invoice *</label><select class="form-select" id="srSale" required><option value="">Select Sale</option><?php foreach ($sales as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['invoice_no']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="form-label">Reason</label><input type="text" class="form-control" id="srReason"></div>
        <div class="mb-3"><label class="form-label">Metode Pengembalian</label><select class="form-select" id="srRefundMethod"><option value="cash">Cash</option><option value="credit">Credit</option><option value="transfer">Transfer</option></select></div>
        <div id="srItemsContainer"></div>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSRItem()"><i class="bi bi-plus"></i> Add Item</button>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" onclick="submitSR()">Create Return</button></div>
</div></div></div>

<div class="modal fade" id="prModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">New Purchase Return</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Purchase Order *</label><select class="form-select" id="prPO" required><option value="">Select PO</option><?php foreach ($pos as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['po_number']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="form-label">Reason</label><input type="text" class="form-control" id="prReason"></div>
        <div class="mb-3"><label class="form-label">Metode Pengembalian</label><select class="form-select" id="prRefundMethod"><option value="credit">Credit</option><option value="cash">Cash</option><option value="transfer">Transfer</option></select></div>
        <div id="prItemsContainer"></div>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPRItem()"><i class="bi bi-plus"></i> Add Item</button>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" onclick="submitPR()">Create Return</button></div>
</div></div></div>

<script>
const productsJson = <?= json_encode($products) ?>;

function resetSRForm() { document.getElementById('srItemsContainer').innerHTML = ''; addSRItem(); }
function resetPRForm() { document.getElementById('prItemsContainer').innerHTML = ''; addPRItem(); }

function addSRItem() {
    const opts = productsJson.map(p => `<option value="${p.id}">${p.code} - ${p.name}</option>`).join('');
    const div = document.createElement('div');
    div.className = 'row mb-2 sr-item';
    div.innerHTML = `<div class="col-md-6"><select class="form-select form-select-sm">${opts}</select></div>
        <div class="col-md-2"><input type="number" class="form-control form-control-sm" placeholder="Qty" step="0.001" min="0.001" value="1"></div>
        <div class="col-md-2"><input type="number" class="form-control form-control-sm" placeholder="Price" min="0" value="0"></div>
        <div class="col-md-2"><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.sr-item').remove()"><i class="bi bi-trash"></i></button></div>`;
    document.getElementById('srItemsContainer').appendChild(div);
}

function addPRItem() {
    const opts = productsJson.map(p => `<option value="${p.id}">${p.code} - ${p.name}</option>`).join('');
    const div = document.createElement('div');
    div.className = 'row mb-2 pr-item';
    div.innerHTML = `<div class="col-md-6"><select class="form-select form-select-sm">${opts}</select></div>
        <div class="col-md-2"><input type="number" class="form-control form-control-sm" placeholder="Qty" step="0.001" min="0.001" value="1"></div>
        <div class="col-md-2"><input type="number" class="form-control form-control-sm" placeholder="Price" min="0" value="0"></div>
        <div class="col-md-2"><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.pr-item').remove()"><i class="bi bi-trash"></i></button></div>`;
    document.getElementById('prItemsContainer').appendChild(div);
}

function submitSR() {
    const saleId = parseInt(document.getElementById('srSale').value);
    if (!saleId) { alert('Select a sale'); return; }
    const items = [];
    document.querySelectorAll('.sr-item').forEach(row => {
        items.push({ product_id: parseInt(row.querySelector('select').value), quantity: parseFloat(row.querySelectorAll('input')[0].value), unit_price: parseFloat(row.querySelectorAll('input')[1].value) });
    });
    fetch(API_URL+'?endpoint=sales-returns', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ sale_id: saleId, reason: document.getElementById('srReason').value, refund_method: document.getElementById('srRefundMethod').value, items }) })
    .then(r=>r.json()).then(res=>{ if(res.success){alert('Return created: '+res.data.return_no); location.reload();} else alert('Kesalahan: '+res.message); });
}

function submitPR() {
    const poId = parseInt(document.getElementById('prPO').value);
    if (!poId) { alert('Select a PO'); return; }
    const items = [];
    document.querySelectorAll('.pr-item').forEach(row => {
        items.push({ product_id: parseInt(row.querySelector('select').value), quantity: parseFloat(row.querySelectorAll('input')[0].value), unit_price: parseFloat(row.querySelectorAll('input')[1].value) });
    });
    fetch(API_URL+'?endpoint=purchase-returns', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ po_id: poId, reason: document.getElementById('prReason').value, refund_method: document.getElementById('prRefundMethod').value, items }) })
    .then(r=>r.json()).then(res=>{ if(res.success){alert('Return created: '+res.data.return_no); location.reload();} else alert('Kesalahan: '+res.message); });
}

function viewSR(id) { fetch(`${API_URL}?endpoint=sales-returns&id=${id}`).then(r=>r.json()).then(res=>{ if(res.success){ const r=res.data; alert(`${r.return_no}\nPenjualan: ${r.invoice_no||'-'}\nPelanggan: ${r.customer_name||'-'}\nRefund: Rp ${Number(r.total_refund).toLocaleString()}\nStatus: ${r.status}\nItem: ${r.items?.length||0}`); } }); }
function viewPR(id) { fetch(`${API_URL}?endpoint=purchase-returns&id=${id}`).then(r=>r.json()).then(res=>{ if(res.success){ const r=res.data; alert(`${r.return_no}\nPO: ${r.po_number||'-'}\nSupplier: ${r.supplier_name||'-'}\nRefund: Rp ${Number(r.total_refund).toLocaleString()}\nStatus: ${r.status}\nItem: ${r.items?.length||0}`); } }); }
function approveSR(id) { if(!confirm('Setujui?'))return; fetch(`${API_URL}?endpoint=sales-returns&id=${id}`,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({status:'approved'})}).then(r=>r.json()).then(res=>{if(res.success)location.reload();else alert(res.message);}); }
function approvePR(id) { if(!confirm('Setujui?'))return; fetch(`${API_URL}?endpoint=purchase-returns&id=${id}`,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({status:'approved'})}).then(r=>r.json()).then(res=>{if(res.success)location.reload();else alert(res.message);}); }
</script>
<?php renderFoot(); ?>
