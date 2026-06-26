<?php
require_once 'config.php';
requireLogin();

$d = db();

// Get sales orders for this salesman
$userId = $_SESSION['user']['id'];
$salesOrders = $d->query("SELECT so.*, c.name as customer_name, c.phone as customer_phone FROM sales_orders so LEFT JOIN customers c ON so.customer_id = c.id WHERE so.created_by = $userId OR 1=1 ORDER BY so.id DESC LIMIT 50")->fetchAll();

$customers = $d->query("SELECT id, name, phone, address FROM customers WHERE is_active = 1 ORDER BY name")->fetchAll();
$products = $d->query("SELECT id, code, name, sell_price, min_stock FROM products WHERE is_active = 1 ORDER BY name LIMIT 200")->fetchAll();

renderHead('Salesman Mobile - Sales Order');
renderNav('salesman_app');
?>

<div class="container-fluid mt-3">
    <h4><i class="bi bi-phone"></i> Aplikasi Salesman Mobile</h4>
    <p class="text-muted small">Input Pesanan Penjualan dari lapangan. Dioptimalkan untuk mobile — install as PWA for offline use.</p>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link active" href="#" onclick="switchTab('orders')">Pesanan Saya</a></li>
        <li class="nav-item"><a class="nav-link" href="#" onclick="switchTab('new')">Pesanan Baru</a></li>
    </ul>

    <div id="tab-orders">
        <div class="card">
            <div class="card-body">
                <table class="table table-striped table-sm" id="soTable">
                    <thead>
                        <tr>
                            <th>Nomor SO</th><th>Tanggal</th><th>Customer</th><th>Telepon</th>
                            <th>Total</th><th>Status</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salesOrders as $so): ?>
                        <tr>
                            <td><?= htmlspecialchars($so['so_number']) ?></td>
                            <td><?= $so['order_date'] ?></td>
                            <td><?= htmlspecialchars($so['customer_name'] ?? 'Pelanggan Umum') ?></td>
                            <td><?= htmlspecialchars($so['customer_phone'] ?? '-') ?></td>
                            <td><?= rupiah($so['total'] ?? 0) ?></td>
                            <td><span class="badge bg-<?= $so['status'] === 'fulfilled' ? 'success' : ($so['status'] === 'pending' ? 'warning' : 'info') ?>"><?= $so['status'] === 'fulfilled' ? 'Dipenuhi' : ($so['status'] === 'pending' ? 'Pending' : 'Diproses') ?></span></td>
                            <td><button class="btn btn-sm btn-outline-primary" onclick="viewSO(<?= $so['id'] ?>)"><i class="bi bi-eye"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($salesOrders)): ?>
                        <tr><td colspan="7" class="text-center text-muted">Belum ada pesanan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="tab-new" style="display:none">
        <div class="card">
            <div class="card-header"><strong>Buat Pesanan Penjualan</strong></div>
            <div class="card-body">
                <form id="soForm">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" id="custSelect" class="form-select" required>
                                <option value="">-- Pilih Pelanggan --</option>
                                <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>" data-phone="<?= htmlspecialchars($c['phone']) ?>" data-address="<?= htmlspecialchars($c['address']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Order Date</label>
                            <input type="date" name="order_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Perkiraan Kirim</label>
                            <input type="date" name="expected_delivery_date" class="form-control">
                        </div>
                    </div>

                    <div id="customerInfo" class="alert alert-light" style="display:none">
                        <span id="custPhone"></span><br>
                        <span id="custAddress"></span>
                    </div>

                    <hr>
                    <h6>Items</h6>
                    <table class="table table-sm" id="itemsTable">
                        <thead>
                            <tr><th>Product</th><th>Qty</th><th>Harga</th><th>Subtotal</th><th></th></tr>
                        </thead>
                        <tbody id="itemsBody">
                            <tr class="item-row">
                                <td>
                                    <select class="form-select form-select-sm product-select" onchange="updatePrice(this)">
                                        <option value="">-- Pilih --</option>
                                        <?php foreach ($products as $p): ?>
                                        <option value="<?= $p['id'] ?>" data-price="<?= $p['sell_price'] ?>" data-name="<?= htmlspecialchars($p['name']) ?>"><?= htmlspecialchars($p['code'] . ' - ' . $p['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" class="form-control form-control-sm qty-input" onchange="calcRow(this)" style="width:80px"></td>
                                <td><input type="number" step="0.01" class="form-control form-control-sm price-input" readonly style="width:100px"></td>
                                <td class="subtotal-cell fw-bold" style="width:120px">Rp 0</td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="bi bi-x"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-outline-primary mb-3" onclick="addRow()"><i class="bi bi-plus"></i> Tambah Item</button>

                    <div class="row mb-3">
                        <div class="col-md-6 offset-md-6">
                            <table class="table table-sm">
                                <tr><td>Subtotal</td><td class="text-end" id="grandSubtotal">Rp 0</td></tr>
                                <tr><td>Diskon</td><td class="text-end"><input type="number" step="0.01" name="discount" class="form-control form-control-sm d-inline-block" style="width:120px" value="0" onchange="calcTotal()"></td></tr>
                                <tr><td>PPN (11%)</td><td class="text-end" id="taxAmount">Rp 0</td></tr>
                                <tr class="table-primary fw-bold"><td>Total</td><td class="text-end" id="grandTotal">Rp 0</td></tr>
                            </table>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Instruksi pengiriman, dll."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-send"></i> Kirim Pesanan Penjualan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="soDetailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail SO</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="soDetailBody"></div>
        </div>
    </div>
</div>

<?php renderFoot(); ?>
<script>
function switchTab(tab) {
    $('#tab-orders, #tab-new').hide();
    $('#tab-' + tab).show();
    $('.nav-link').removeClass('active');
    event.target.classList.add('active');
}

$('#custSelect').on('change', function() {
    const opt = $(this).find(':selected');
    if (opt.val()) {
        $('#custPhone').text('<i class="bi bi-telephone"></i> ' + opt.data('phone'));
        $('#custAddress').text('<i class="bi bi-geo-alt"></i> ' + opt.data('address'));
        $('#customerInfo').show();
    } else {
        $('#customerInfo').hide();
    }
});

function addRow() {
    const row = $('.item-row').first().clone();
    row.find('select').val('');
    row.find('input').val('');
    row.find('.subtotal-cell').text('Rp 0');
    $('#itemsBody').append(row);
}

function removeRow(btn) {
    if ($('.item-row').length > 1) {
        $(btn).closest('tr').remove();
        calcTotal();
    }
}

function updatePrice(sel) {
    const price = $(sel).find(':selected').data('price') || 0;
    $(sel).closest('tr').find('.price-input').val(price);
    calcRow($(sel).closest('tr').find('.qty-input'));
}

function calcRow(input) {
    const row = $(input).closest('tr');
    const qty = parseFloat($(row).find('.qty-input').val()) || 0;
    const price = parseFloat($(row).find('.price-input').val()) || 0;
    const sub = qty * price;
    $(row).find('.subtotal-cell').text('Rp ' + sub.toLocaleString('id-ID'));
    calcTotal();
}

function calcTotal() {
    let subtotal = 0;
    $('.item-row').each(function() {
        const qty = parseFloat($(this).find('.qty-input').val()) || 0;
        const price = parseFloat($(this).find('.price-input').val()) || 0;
        subtotal += qty * price;
    });
    const discount = parseFloat($('input[name="discount"]').val()) || 0;
    const afterDisc = subtotal - discount;
    const tax = afterDisc * 0.11;
    const total = afterDisc + tax;
    $('#grandSubtotal').text('Rp ' + subtotal.toLocaleString('id-ID'));
    $('#taxAmount').text('Rp ' + tax.toLocaleString('id-ID'));
    $('#grandTotal').text('Rp ' + total.toLocaleString('id-ID'));
}

$('#soForm').on('submit', function(e) {
    e.preventDefault();
    const items = [];
    let valid = true;
    $('.item-row').each(function() {
        const productId = $(this).find('.product-select').val();
        const qty = parseFloat($(this).find('.qty-input').val()) || 0;
        const price = parseFloat($(this).find('.price-input').val()) || 0;
        if (productId && qty > 0) {
            items.push({ product_id: parseInt(productId), quantity: qty, unit_price: price, subtotal: qty * price });
        } else if (productId || qty > 0) {
            valid = false;
        }
    });
    if (!valid || items.length === 0) {
        Tidak boleh kosong fill all item rows completely.');
        return;
    }
    const data = {
        customer_id: parseInt($('select[name="customer_id"]').val()),
        order_date: $('input[name="order_date"]').val(),
        expected_delivery_date: $('input[name="expected_delivery_date"]').val() || null,
        notes: $('textarea[name="notes"]').val(),
        discount: parseFloat($('input[name="discount"]').val()) || 0,
        items: items
    };
    data.subtotal = items.reduce((s, i) => s + i.subtotal, 0);
    data.tax = (data.subtotal - data.discount) * 0.11;
    data.total = data.subtotal - data.discount + data.tax;
    $.ajax({
        url: API_URL + '?endpoint=sales-orders',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function() {
            alert('Pesanan Penjualan dikirim!');
            location.reload();
        },
        error: function(xhr) {
            const err = JSON.parse(xhr.responseText);
            alert('Kesalahan: ' + (err.message || 'Failed to submit'));
        }
    });
});

function viewSO(id) {
    $.get(API_URL + '?endpoint=sales-orders&id=' + id, function(resp) {
        const so = resp.data;
        let html = '<p><strong>Nomor SO:</strong> ' + so.so_number + '</p>';
        html += '<p><strong>Date:</strong> ' + so.order_date + '</p>';
        html += '<p><strong>Status:</strong> ' + so.status + '</p>';
        html += '<p><strong>Notes:</strong> ' + (so.notes || '-') + '</p>';
        if (so.items) {
            html += '<table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr></thead><tbody>';
            so.items.forEach(function(i) {
                html += '<tr><td>' + (i.product_name || '') + '</td><td>' + i.quantity + '</td><td>Rp ' + (i.unit_price || 0).toLocaleString('id-ID') + '</td><td>Rp ' + (i.subtotal || 0).toLocaleString('id-ID') + '</td></tr>';
            });
            html += '</tbody></table>';
            html += '<p class="fw-bold">Total: Rp ' + (so.total || 0).toLocaleString('id-ID') + '</p>';
        }
        $('#soDetailBody').html(html);
        new bootstrap.Modal($('#soDetailModal')[0]).show();
    });
}
</script>
