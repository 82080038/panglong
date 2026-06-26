<?php
require_once 'config.php';

$d = db();

$customers = $d->query("SELECT id, name FROM customers ORDER BY name LIMIT 200")->fetchAll();
$products = $d->query("SELECT id, code, name, sell_price FROM products WHERE is_active = 1 ORDER BY name LIMIT 200")->fetchAll();
$suppliers = $d->query("SELECT id, name FROM suppliers ORDER BY name LIMIT 200")->fetchAll();

$customerPrices = $d->query("SELECT cpp.*, c.name as customer_name, p.name as product_name, p.code as product_code FROM customer_product_prices cpp LEFT JOIN customers c ON cpp.customer_id = c.id LEFT JOIN products p ON cpp.product_id = p.id WHERE cpp.is_active = 1 ORDER BY cpp.id DESC LIMIT 50")->fetchAll();
$tierPrices = $d->query("SELECT pt.*, p.name as product_name, p.code as product_code FROM product_tier_prices pt LEFT JOIN products p ON pt.product_id = p.id WHERE pt.is_active = 1 ORDER BY pt.id DESC LIMIT 50")->fetchAll();
$priceHistory = $d->query("SELECT sph.*, s.name as supplier_name, p.name as product_name, p.code as product_code FROM supplier_price_history sph LEFT JOIN suppliers s ON sph.supplier_id = s.id LEFT JOIN products p ON sph.product_id = p.id ORDER BY sph.id DESC LIMIT 50")->fetchAll();

