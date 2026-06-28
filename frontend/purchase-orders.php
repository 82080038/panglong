<?php
require_once 'config.php';
requirePermission('manage_purchase_orders');

$d = db();
$user = currentUser();
$tenantId = $user['tenant_id'] ?? null;
$branchId = $user['branch_id'] ?? null;
$isSuperAdmin = $user['role_slug'] === 'super_admin';

$supplierSql = "SELECT id, name FROM suppliers";
$supplierParams = [];
if (!$isSuperAdmin && $tenantId) {
    $supplierSql .= " WHERE tenant_id = ?";
    $supplierParams[] = $tenantId;
}
$supplierSql .= " ORDER BY name";
$supplierStmt = $d->prepare($supplierSql);
$supplierStmt->execute($supplierParams);
$suppliers = $supplierStmt->fetchAll();

$productSql = "SELECT id, code, name, buy_price FROM products WHERE is_active = 1";
$productParams = [];
if (!$isSuperAdmin && $tenantId) {
    $productSql .= " AND tenant_id = ?";
    $productParams[] = $tenantId;
}
$productSql .= " ORDER BY name LIMIT 100";
$productStmt = $d->prepare($productSql);
$productStmt->execute($productParams);
$products = $productStmt->fetchAll();

$poSql = "SELECT po.*, s.name as supplier_name FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.id";
$poParams = [];
if (!$isSuperAdmin && $tenantId) {
    $poSql .= " WHERE po.tenant_id = ?";
    $poParams[] = $tenantId;
    if ($branchId) {
        $poSql .= " AND po.branch_id = ?";
        $poParams[] = $branchId;
    }
}
$poSql .= " ORDER BY po.id DESC LIMIT 20";
$poStmt = $d->prepare($poSql);
$poStmt->execute($poParams);
$pos = $poStmt->fetchAll();
foreach ($pos as &$po) {
    $po['supplier'] = ['name' => $po['supplier_name'] ?? ''];
}

renderHead('Purchase Orders');
renderNav('purchase-orders');
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Pesanan Pembelian</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#poModal" onclick="resetPOForm()">
            <i class="bi bi-plus-circle"></i> PO Baru
        </button>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive"><table class="table table-striped">
                <thead><tr><th>Nomor PO</th><th>Tanggal</th><th>Supplier</th><th>Total</th><th>Payment</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($pos as $po): ?>
                    <tr>
                        <td><?= htmlspecialchars($po['po_number']) ?></td>
                        <td><?= tglIndo($po['po_date']) ?></td>
                        <td><?= htmlspecialchars($po['supplier']['name'] ?? '') ?></td>
                        <td><?= rupiah($po['total']) ?></td>
                        <td><span class="badge bg-<?= $po['payment_status']==='paid'?'success':($po['payment_status']==='partial'?'warning':'danger') ?>"><?= ucfirst($po['payment_status']) ?></span></td>
                        <td><span class="badge bg-<?= $po['status']==='received'?'success':($po['status']==='cancelled'?'danger':($po['status']==='partially_received'?'warning':'info')) ?>"><?= ucfirst(str_replace('_',' ',$po['status'])) ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="viewPO(<?= $po['id'] ?>)"><i class="bi bi-eye"></i></button>
                            <?php if ($po['status'] !== 'received' && $po['status'] !== 'cancelled'): ?>
                            <button class="btn btn-sm btn-success" onclick="receivePO(<?= $po['id'] ?>)"><i class="bi bi-box-arrow-in-down"></i></button>
                            <?php endif; ?>
                            <?php if ($po['payment_status'] !== 'paid'): ?>
                            <button class="btn btn-sm btn-warning" onclick="payPO(<?= $po['id'] ?>, <?= $po['total'] ?>)"><i class="bi bi-cash"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>

