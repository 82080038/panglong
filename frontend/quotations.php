<?php
require_once 'config.php';
requirePermission('manage_quotations');

$d = db();
$user = currentUser();
$tenantId = $user['tenant_id'] ?? null;
$isSuperAdmin = $user['role_slug'] === 'super_admin';

$customerSql = "SELECT id, name FROM customers";
$customerParams = [];
if (!$isSuperAdmin && $tenantId) {
    $customerSql .= " WHERE tenant_id = ?";
    $customerParams[] = $tenantId;
}
$customerSql .= " ORDER BY name LIMIT 200";
$customerStmt = $d->prepare($customerSql);
$customerStmt->execute($customerParams);
$customers = $customerStmt->fetchAll();

$productSql = "SELECT id, code, name, sell_price FROM products WHERE is_active = 1";
$productParams = [];
if (!$isSuperAdmin && $tenantId) {
    $productSql .= " AND tenant_id = ?";
    $productParams[] = $tenantId;
}
$productSql .= " ORDER BY name LIMIT 200";
$productStmt = $d->prepare($productSql);
$productStmt->execute($productParams);
$products = $productStmt->fetchAll();

$quoteSql = "SELECT q.*, c.name as customer_name FROM quotations q LEFT JOIN customers c ON q.customer_id = c.id";
$quoteParams = [];
if (!$isSuperAdmin && $tenantId) {
    $quoteSql .= " WHERE q.tenant_id = ?";
    $quoteParams[] = $tenantId;
}
$quoteSql .= " ORDER BY q.id DESC LIMIT 20";
$quoteStmt = $d->prepare($quoteSql);
$quoteStmt->execute($quoteParams);
$quotes = $quoteStmt->fetchAll();

renderHead('Quotations');
renderNav('quotations');
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Penawaran</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#quoteModal" onclick="resetQuoteForm()">
            <i class="bi bi-plus-circle"></i> Penawaran Baru
        </button>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive"><table class="table table-striped">
                <thead><tr><th>Quote No</th><th>Tanggal</th><th>Customer</th><th>Berlaku Sampai</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($quotes as $q): ?>
                    <tr>
                        <td><?= htmlspecialchars($q['quote_no']) ?></td>
                        <td><?= tglIndo($q['quote_date']) ?></td>
                        <td><?= htmlspecialchars($q['customer_name'] ?: $q['customer_name'] ?? 'Pelanggan Umum') ?></td>
                        <td><?= tglIndo($q['valid_until']) ?></td>
                        <td><?= rupiah($q['total']) ?></td>
                        <td><span class="badge bg-<?= $q['status']==='accepted'?'success':($q['status']==='sent'?'info':($q['status']==='expired'?'danger':'secondary')) ?>"><?= ucfirst($q['status']) ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="viewQuote(<?= $q['id'] ?>)"><i class="bi bi-eye"></i></button>
                            <?php if ($q['status'] === 'draft' || $q['status'] === 'sent'): ?>
                            <button class="btn btn-sm btn-success" onclick="acceptQuote(<?= $q['id'] ?>)"><i class="bi bi-check-circle"></i></button>
                            <?php endif; ?>
                            <a href="print_quote.php?id=<?= $q['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>

<div class="modal fade" id="quoteModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Penawaran Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="quoteForm">
                    <div class="row mb-3">
                        <div class="col-md-6"><label class="form-label">Customer *</label><select class="form-select" id="quoteCustomer" required><option value="">Select Customer</option><?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-3"><label class="form-label">Quote Date</label><input type="date" class="form-control" id="quoteDate" required></div>
                        <div class="col-md-3"><label class="form-label">Berlaku Sampai</label><input type="date" class="form-control" id="quoteValidUntil"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Items</label>
                        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th><th>Bonus</th><th>Unit Price</th><th>Diskon</th><th>Subtotal</th><th></th></tr></thead><tbody id="quoteItemsBody"></tbody></table></div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addQuoteRow()"><i class="bi bi-plus"></i> Add Item</button>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4"><label class="form-label">Discount (Rp)</label><input type="number" class="form-control" id="quoteDiscount" value="0" min="0" oninput="calcQuoteTotal()"></div>
                        <div class="col-md-8"><label class="form-label">Alamat Pengiriman</label><input type="text" class="form-control" id="quoteDeliveryAddress"></div>
                    </div>
                    <div class="row"><div class="col-md-4 offset-md-8"><div class="table-responsive"><table class="table table-sm"><tr class="fw-bold"><td>Grand Total</td><td class="text-end" id="quoteGrandTotal">Rp 0</td></tr></table></div></div></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" onclick="submitQuote()">Create Quotation</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="quoteDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Quotation Detail</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="quoteDetailBody"></div>
        </div>
    </div>
</div>

<script>
const productsJson = <?= json_encode($products) ?>;

function resetQuoteForm() {
    document.getElementById('quoteForm').reset();
    document.getElementById('quoteDate').value = new Date().toISOString().split('T')[0];
    const d = new Date(); d.setDate(d.getDate()+30);
    document.getElementById('quoteValidUntil').value = d.toISOString().split('T')[0];
    document.getElementById('quoteItemsBody').innerHTML = '';
    addQuoteRow();
    calcQuoteTotal();
}

