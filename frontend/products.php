<?php
require_once __DIR__ . '/config.php';

$d = db();

$search = $_GET['search'] ?? '';
$searchSql = '';
$searchParams = [];
if ($search) {
    $searchSql = "WHERE p.name LIKE ? OR p.code LIKE ? OR p.brand LIKE ?";
    $q = '%' . $search . '%';
    $searchParams = [$q, $q, $q];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO products (code, name, alias, category_id, brand, min_stock, max_stock, location, buy_price, sell_price, is_active, created_at, updated_at, weight_kg, length_cm, width_cm, height_cm) VALUES (?,?,?,?,?,?,?,?,'',?,?,1,?,?,0,0,0)");
        $stmt->execute([
            $_POST['code'], $_POST['name'], $_POST['alias'] ?? null, $_POST['category_id'] ?? null,
            $_POST['brand'] ?? null, $_POST['min_stock'] ?? 0, $_POST['max_stock'] ?? 0,
            $_POST['buy_price'] ?? 0, $_POST['sell_price'] ?? 0, $now, $now
        ]);
        $pid = $d->lastInsertId();
        if (!empty($_POST['unit_name'][0])) {
            foreach ($_POST['unit_name'] as $i => $unitName) {
                $stmt = $d->prepare("INSERT INTO product_units (product_id, unit_name, conversion_factor, is_base_unit, price_per_unit, created_at, updated_at) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([
                    $pid, $unitName,
                    $_POST['conversion_factor'][$i] ?? 1,
                    $i === 0 ? 1 : 0,
                    $_POST['unit_price'][$i] ?? 0,
                    $now, $now
                ]);
            }
        }
        header('Lokasi: products.php?msg=created');
        exit;
    } elseif ($action === 'edit') {
        $id = $_POST['id'];
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("UPDATE products SET name=?, category_id=?, brand=?, min_stock=?, max_stock=?, buy_price=?, sell_price=?, is_active=?, updated_at=? WHERE id=?");
        $stmt->execute([
            $_POST['name'], $_POST['category_id'] ?? null, $_POST['brand'] ?? null,
            $_POST['min_stock'] ?? 0, $_POST['max_stock'] ?? 0,
            $_POST['buy_price'] ?? 0, $_POST['sell_price'] ?? 0,
            isset($_POST['is_active']) ? 1 : 0, $now, $id
        ]);
        header('Lokasi: products.php?msg=updated');
        exit;
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        $d->prepare("DELETE FROM product_units WHERE product_id = ?")->execute([$id]);
        $d->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        header('Lokasi: products.php?msg=deleted');
        exit;
    }
}

$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $searchSql ORDER BY p.id DESC LIMIT 100";
$stmt = $d->prepare($sql);
$stmt->execute($searchParams);
$products = $stmt->fetchAll();

$categories = $d->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

