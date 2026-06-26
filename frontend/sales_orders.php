<?php
require_once 'config.php';

$d = db();
$user = currentUser();
$tenantId = $user['tenant_id'] ?? null;
$isSuperAdmin = $user['role_slug'] === 'super_admin';

$customerSql = "SELECT id, name FROM customers";
if (!$isSuperAdmin && $tenantId) {
    $customerSql .= " WHERE tenant_id = $tenantId";
}
$customerSql .= " ORDER BY name LIMIT 200";
$customers = $d->query($customerSql)->fetchAll();

$productSql = "SELECT id, code, name, sell_price FROM products WHERE is_active = 1";
if (!$isSuperAdmin && $tenantId) {
    $productSql .= " AND tenant_id = $tenantId";
}
$productSql .= " ORDER BY name LIMIT 200";
$products = $d->query($productSql)->fetchAll();

$soSql = "SELECT so.*, c.name as customer_name FROM sales_orders so LEFT JOIN customers c ON so.customer_id = c.id";
if (!$isSuperAdmin && $tenantId) {
    $soSql .= " WHERE so.tenant_id = $tenantId";
}
$soSql .= " ORDER BY so.id DESC LIMIT 20";
$sos = $d->query($soSql)->fetchAll();

renderHead('Sales Orders');
renderNav('sales-orders');
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Pesanan Penjualan</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#soModal" onclick="resetSOForm()">
            <i class="bi bi-plus-circle"></i> Pesanan Baru
        </button>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive"><table class="table table-striped">
                <thead><tr><th>SO Number</th><th>Tanggal</th><th>Customer</th><th>Perkiraan Kirim</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($sos as $so): ?>
                    <tr>
                        <td><?= htmlspecialchars($so['so_number']) ?></td>
                        <td><?= tglIndo($so['order_date']) ?></td>
                        <td><?= htmlspecialchars($so['customer_name'] ?: $so['customer_name'] ?? 'Pelanggan Umum') ?></td>
                        <td><?= htmlspecialchars($so['expected_delivery_date'] ?? '-') ?></td>
                        <td><?= rupiah($so['total']) ?></td>
                        <td><span class="badge bg-<?= $so['status']==='fulfilled'?'success':($so['status']==='open'?'info':'secondary') ?>"><?= ucfirst($so['status']) ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="viewSO(<?= $so['id'] ?>)"><i class="bi bi-eye"></i></button>
                            <?php if ($so['status'] === 'open'): ?>
                            <button class="btn btn-sm btn-success" onclick="fulfillSO(<?= $so['id'] ?>)"><i class="bi bi-check-circle"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>

<div class="modal fade" id="soModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Pesanan Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="soForm">
                    <div class="row mb-3">
                        <div class="col-md-6"><label class="form-label">Customer *</label><select class="form-select" id="soCustomer" required><option value="">Select Customer</option><?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-3"><label class="form-label">Tanggal Pesanan</label><input type="date" class="form-control" id="soDate" required></div>
                        <div class="col-md-3"><label class="form-label">Perkiraan Kirim</label><input type="date" class="form-control" id="soExpectedDelivery"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Items</label>
                        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th><th>Bonus</th><th>Unit Price</th><th>Diskon</th><th>Subtotal</th><th></th></tr></thead><tbody id="soItemsBody"></tbody></table></div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSORow()"><i class="bi bi-plus"></i> Add Item</button>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4"><label class="form-label">Discount (Rp)</label><input type="number" class="form-control" id="soDiscount" value="0" min="0" oninput="calcSOTotal()"></div>
                        <div class="col-md-4"><label class="form-label">Payment Method</label><select class="form-select" id="soPayment"><option value="cash">Cash</option><option value="credit">Credit</option><option value="transfer">Transfer</option></select></div>
                        <div class="col-md-4"><label class="form-label">Delivery Address</label><input type="text" class="form-control" id="soDeliveryAddress"></div>
                    </div>
                    <div class="row"><div class="col-md-4 offset-md-8"><div class="table-responsive"><table class="table table-sm"><tr class="fw-bold"><td>Grand Total</td><td class="text-end" id="soGrandTotal">Rp 0</td></tr></table></div></div></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" onclick="submitSO()">Create SO</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="soDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">SO Detail</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="soDetailBody"></div>
        </div>
    </div>
</div>

<script>
const productsJson = <?= json_encode($products) ?>;

