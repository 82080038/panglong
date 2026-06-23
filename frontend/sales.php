<?php
require_once 'config.php';

$customersResp = apiCall('/customers');
$customers = $customersResp['body']['data'] ?? [];

$productsResp = apiCall('/products?per_page=100');
$products = $productsResp['body']['data'] ?? [];

$salesResp = apiCall('/sales?per_page=20');
$sales = $salesResp['body']['data'] ?? [];

renderHead('Sales');
renderNav('sales');
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Sales</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#saleModal" onclick="resetSaleForm()">
            <i class="bi bi-plus-circle"></i> New Sale
        </button>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8"><input type="text" class="form-control" id="saleSearch" placeholder="Search by invoice or customer..."></div>
                <div class="col-md-4"><button class="btn btn-outline-primary w-100" onclick="loadSales()"><i class="bi bi-search"></i> Search</button></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr><th>Invoice</th><th>Date</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody id="salesTableBody">
                    <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td><?= htmlspecialchars($sale['invoice_no']) ?></td>
                        <td><?= htmlspecialchars($sale['sale_date']) ?></td>
                        <td><?= htmlspecialchars($sale['customer_name_snapshot'] ?? ($sale['customer']['name'] ?? 'Walk-in')) ?></td>
                        <td>Rp <?= number_format($sale['total'], 0) ?></td>
                        <td><span class="badge bg-<?= $sale['payment_status']==='paid'?'success':($sale['payment_status']==='partial'?'warning':'danger') ?>"><?= ucfirst($sale['payment_status']) ?></span></td>
                        <td><span class="badge bg-<?= $sale['status']==='completed'?'success':($sale['status']==='voided'?'danger':'secondary') ?>"><?= ucfirst($sale['status']) ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="viewSale(<?= $sale['id'] ?>)"><i class="bi bi-eye"></i></button>
                            <?php if ($sale['status'] !== 'voided'): ?>
                            <button class="btn btn-sm btn-warning" onclick="recordPayment(<?= $sale['id'] ?>, <?= $sale['total'] ?>)"><i class="bi bi-cash"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="voidSale(<?= $sale['id'] ?>)"><i class="bi bi-x-circle"></i></button>
                            <button class="btn btn-sm btn-secondary" onclick="createDelivery(<?= $sale['id'] ?>, '<?= htmlspecialchars($sale['customer_name_snapshot'] ?? $sale['customer']['name'] ?? 'Walk-in', ENT_QUOTES) ?>')"><i class="bi bi-truck"></i></button>
                            <?php endif; ?>
                            <a href="print_nota.php?id=<?= $sale['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div id="salesPagination" class="d-flex justify-content-between align-items-center mt-3">
                <span id="salesPageInfo" class="text-muted"></span>
                <div>
                    <button class="btn btn-sm btn-outline-primary" id="prevPageBtn" onclick="changePage(-1)">Prev</button>
                    <button class="btn btn-sm btn-outline-primary" id="nextPageBtn" onclick="changePage(1)">Next</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Sale Modal -->