$msg = $_GET['msg'] ?? '';
?>
<?php renderHead('Products - Panglong ERP'); ?>
<?php renderNav('products'); ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Produk</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus"></i> Tambah Produk
        </button>
    </div>

    <?php if ($msg === 'created'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            Produk dibuat Berhasil. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($msg === 'updated'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            Produk diperbarui Berhasil. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($msg === 'deleted'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            Produk dihapus Berhasil. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-8"><input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari berdasarkan nama, kode, atau merek..."></div>
                <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Search</button></div>
                <div class="col-md-2"><a href="products.php" class="btn btn-outline-secondary w-100">Reset</a></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped" id="productsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Kode</th>
                        <th>Kategori</th>
                        <th>Harga Beli</th>
                        <th>Harga Jual</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (is_array($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['id']); ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['code']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                <td><?php echo rupiah($product['buy_price'] ?? 0) ?></td>
                                <td><?php echo rupiah($product['sell_price'] ?? 0) ?></td>
                                <td>
                                    <a href="product_detail.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                                    <button class="btn btn-sm btn-warning" onclick='editProduct(<?= json_encode($product) ?>)'><i class="bi bi-pencil"></i></button>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Hapus this product?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">No products found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="products.php">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Code *</label>
                            <input type="text" name="code" class="form-control" required placeholder="PRD-001">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kategori</label>
                            <select name="category_id" class="form-select">
                                <option value="">Select Kategori</option>
                                <?php if (is_array($categories)): ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Merek</label>
                            <input type="text" name="brand" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Harga Beli</label>
                            <input type="number" name="buy_price" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Harga Jual</label>
                            <input type="number" name="sell_price" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Stok Min</label>
                            <input type="number" name="min_stock" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Stok Maks</label>
                            <input type="number" name="max_stock" class="form-control" value="0" min="0">
                        </div>
                    </div>
                    <hr>
                    <h6>Units</h6>
                    <div id="unitsContainer">
                        <div class="row unit-row">
                            <div class="col-md-4 mb-2">
                                <input type="text" name="unit_name[]" class="form-control" placeholder="Unit name (e.g. pcs)" required>
                            </div>
                            <div class="col-md-3 mb-2">
                                <input type="number" name="conversion_factor[]" class="form-control" placeholder="Factor" value="1" step="0.001">
                            </div>
                            <div class="col-md-4 mb-2">
                                <input type="number" name="unit_price[]" class="form-control" placeholder="Price per unit" value="0" min="0">
                            </div>
                            <div class="col-md-1 mb-2">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-unit"><i class="bi bi-dash"></i></button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addUnitBtn"><i class="bi bi-plus"></i> Add Unit</button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Produk</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="products.php">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editId">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Kode</label><input type="text" class="form-control" id="editCode" readonly></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Name *</label><input type="text" name="name" id="editName" class="form-control" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Kategori</label><select name="category_id" id="editKategori" class="form-select"><option value="">Select Kategori</option><?php if (is_array($categories)): foreach ($categories as $cat): ?><option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option><?php endforeach; endif; ?></select></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Merek</label><input type="text" name="brand" id="editMerek" class="form-control"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Harga Beli</label><input type="number" name="buy_price" id="editBuyPrice" class="form-control" value="0" min="0"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Harga Jual</label><input type="number" name="sell_price" id="editSellPrice" class="form-control" value="0" min="0"></div>
                        <div class="col-md-2 mb-3"><label class="form-label">Stok Min</label><input type="number" name="min_stock" id="editMinStock" class="form-control" value="0" min="0"></div>
                        <div class="col-md-2 mb-3"><label class="form-label">Stok Maks</label><input type="number" name="max_stock" id="editMaxStock" class="form-control" value="0" min="0"></div>
                    </div>
                    <div class="mb-3"><div class="form-check"><input type="checkbox" name="is_active" class="form-check-input" id="editActive"><label class="form-check-label" for="editActive">Aktif</label></div></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Update Product</button></div>
            </form>
        </div>
    </div>
</div>

<script>
function editProduct(p) {
    document.getElementById('editId').value = p.id;
    document.getElementById('editCode').value = p.code;
    document.getElementById('editName').value = p.name;
    document.getElementById('editMerek').value = p.brand || '';
    document.getElementById('editBuyPrice').value = p.buy_price || 0;
    document.getElementById('editSellPrice').value = p.sell_price || 0;
    document.getElementById('editMinStock').value = p.min_stock || 0;
    document.getElementById('editMaxStock').value = p.max_stock || 0;
    document.getElementById('editActive').checked = p.is_active !== false;
    if (p.category_id) document.getElementById('editKategori').value = p.category_id;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

$(document).ready(function() {
    $('#addUnitBtn').click(function() {
        var row = '<div class="row unit-row mb-1">' +
            '<div class="col-md-4"><input type="text" name="unit_name[]" class="form-control" placeholder="Unit name" required></div>' +
            '<div class="col-md-3"><input type="number" name="conversion_factor[]" class="form-control" placeholder="Factor" value="1" step="0.001"></div>' +
            '<div class="col-md-4"><input type="number" name="unit_price[]" class="form-control" placeholder="Price" value="0" min="0"></div>' +
            '<div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-unit"><i class="bi bi-dash"></i></button></div>' +
            '</div>';
        $('#unitsContainer').append(row);
    });
    $(document).on('click', '.remove-unit', function() {
        if ($('.unit-row').length > 1) {
            $(this).closest('.unit-row').remove();
        }
    });
});
</script>
<?php renderFoot(); ?>
