<?php
require_once 'config.php';
requirePermission('manage_products');

$d = db();

$batches = $d->query("SELECT pb.*, p.name as product_name, p.code as product_code, s.name as supplier_name FROM product_batches pb JOIN products p ON pb.product_id = p.id LEFT JOIN suppliers s ON pb.supplier_id = s.id ORDER BY pb.id DESC LIMIT 100")->fetchAll();

$products = $d->query("SELECT id, code, name FROM products WHERE is_active = 1 ORDER BY name")->fetchAll();
$suppliers = $d->query("SELECT id, name FROM suppliers ORDER BY name")->fetchAll();

// FIFO valuation summary
$productsFIFO = $d->query("SELECT id, code, name FROM products WHERE is_active = 1 ORDER BY name")->fetchAll();
$valuation = [];
$grandTotal = 0;
foreach ($productsFIFO as $p) {
    $stmt = $d->prepare("SELECT * FROM product_batches WHERE product_id = ? AND quantity_remaining > 0 ORDER BY received_date ASC, id ASC");
    $stmt->execute([$p['id']]);
    $pBatches = $stmt->fetchAll();
    $totalValue = 0; $totalQty = 0;
    foreach ($pBatches as $b) {
        $cost = (float)($b['landed_unit_cost'] ?? $b['unit_cost']);
        $totalValue += $cost * (float)$b['quantity_remaining'];
        $totalQty += (float)$b['quantity_remaining'];
    }
    if ($totalQty > 0) {
        $valuation[] = ['name' => $p['name'], 'code' => $p['code'], 'qty' => $totalQty, 'value' => $totalValue, 'avg' => $totalValue / $totalQty];
        $grandTotal += $totalValue;
    }
}

renderHead('Batch/Lot Tracking & FIFO');
renderNav('batches');
?>