<div class="modal fade" id="saleModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Sale</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="saleForm">
                    <div class="row mb-2">
                        <div class="col-md-8">
                            <label class="form-label"><i class="bi bi-upc-scan"></i> Barcode Scanner</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="barcodeInput" placeholder="Scan or type barcode..." autocomplete="off">
                                <button type="button" class="btn btn-outline-primary" onclick="lookupBarcode()"><i class="bi bi-search"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Customer (optional)</label>
                            <select class="form-select" id="customerSelect" onchange="onCustomerChange()">
                                <option value="">Walk-in Customer</option>
                                <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>" data-group="<?= $c['group_id'] ?? '' ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sale Date</label>
                            <input type="date" class="form-control" id="saleDate" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" id="paymentMethod" required>
                                <option value="cash">Cash</option>
                                <option value="transfer">Transfer</option>
                                <option value="credit">Credit (Tempo)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Delivery Address (optional)</label>
                        <textarea class="form-control" id="deliveryAddress" rows="1"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Items</label>
                        <table class="table table-sm" id="itemsTable">
                            <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Discount</th><th>Subtotal</th><th></th></tr></thead>
                            <tbody id="itemsBody"></tbody>
                        </table>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn" onclick="addItemRow()"><i class="bi bi-plus"></i> Add Item</button>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Global Discount (Rp)</label>
                            <input type="number" class="form-control" id="globalDiscount" value="0" min="0" oninput="calcTotal()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Notes</label>
                            <input type="text" class="form-control" id="saleNotes">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 offset-md-8">
                            <table class="table table-sm">
                                <tr><td>Subtotal</td><td class="text-end" id="subtotalDisplay">Rp 0</td></tr>
                                <tr><td>Discount</td><td class="text-end" id="discountDisplay">Rp 0</td></tr>
                                <tr><td>Tax (PPN)</td><td class="text-end" id="taxDisplay">Rp 0</td></tr>
                                <tr class="fw-bold"><td>Grand Total</td><td class="text-end" id="grandTotalDisplay">Rp 0</td></tr>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitSale()">Create Sale</button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Record Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="paymentForm">
                    <input type="hidden" id="paymentSaleId">
                    <div class="mb-3"><label class="form-label">Outstanding Amount</label><input type="text" class="form-control" id="outstandingAmt" readonly></div>
                    <div class="mb-3"><label class="form-label">Payment Amount</label><input type="number" class="form-control" id="paymentAmount" required></div>
                    <div class="mb-3"><label class="form-label">Method</label><select class="form-select" id="paymentMethodType"><option value="cash">Cash</option><option value="transfer">Transfer</option><option value="check">Check</option></select></div>
                    <div class="mb-3"><label class="form-label">Date</label><input type="date" class="form-control" id="paymentDate" required></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-success" onclick="submitPayment()">Record</button></div>
        </div>
    </div>
</div>

<!-- Void Modal -->
<div class="modal fade" id="voidModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Void Sale</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="voidSaleId">
                <div class="mb-3"><label class="form-label">Reason</label><textarea class="form-control" id="voidReason" rows="3" required></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-danger" onclick="submitVoid()">Void Sale</button></div>
        </div>
    </div>
</div>

<!-- Sale Detail Modal -->
<div class="modal fade" id="saleDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Sale Detail</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="saleDetailBody"></div>
        </div>
    </div>
</div>

<!-- Delivery Modal -->
<div class="modal fade" id="deliveryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Create Delivery (Surat Jalan)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="deliveryForm">
                    <input type="hidden" id="deliverySaleId">
                    <div class="row mb-3">
                        <div class="col-md-6"><label class="form-label">Customer Name</label><input type="text" class="form-control" id="deliveryCustomerName" readonly></div>
                        <div class="col-md-6"><label class="form-label">Phone</label><input type="text" class="form-control" id="deliveryPhone"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Delivery Address</label><textarea class="form-control" id="deliveryAddr" rows="2"></textarea></div>
                    <div class="row mb-3">
                        <div class="col-md-4"><label class="form-label">Delivery Date</label><input type="date" class="form-control" id="deliveryDate" required></div>
                        <div class="col-md-4"><label class="form-label">Time</label><input type="time" class="form-control" id="deliveryTime"></div>
                        <div class="col-md-4"><label class="form-label">Vehicle Plate</label><input type="text" class="form-control" id="deliveryPlate"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Driver Name</label><input type="text" class="form-control" id="deliveryDriver"></div>
                    <div class="mb-3"><label class="form-label">Notes</label><textarea class="form-control" id="deliveryNotes" rows="2"></textarea></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="submitDelivery()">Create Delivery</button></div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let productsJson = <?= json_encode($products) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const barcodeInput = document.getElementById('barcodeInput');
    if (barcodeInput) {
        barcodeInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); lookupBarcode(); }
        });
    }
});

function lookupBarcode() {
    const barcode = document.getElementById('barcodeInput').value.trim();
    if (!barcode) return;
    fetch('http://127.0.0.1:8000/api/v1/barcode/lookup?barcode=' + encodeURIComponent(barcode), {
        headers: { 'Authorization': 'Bearer <?= $_SESSION['token'] ?>' }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.data) {
            addProductToSale(res.data);
            document.getElementById('barcodeInput').value = '';
        } else {
            alert('Product not found for barcode: ' + barcode);
        }
    })
    .catch(err => alert('Error looking up barcode'));
}

