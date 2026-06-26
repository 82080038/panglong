<?php
require_once 'config.php';
requirePermission('manage_suppliers');

$d = db();

$pos = $d->query("SELECT po.id, po.po_number, po.po_date, po.supplier_id, po.subtotal, po.freight_cost, po.insurance_cost, po.handling_cost, po.landed_total, po.status, s.name as supplier_name FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.id ORDER BY po.id DESC LIMIT 100")->fetchAll();

$distributions = $d->query("SELECT lcd.*, p.name as product_name, p.code as product_code, po.po_number FROM landed_cost_distributions lcd JOIN products p ON lcd.product_id = p.id JOIN purchase_orders po ON lcd.purchase_order_id = po.id ORDER BY lcd.id DESC LIMIT 50")->fetchAll();

renderHead('Landed Cost Distribution');
renderNav('landed_cost');
?>

<div class="container-fluid mt-3">
    <h4><i class="bi bi-box-seam"></i> Landed Cost Distribution</h4>
    <p class="text-muted small">Distribusi ongkos angkut, asuransi, dan handling ke HPP per produk (proporsional berdasarkan nilai item).</p>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link active" href="#" onclick="switchTab('pos')">Pesanan Pembelian</a></li>
        <li class="nav-item"><a class="nav-link" href="#" onclick="switchTab('history')">Riwayat Distribusi</a></li>
    </ul>

    <div id="tab-pos">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive"><table class="table table-striped table-sm" id="poTable">
                    <thead>
                        <tr>
                            <th>PO Number</th><th>Tanggal</th><th>Supplier</th>
                            <th>Subtotal</th><th>Ongkos Angkut</th><th>Asuransi</th><th>Penanganan</th>
                            <th>Total Landed</th><th>Status</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pos as $po): ?>
                        <tr>
                            <td><?= htmlspecialchars($po['po_number']) ?></td>
                            <td><?= tglIndo($po['po_date']) ?></td>
                            <td><?= htmlspecialchars($po['supplier_name'] ?? '-') ?></td>
                            <td><?= rupiah($po['subtotal']) ?></td>
                            <td><?= rupiah($po['freight_cost'] ?? 0) ?></td>
                            <td><?= rupiah($po['insurance_cost'] ?? 0) ?></td>
                            <td><?= rupiah($po['handling_cost'] ?? 0) ?></td>
                            <td class="fw-bold"><?= rupiah($po['landed_total'] ?? $po['subtotal']) ?></td>
                            <td><span class="badge bg-<?= $po['status'] === 'received' ? 'success' : 'warning' ?>"><?= $po['status'] === 'received' ? 'Diterima' : 'Pending' ?></span></td>
                            <td>
                                <?php $totalLanded = ($po['freight_cost'] ?? 0) + ($po['insurance_cost'] ?? 0) + ($po['handling_cost'] ?? 0); ?>
                                <?php if ($totalLanded > 0): ?>
                                <button class="btn btn-sm btn-primary" onclick="distribute(<?= $po['id'] ?>, '<?= htmlspecialchars($po['po_number']) ?>')">
                                    <i class="bi bi-share"></i> Distribute
                                </button>
                                <?php else: ?>
                                <span class="text-muted small">Belum ada landed cost</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>

    <div id="tab-history" style="display:none">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive"><table class="table table-striped table-sm" id="distTable">
                    <thead>
                        <tr>
                            <th>PO Number</th><th>Product</th><th>Qty</th>
                            <th>Ongkos Angkut Alloc</th><th>Asuransi Alloc</th><th>Penanganan Alloc</th>
                            <th>Total Landed</th><th>HPP per Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($distributions as $dist): ?>
                        <tr>
                            <td><?= htmlspecialchars($dist['po_number']) ?></td>
                            <td><?= htmlspecialchars($dist['product_name']) ?></td>
                            <td><?= $dist['quantity'] ?></td>
                            <td><?= rupiah($dist['freight_allocated']) ?></td>
                            <td><?= rupiah($dist['insurance_allocated']) ?></td>
                            <td><?= rupiah($dist['handling_allocated']) ?></td>
                            <td class="fw-bold"><?= rupiah($dist['total_landed_cost']) ?></td>
                            <td class="fw-bold text-primary"><?= rupiahDetail($dist['landed_unit_cost']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($distributions)): ?>
                        <tr><td colspan="8" class="text-center text-muted">No distributions yet. Select a PO and click Distribute.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="resultModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hasil Distribusi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="resultBody"></div>
        </div>
    </div>
</div>

<?php renderFoot(); ?>
<script>
function switchTab(tab) {
    $('#tab-pos, #tab-history').hide();
    $('#tab-' + tab).show();
    $('.nav-link').removeClass('active');
    event.target.classList.add('active');
}

function distribute(poId, poNumber) {
    if (!confirm('Distribusi landed cost untuk ' + poNumber + '?\nIni akan memperbarui HPP produk.')) return;
    $.ajax({
        url: API_URL + '?endpoint=landed-cost',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ po_id: poId }),
        success: function(resp) {
            let html = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th><th>Ongkos Angkut</th><th>Asuransi</th><th>Penanganan</th><th>HPP per Unit</th></tr></thead><tbody>';
            resp.data.distributions.forEach(function(d) {
                html += '<tr><td>' + d.product_name + '</td><td>' + d.quantity + '</td>';
                html += '<td>Rp ' + formatNum(d.freight_allocated) + '</td>';
                html += '<td>Rp ' + formatNum(d.insurance_allocated) + '</td>';
                html += '<td>Rp ' + formatNum(d.handling_allocated) + '</td>';
                html += '<td class="fw-bold text-primary">Rp ' + formatNum(d.landed_unit_cost) + '</td></tr>';
            });
            html += '</tbody></table></div>';
            html += '<div class="alert alert-info">Total Landed Cost: Rp ' + formatNum(resp.data.total_landed_cost) + '</div>';
            $('#resultBody').html(html);
            new bootstrap.Modal($('#resultModal')[0]).show();
            setTimeout(function() { location.reload(); }, 2000);
        },
        error: function(xhr) {
            let err = JSON.parse(xhr.responseText);
            alert('Kesalahan: ' + (err.message || 'Gagal'));
        }
    });
}

function formatNum(n) {
    return parseFloat(n || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}
</script>
