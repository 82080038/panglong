<?php
require_once 'config.php';

$id = $_GET['id'] ?? 0;
if (!$id) { header('Lokasi: products.php'); exit; }

$d = db();

$stmt = $d->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) { header('Lokasi: products.php?msg=notfound'); exit; }

$units = $d->prepare("SELECT * FROM product_units WHERE product_id = ?");
$units->execute([$id]);
$product['units'] = $units->fetchAll();

$baseUnit = $d->prepare("SELECT * FROM product_units WHERE product_id = ? AND is_base_unit = 1 LIMIT 1");
$baseUnit->execute([$id]);
$bu = $baseUnit->fetch();

$stockQty = $d->query("SELECT COALESCE(SUM(quantity),0) FROM stock_movements WHERE product_id = $id")->fetchColumn();
$stockData = [
    'current_stock' => $stockQty,
    'base_unit' => $bu['unit_name'] ?? 'pcs',
    'status' => 'normal',
];
if ((float)$stockQty <= (float)($product['min_stock'] ?? 0) && (float)($product['min_stock'] ?? 0) > 0) $stockData['status'] = 'low_stock';

renderHead('Product Detail - ' . $product['name']);
renderNav('products');
?>
<div class="container mt-4">
    <a href="products.php" class="btn btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Back</a>
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h4 class="mb-0"><?= htmlspecialchars($product['name']) ?> <small class="text-muted">(<?= htmlspecialchars($product['code']) ?>)</small></h4></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Merek:</strong> <?= htmlspecialchars($product['brand'] ?? '-') ?></p>
                            <p><strong>Kategori:</strong> <?= htmlspecialchars($product['category_name'] ?? '-') ?></p>
                            <p><strong>Lokasi:</strong> <?= htmlspecialchars($product['location'] ?? '-') ?></p>
                            <p><strong>Status:</strong> <span class="badge bg-<?= $product['is_active'] ? 'success' : 'danger' ?>"><?= $product['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Harga Beli:</strong> <?= rupiah($product['buy_price'] ?? 0) ?></p>
                            <p><strong>Harga Jual:</strong> <?= rupiah($product['sell_price'] ?? 0) ?></p>
                            <p><strong>Stok Min:</strong> <?= $product['min_stock'] ?? 0 ?></p>
                            <p><strong>Stok Maks:</strong> <?= $product['max_stock'] ?? 0 ?></p>
                        </div>
                    </div>
                    <?php if (!empty($product['units'])): ?>
                    <hr><h6>Units</h6>
                    <div class="table-responsive"><table class="table table-sm">
                        <thead><tr><th>Satuan</th><th>Konversi</th><th>Harga/Satuan</th><th>Dasar?</th></tr></thead>
                        <tbody>
                            <?php foreach ($product['units'] as $unit): ?>
                            <tr><td><?= htmlspecialchars($unit['unit_name']) ?></td><td><?= $unit['conversion_factor'] ?></td><td><?= rupiah($unit['price_per_unit'] ?? 0) ?></td><td><?= $unit['is_base_unit'] ? 'Yes' : 'No' ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table></div>
                    <?php endif; ?>
                    <?php if (!empty($product['barcodes'])): ?>
                    <hr><h6>Barcode</h6>
                    <ul><?php foreach ($product['barcodes'] as $bc): ?><li><?= htmlspecialchars($bc['barcode']) ?> <?= $bc['is_primary'] ? '<span class="badge bg-primary">Primary</span>' : '' ?></li><?php endforeach; ?></ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body text-center">
                    <h6>Stok Saat Ini</h6>
                    <h3 class="<?= ($stockData['status'] ?? 'normal') === 'low_stock' ? 'text-warning' : (($stockData['status'] ?? 'normal') === 'overstock' ? 'text-info' : 'text-success') ?>">
                        <?= $stockData['current_stock'] ?? 0 ?> <?= $stockData['base_unit'] ?? 'pcs' ?>
                    </h3>
                    <hr>
                    <span class="badge bg-<?= ($stockData['status'] ?? 'normal') === 'low_stock' ? 'warning' : (($stockData['status'] ?? 'normal') === 'overstock' ? 'info' : 'success') ?>">
                        <?= $stockData['status'] === 'normal' ? 'Normal' : ($stockData['status'] === 'low_stock' ? 'Stok Menipis' : 'Stok Habis') ?>
                    </span>
                    <hr>
                    <h6>Nilai Stok</h6>
                    <h4>Rp <?= number_format(($stockData['current_stock'] ?? 0) * ($product['buy_price'] ?? 0), 0) ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>
<?php renderFoot(); ?>