function addProductToSale(product) {
    const tbody = document.getElementById('saleItemsBody');
    if (!tbody) return;
    const existingRow = tbody.querySelector('tr[data-product-id="' + product.id + '"]');
    if (existingRow) {
        const qtyInput = existingRow.querySelector('.qty-input');
        qtyInput.value = (parseFloat(qtyInput.value) || 0) + 1;
        qtyInput.dispatchEvent(new Event('input'));
        return;
    }
    const row = document.createElement('tr');
    row.className = 'item-row';
    row.dataset.productId = product.id;
    row.innerHTML = `
        <td><select class="form-select form-select-sm productSelect" onchange="onProductChange(this)">${productsJson.map(p=>`<option value="${p.id}" ${p.id===product.id?'selected':''}>${p.code} - ${p.name}</option>`).join('')}</select></td>
        <td><input type="number" class="form-control form-control-sm qty-input" value="1" min="1" style="width:70px" oninput="calcRowTotal(this)"></td>
        <td><select class="form-select form-select-sm unitSelect">${(product.units||[]).map(u=>`<option value="${u.unit_id}" ${u.is_base_unit?'selected':''}>${u.unit_name}</option>`).join('')}</select></td>
        <td><input type="number" class="form-control form-control-sm price-input" value="${product.sell_price||0}" step="0.01" style="width:100px" oninput="calcRowTotal(this)"></td>
        <td><input type="number" class="form-control form-control-sm discount-input" value="0" min="0" style="width:80px" oninput="calcRowTotal(this)"></td>
        <td class="row-total">Rp ${number_format(product.sell_price||0,0)}</td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove(); recalcTotal();"><i class="bi bi-x"></i></button></td>
    `;
    tbody.appendChild(row);
    recalcTotal();
}

function resetSaleForm() {
    document.getElementById('saleForm').reset();
    document.getElementById('saleDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('customerSelect').value = '';
    document.getElementById('itemsBody').innerHTML = '';
    addItemRow();
    calcTotal();
}

function addItemRow() {
    const tbody = document.getElementById('itemsBody');
    const row = document.createElement('tr');
    row.className = 'item-row';
    const prodOptions = productsJson.map(p => `<option value="${p.id}" data-price="${p.sell_price}">${p.code} - ${p.name}</option>`).join('');
    row.innerHTML = `<td><select class="form-select form-select-sm productSelect" onchange="onProductChange(this)"><option value="">Select Product</option>${prodOptions}</select></td>
        <td><input type="number" class="form-control form-control-sm qtyInput" step="0.001" min="0.001" value="1" oninput="calcRow(this); calcTotal()"></td>
        <td><input type="number" class="form-control form-control-sm priceInput" min="0" value="0" oninput="calcRow(this); calcTotal()"></td>
        <td><input type="number" class="form-control form-control-sm discountInput" min="0" value="0" oninput="calcRow(this); calcTotal()"></td>
        <td class="subtotalCell">Rp 0</td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove(); calcTotal()"><i class="bi bi-trash"></i></button></td>`;
    tbody.appendChild(row);
}

function onProductChange(sel) {
    const row = sel.closest('tr');
    const price = sel.options[sel.selectedIndex].dataset.price || 0;
    row.querySelector('.priceInput').value = price;
    const customerId = document.getElementById('customerSelect').value;
    if (customerId && sel.value) {
        fetchPrice(customerId, sel.value, row);
    }
    calcRow(row.querySelector('.qtyInput'));
    calcTotal();
}

function onCustomerChange() {
    const customerId = document.getElementById('customerSelect').value;
    document.querySelectorAll('.item-row').forEach(row => {
        const productId = row.querySelector('.productSelect').value;
        if (productId) fetchPrice(customerId, productId, row);
    });
}

function fetchPrice(customerId, productId, row) {
    const unitId = row.querySelector('.productSelect').value;
    fetch(`http://127.0.0.1:8000/api/v1/sales/price?product_id=${productId}&unit_id=${unitId || productId}&customer_id=${customerId || ''}`, {
        headers: { 'Authorization': 'Bearer <?= $_SESSION['token'] ?>' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.data.unit_price) {
            row.querySelector('.priceInput').value = data.data.unit_price;
            calcRow(row.querySelector('.qtyInput'));
            calcTotal();
        }
    }).catch(() => {});
}

function calcRow(input) {
    const row = input.closest('tr');
    const qty = parseFloat(row.querySelector('.qtyInput').value) || 0;
    const price = parseFloat(row.querySelector('.priceInput').value) || 0;
    const discount = parseFloat(row.querySelector('.discountInput').value) || 0;
    const subtotal = (qty * price) - discount;
    row.querySelector('.subtotalCell').textContent = 'Rp ' + Math.round(subtotal).toLocaleString('id-ID');
}

function calcTotal() {
    let subtotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.qtyInput').value) || 0;
        const price = parseFloat(row.querySelector('.priceInput').value) || 0;
        const discount = parseFloat(row.querySelector('.discountInput').value) || 0;
        subtotal += (qty * price) - discount;
    });
    const globalDiscount = parseFloat(document.getElementById('globalDiscount').value) || 0;
    const taxRate = 0.11;
    const taxable = subtotal - globalDiscount;
    const tax = taxable * taxRate;
    const total = taxable + tax;
    document.getElementById('subtotalDisplay').textContent = 'Rp ' + Math.round(subtotal).toLocaleString('id-ID');
    document.getElementById('discountDisplay').textContent = 'Rp ' + Math.round(globalDiscount).toLocaleString('id-ID');
    document.getElementById('taxDisplay').textContent = 'Rp ' + Math.round(tax).toLocaleString('id-ID');
    document.getElementById('grandTotalDisplay').textContent = 'Rp ' + Math.round(total).toLocaleString('id-ID');
}

