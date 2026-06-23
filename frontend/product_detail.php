<?php
require_once 'config.php';

$id = $_GET['id'] ?? 0;
if (!$id) { header('Location: products.php'); exit; }

$productResp = apiCall('/products/' . $id);
$product = $productResp['body']['data'] ?? null;
if (!$product) { header('Location: products.php?msg=notfound'); exit; }

$stockResp = apiCall('/stock/' . $id);
$stockData = $stockResp['body']['data'] ?? [];

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
                            <p><strong>Brand:</strong> <?= htmlspecialchars($product['brand'] ?? '-') ?></p>
                            <p><strong>Category:</strong> <?= htmlspecialchars($product['category']['name'] ?? '-') ?></p>
                            <p><strong>Location:</strong> <?= htmlspecialchars($product['location'] ?? '-') ?></p>
                            <p><strong>Status:</strong> <span class="badge bg-<?= $product['is_active'] ? 'success' : 'danger' ?>"><?= $product['is_active'] ? 'Active' : 'Inactive' ?></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Buy Price:</strong> Rp <?= number_format($product['buy_price'] ?? 0, 0) ?></p>
                            <p><strong>Sell Price:</strong> Rp <?= number_format($product['sell_price'] ?? 0, 0) ?></p>
                            <p><strong>Min Stock:</strong> <?= $product['min_stock'] ?? 0 ?></p>
                            <p><strong>Max Stock:</strong> <?= $product['max_stock'] ?? 0 ?></p>
                        </div>
                    </div>
                    <?php if (!empty($product['units'])): ?>
                    <hr><h6>Units</h6>
                    <table class="table table-sm">
                        <thead><tr><th>Unit</th><th>Conversion</th><th>Price/Unit</th><th>Base?</th></tr></thead>
                        <tbody>
                            <?php foreach ($product['units'] as $unit): ?>
                            <tr><td><?= htmlspecialchars($unit['unit_name']) ?></td><td><?= $unit['conversion_factor'] ?></td><td>Rp <?= number_format($unit['price_per_unit'] ?? 0, 0) ?></td><td><?= $unit['is_base_unit'] ? 'Yes' : 'No' ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                    <?php if (!empty($product['barcodes'])): ?>
                    <hr><h6>Barcodes</h6>
                    <ul><?php foreach ($product['barcodes'] as $bc): ?><li><?= htmlspecialchars($bc['barcode']) ?> <?= $bc['is_primary'] ? '<span class="badge bg-primary">Primary</span>' : '' ?></li><?php endforeach; ?></ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body text-center">
                    <h6>Current Stock</h6>
                    <h3 class="<?= ($stockData['status'] ?? 'normal') === 'low_stock' ? 'text-warning' : (($stockData['status'] ?? 'normal') === 'overstock' ? 'text-info' : 'text-success') ?>">
                        <?= $stockData['current_stock'] ?? 0 ?> <?= $stockData['base_unit'] ?? 'pcs' ?>
                    </h3>
                    <hr>
                    <span class="badge bg-<?= ($stockData['status'] ?? 'normal') === 'low_stock' ? 'warning' : (($stockData['status'] ?? 'normal') === 'overstock' ? 'info' : 'success') ?>">
                        <?= ucfirst(str_replace('_', ' ', $stockData['status'] ?? 'normal')) ?>
                    </span>
                    <hr>
                    <h6>Stock Value</h6>
                    <h4>Rp <?= number_format(($stockData['current_stock'] ?? 0) * ($product['buy_price'] ?? 0), 0) ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>
<?php renderFoot(); ?>