renderHead('Pricing Management');
renderNav('pricing');
?>
<div class="container mt-4">
    <h1>Manajemen Harga/h1>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#customerPrices">Harga Khusus Pelanggan</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tierPrices">Harga Bertingkat</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#priceHistory">Riwayat Harga Supplier</a></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="customerPrices">
            <button class="btn btn-primary btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#cpModal"><i class="bi bi-plus"></i> Tambah Pelanggan Price</button>
            <div class="card"><div class="card-body">
                <table class="table table-striped">
                    <thead><tr><th>Customer</th><th>Product</th><th>Custom Price</th><th>Min. Jumlah</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php foreach ($customerPrices as $cp): ?>
                        <tr>
                            <td><?= htmlspecialchars($cp['customer_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($cp['product_code'] ?? '') ?> - <?= htmlspecialchars($cp['product_name'] ?? '') ?></td>
                            <td><?= rupiah($cp['custom_price']) ?></td>
                            <td><?= $cp['min_qty'] ?></td>
                            <td><button class="btn btn-sm btn-danger" onclick="deleteCP(<?= $cp['id'] ?>)"><i class="bi bi-trash"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div></div>
        </div>
        <div class="tab-pane fade" id="tierPrices">
            <button class="btn btn-primary btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#tpModal"><i class="bi bi-plus"></i> Add Tier Price</button>
            <div class="card"><div class="card-body">
                <table class="table table-striped">
                    <thead><tr><th>Product</th><th>Min. Jumlah</th><th>Max Qty</th><th>Unit Price</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php foreach ($tierPrices as $tp): ?>
                        <tr>
                            <td><?= htmlspecialchars($tp['product_code'] ?? '') ?> - <?= htmlspecialchars($tp['product_name'] ?? '') ?></td>
                            <td><?= $tp['min_qty'] ?></td>
                            <td><?= $tp['max_qty'] ?? '-' ?></td>
                            <td><?= rupiah($tp['unit_price']) ?></td>
                            <td><button class="btn btn-sm btn-danger" onclick="deleteTP(<?= $tp['id'] ?>)"><i class="bi bi-trash"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div></div>
        </div>
        <div class="tab-pane fade" id="priceHistory">
            <button class="btn btn-primary btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#phModal"><i class="bi bi-plus"></i> Add Price Record</button>
            <div class="card"><div class="card-body">
                <table class="table table-striped">
                    <thead><tr><th>Supplier</th><th>Product</th><th>Unit Price</th><th>Effective Date</th><th>PO Ref</th></tr></thead>
                    <tbody>
                        <?php foreach ($priceHistory as $ph): ?>
                        <tr>
                            <td><?= htmlspecialchars($ph['supplier_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($ph['product_code'] ?? '') ?> - <?= htmlspecialchars($ph['product_name'] ?? '') ?></td>
                            <td><?= rupiah($ph['unit_price']) ?></td>
                            <td><?= htmlspecialchars($ph['effective_date']) ?></td>
                            <td><?= htmlspecialchars($ph['po_reference'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div></div>
        </div>
    </div>
</div>

<div class="modal fade" id="cpModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Tambah Pelanggan Price</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Customer</label><select class="form-select" id="cpCustomer"><?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="form-label">Product</label><select class="form-select" id="cpProduct"><?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['code']) ?> - <?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="form-label">Custom Price</label><input type="number" class="form-control" id="cpPrice" min="0" required></div>
        <div class="mb-3"><label class="form-label">Min. Jumlah</label><input type="number" class="form-control" id="cpMinQty" value="1" min="1"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" onclick="submitCP()">Simpan</button></div>
</div></div></div>

<div class="modal fade" id="tpModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Add Tier Price</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Product</label><select class="form-select" id="tpProduct"><?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['code']) ?> - <?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="form-label">Min. Jumlah</label><input type="number" class="form-control" id="tpMinQty" min="1" required></div>
        <div class="mb-3"><label class="form-label">Max Qty</label><input type="number" class="form-control" id="tpMaxQty"></div>
        <div class="mb-3"><label class="form-label">Unit Price</label><input type="number" class="form-control" id="tpPrice" min="0" required></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" onclick="submitTP()">Simpan</button></div>
</div></div></div>

<div class="modal fade" id="phModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Add Price History</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Supplier</label><select class="form-select" id="phSupplier"><?php foreach ($suppliers as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="form-label">Product</label><select class="form-select" id="phProduct"><?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['code']) ?> - <?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="form-label">Unit Price</label><input type="number" class="form-control" id="phPrice" min="0" required></div>
        <div class="mb-3"><label class="form-label">Effective Date</label><input type="date" class="form-control" id="phDate" value="<?= date('Y-m-d') ?>"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" onclick="submitPH()">Simpan</button></div>
</div></div></div>

<script>
function submitCP() {
    fetch(API_URL+'?endpoint=customer-prices', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ customer_id: parseInt(document.getElementById('cpCustomer').value), product_id: parseInt(document.getElementById('cpProduct').value), custom_price: parseFloat(document.getElementById('cpPrice').value), min_qty: parseFloat(document.getElementById('cpMinQty').value) }) })
    .then(r=>r.json()).then(res=>{ if(res.success) location.reload(); else alert(res.message); });
}
function deleteCP(id) { if(!confirm('Hapus?'))return; fetch(`${API_URL}?endpoint=customer-prices&id=${id}`,{method:'DELETE'}).then(r=>r.json()).then(res=>{if(res.success)location.reload();}); }
function submitTP() {
    fetch(API_URL+'?endpoint=tier-prices', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ product_id: parseInt(document.getElementById('tpProduct').value), min_qty: parseFloat(document.getElementById('tpMinQty').value), max_qty: parseFloat(document.getElementById('tpMaxQty').value)||null, unit_price: parseFloat(document.getElementById('tpPrice').value) }) })
    .then(r=>r.json()).then(res=>{ if(res.success) location.reload(); else alert(res.message); });
}
function deleteTP(id) { if(!confirm('Hapus?'))return; fetch(`${API_URL}?endpoint=tier-prices&id=${id}`,{method:'DELETE'}).then(r=>r.json()).then(res=>{if(res.success)location.reload();}); }
function submitPH() {
    fetch(API_URL+'?endpoint=supplier-price-history', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ supplier_id: parseInt(document.getElementById('phSupplier').value), product_id: parseInt(document.getElementById('phProduct').value), unit_price: parseFloat(document.getElementById('phPrice').value), effective_date: document.getElementById('phDate').value }) })
    .then(r=>r.json()).then(res=>{ if(res.success) location.reload(); else alert(res.message); });
}
</script>
<?php renderFoot(); ?>