function submitSale() {
    const items = [];
    document.querySelectorAll('.item-row').forEach(row => {
        const productId = row.querySelector('.productSelect').value;
        if (!productId) return;
        items.push({
            product_id: parseInt(productId),
            quantity: parseFloat(row.querySelector('.qtyInput').value),
            unit_id: parseInt(productId),
            unit_price: parseFloat(row.querySelector('.priceInput').value),
            discount: parseFloat(row.querySelector('.discountInput').value) || 0,
        });
    });
    if (items.length === 0) { alert('Add at least 1 item'); return; }
    const data = {
        customer_id: document.getElementById('customerSelect').value || null,
        sale_date: document.getElementById('saleDate').value,
        items: items,
        discount: parseFloat(document.getElementById('globalDiscount').value) || 0,
        payment_method: document.getElementById('paymentMethod').value,
        notes: document.getElementById('saleNotes').value,
        delivery_address: document.getElementById('deliveryAddress').value,
    };
    fetch('http://127.0.0.1:8000/api/v1/sales', {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer <?= $_SESSION['token'] ?>' },
        body: JSON.stringify(data)
    }).then(r => r.json()).then(res => {
        if (res.success) { alert('Sale created: ' + res.data.invoice_no); location.reload(); }
        else { alert('Error: ' + res.message); }
    });
}

function viewSale(id) {
    fetch(`http://127.0.0.1:8000/api/v1/sales/${id}`, { headers: { 'Authorization': 'Bearer <?= $_SESSION['token'] ?>' } })
    .then(r => r.json()).then(res => {
        if (res.success) {
            const s = res.data;
            let html = `<h6>Invoice: ${s.invoice_no}</h6><p>Date: ${s.sale_date} | Customer: ${s.customer_name_snapshot || 'Walk-in'}</p>`;
            html += '<table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Disc</th><th>Subtotal</th></tr></thead><tbody>';
            s.items.forEach(i => {
                html += `<tr><td>${i.product?.name || ''}</td><td>${i.quantity}</td><td>Rp ${Number(i.unit_price).toLocaleString()}</td><td>Rp ${Number(i.discount).toLocaleString()}</td><td>Rp ${Number(i.subtotal).toLocaleString()}</td></tr>`;
            });
            html += '</tbody></table>';
            html += `<p>Subtotal: Rp ${Number(s.subtotal).toLocaleString()} | Tax: Rp ${Number(s.tax).toLocaleString()} | Total: Rp ${Number(s.total).toLocaleString()}</p>`;
            if (s.payments && s.payments.length) {
                html += '<h6>Payments</h6><ul>';
                s.payments.forEach(p => { html += `<li>Rp ${Number(p.amount).toLocaleString()} - ${p.payment_method} - ${p.payment_date}</li>`; });
                html += '</ul>';
            }
            document.getElementById('saleDetailBody').innerHTML = html;
            new bootstrap.Modal(document.getElementById('saleDetailModal')).show();
        }
    });
}

function recordPayment(id, total) {
    document.getElementById('paymentSaleId').value = id;
    document.getElementById('outstandingAmt').value = 'Rp ' + Math.round(total).toLocaleString('id-ID');
    document.getElementById('paymentAmount').value = total;
    document.getElementById('paymentDate').value = new Date().toISOString().split('T')[0];
    new bootstrap.Modal(document.getElementById('paymentModal')).show();
}