function resetSOForm() {
    document.getElementById('soForm').reset();
    document.getElementById('soDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('soItemsBody').innerHTML = '';
    addSORow();
    calcSOTotal();
}

function addSORow() {
    const tbody = document.getElementById('soItemsBody');
    const row = document.createElement('tr');
    row.className = 'so-item-row';
    const opts = productsJson.map(p => `<option value="${p.id}" data-price="${p.sell_price}">${p.code} - ${p.name}</option>`).join('');
    row.innerHTML = `<td><select class="form-select form-select-sm" onchange="onSOProductChange(this)">${opts}</select></td>
        <td><input type="number" class="form-control form-control-sm" step="0.001" min="0.001" value="1" oninput="calcSORow(this); calcSOTotal()"></td>
        <td><input type="number" class="form-control form-control-sm" step="0.001" min="0" value="0"></td>
        <td><input type="number" class="form-control form-control-sm" min="0" value="0" oninput="calcSORow(this); calcSOTotal()"></td>
        <td><input type="number" class="form-control form-control-sm" min="0" value="0" oninput="calcSORow(this); calcSOTotal()"></td>
        <td class="subtotal">Rp 0</td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove(); calcSOTotal()"><i class="bi bi-trash"></i></button></td>`;
    tbody.appendChild(row);
    onSOProductChange(row.querySelector('select'));
}

function onSOProductChange(sel) {
    const opt = sel.selectedOptions[0];
    const row = sel.closest('tr');
    row.querySelectorAll('input')[2].value = opt.dataset.price || 0;
    calcSORow(row);
    calcSOTotal();
}

function calcSORow(input) {
    const row = input.closest ? input.closest('tr') : input;
    const qty = parseFloat(row.querySelectorAll('input')[0].value) || 0;
    const price = parseFloat(row.querySelectorAll('input')[2].value) || 0;
    const disc = parseFloat(row.querySelectorAll('input')[3].value) || 0;
    row.querySelector('.subtotal').textContent = 'Rp ' + Math.round(qty * price - disc).toLocaleString('id-ID');
}

function calcSOTotal() {
    let total = 0;
    document.querySelectorAll('.so-item-row').forEach(row => {
        const qty = parseFloat(row.querySelectorAll('input')[0].value) || 0;
        const price = parseFloat(row.querySelectorAll('input')[2].value) || 0;
        const disc = parseFloat(row.querySelectorAll('input')[3].value) || 0;
        total += qty * price - disc;
    });
    const disc = parseFloat(document.getElementById('soDiscount').value) || 0;
    const tax = (total - disc) * 0.11;
    document.getElementById('soGrandTotal').textContent = 'Rp ' + Math.round(total - disc + tax).toLocaleString('id-ID');
}

function submitSO() {
    const custSel = document.getElementById('soCustomer');
    const items = [];
    document.querySelectorAll('.so-item-row').forEach(row => {
        items.push({
            product_id: parseInt(row.querySelector('select').value),
            quantity: parseFloat(row.querySelectorAll('input')[0].value),
            bonus_qty: parseFloat(row.querySelectorAll('input')[1].value) || 0,
            unit_price: parseFloat(row.querySelectorAll('input')[2].value),
            discount: parseFloat(row.querySelectorAll('input')[3].value) || 0,
        });
    });
    if (items.length === 0) { alert('Add at least 1 item'); return; }
    const data = {
        customer_id: parseInt(custSel.value),
        customer_name: custSel.selectedOptions[0]?.dataset.name || '',
        order_date: document.getElementById('soDate').value,
        expected_delivery_date: document.getElementById('soExpectedDelivery').value,
        items: items,
        discount: parseFloat(document.getElementById('soDiscount').value) || 0,
        payment_method: document.getElementById('soPayment').value,
        delivery_address: document.getElementById('soDeliveryAddress').value,
    };
    fetch(API_URL+'?endpoint=sales-orders', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) })
    .then(r => r.json()).then(res => { if (res.success) { alert('SO created: ' + res.data.so_number); location.reload(); } else alert('Kesalahan: ' + res.message); });
}

function viewSO(id) {
    fetch(`${API_URL}?endpoint=sales-orders&id=${id}`).then(r => r.json()).then(res => {
        if (res.success) {
            const so = res.data;
            let html = `<h6>${so.so_number}</h6><p>Customer: ${so.customer_name || 'Pelanggan Umum'} | Date: ${so.order_date} | Status: ${so.status}</p>`;
            html += '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th><th>Delivered</th><th>Harga</th><th>Subtotal</th></tr></thead><tbody>';
            so.items.forEach(i => { html += `<tr><td>${i.product_name || ''}</td><td>${i.quantity}</td><td>${i.delivered_qty || 0}</td><td>Rp ${Number(i.unit_price).toLocaleString()}</td><td>Rp ${Number(i.subtotal).toLocaleString()}</td></tr>`; });
            html += '</tbody></table></div>';
            html += `<p>Subtotal: Rp ${Number(so.subtotal).toLocaleString()} | Total: Rp ${Number(so.total).toLocaleString()}</p>`;
            document.getElementById('soDetailBody').innerHTML = html;
            new bootstrap.Modal(document.getElementById('soDetailModal')).show();
        }
    });
}

function fulfillSO(id) {
    if (!confirm('Mark this SO as fulfilled?')) return;
    fetch(`${API_URL}?endpoint=sales-orders&id=${id}`, { method: 'PUT', headers: {'Content-Type':'application/json'}, body: JSON.stringify({status:'fulfilled'}) })
    .then(r => r.json()).then(res => { if (res.success) { alert('SO fulfilled'); location.reload(); } else alert('Kesalahan: ' + res.message); });
}

$(function() { resetSOForm(); });
</script>
<?php renderFoot(); ?>