function addQuoteRow() {
    const tbody = document.getElementById('quoteItemsBody');
    const row = document.createElement('tr');
    row.className = 'quote-item-row';
    const opts = productsJson.map(p => `<option value="${p.id}" data-price="${p.sell_price}">${p.code} - ${p.name}</option>`).join('');
    row.innerHTML = `<td><select class="form-select form-select-sm" onchange="onQuoteProductChange(this)">${opts}</select></td>
        <td><input type="number" class="form-control form-control-sm" step="0.001" min="0.001" value="1" oninput="calcQuoteRow(this); calcQuoteTotal()"></td>
        <td><input type="number" class="form-control form-control-sm" step="0.001" min="0" value="0"></td>
        <td><input type="number" class="form-control form-control-sm" min="0" value="0" oninput="calcQuoteRow(this); calcQuoteTotal()"></td>
        <td><input type="number" class="form-control form-control-sm" min="0" value="0" oninput="calcQuoteRow(this); calcQuoteTotal()"></td>
        <td class="subtotal">Rp 0</td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove(); calcQuoteTotal()"><i class="bi bi-trash"></i></button></td>`;
    tbody.appendChild(row);
    onQuoteProductChange(row.querySelector('select'));
}

function onQuoteProductChange(sel) {
    const opt = sel.selectedOptions[0];
    if (!opt) return;
    const row = sel.closest('tr');
    row.querySelectorAll('input')[2].value = opt.dataset.price || 0;
    calcQuoteRow(row);
    calcQuoteTotal();
}

function calcQuoteRow(input) {
    const row = input.closest ? input.closest('tr') : input;
    const qty = parseFloat(row.querySelectorAll('input')[0].value) || 0;
    const price = parseFloat(row.querySelectorAll('input')[2].value) || 0;
    const disc = parseFloat(row.querySelectorAll('input')[3].value) || 0;
    row.querySelector('.subtotal').textContent = 'Rp ' + Math.round((qty * price - disc)).toLocaleString('id-ID');
}

function calcQuoteTotal() {
    let total = 0;
    document.querySelectorAll('.quote-item-row').forEach(row => {
        const qty = parseFloat(row.querySelectorAll('input')[0].value) || 0;
        const price = parseFloat(row.querySelectorAll('input')[2].value) || 0;
        const disc = parseFloat(row.querySelectorAll('input')[3].value) || 0;
        total += qty * price - disc;
    });
    const disc = parseFloat(document.getElementById('quoteDiscount').value) || 0;
    const tax = (total - disc) * 0.11;
    document.getElementById('quoteGrandTotal').textContent = 'Rp ' + Math.round(total - disc + tax).toLocaleString('id-ID');
}

function submitQuote() {
    const custSel = document.getElementById('quoteCustomer');
    const items = [];
    document.querySelectorAll('.quote-item-row').forEach(row => {
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
        quote_date: document.getElementById('quoteDate').value,
        valid_until: document.getElementById('quoteValidUntil').value,
        items: items,
        discount: parseFloat(document.getElementById('quoteDiscount').value) || 0,
        delivery_address: document.getElementById('quoteDeliveryAddress').value,
    };
    fetch(API_URL+'?endpoint=quotations', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) })
    .then(r => r.json()).then(res => { if (res.success) { alert('Quotation created: ' + res.data.quote_no); location.reload(); } else alert('Kesalahan: ' + res.message); });
}

function viewQuote(id) {
    fetch(`${API_URL}?endpoint=quotations&id=${id}`).then(r => r.json()).then(res => {
        if (res.success) {
            const q = res.data;
            let html = `<h6>${q.quote_no}</h6><p>Pelanggan: ${q.customer_name || 'Pelanggan Umum'} | Tanggal: ${q.quote_date} | Berlaku: ${q.valid_until} | Status: ${q.status==='draft'?'Draft':(q.status==='accepted'?'Diterima':(q.status==='rejected'?'Ditolak':'Kedaluwarsa'))}</p>`;
            html += '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th><th>Bonus</th><th>Harga</th><th>Disc</th><th>Subtotal</th></tr></thead><tbody>';
            q.items.forEach(i => { html += `<tr><td>${i.product_name || i.product_code || ''}</td><td>${i.quantity}</td><td>${i.bonus_qty || 0}</td><td>Rp ${Number(i.unit_price).toLocaleString()}</td><td>Rp ${Number(i.discount).toLocaleString()}</td><td>Rp ${Number(i.subtotal).toLocaleString()}</td></tr>`; });
            html += '</tbody></table></div>';
            html += `<p>Subtotal: Rp ${Number(q.subtotal).toLocaleString()} | Tax: Rp ${Number(q.tax).toLocaleString()} | Total: Rp ${Number(q.total).toLocaleString()}</p>`;
            document.getElementById('quoteDetailBody').innerHTML = html;
            new bootstrap.Modal(document.getElementById('quoteDetailModal')).show();
        }
    });
}

function acceptQuote(id) {
    if (!confirm('Accept this quotation? It will create a Sales Order.')) return;
    fetch(`${API_URL}?endpoint=quotations&id=${id}`, { method: 'PUT', headers: {'Content-Type':'application/json'}, body: JSON.stringify({status:'accepted'}) })
    .then(r => r.json()).then(res => { if (res.success) { alert('Quotation accepted'); location.reload(); } else alert('Kesalahan: ' + res.message); });
}

$(function() { resetQuoteForm(); });
</script>
<?php renderFoot(); ?>
