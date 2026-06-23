<?php
require_once __DIR__ . '/config.php';

$search = $_GET['search'] ?? '';
$productsEndpoint = '/products?per_page=50';
if ($search) $productsEndpoint .= '&search=' . urlencode($search);
$products = apiCall($productsEndpoint);
$categories = apiCall('/categories');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $units = [];
        if (!empty($_POST['unit_name'][0])) {
            foreach ($_POST['unit_name'] as $i => $unitName) {
                $units[] = [
                    'unit_name' => $unitName,
                    'conversion_factor' => $_POST['conversion_factor'][$i] ?? 1,
                    'is_base_unit' => $i === 0,
                    'price_per_unit' => $_POST['unit_price'][$i] ?? 0,
                ];
            }
        }
        $data = [
            'code' => $_POST['code'],
            'name' => $_POST['name'],
            'alias' => $_POST['alias'] ?? null,
            'category_id' => $_POST['category_id'] ?? null,
            'brand' => $_POST['brand'] ?? null,
            'min_stock' => $_POST['min_stock'] ?? 0,
            'max_stock' => $_POST['max_stock'] ?? 0,
            'buy_price' => $_POST['buy_price'] ?? 0,
            'sell_price' => $_POST['sell_price'] ?? 0,
            'is_active' => true,
            'units' => $units,
        ];
        $result = apiCall('/products', 'POST', $data);
        if ($result['code'] === 201) {
            header('Location: products.php?msg=created');
            exit;
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'];
        $data = [
            'name' => $_POST['name'],
            'category_id' => $_POST['category_id'] ?? null,
            'brand' => $_POST['brand'] ?? null,
            'min_stock' => $_POST['min_stock'] ?? 0,
            'max_stock' => $_POST['max_stock'] ?? 0,
            'buy_price' => $_POST['buy_price'] ?? 0,
            'sell_price' => $_POST['sell_price'] ?? 0,
            'is_active' => isset($_POST['is_active']),
        ];
        $result = apiCall('/products/' . $id, 'PUT', $data);
        header('Location: products.php?msg=updated');
        exit;
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        $result = apiCall('/products/' . $id, 'DELETE');
        header('Location: products.php?msg=deleted');
        exit;
    }
}

$msg = $_GET['msg'] ?? '';
?>
<?php renderHead('Products - Panglong ERP'); ?>
<?php renderNav('products'); ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Products</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus"></i> Add Product
        </button>
    </div>

    <?php if ($msg === 'created'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            Product created successfully. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($msg === 'updated'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            Product updated successfully. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($msg === 'deleted'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            Product deleted successfully. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-8"><input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, code, or brand..."></div>
                <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Search</button></div>
                <div class="col-md-2"><a href="products.php" class="btn btn-outline-secondary w-100">Clear</a></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped" id="productsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Category</th>
                        <th>Buy Price</th>
                        <th>Sell Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($products['body']['data']) && is_array($products['body']['data'])): ?>
                        <?php foreach ($products['body']['data'] as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['id']); ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['code']); ?></td>
                                <td><?php echo htmlspecialchars($product['category']['name'] ?? 'N/A'); ?></td>
                                <td>Rp <?php echo number_format($product['buy_price'] ?? 0, 0, ',', '.'); ?></td>
                                <td>Rp <?php echo number_format($product['sell_price'] ?? 0, 0, ',', '.'); ?></td>
                                <td>
                                    <a href="product_detail.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                                    <button class="btn btn-sm btn-warning" onclick='editProduct(<?= json_encode($product) ?>)'><i class="bi bi-pencil"></i></button>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this product?')">
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
                <h5 class="modal-title">Add Product</h5>
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
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">Select Category</option>
                                <?php if (isset($categories['body']['data'])): ?>
                                    <?php foreach ($categories['body']['data'] as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Brand</label>
                            <input type="text" name="brand" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Buy Price</label>
                            <input type="number" name="buy_price" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Sell Price</label>
                            <input type="number" name="sell_price" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Min Stock</label>
                            <input type="number" name="min_stock" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Max Stock</label>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
            <div class="modal-header"><h5 class="modal-title">Edit Product</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="products.php">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editId">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Code</label><input type="text" class="form-control" id="editCode" readonly></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Name *</label><input type="text" name="name" id="editName" class="form-control" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Category</label><select name="category_id" id="editCategory" class="form-select"><option value="">Select Category</option><?php if (isset($categories['body']['data'])): foreach ($categories['body']['data'] as $cat): ?><option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option><?php endforeach; endif; ?></select></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Brand</label><input type="text" name="brand" id="editBrand" class="form-control"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Buy Price</label><input type="number" name="buy_price" id="editBuyPrice" class="form-control" value="0" min="0"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Sell Price</label><input type="number" name="sell_price" id="editSellPrice" class="form-control" value="0" min="0"></div>
                        <div class="col-md-2 mb-3"><label class="form-label">Min Stock</label><input type="number" name="min_stock" id="editMinStock" class="form-control" value="0" min="0"></div>
                        <div class="col-md-2 mb-3"><label class="form-label">Max Stock</label><input type="number" name="max_stock" id="editMaxStock" class="form-control" value="0" min="0"></div>
                    </div>
                    <div class="mb-3"><div class="form-check"><input type="checkbox" name="is_active" class="form-check-input" id="editActive"><label class="form-check-label" for="editActive">Active</label></div></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Update Product</button></div>
            </form>
        </div>
    </div>
</div>

<script>
function editProduct(p) {
    document.getElementById('editId').value = p.id;
    document.getElementById('editCode').value = p.code;
    document.getElementById('editName').value = p.name;
    document.getElementById('editBrand').value = p.brand || '';
    document.getElementById('editBuyPrice').value = p.buy_price || 0;
    document.getElementById('editSellPrice').value = p.sell_price || 0;
    document.getElementById('editMinStock').value = p.min_stock || 0;
    document.getElementById('editMaxStock').value = p.max_stock || 0;
    document.getElementById('editActive').checked = p.is_active !== false;
    if (p.category_id) document.getElementById('editCategory').value = p.category_id;
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