<!-- PO Modal -->
<div class="modal fade" id="poModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">New Purchase Order</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="poForm">
                    <div class="row mb-3">
                        <div class="col-md-6"><label class="form-label">Supplier *</label><select class="form-select" id="poSupplier" required><option value="">Select Supplier</option><?php foreach ($suppliers as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-3"><label class="form-label">Tanggal PO</label><input type="date" class="form-control" id="poDate" required></div>
                        <div class="col-md-3"><label class="form-label">Metode Bayar</label><select class="form-select" id="poPayment"><option value="credit">Kredit</option><option value="cash">Tunai</option><option value="transfer">Transfer</option></select></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Items</label>
                        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Produk</th><th>Qty</th><th>Harga Satuan</th><th>Subtotal</th><th></th></tr></thead><tbody id="poItemsBody"></tbody></table></div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPORow()"><i class="bi bi-plus"></i> Add Item</button>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4"><label class="form-label">Discount (Rp)</label><input type="number" class="form-control" id="poDiscount" value="0" min="0" oninput="calcPOTotal()"></div>
                        <div class="col-md-8"><label class="form-label">Catatan</label><input type="text" class="form-control" id="poNotes"></div>
                    </div>
                    <div class="row"><div class="col-md-4 offset-md-8"><div class="table-responsive"><table class="table table-sm"><tr class="fw-bold"><td>Grand Total</td><td class="text-end" id="poGrandTotal">Rp 0</td></tr></table></div></div></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" onclick="submitPO()">Create PO</button></div>
        </div>
    </div>
</div>

<!-- Receive Modal -->
<div class="modal fade" id="receiveModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Receive PO Items</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="receivePOId">
                <p class="text-muted">Enter received quantity for each item. Leave as 0 to skip.</p>
                <div id="receiveItemsBody"></div>
                <div class="mt-2"><button class="btn btn-sm btn-success" onclick="receiveAll()">Receive All</button></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" onclick="submitReceive()">Receive Items</button></div>
        </div>
    </div>
</div>

<!-- PO Payment Modal -->
<div class="modal fade" id="poPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Pay Purchase Order</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="poPayId">
                <div class="mb-3"><label class="form-label">Total Amount</label><input type="text" class="form-control" id="poPayTotal" readonly></div>
                <div class="mb-3"><label class="form-label">Payment Amount</label><input type="number" class="form-control" id="poPayAmount" required></div>
                <div class="mb-3"><label class="form-label">Metode</label><select class="form-select" id="poPayMethod"><option value="cash">Tunai</option><option value="transfer">Transfer</option><option value="check">Cek</option></select></div>
                <div class="mb-3"><label class="form-label">Tanggal</label><input type="date" class="form-control" id="poPayDate" required></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-success" onclick="submitPOPayment()">Pay</button></div>
        </div>
    </div>
</div>

<!-- PO Detail Modal -->
<div class="modal fade" id="poDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">PO Detail</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="poDetailBody"></div>
        </div>
    </div>
</div>

<script>
const productsJson = <?= json_encode($products) ?>;

function resetPOForm() {
    document.getElementById('poForm').reset();
    document.getElementById('poDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('poItemsBody').innerHTML = '';
    addPORow();
    calcPOTotal();
}

function addPORow() {
    const tbody = document.getElementById('poItemsBody');
    const row = document.createElement('tr');
    row.className = 'po-item-row';
    const opts = productsJson.map(p => `<option value="${p.id}">${p.code} - ${p.name}</option>`).join('');
    row.innerHTML = `<td><select class="form-select form-select-sm">${opts}</select></td>
        <td><input type="number" class="form-control form-control-sm" step="0.001" min="0.001" value="1" oninput="calcPORow(this); calcPOTotal()"></td>
        <td><input type="number" class="form-control form-control-sm" min="0" value="0" oninput="calcPORow(this); calcPOTotal()"></td>
        <td class="subtotal">Rp 0</td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove(); calcPOTotal()"><i class="bi bi-trash"></i></button></td>`;
    tbody.appendChild(row);
}

function calcPORow(input) {
    const row = input.closest('tr');
    const qty = parseFloat(row.querySelectorAll('input')[0].value) || 0;
    const price = parseFloat(row.querySelectorAll('input')[1].value) || 0;
    row.querySelector('.subtotal').textContent = 'Rp ' + Math.round(qty * price).toLocaleString('id-ID');
}

function calcPOTotal() {
    let total = 0;
    document.querySelectorAll('.po-item-row').forEach(row => {
        const qty = parseFloat(row.querySelectorAll('input')[0].value) || 0;
        const price = parseFloat(row.querySelectorAll('input')[1].value) || 0;
        total += qty * price;
    });
    const discount = parseFloat(document.getElementById('poDiscount').value) || 0;
    document.getElementById('poGrandTotal').textContent = 'Rp ' + Math.round(total - discount).toLocaleString('id-ID');
}

function submitPO() {
    const items = [];
    document.querySelectorAll('.po-item-row').forEach(row => {
        items.push({
            product_id: parseInt(row.querySelector('select').value),
            quantity: parseFloat(row.querySelectorAll('input')[0].value),
            unit_id: parseInt(row.querySelector('select').value),
            unit_price: parseFloat(row.querySelectorAll('input')[1].value),
        });
    });
    if (items.length === 0) { alert('Add at least 1 item'); return; }
    const data = {
        supplier_id: parseInt(document.getElementById('poSupplier').value),
        po_date: document.getElementById('poDate').value,
        items: items,
        discount: parseFloat(document.getElementById('poDiscount').value) || 0,
        payment_method: document.getElementById('poPayment').value,
        notes: document.getElementById('poNotes').value,
    };
    fetch(API_URL+'?endpoint=purchase-orders', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(r => r.json()).then(res => {
        if (res.success) { alert('PO created: ' + res.data.po_number); location.reload(); }
        else { alert('Kesalahan: ' + res.message); }
    });
}

function receivePO(id) {
    document.getElementById('receivePOId').value = id;
    fetch(`${API_URL}?endpoint=purchase-orders&id=${id}`)
    .then(r => r.json()).then(res => {
        if (res.success) {
            const po = res.data;
            let html = '';
            po.items.forEach(item => {
                const remaining = item.quantity - item.received_quantity;
                html += `<div class="row mb-2 align-items-center">
                    <div class="col-md-5">${item.product?.name || ''} (Ordered: ${item.quantity}, Received: ${item.received_quantity})</div>
                    <div class="col-md-3"><input type="number" class="form-control form-control-sm receive-qty" data-item-id="${item.id}" value="${remaining > 0 ? remaining : 0}" min="0" max="${remaining}" step="0.001"></div>
                    <div class="col-md-2 text-muted">Remaining: ${remaining}</div>
                </div>`;
            });
            document.getElementById('receiveItemsBody').innerHTML = html;
            new bootstrap.Modal(document.getElementById('receiveModal')).show();
        }
    });
}

function receiveAll() {
    document.querySelectorAll('.receive-qty').forEach(input => {
        input.value = input.max;
    });
}

function submitReceive() {
    const id = document.getElementById('receivePOId').value;
    const items = [];
    document.querySelectorAll('.receive-qty').forEach(input => {
        const qty = parseFloat(input.value) || 0;
        if (qty > 0) items.push({ purchase_item_id: parseInt(input.dataset.itemId), received_quantity: qty });
    });
    const body = items.length > 0 ? { items } : {};
    fetch(`${API_URL}?endpoint=purchase-orders&id=${id}&action=receive`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
    }).then(r => r.json()).then(res => {
        if (res.success) { alert(res.message || 'Received'); location.reload(); }
        else { alert('Kesalahan: ' + res.message); }
    });
}

function payPO(id, total) {
    document.getElementById('poPayId').value = id;
    document.getElementById('poPayTotal').value = 'Rp ' + Math.round(total).toLocaleString('id-ID');
    document.getElementById('poPayAmount').value = total;
    document.getElementById('poPayDate').value = new Date().toISOString().split('T')[0];
    new bootstrap.Modal(document.getElementById('poPaymentModal')).show();
}

function submitPOPayment() {
    const id = document.getElementById('poPayId').value;
    const data = {
        amount: parseFloat(document.getElementById('poPayAmount').value),
        payment_method: document.getElementById('poPayMethod').value,
        payment_date: document.getElementById('poPayDate').value,
    };
    fetch(`${API_URL}?endpoint=purchase-orders&id=${id}&action=payment`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(r => r.json()).then(res => {
        if (res.success) { alert('Payment recorded'); location.reload(); }
        else { alert('Kesalahan: ' + res.message); }
    });
}

function viewPO(id) {
    fetch(`${API_URL}?endpoint=purchase-orders&id=${id}`)
    .then(r => r.json()).then(res => {
        if (res.success) {
            const po = res.data;
            let html = `<h6>PO: ${po.po_number}</h6><p>Supplier: ${po.supplier?.name} | Tanggal: ${po.po_date} | Status: ${po.status==='pending'?'Pending':(po.status==='received'?'Diterima':(po.status==='partial'?'Sebagian':'Selesai'))}</p>`;
            html += '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Produk</th><th>Qty</th><th>Received</th><th>Harga</th><th>Subtotal</th></tr></thead><tbody>';
            po.items.forEach(i => {
                html += `<tr><td>${i.product?.name || ''}</td><td>${i.quantity}</td><td>${i.received_quantity}</td><td>Rp ${Number(i.unit_price).toLocaleString()}</td><td>Rp ${Number(i.subtotal).toLocaleString()}</td></tr>`;
            });
            html += '</tbody></table></div>';
            html += `<p>Subtotal: Rp ${Number(po.subtotal).toLocaleString()} | Total: Rp ${Number(po.total).toLocaleString()} | Pembayaran: ${po.payment_status}</p>`;
            document.getElementById('poDetailBody').innerHTML = html;
            new bootstrap.Modal(document.getElementById('poDetailModal')).show();
        }
    });
}

$(function() { resetPOForm(); });
</script>
<?php renderFoot(); ?>