<div class="container-fluid mt-3">
    <h4><i class="bi bi-layers"></i> Batch/Lot Tracking & FIFO Valuation</h4>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link active" href="#" onclick="switchTab('batches')">Batches</a></li>
        <li class="nav-item"><a class="nav-link" href="#" onclick="switchTab('valuation')">FIFO Valuation</a></li>
        <li class="nav-item"><a class="nav-link" href="#" onclick="switchTab('add')">Tambah Batch</a></li>
    </ul>

    <div id="tab-batches">
        <div class="card">
            <div class="card-body">
                <table class="table table-striped table-sm" id="batchTable">
                    <thead>
                        <tr>
                            <th>Nomor Batch</th><th>Product</th><th>Nomor Lot</th><th>Tgl Terima</th><th>Kedaluwarsa</th>
                            <th>Jml Diterima</th><th>Jml Tersisa</th><th>Harga Satuan</th><th>Landed Cost</th>
                            <th>Supplier</th><th>Status</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($batches as $b): ?>
                        <tr>
                            <td><?= htmlspecialchars($b['batch_no'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($b['product_name']) ?></td>
                            <td><?= htmlspecialchars($b['lot_no'] ?? '-') ?></td>
                            <td><?= tglIndo($b['received_date']) ?></td>
                            <td><?= $b['expiry_date'] ? tglIndo($b['expiry_date']) : '-' ?></td>
                            <td><?= $b['quantity_received'] ?></td>
                            <td class="fw-bold <?= (float)$b['quantity_remaining'] <= 0 ? 'text-danger' : '' ?>"><?= $b['quantity_remaining'] ?></td>
                            <td><?= rupiah($b['unit_cost']) ?></td>
                            <td class="text-primary"><?= $b['landed_unit_cost'] ? 'Rp ' . number_format($b['landed_unit_cost'], 2) : '-' ?></td>
                            <td><?= htmlspecialchars($b['supplier_name'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= $b['status'] === 'active' ? 'success' : 'secondary' ?>"><?= $b['status'] === 'active' ? 'Aktif' : 'Tidak Aktif' ?></span></td>
                            <td>
                                <?php if ($b['expiry_date'] && $b['expiry_date'] < date('Y-m-d')): ?>
                                <span class="badge bg-danger">EXPIRED</span>
                                <?php elseif ($b['expiry_date'] && $b['expiry_date'] < date('Y-m-d', strtotime('+30 days'))): ?>
                                <span class="badge bg-warning">EXPIRING</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($batches)): ?>
                        <tr><td colspan="12" class="text-center text-muted">Belum ada batch. Tambah batch untuk mulai melacak.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="tab-valuation" style="display:none">
        <div class="card">
            <div class="card-header">
                <strong>Valuasi Stok FIFO</strong>
                <button class="btn btn-sm btn-outline-primary float-end" onclick="exportCSV()"><i class="bi bi-download"></i> CSV</button>
            </div>
            <div class="card-body">
                <table class="table table-striped table-sm" id="valTable">
                    <thead>
                        <tr>
                            <th>Kode</th><th>Product</th><th>Total Qty</th><th>Harga Rata-rata</th><th>Total Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($valuation as $v): ?>
                        <tr>
                            <td><?= htmlspecialchars($v['code']) ?></td>
                            <td><?= htmlspecialchars($v['name']) ?></td>
                            <td><?= $v['qty'] ?></td>
                            <td><?= rupiahDetail($v['avg']) ?></td>
                            <td class="fw-bold"><?= rupiah($v['value']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($valuation)): ?>
                        <tr><td colspan="5" class="text-center text-muted">Belum ada data batch. Tambah batch untuk melihat valuasi FIFO.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($valuation)): ?>
                    <tfoot>
                        <tr class="table-primary fw-bold">
                            <td colspan="4">Total Keseluruhan</td>
                            <td><?= rupiah($grandTotal) ?></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <div id="tab-add" style="display:none">
        <div class="card">
            <div class="card-body">
                <form id="batchForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Product</label>
                            <select name="product_id" class="form-select" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['code'] . ' - ' . $p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Nomor Batch</label>
                            <input type="text" name="batch_no" class="form-control" placeholder="e.g. BAT-001">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Nomor Lot</label>
                            <input type="text" name="lot_no" class="form-control" placeholder="e.g. LOT-001">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tgl Terima Date</label>
                            <input type="date" name="received_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Kedaluwarsa Date</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Quantity Tgl Terima</label>
                            <input type="number" step="0.01" name="quantity_received" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Harga Satuan</label>
                            <input type="number" step="0.01" name="unit_cost" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" class="form-select">
                                <option value="">-- None --</option>
                                <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Catatan</label>
                            <input type="text" name="notes" class="form-control" placeholder="Optional notes">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Batch</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php renderFoot(); ?>
<script>
function switchTab(tab) {
    $('#tab-batches, #tab-valuation, #tab-add').hide();
    $('#tab-' + tab).show();
    $('.nav-link').removeClass('active');
    event.target.classList.add('active');
}

$('#batchForm').on('submit', function(e) {
    e.preventDefault();
    const data = {};
    $(this).serializeArray().forEach(f => data[f.name] = f.value);
    data.quantity_received = parseFloat(data.quantity_received);
    data.unit_cost = parseFloat(data.unit_cost);
    $.ajax({
        url: API_URL + '?endpoint=product-batches',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function() {
            alert('Batch tersimpan!');
            location.reload();
        },
        error: function(xhr) {
            const err = JSON.parse(xhr.responseText);
            alert('Kesalahan: ' + (err.message || 'Failed'));
        }
    });
});

function exportCSV() {
    const t = document.querySelector('#valTable');
    if (!t) return;
    let csv = [];
    t.querySelectorAll('tr').forEach(r => {
        let row = [];
        r.querySelectorAll('th,td').forEach(c => row.push('"' + c.textContent.trim().replace(/"/g, '""') + '"'));
        csv.push(row.join(','));
    });
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'fifo_valuation.csv';
    a.click();
}
</script>
