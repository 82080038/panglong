<?php
require_once 'config.php';
requirePermission('manage_sales');

$d = db();
$user = currentUser();
$tenantId = $user['tenant_id'] ?? null;
$branchId = $user['branch_id'] ?? null;
$isSuperAdmin = $user['role_slug'] === 'super_admin';

$customerSql = "SELECT id, name, group_id FROM customers";
$customerParams = [];
if (!$isSuperAdmin && $tenantId) {
    $customerSql .= " WHERE tenant_id = ?";
    $customerParams[] = $tenantId;
}
$customerSql .= " ORDER BY name LIMIT 200";
$stmt = $d->prepare($customerSql);
$stmt->execute($customerParams);
$customers = $stmt->fetchAll();

$productSql = "SELECT id, code, name, sell_price FROM products WHERE is_active = 1";
$productParams = [];
if (!$isSuperAdmin && $tenantId) {
    $productSql .= " AND (tenant_id = ? OR tenant_id IS NULL)";
    $productParams[] = $tenantId;
}
$productSql .= " ORDER BY name LIMIT 200";
$stmt = $d->prepare($productSql);
$stmt->execute($productParams);
$products = $stmt->fetchAll();

// Fetch payment methods for dropdown
$pmStmt = $d->prepare("SELECT code, name FROM payment_methods WHERE is_active = 1" . ($isSuperAdmin ? "" : " AND tenant_id = ?") . " ORDER BY name");
$pmStmt->execute($isSuperAdmin ? [] : [$tenantId]);
$paymentMethods = $pmStmt->fetchAll();

// Fetch sales status codes for dropdown
$scStmt = $d->prepare("SELECT code, name FROM status_codes WHERE module = 'sales' AND is_active = 1" . ($isSuperAdmin ? "" : " AND tenant_id = ?") . " ORDER BY name");
$scStmt->execute($isSuperAdmin ? [] : [$tenantId]);
$salesStatuses = $scStmt->fetchAll();

$salesSql = "SELECT s.*, c.name as customer_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.id";
$salesParams = [];
if (!$isSuperAdmin && $tenantId) {
    $salesSql .= " WHERE s.tenant_id = ?";
    $salesParams[] = $tenantId;
    if ($branchId) {
        $salesSql .= " AND s.branch_id = ?";
        $salesParams[] = $branchId;
    }
}
$salesSql .= " ORDER BY s.id DESC LIMIT 20";
$stmt = $d->prepare($salesSql);
$stmt->execute($salesParams);
$sales = $stmt->fetchAll();
foreach ($sales as &$s) {
    $s['customer'] = ['name' => $s['customer_name'] ?? 'Pelanggan Umum'];
    $s['customer_name_snapshot'] = $s['customer_name'] ?? 'Pelanggan Umum';
}