function submitPayment() {
    const id = document.getElementById('paymentSaleId').value;
    const data = {
        amount: parseFloat(document.getElementById('paymentAmount').value),
        payment_method: document.getElementById('paymentMethodType').value,
        payment_date: document.getElementById('paymentDate').value,
    };
    fetch(`http://127.0.0.1:8000/api/v1/sales/${id}/payment`, {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer <?= $_SESSION['token'] ?>' },
        body: JSON.stringify(data)
    }).then(r => r.json()).then(res => {
        if (res.success) { alert('Payment recorded'); location.reload(); }
        else { alert('Error: ' + res.message); }
    });
}

function voidSale(id) {
    document.getElementById('voidSaleId').value = id;
    document.getElementById('voidReason').value = '';
    new bootstrap.Modal(document.getElementById('voidModal')).show();
}

function submitVoid() {
    const id = document.getElementById('voidSaleId').value;
    const reason = document.getElementById('voidReason').value;
    if (!reason) { alert('Reason required'); return; }
    fetch(`http://127.0.0.1:8000/api/v1/sales/${id}`, {
        method: 'DELETE', headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer <?= $_SESSION['token'] ?>' },
        body: JSON.stringify({ reason })
    }).then(r => r.json()).then(res => {
        if (res.success) { alert('Sale voided'); location.reload(); }
        else { alert('Error: ' + res.message); }
    });
}

function createDelivery(saleId, customerName) {
    document.getElementById('deliverySaleId').value = saleId;
    document.getElementById('deliveryCustomerName').value = customerName;
    document.getElementById('deliveryDate').value = new Date().toISOString().split('T')[0];
    new bootstrap.Modal(document.getElementById('deliveryModal')).show();
}

function submitDelivery() {
    const data = {
        sale_id: document.getElementById('deliverySaleId').value,
        customer_name: document.getElementById('deliveryCustomerName').value,
        delivery_address: document.getElementById('deliveryAddr').value,
        phone: document.getElementById('deliveryPhone').value,
        delivery_date: document.getElementById('deliveryDate').value,
        delivery_time: document.getElementById('deliveryTime').value,
        driver_name: document.getElementById('deliveryDriver').value,
        vehicle_plate: document.getElementById('deliveryPlate').value,
        notes: document.getElementById('deliveryNotes').value,
    };
    fetch('http://127.0.0.1:8000/api/v1/deliveries', {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer <?= $_SESSION['token'] ?>' },
        body: JSON.stringify(data)
    }).then(r => r.json()).then(res => {
        if (res.success) { alert('Delivery created: ' + res.data.delivery_no); bootstrap.Modal.getInstance(document.getElementById('deliveryModal')).hide(); }
        else { alert('Error: ' + res.message); }
    });
}

function loadSales() {
    const search = document.getElementById('saleSearch').value;
    let url = `http://127.0.0.1:8000/api/v1/sales?per_page=20&page=${currentPage}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    fetch(url, { headers: { 'Authorization': 'Bearer <?= $_SESSION['token'] ?>' } })
    .then(r => r.json()).then(res => {
        if (res.success) {
            const tbody = document.getElementById('salesTableBody');
            tbody.innerHTML = res.data.map(s => `<tr>
                <td>${s.invoice_no}</td><td>${s.sale_date}</td>
                <td>${s.customer_name_snapshot || s.customer?.name || 'Walk-in'}</td>
                <td>Rp ${Number(s.total).toLocaleString()}</td>
                <td><span class="badge bg-${s.payment_status==='paid'?'success':(s.payment_status==='partial'?'warning':'danger')}">${s.payment_status}</span></td>
                <td><span class="badge bg-${s.status==='completed'?'success':(s.status==='voided'?'danger':'secondary')}">${s.status}</span></td>
                <td><a href="print_nota.php?id=${s.id}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i></a></td>
            </tr>`).join('');
            document.getElementById('salesPageInfo').textContent = `Page ${res.meta.current_page} of ${res.meta.last_page} (${res.meta.total} total)`;
        }
    });
}

function changePage(dir) {
    currentPage = Math.max(1, currentPage + dir);
    loadSales();
}

$(function() { resetSaleForm(); });
</script>
<?php renderFoot(); ?>