renderHead('Sales');
renderNav('sales');
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Penjualan</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#saleModal" onclick="resetSaleForm()">
            <i class="bi bi-plus-circle"></i> Penjualan Baru
        </button>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8"><input type="text" class="form-control" id="saleSearch" placeholder="Cari berdasarkan faktur atau pelanggan..."></div>
                <div class="col-md-4"><button class="btn btn-outline-primary w-100" onclick="loadSales()"><i class="bi bi-search"></i> Search</button></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive"><table class="table table-striped">
                <thead>
                    <tr><th>Invoice</th><th>Tanggal</th><th>Pelanggan</th><th>Total</th><th>Payment</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody id="salesTableBody">
                    <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td><?= htmlspecialchars($sale['invoice_no']) ?></td>
                        <td><?= tglIndo($sale['sale_date']) ?></td>
                        <td><?= htmlspecialchars($sale['customer_name_snapshot'] ?? ($sale['customer']['name'] ?? 'Pelanggan Umum')) ?></td>
                        <td><?= rupiah($sale['total']) ?></td>
                        <td><span class="badge bg-<?= $sale['payment_status']==='paid'?'success':($sale['payment_status']==='partial'?'warning':'danger') ?>"><?= ucfirst($sale['payment_status']) ?></span></td>
                        <td>
                            <select class="form-select form-select-sm d-inline-block" style="width:auto" onchange="updateSaleStatus(<?= $sale['id'] ?>, this.value)">
                                <?php foreach ($salesStatuses as $ss): ?>
                                    <option value="<?= htmlspecialchars($ss['code']) ?>" <?= $sale['status'] === $ss['code'] ? 'selected' : '' ?>><?= htmlspecialchars($ss['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="viewSale(<?= $sale['id'] ?>)"><i class="bi bi-eye"></i></button>
                            <?php if ($sale['status'] !== 'voided'): ?>
                            <button class="btn btn-sm btn-warning" onclick="recordPayment(<?= $sale['id'] ?>, <?= $sale['total'] ?>)"><i class="bi bi-cash"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="voidSale(<?= $sale['id'] ?>)"><i class="bi bi-x-circle"></i></button>
                            <button class="btn btn-sm btn-secondary" onclick="createDelivery(<?= $sale['id'] ?>, '<?= htmlspecialchars($sale['customer_name_snapshot'] ?? $sale['customer']['name'] ?? 'Pelanggan Umum', ENT_QUOTES) ?>')"><i class="bi bi-truck"></i></button>
                            <?php endif; ?>
                            <a href="print_nota.php?id=<?= $sale['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
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

<!-- Penjualan Baru Modal -->
<div class="modal fade" id="saleModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Penjualan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="saleForm">
                    <div class="row mb-2">
                        <div class="col-md-8">
                            <label class="form-label"><i class="bi bi-upc-scan"></i> Barcode Scanner</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="barcodeInput" placeholder="Scan or type barcode..." autocomplete="off" onkeydown="handleEnter(event, 'customerSelect')">
                                <button type="button" class="btn btn-outline-primary" onclick="lookupBarcode()"><i class="bi bi-search"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Pelanggan (optional)</label>
                            <div class="input-group">
                                <select class="form-select" id="customerSelect" onchange="onPelangganChange()" onkeydown="handleEnter(event, 'saleDate')">
                                    <option value="">Pelanggan Umum Pelanggan</option>
                                    <?php foreach ($customers as $c): ?>
                                    <option value="<?= $c['id'] ?>" data-group="<?= $c['group_id'] ?? '' ?>"><?= htmlspecialchars($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-primary" onclick="openQuickAddCustomerModal()"><i class="bi bi-plus"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Jual</label>
                            <input type="date" class="form-control" id="saleDate" required onkeydown="handleEnter(event, 'paymentMethod')">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Metode Bayar</label>
                            <div class="input-group">
                                <select class="form-select" id="paymentMethod" required onkeydown="handleEnter(event, 'addItemBtn')">
                                    <?php if (is_array($paymentMethods)): ?>
                                        <?php foreach ($paymentMethods as $pm): ?>
                                            <option value="<?php echo htmlspecialchars($pm['code']); ?>"><?php echo htmlspecialchars($pm['name']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <button type="button" class="btn btn-outline-primary" onclick="openQuickAddPaymentModal()"><i class="bi bi-plus"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Kirim (optional)</label>
                        <textarea class="form-control" id="deliveryAddress" rows="1" onkeydown="handleEnter(event, 'addItemBtn')"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Items</label>
                        <div class="table-responsive"><table class="table table-sm" id="itemsTable">
                            <thead><tr><th>Produk</th><th>Qty</th><th>Unit</th><th>Unit Price</th><th>Diskon</th><th>Subtotal</th><th></th></tr></thead>
                            <tbody id="itemsBody"></tbody>
                        </table></div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn" onclick="addItemRow()"><i class="bi bi-plus"></i> Add Item</button>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Diskon Global (Rp)</label>
                            <input type="number" class="form-control" id="globalDiscount" value="0" min="0" oninput="calcTotal()" onkeydown="handleEnter(event, 'saleCatatan')">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Catatan</label>
                            <input type="text" class="form-control" id="saleCatatan" onkeydown="handleEnter(event, 'submitSaleBtn')">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 offset-md-8">
                            <div class="table-responsive"><table class="table table-sm">
                                <tr><td>Subtotal</td><td class="text-end" id="subtotalDisplay">Rp 0</td></tr>
                                <tr><td>Diskon</td><td class="text-end" id="discountDisplay">Rp 0</td></tr>
                                <tr><td>Tax (PPN)</td><td class="text-end" id="taxDisplay">Rp 0</td></tr>
                                <tr class="fw-bold"><td>Grand Total</td><td class="text-end" id="grandTotalDisplay">Rp 0</td></tr>
                            </table></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="submitSaleBtn" onclick="submitSale()">Create Sale</button>
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
                    <div class="mb-3"><label class="form-label">Method</label><select class="form-select" id="paymentMethodType"><?php if (is_array($paymentMethods)): foreach ($paymentMethods as $pm): ?><option value="<?php echo htmlspecialchars($pm['code']); ?>"><?php echo htmlspecialchars($pm['name']); ?></option><?php endforeach; endif; ?></select></div>
                    <div class="mb-3"><label class="form-label">Tanggal</label><input type="date" class="form-control" id="paymentDate" required></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-success" onclick="submitPayment()">Record</button></div>
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
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-danger" onclick="submitVoid()">Void Sale</button></div>
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

<!-- Quick Add Customer Modal -->
<div class="modal fade" id="quickAddCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Pelanggan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Nama Pelanggan *</label><input type="text" class="form-control" id="quickCustomerName" required></div>
                <div class="mb-3"><label class="form-label">Telepon</label><input type="text" class="form-control" id="quickCustomerPhone"></div>
                <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" id="quickCustomerEmail"></div>
                <div class="mb-3"><label class="form-label">Alamat</label><textarea class="form-control" id="quickCustomerAddress" rows="2"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="submitQuickAddCustomer()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Add Payment Method Modal -->
<div class="modal fade" id="quickAddPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Metode Bayar Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Kode *</label><input type="text" class="form-control" id="quickPaymentCode" required></div>
                <div class="mb-3"><label class="form-label">Nama *</label><input type="text" class="form-control" id="quickPaymentName" required></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="submitQuickAddPayment()">Simpan</button>
            </div>
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
                        <div class="col-md-6"><label class="form-label">Pelanggan Name</label><input type="text" class="form-control" id="deliveryPelangganName" readonly></div>
                        <div class="col-md-6"><label class="form-label">Telepon</label><input type="text" class="form-control" id="deliveryPhone"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Alamat Kirim</label><textarea class="form-control" id="deliveryAddr" rows="2"></textarea></div>
                    <div class="row mb-3">
                        <div class="col-md-4"><label class="form-label">Delivery Date</label><input type="date" class="form-control" id="deliveryDate" required></div>
                        <div class="col-md-4"><label class="form-label">Time</label><input type="time" class="form-control" id="deliveryTime"></div>
                        <div class="col-md-4"><label class="form-label">Vehicle Plate</label><input type="text" class="form-control" id="deliveryPlate"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Driver Name</label><input type="text" class="form-control" id="deliveryDriver"></div>
                    <div class="mb-3"><label class="form-label">Catatan</label><textarea class="form-control" id="deliveryCatatan" rows="2"></textarea></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" onclick="submitDelivery()">Create Delivery</button></div>
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
    fetch(API_URL+'?endpoint=barcode-lookup&barcode=' + encodeURIComponent(barcode))
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
    clearCartStorage();
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
        <td><select class="form-select form-select-sm unitSelect" onchange="onUnitChange(this)"><option value="">Unit</option></select></td>
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
    const productId = sel.value;
    
    // Load product units
    if (productId) {
        fetch(`${API_URL}?endpoint=product-units&product_id=${productId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data) {
                const unitSelect = row.querySelector('.unitSelect');
                unitSelect.innerHTML = '<option value="">Unit</option>';
                data.data.forEach(unit => {
                    unitSelect.innerHTML += `<option value="${unit.id}" data-conversion="${unit.conversion_factor}" data-price="${unit.price_per_unit}" ${unit.is_base_unit ? 'selected' : ''}>${unit.unit_name}</option>`;
                });
            }
        }).catch(() => {});
    }
    
    if (customerId && sel.value) {
        fetchPrice(customerId, sel.value, row);
    }
    calcRow(row.querySelector('.qtyInput'));
    calcTotal();
}

function onUnitChange(sel) {
    const row = sel.closest('tr');
    const selectedOption = sel.options[sel.selectedIndex];
    const conversion = parseFloat(selectedOption.dataset.conversion) || 1;
    const unitPrice = parseFloat(selectedOption.dataset.price) || 0;
    
    if (unitPrice > 0) {
        row.querySelector('.priceInput').value = unitPrice;
    }
    
    calcRow(row.querySelector('.qtyInput'));
    calcTotal();
}

function onPelangganChange() {
    const customerId = document.getElementById('customerSelect').value;
    document.querySelectorAll('.item-row').forEach(row => {
        const productId = row.querySelector('.productSelect').value;
        if (productId) fetchPrice(customerId, productId, row);
    });
}

function fetchPrice(customerId, productId, row) {
    const unitId = row.querySelector('.productSelect').value;
    fetch(`${API_URL}?endpoint=sales-price&product_id=${productId}&unit_id=${unitId || productId}&customer_id=${customerId || ''}`)
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
    saveCartToStorage();
}

// P0 #5: Auto-save cart to localStorage
function saveCartToStorage() {
    const cart = {
        customer_id: document.getElementById('customerSelect').value,
        sale_date: document.getElementById('saleDate').value,
        payment_method: document.getElementById('paymentMethod').value,
        delivery_address: document.getElementById('deliveryAddress').value,
        global_discount: document.getElementById('globalDiscount').value,
        notes: document.getElementById('saleCatatan').value,
        items: []
    };
    document.querySelectorAll('.item-row').forEach(row => {
        const productId = row.querySelector('.productSelect').value;
        if (!productId) return;
        cart.items.push({
            product_id: productId,
            product_name: row.querySelector('.productSelect').selectedOptions[0]?.text || '',
            quantity: row.querySelector('.qtyInput').value,
            unit_id: row.querySelector('.unitSelect').value,
            unit_price: row.querySelector('.priceInput').value,
            discount: row.querySelector('.discountInput').value,
        });
    });
    try {
        localStorage.setItem('panglong_sale_cart', JSON.stringify(cart));
    } catch(e) {}
}

function restoreCartFromStorage() {
    try {
        const saved = localStorage.getItem('panglong_sale_cart');
        if (!saved) return false;
        const cart = JSON.parse(saved);
        if (!cart || !cart.items || cart.items.length === 0) return false;

        if (cart.customer_id) document.getElementById('customerSelect').value = cart.customer_id;
        if (cart.sale_date) document.getElementById('saleDate').value = cart.sale_date;
        if (cart.payment_method) document.getElementById('paymentMethod').value = cart.payment_method;
        if (cart.delivery_address) document.getElementById('deliveryAddress').value = cart.delivery_address;
        if (cart.global_discount) document.getElementById('globalDiscount').value = cart.global_discount;
        if (cart.notes) document.getElementById('saleCatatan').value = cart.notes;

        const tbody = document.getElementById('itemsBody');
        tbody.innerHTML = '';
        cart.items.forEach(item => {
            addItemRow();
            const row = tbody.lastElementChild;
            row.querySelector('.productSelect').value = item.product_id;
            row.querySelector('.qtyInput').value = item.quantity;
            row.querySelector('.priceInput').value = item.unit_price;
            row.querySelector('.discountInput').value = item.discount;
            if (item.unit_id) {
                const unitSelect = row.querySelector('.unitSelect');
                if (unitSelect) unitSelect.value = item.unit_id;
            }
            calcRow(row.querySelector('.qtyInput'));
        });
        calcTotal();
        return true;
    } catch(e) {
        return false;
    }
}

function clearCartStorage() {
    try {
        localStorage.removeItem('panglong_sale_cart');
    } catch(e) {}
}

function submitSale() {
    var btn = document.getElementById('submitSaleBtn');
    if (btn.disabled) return;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses...';
    const items = [];
    document.querySelectorAll('.item-row').forEach(row => {
        const productId = row.querySelector('.productSelect').value;
        if (!productId) return;
        items.push({
            product_id: parseInt(productId),
            quantity: parseFloat(row.querySelector('.qtyInput').value),
            unit_id: parseInt(row.querySelector('.unitSelect').value || productId),
            unit_price: parseFloat(row.querySelector('.priceInput').value),
            discount: parseFloat(row.querySelector('.discountInput').value) || 0,
        });
    });
    if (items.length === 0) { alert('Add at least 1 item'); btn.disabled = false; btn.innerHTML = 'Create Sale'; return; }
    const data = {
        customer_id: document.getElementById('customerSelect').value || null,
        sale_date: document.getElementById('saleDate').value,
        items: items,
        discount: parseFloat(document.getElementById('globalDiscount').value) || 0,
        payment_method: document.getElementById('paymentMethod').value,
        notes: document.getElementById('saleCatatan').value,
        delivery_address: document.getElementById('deliveryAddress').value,
        idempotency_key: 'sale_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
    };
    fetch(API_URL+'?endpoint=sales', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(r => r.json()).then(res => {
        if (res.success) { clearCartStorage(); alert('Sale created: ' + res.data.invoice_no); location.reload(); }
        else { alert('Kesalahan: ' + res.message); btn.disabled = false; btn.innerHTML = 'Create Sale'; }
    }).catch(err => {
        alert('Terjadi kesalahan jaringan');
        btn.disabled = false;
        btn.innerHTML = 'Create Sale';
    });
}

function viewSale(id) {
    fetch(`${API_URL}?endpoint=sales&id=${id}`)
    .then(r => r.json()).then(res => {
        if (res.success) {
            const s = res.data;
            let html = `<h6>Invoice: ${s.invoice_no}</h6><p>Date: ${s.sale_date} | Pelanggan: ${s.customer_name_snapshot || 'Pelanggan Umum'}</p>`;
            html += '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Produk</th><th>Qty</th><th>Harga</th><th>Disc</th><th>Subtotal</th></tr></thead><tbody>';
            s.items.forEach(i => {
                html += `<tr><td>${i.product?.name || ''}</td><td>${i.quantity}</td><td>Rp ${Number(i.unit_price).toLocaleString()}</td><td>Rp ${Number(i.discount).toLocaleString()}</td><td>Rp ${Number(i.subtotal).toLocaleString()}</td></tr>`;
            });
            html += '</tbody></table></div>';
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
    fetch(`${API_URL}?endpoint=sale-payment&id=${id}`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(r => r.json()).then(res => {
        if (res.success) { alert('Payment recorded'); location.reload(); }
        else { alert('Kesalahan: ' + res.message); }
    });
}

function voidSale(id) {
    document.getElementById('voidSaleId').value = id;
    document.getElementById('voidReason').value = '';
    new bootstrap.Modal(document.getElementById('voidModal')).show();
}

function updateSaleStatus(id, status) {
    if (!status) return;
    fetch(`${API_URL}?endpoint=sales&id=${id}`, {
        method: 'PUT', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status })
    }).then(r => r.json()).then(res => {
        if (res.success) { alert('Status updated'); location.reload(); }
        else { alert('Kesalahan: ' + res.message); }
    });
}

function submitVoid() {
    const id = document.getElementById('voidSaleId').value;
    const reason = document.getElementById('voidReason').value;
    if (!reason) { alert('Reason required'); return; }
    fetch(`${API_URL}?endpoint=sales&id=${id}`, {
        method: 'DELETE', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ void_reason: reason })
    }).then(r => r.json()).then(res => {
        if (res.success) { alert(res.message || 'Sale voided'); location.reload(); }
        else { alert('Kesalahan: ' + res.message); }
    });
}

function createDelivery(saleId, customerName) {
    document.getElementById('deliverySaleId').value = saleId;
    document.getElementById('deliveryPelangganName').value = customerName;
    document.getElementById('deliveryDate').value = new Date().toISOString().split('T')[0];
    new bootstrap.Modal(document.getElementById('deliveryModal')).show();
}

function openQuickAddCustomerModal() {
    document.getElementById('quickCustomerName').value = '';
    document.getElementById('quickCustomerPhone').value = '';
    document.getElementById('quickCustomerEmail').value = '';
    document.getElementById('quickCustomerAddress').value = '';
    new bootstrap.Modal(document.getElementById('quickAddCustomerModal')).show();
}

function openQuickAddPaymentModal() {
    document.getElementById('quickPaymentCode').value = '';
    document.getElementById('quickPaymentName').value = '';
    new bootstrap.Modal(document.getElementById('quickAddPaymentModal')).show();
}

function submitQuickAddPayment() {
    var code = document.getElementById('quickPaymentCode').value.trim();
    var name = document.getElementById('quickPaymentName').value.trim();
    
    if (!code || !name) {
        alert('Kode dan nama tidak boleh kosong');
        return;
    }
    
    var data = {
        code: code,
        name: name
    };
    
    fetch(API_URL + '?endpoint=payment-methods', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            var select = document.getElementById('paymentMethod');
            var option = document.createElement('option');
            option.value = res.data.code;
            option.textContent = res.data.name;
            select.appendChild(option);
            select.value = res.data.code;
            
            bootstrap.Modal.getInstance(document.getElementById('quickAddPaymentModal')).hide();
            alert('Metode bayar berhasil ditambahkan');
            
            // Return focus to the select element
            select.focus();
        } else {
            alert('Gagal menambahkan: ' + res.message);
        }
    })
    .catch(err => {
        alert('Terjadi kesalahan: ' + err);
    });
}

function submitQuickAddCustomer() {
    var name = document.getElementById('quickCustomerName').value.trim();
    if (!name) {
        alert('Nama pelanggan tidak boleh kosong');
        return;
    }
    
    var data = {
        name: name,
        phone: document.getElementById('quickCustomerPhone').value,
        email: document.getElementById('quickCustomerEmail').value,
        address: document.getElementById('quickCustomerAddress').value
    };
    
    fetch(API_URL + '?endpoint=customers', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            var select = document.getElementById('customerSelect');
            var option = document.createElement('option');
            option.value = res.data.id;
            option.textContent = res.data.name;
            option.setAttribute('data-group', res.data.group_id || '');
            select.appendChild(option);
            select.value = res.data.id;
            
            bootstrap.Modal.getInstance(document.getElementById('quickAddCustomerModal')).hide();
            alert('Pelanggan berhasil ditambahkan');
            onPelangganChange();
            
            // Return focus to the select element
            select.focus();
        } else {
            alert('Gagal menambahkan: ' + res.message);
        }
    })
    .catch(err => {
        alert('Terjadi kesalahan: ' + err);
    });
}

function submitDelivery() {
    const data = {
        sale_id: document.getElementById('deliverySaleId').value,
        customer_name: document.getElementById('deliveryPelangganName').value,
        delivery_address: document.getElementById('deliveryAddr').value,
        phone: document.getElementById('deliveryPhone').value,
        delivery_date: document.getElementById('deliveryDate').value,
        delivery_time: document.getElementById('deliveryTime').value,
        driver_name: document.getElementById('deliveryDriver').value,
        vehicle_plate: document.getElementById('deliveryPlate').value,
        notes: document.getElementById('deliveryCatatan').value,
    };
    fetch(API_URL+'?endpoint=deliveries', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(r => r.json()).then(res => {
        if (res.success) { alert('Delivery created: ' + res.data.delivery_no); bootstrap.Modal.getInstance(document.getElementById('deliveryModal')).hide(); }
        else { alert('Kesalahan: ' + res.message); }
    });
}

function loadSales() {
    const search = document.getElementById('saleSearch').value;
    let url = `${API_URL}?endpoint=sales&per_page=20&page=${currentPage}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    fetch(url)
    .then(r => r.json()).then(res => {
        if (res.success) {
            const tbody = document.getElementById('salesTableBody');
            tbody.innerHTML = res.data.map(s => `<tr>
                <td>${s.invoice_no}</td><td>${s.sale_date}</td>
                <td>${s.customer_name_snapshot || s.customer?.name || 'Pelanggan Umum'}</td>
                <td>Rp ${Number(s.total).toLocaleString()}</td>
                <td><span class="badge bg-${s.payment_status==='paid'?'success':(s.payment_status==='partial'?'warning':'danger')}">${s.payment_status}</span></td>
                <td><span class="badge bg-${s.status==='completed'?'success':(s.status==='voided'?'danger':'secondary')}">${s.status}</span></td>
                <td><a href="print_nota.php?id=${s.id}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i></a></td>
            </tr>`).join('');
            document.getElementById('salesPageInfo').textContent = `Page ${res.meta?.current_page || currentPage} of ${res.meta?.last_page || 1} (${res.meta?.total || res.data.length} total)`;
            document.getElementById('prevPageBtn').disabled = currentPage <= 1;
            document.getElementById('nextPageBtn').disabled = !res.has_next;
        }
    });
}

// Auto focus and enter key navigation
function handleEnter(event, nextId) {
    if (event.key === 'Enter') {
        event.preventDefault();
        var nextElement = document.getElementById(nextId);
        if (nextElement) {
            nextElement.focus();
            if (nextElement.tagName === 'SELECT') {
                nextElement.click();
            }
        }
    }
}

// Auto focus on modal open
var saleModal = document.getElementById('saleModal');
if (saleModal) {
    saleModal.addEventListener('shown.bs.modal', function() {
        document.getElementById('barcodeInput').focus();
    });
}

function changePage(dir) {
    currentPage = Math.max(1, currentPage + dir);
    loadSales();
}

$(function() {
    if (!restoreCartFromStorage()) {
        resetSaleForm();
    }
});
</script>
<?php renderFoot(); ?>
