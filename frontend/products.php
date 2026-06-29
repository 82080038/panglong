<?php
require_once __DIR__ . '/config.php';
requirePermission('manage_products');

$d = db();
$user = currentUser();
$tenantId = $user['tenant_id'] ?? null;
$isSuperAdmin = $user['role_slug'] === 'super_admin';

// Fetch warehouse locations for dropdown
$whParams = [];
$whSql = "SELECT id, code, name FROM warehouse_locations WHERE is_active = 1";
if (!$isSuperAdmin && $tenantId) {
    $whSql .= " AND tenant_id = ?";
    $whParams[] = $tenantId;
}
$whSql .= " ORDER BY code";
$whStmt = $d->prepare($whSql);
$whStmt->execute($whParams);
$warehouseLocations = $whStmt->fetchAll();

// Fetch brands for dropdown (from distinct values in products table)
$brandSql = "SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != ''";
$brandParams = [];
if (!$isSuperAdmin && $tenantId) {
    $brandSql .= " AND (tenant_id = ? OR tenant_id IS NULL)";
    $brandParams[] = $tenantId;
}
$brandSql .= " ORDER BY brand";
$brandStmt = $d->prepare($brandSql);
$brandStmt->execute($brandParams);
$brands = $brandStmt->fetchAll();

// Fetch unit measurements for dropdown
$unitParams = [];
$unitSql = "SELECT code, name FROM unit_measurements WHERE is_active = 1";
if (!$isSuperAdmin && $tenantId) {
    $unitSql .= " AND (tenant_id = ? OR tenant_id IS NULL)";
    $unitParams[] = $tenantId;
}
$unitSql .= " ORDER BY name";
$unitStmt = $d->prepare($unitSql);
$unitStmt->execute($unitParams);
$unitMeasurements = $unitStmt->fetchAll();

$search = $_GET['search'] ?? '';
$searchSql = '';
$searchParams = [];
if (!$isSuperAdmin && $tenantId) {
    $searchSql = "WHERE (p.tenant_id = ? OR p.tenant_id IS NULL)";
    $searchParams = [$tenantId];
    if ($search) {
        $searchSql .= " AND (p.name LIKE ? OR p.code LIKE ? OR p.brand LIKE ?)";
        $q = '%' . $search . '%';
        $searchParams[] = $q;
        $searchParams[] = $q;
        $searchParams[] = $q;
    }
} elseif ($search) {
    $searchSql = "WHERE (p.name LIKE ? OR p.code LIKE ? OR p.brand LIKE ?)";
    $q = '%' . $search . '%';
    $searchParams = [$q, $q, $q];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $now = date('Y-m-d H:i:s');
        // Auto-generate product code if not provided
        $code = $_POST['code'] ?? '';
        if (empty($code)) {
            $code = 'PRD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
        
        // Check for duplicate code (tenant scope)
        $dupCodeParams = [$code];
        $dupCodeSql = "SELECT id, name FROM products WHERE code = ?";
        if (!$isSuperAdmin && $tenantId) {
            $dupCodeSql .= " AND tenant_id = ?";
            $dupCodeParams[] = $tenantId;
        }
        $existingCode = $d->prepare($dupCodeSql);
        $existingCode->execute($dupCodeParams);
        if ($existing = $existingCode->fetch()) {
            header('Location: products.php?error=duplicate_code&existing_name=' . urlencode($existing['name']));
            exit;
        }
        
        // Check for duplicate name (case-insensitive, tenant scope)
        $dupNameParams = [$_POST['name']];
        $dupNameSql = "SELECT id, code FROM products WHERE LOWER(name) = LOWER(?)";
        if (!$isSuperAdmin && $tenantId) {
            $dupNameSql .= " AND tenant_id = ?";
            $dupNameParams[] = $tenantId;
        }
        $existingName = $d->prepare($dupNameSql);
        $existingName->execute($dupNameParams);
        if ($existing = $existingName->fetch()) {
            header('Location: products.php?error=duplicate_name&existing_code=' . urlencode($existing['code']));
            exit;
        }
        
        $stmt = $d->prepare("INSERT INTO products (code, name, alias, category_id, brand, min_stock, max_stock, location, buy_price, sell_price, is_active, created_at, updated_at, weight_kg, length_cm, width_cm, height_cm, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $code, 
            $_POST['name'], 
            $_POST['alias'] ?? null, 
            $_POST['category_id'] ?? null,
            $_POST['brand'] ?? null, 
            $_POST['min_stock'] ?? 0, 
            $_POST['max_stock'] ?? 0,
            $_POST['location'] ?? null,
            $_POST['buy_price'] ?? 0, 
            $_POST['sell_price'] ?? 0, 
            $_POST['is_active'] ?? 1, 
            $now, 
            $now,
            $_POST['weight_kg'] ?? 0,
            $_POST['length_cm'] ?? 0,
            $_POST['width_cm'] ?? 0,
            $_POST['height_cm'] ?? 0,
            $tenantId
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
        header('Location: products.php?msg=created');
        exit;
    } elseif ($action === 'edit') {
        $id = $_POST['id'];
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("UPDATE products SET name=?, alias=?, category_id=?, brand=?, location=?, min_stock=?, max_stock=?, buy_price=?, sell_price=?, is_active=?, weight_kg=?, length_cm=?, width_cm=?, height_cm=?, updated_at=? WHERE id=?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
        $editParams = [
            $_POST['name'], 
            $_POST['alias'] ?? null, 
            $_POST['category_id'] ?? null, 
            $_POST['brand'] ?? null,
            $_POST['location'] ?? null,
            $_POST['min_stock'] ?? 0, 
            $_POST['max_stock'] ?? 0,
            $_POST['buy_price'] ?? 0, 
            $_POST['sell_price'] ?? 0, 
            $_POST['is_active'] ?? 1,
            $_POST['weight_kg'] ?? 0,
            $_POST['length_cm'] ?? 0,
            $_POST['width_cm'] ?? 0,
            $_POST['height_cm'] ?? 0,
            $now, 
            $id
        ];
        if (!$isSuperAdmin && $tenantId) $editParams[] = $tenantId;
        $stmt->execute($editParams);
        header('Location: products.php?msg=updated');
        exit;
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        
        // Check if product has transactions (tenant scope)
        $hasSales = $d->prepare("SELECT COUNT(*) FROM sale_items si JOIN sales s ON si.sale_id = s.id WHERE si.product_id = ?" . ($isSuperAdmin ? "" : " AND s.tenant_id = ?"));
        $hasSales->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
        $salesCount = $hasSales->fetchColumn();
        
        $hasPurchases = $d->prepare("SELECT COUNT(*) FROM purchase_order_items poi JOIN purchase_orders po ON poi.po_id = po.id WHERE poi.product_id = ?" . ($isSuperAdmin ? "" : " AND po.tenant_id = ?"));
        $hasPurchases->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
        $purchasesCount = $hasPurchases->fetchColumn();
        
        if ($salesCount > 0 || $purchasesCount > 0) {
            header('Location: products.php?error=has_transactions&sales=' . $salesCount . '&purchases=' . $purchasesCount);
            exit;
        }
        
        // Soft delete instead of hard delete
        $delParams = [date('Y-m-d H:i:s'), $id];
        $delSql = "UPDATE products SET is_active = 0, updated_at = ? WHERE id = ?";
        if (!$isSuperAdmin && $tenantId) {
            $delSql .= " AND tenant_id = ?";
            $delParams[] = $tenantId;
        }
        $d->prepare($delSql)->execute($delParams);
        header('Location: products.php?msg=deactivated');
        exit;
    }
}

$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $searchSql ORDER BY p.id DESC LIMIT 100";
$stmt = $d->prepare($sql);
$stmt->execute($searchParams);
$products = $stmt->fetchAll();

$catParams = [];
$catSql = "SELECT id, name FROM categories";
if (!$isSuperAdmin && $tenantId) {
    $catSql .= " WHERE tenant_id = ? OR tenant_id IS NULL";
    $catParams[] = $tenantId;
}
$catSql .= " ORDER BY name";
$catStmt = $d->prepare($catSql);
$catStmt->execute($catParams);
$categories = $catStmt->fetchAll();

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';
$existingName = $_GET['existing_name'] ?? '';
$existingCode = $_GET['existing_code'] ?? '';
$salesCount = $_GET['sales'] ?? 0;
$purchasesCount = $_GET['purchases'] ?? 0;
?>
<?php renderHead('Products - Panglong ERP'); ?>
<?php renderNav('products'); ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Produk</h1>
        <div class="d-flex gap-2">
            <?php if (!$isSuperAdmin && $tenantId): ?>
            <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#masterCatalogModal">
                <i class="bi bi-cloud-download"></i> Import dari Master Catalog
            </button>
            <?php endif; ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus"></i> Tambah Produk
            </button>
        </div>
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
    <?php elseif ($msg === 'deactivated'): ?>
        <div class="alert alert-warning alert-dismissible fade show">
            Produk dinonaktifkan Berhasil. Produk tidak akan muncul di pencarian tetapi data tetap tersimpan. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($error === 'duplicate_code'): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Peringatan:</strong> Kode produk sudah digunakan oleh produk lain: <strong><?= htmlspecialchars(urldecode($existingName)) ?></strong>. Silakan gunakan kode yang berbeda. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($error === 'duplicate_name'): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Peringatan:</strong> Nama produk sudah digunakan dengan kode: <strong><?= htmlspecialchars(urldecode($existingCode)) ?></strong>. Silakan gunakan nama yang berbeda atau edit produk yang sudah ada. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($error === 'has_transactions'): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Peringatan:</strong> Produk ini memiliki transaksi (<?= $salesCount ?> penjualan, <?= $purchasesCount ?> pembelian). Produk tidak dapat dihapus. Gunakan fitur "Edit" untuk menonaktifkan produk jika diperlukan. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2" id="searchForm">
                <div class="col-md-8"><input type="text" class="form-control" name="search" id="searchInput" value="<?= htmlspecialchars($search) ?>" placeholder="Cari berdasarkan nama, kode, atau merek..." autocomplete="off"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Search</button></div>
                <div class="col-md-2"><a href="products.php" class="btn btn-outline-secondary w-100">Reset</a></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive"><table class="table table-striped" id="productsTable">
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
            </table></div>
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
                    <!-- Basic Information -->
                    <h6 class="mb-3">Informasi Dasar</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kode Produk (Opsional - Auto-generate jika kosong)</label>
                            <input type="text" name="code" class="form-control" placeholder="PRD-001" id="productCode" oninput="checkSimilarProducts()" onkeydown="handleEnter(event, 'productName')">
                            <div id="similarCodeWarning" class="text-danger small mt-1"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Produk *</label>
                            <input type="text" name="name" class="form-control" required id="productName" oninput="checkSimilarProducts()" onkeydown="handleEnter(event, 'productAlias')">
                            <div id="similarNameWarning" class="text-danger small mt-1"></div>
                        </div>
                    </div>
                    <div id="similarProductsList" class="alert alert-info small" style="display:none;"></div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Alias/Nama Lain</label>
                            <input type="text" name="alias" class="form-control" placeholder="Nama alternatif produk" id="productAlias" onkeydown="handleEnter(event, 'brandSelect')">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Merek/Brand</label>
                            <div class="input-group">
                                <select name="brand" class="form-select" id="brandSelect" onchange="handleEnter(event, 'categorySelect')">
                                    <option value="">Pilih Merek</option>
                                    <?php if (is_array($brands)): ?>
                                        <?php foreach ($brands as $b): ?>
                                            <option value="<?php echo htmlspecialchars($b['brand']); ?>"><?php echo htmlspecialchars($b['brand']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <button type="button" class="btn btn-outline-primary" onclick="openQuickAddModal('brand')"><i class="bi bi-plus"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kategori</label>
                            <div class="input-group">
                                <select name="category_id" class="form-select" id="categorySelect" onchange="handleEnter(event, 'locationSelect')">
                                    <option value="">Pilih Kategori</option>
                                    <?php if (is_array($categories)): ?>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <button type="button" class="btn btn-outline-primary" onclick="openQuickAddModal('category')"><i class="bi bi-plus"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Lokasi Penyimpanan</label>
                            <div class="input-group">
                                <select name="location" class="form-select" id="locationSelect" onchange="handleEnter(event, 'buyPrice')">
                                    <option value="">Pilih Lokasi</option>
                                    <?php if (is_array($warehouseLocations)): ?>
                                        <?php foreach ($warehouseLocations as $loc): ?>
                                            <option value="<?php echo $loc['code']; ?>"><?php echo htmlspecialchars($loc['code'] . ' - ' . $loc['name']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <button type="button" class="btn btn-outline-primary" onclick="openQuickAddModal('location')"><i class="bi bi-plus"></i></button>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Pricing -->
                    <h6 class="mb-3">Harga</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Harga Beli (Rp)</label>
                            <input type="number" name="buy_price" class="form-control" value="0" min="0" step="0.01" id="buyPrice" onkeydown="handleEnter(event, 'sellPrice')">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Harga Jual (Rp)</label>
                            <input type="number" name="sell_price" class="form-control" value="0" min="0" step="0.01" id="sellPrice" onkeydown="handleEnter(event, 'minStock')">
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Stock Settings -->
                    <h6 class="mb-3">Pengaturan Stok</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Stok Minimum</label>
                            <input type="number" name="min_stock" class="form-control" value="0" min="0" id="minStock" onkeydown="handleEnter(event, 'maxStock')">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Stok Maksimum</label>
                            <input type="number" name="max_stock" class="form-control" value="0" min="0" id="maxStock" onkeydown="handleEnter(event, 'isActive')">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select" id="isActive" onchange="handleEnter(event, 'weightKg')">
                                <option value="1">Aktif</option>
                                <option value="0">Tidak Aktif</option>
                            </select>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Dimensions -->
                    <h6 class="mb-3">Dimensi & Berat</h6>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Berat (kg)</label>
                            <input type="number" name="weight_kg" class="form-control" value="0" min="0" step="0.01" id="weightKg" onkeydown="handleEnter(event, 'lengthCm')">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Panjang (cm)</label>
                            <input type="number" name="length_cm" class="form-control" value="0" min="0" step="0.1" id="lengthCm" onkeydown="handleEnter(event, 'widthCm')">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Lebar (cm)</label>
                            <input type="number" name="width_cm" class="form-control" value="0" min="0" step="0.1" id="widthCm" onkeydown="handleEnter(event, 'heightCm')">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tinggi (cm)</label>
                            <input type="number" name="height_cm" class="form-control" value="0" min="0" step="0.1" id="heightCm" onkeydown="handleEnter(event, 'submitBtn')">
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Units -->
                    <h6 class="mb-3">Satuan Produk</h6>
                    <div id="unitsContainer">
                        <div class="row unit-row mb-2">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <select name="unit_name[]" class="form-select" required>
                                        <option value="">Pilih Satuan</option>
                                        <?php if (is_array($unitMeasurements)): ?>
                                            <?php foreach ($unitMeasurements as $um): ?>
                                                <option value="<?php echo htmlspecialchars($um['code']); ?>"><?php echo htmlspecialchars($um['name']); ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" onclick="openQuickAddModal('unit')"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="conversion_factor[]" class="form-control" placeholder="Faktor" value="1" step="0.001" title="Faktor konversi ke satuan dasar">
                            </div>
                            <div class="col-md-4">
                                <input type="number" name="unit_price[]" class="form-control" placeholder="Harga per satuan" value="0" min="0" step="0.01">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-unit" title="Hapus satuan"><i class="bi bi-dash"></i></button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addUnitBtn"><i class="bi bi-plus"></i> Tambah Satuan</button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn"><i class="bi bi-save"></i> Simpan Produk</button>
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
                    <!-- Basic Information -->
                    <h6 class="mb-3">Informasi Dasar</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Kode Produk</label><input type="text" class="form-control" id="editCode" readonly></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Nama Produk *</label><input type="text" name="name" id="editName" class="form-control" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Alias/Nama Lain</label><input type="text" name="alias" id="editAlias" class="form-control"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Merek/Brand</label><select name="brand" id="editMerek" class="form-select"><option value="">Pilih Merek</option><?php if (is_array($brands)): foreach ($brands as $b): ?><option value="<?php echo htmlspecialchars($b['brand']); ?>"><?php echo htmlspecialchars($b['brand']); ?></option><?php endforeach; endif; ?></select></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Kategori</label><select name="category_id" id="editKategori" class="form-select"><option value="">Pilih Kategori</option><?php if (is_array($categories)): foreach ($categories as $cat): ?><option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option><?php endforeach; endif; ?></select></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Lokasi Penyimpanan</label><select name="location" id="editLocation" class="form-select"><option value="">Pilih Lokasi</option><?php if (is_array($warehouseLocations)): foreach ($warehouseLocations as $loc): ?><option value="<?php echo $loc['code']; ?>"><?php echo htmlspecialchars($loc['code'] . ' - ' . $loc['name']); ?></option><?php endforeach; endif; ?></select></div>
                    </div>
                    
                    <hr>
                    
                    <!-- Pricing -->
                    <h6 class="mb-3">Harga</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Harga Beli (Rp)</label><input type="number" name="buy_price" id="editBuyPrice" class="form-control" value="0" min="0" step="0.01"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Harga Jual (Rp)</label><input type="number" name="sell_price" id="editSellPrice" class="form-control" value="0" min="0" step="0.01"></div>
                    </div>
                    
                    <hr>
                    
                    <!-- Stock Settings -->
                    <h6 class="mb-3">Pengaturan Stok</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Stok Minimum</label><input type="number" name="min_stock" id="editMinStock" class="form-control" value="0" min="0"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Stok Maksimum</label><input type="number" name="max_stock" id="editMaxStock" class="form-control" value="0" min="0"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Status</label><select name="is_active" id="editActive" class="form-select"><option value="1">Aktif</option><option value="0">Tidak Aktif</option></select></div>
                    </div>
                    
                    <hr>
                    
                    <!-- Dimensions -->
                    <h6 class="mb-3">Dimensi & Berat</h6>
                    <div class="row">
                        <div class="col-md-3 mb-3"><label class="form-label">Berat (kg)</label><input type="number" name="weight_kg" id="editWeight" class="form-control" value="0" min="0" step="0.01"></div>
                        <div class="col-md-3 mb-3"><label class="form-label">Panjang (cm)</label><input type="number" name="length_cm" id="editLength" class="form-control" value="0" min="0" step="0.1"></div>
                        <div class="col-md-3 mb-3"><label class="form-label">Lebar (cm)</label><input type="number" name="width_cm" id="editWidth" class="form-control" value="0" min="0" step="0.1"></div>
                        <div class="col-md-3 mb-3"><label class="form-label">Tinggi (cm)</label><input type="number" name="height_cm" id="editHeight" class="form-control" value="0" min="0" step="0.1"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Quick Add Modal -->
<div class="modal fade" id="quickAddModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickAddModalTitle">Tambah Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" id="quickAddLabel">Nama</label>
                    <input type="text" class="form-control" id="quickAddInput" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="submitQuickAdd()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<script>
function editProduct(p) {
    document.getElementById('editId').value = p.id;
    document.getElementById('editCode').value = p.code;
    document.getElementById('editName').value = p.name;
    document.getElementById('editAlias').value = p.alias || '';
    document.getElementById('editMerek').value = p.brand || '';
    document.getElementById('editLocation').value = p.location || '';
    document.getElementById('editBuyPrice').value = p.buy_price || 0;
    document.getElementById('editSellPrice').value = p.sell_price || 0;
    document.getElementById('editMinStock').value = p.min_stock || 0;
    document.getElementById('editMaxStock').value = p.max_stock || 0;
    document.getElementById('editActive').value = p.is_active !== false ? '1' : '0';
    document.getElementById('editWeight').value = p.weight_kg || 0;
    document.getElementById('editLength').value = p.length_cm || 0;
    document.getElementById('editWidth').value = p.width_cm || 0;
    document.getElementById('editHeight').value = p.height_cm || 0;
    if (p.category_id) document.getElementById('editKategori').value = p.category_id;
    if (p.location) document.getElementById('editLocation').value = p.location;
    if (p.brand) document.getElementById('editMerek').value = p.brand;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

$(document).ready(function() {
    // Build unit options for JavaScript
    var unitOptions = '<?php if (is_array($unitMeasurements)): foreach ($unitMeasurements as $um): ?><option value="<?php echo htmlspecialchars($um['code']); ?>"><?php echo htmlspecialchars($um['name']); ?></option><?php endforeach; endif; ?>';

    $('#addUnitBtn').click(function() {
        var row = '<div class="row unit-row mb-2">' +
            '<div class="col-md-4"><select name="unit_name[]" class="form-select" required><option value="">Pilih Satuan</option>' + unitOptions + '</select></div>' +
            '<div class="col-md-3"><input type="number" name="conversion_factor[]" class="form-control" placeholder="Faktor" value="1" step="0.001"></div>' +
            '<div class="col-md-4"><input type="number" name="unit_price[]" class="form-control" placeholder="Harga" value="0" min="0" step="0.01"></div>' +
            '<div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-unit"><i class="bi bi-dash"></i></button></div>' +
            '</div>';
        $('#unitsContainer').append(row);
    });
    $(document).on('click', '.remove-unit', function() {
        if ($('.unit-row').length > 1) {
            $(this).closest('.unit-row').remove();
        }
    });

    // Live search with debounce
    var searchTimeout;
    $('#searchInput').on('input', function() {
        var searchValue = $(this).val();
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            if (searchValue.length >= 2 || searchValue.length === 0) {
                $('#searchForm').submit();
            }
        }, 500);
    });

    // Check for similar products as user types
    var checkTimeout;
    function checkSimilarProducts() {
        var code = $('#productCode').val();
        var name = $('#productName').val();
        
        clearTimeout(checkTimeout);
        checkTimeout = setTimeout(function() {
            if (code.length >= 2 || name.length >= 2) {
                fetch(API_URL + '?endpoint=products&search=' + encodeURIComponent(code || name))
                    .then(r => r.json())
                    .then(res => {
                        if (res.success && res.data.length > 0) {
                            var similarHtml = '<strong>Produk serupa ditemukan:</strong><ul class="mb-0 mt-2">';
                            res.data.slice(0, 5).forEach(p => {
                                similarHtml += '<li><strong>' + p.code + '</strong> - ' + p.name + ' (Rp ' + Number(p.sell_price).toLocaleString() + ')</li>';
                            });
                            similarHtml += '</ul>';
                            $('#similarProductsList').html(similarHtml).show();
                            
                            // Check for exact matches
                            var exactCodeMatch = res.data.find(p => p.code.toLowerCase() === code.toLowerCase());
                            var exactNameMatch = res.data.find(p => p.name.toLowerCase() === name.toLowerCase());
                            
                            if (exactCodeMatch) {
                                $('#similarCodeWarning').text('Kode ini sudah digunakan!').show();
                            } else {
                                $('#similarCodeWarning').text('').hide();
                            }
                            
                            if (exactNameMatch) {
                                $('#similarNameWarning').text('Nama ini sudah digunakan!').show();
                            } else {
                                $('#similarNameWarning').text('').hide();
                            }
                        } else {
                            $('#similarProductsList').hide();
                            $('#similarCodeWarning').text('').hide();
                            $('#similarNameWarning').text('').hide();
                        }
                    });
            }
        }, 300);
    }
});

// Quick add functionality (global scope)
var currentQuickAddType = '';

function openQuickAddModal(type) {
    currentQuickAddType = type;
    var title, label;
    
    if (type === 'brand') {
        title = 'Tambah Merek Baru';
        label = 'Nama Merek';
    } else if (type === 'category') {
        title = 'Tambah Kategori Baru';
        label = 'Nama Kategori';
    } else if (type === 'location') {
        title = 'Tambah Lokasi Baru';
        label = 'Kode Lokasi';
    } else if (type === 'unit') {
        title = 'Tambah Satuan Baru';
        label = 'Kode Satuan';
    }
    
    document.getElementById('quickAddModalTitle').textContent = title;
    document.getElementById('quickAddLabel').textContent = label;
    document.getElementById('quickAddInput').value = '';
    
    new bootstrap.Modal(document.getElementById('quickAddModal')).show();
}

function submitQuickAdd() {
    var value = document.getElementById('quickAddInput').value.trim();
    if (!value) {
        alert('Nama tidak boleh kosong');
        return;
    }
    
    var endpoint, data;
    
    if (currentQuickAddType === 'brand') {
        endpoint = 'brands';
        data = { name: value };
    } else if (currentQuickAddType === 'category') {
        endpoint = 'categories';
        data = { name: value };
    } else if (currentQuickAddType === 'location') {
        endpoint = 'warehouse-locations';
        // For quick-add, use default warehouse and minimal required fields
        data = {
            warehouse_id: 1, // Default warehouse - may need to be dynamic
            code: value,
            name: value,
            zone_type: 'storage'
        };
    } else if (currentQuickAddType === 'unit') {
        endpoint = 'unit-measurements';
        data = {
            code: value,
            name: value
        };
    }
    
    fetch(API_URL + '?endpoint=' + endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            // Add new option to the dropdown
            var selectId, option;
            
            if (currentQuickAddType === 'brand') {
                selectId = 'brandSelect';
                option = document.createElement('option');
                option.value = res.data.name;
                option.textContent = res.data.name;
            } else if (currentQuickAddType === 'category') {
                selectId = 'categorySelect';
                option = document.createElement('option');
                option.value = res.data.id;
                option.textContent = res.data.name;
            } else if (currentQuickAddType === 'location') {
                selectId = 'locationSelect';
                option = document.createElement('option');
                option.value = res.data.code;
                option.textContent = res.data.code + ' - ' + res.data.name;
            } else if (currentQuickAddType === 'unit') {
                // For units, update all unit dropdowns in the form
                var unitSelects = document.querySelectorAll('select[name="unit_name[]"]');
                unitSelects.forEach(function(select) {
                    var opt = document.createElement('option');
                    opt.value = res.data.code;
                    opt.textContent = res.data.name;
                    select.appendChild(opt);
                });
                // Focus on the first unit select
                if (unitSelects.length > 0) {
                    unitSelects[0].value = res.data.code;
                    unitSelects[0].focus();
                }
                
                bootstrap.Modal.getInstance(document.getElementById('quickAddModal')).hide();
                alert('Berhasil ditambahkan');
                return;
            }
            
            var select = document.getElementById(selectId);
            select.appendChild(option);
            select.value = option.value;
            
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('quickAddModal')).hide();
            alert('Berhasil ditambahkan');
            
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
var addModal = document.getElementById('addModal');
if (addModal) {
    addModal.addEventListener('shown.bs.modal', function() {
        document.getElementById('productCode').focus();
    });
}

var editModal = document.getElementById('editModal');
if (editModal) {
    editModal.addEventListener('shown.bs.modal', function() {
        document.getElementById('editCode').focus();
    });
}

// Master Catalog search and import
var masterModal = document.getElementById('masterCatalogModal');
if (masterModal) {
    masterModal.addEventListener('shown.bs.modal', function() {
        loadMasterProducts();
    });
}

function loadMasterProducts(page) {
    page = page || 1;
    var search = document.getElementById('masterSearch').value || '';
    var categoryId = document.getElementById('masterCategoryFilter').value || '';
    var url = API_URL + '?endpoint=master-products&search=' + encodeURIComponent(search) + '&page=' + page + '&per_page=20';
    if (categoryId) url += '&category_id=' + categoryId;

    fetch(url)
        .then(r => r.json())
        .then(res => {
            var tbody = document.getElementById('masterProductsBody');
            tbody.innerHTML = '';
            if (res.data && res.data.length > 0) {
                res.data.forEach(function(p) {
                    var tr = document.createElement('tr');
                    var catName = (p.category && p.category.name) ? p.category.name : '-';
                    tr.innerHTML = '<td>' + (p.code || '-') + '</td>' +
                        '<td>' + (p.name || '-') + '</td>' +
                        '<td>' + (p.brand || '-') + '</td>' +
                        '<td>' + catName + '</td>' +
                        '<td><button class="btn btn-sm btn-success" onclick="importMasterProduct(' + p.id + ', \'' + (p.name || '').replace(/'/g, "\\'") + '\')"><i class="bi bi-plus-circle"></i> Import</button></td>';
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Tidak ada produk di master catalog</td></tr>';
            }

            // Pagination
            var pagDiv = document.getElementById('masterPagination');
            if (res.meta) {
                pagDiv.innerHTML = '';
                for (var i = 1; i <= res.meta.last_page; i++) {
                    var btn = document.createElement('button');
                    btn.className = 'btn btn-sm ' + (i === res.meta.current_page ? 'btn-primary' : 'btn-outline-primary');
                    btn.textContent = i;
                    btn.onclick = function(pg) { return function() { loadMasterProducts(pg); }; }(i);
                    pagDiv.appendChild(btn);
                }
            }
        })
        .catch(err => alert('Error: ' + err));
}

function importMasterProduct(id, name) {
    if (!confirm('Import "' + name + '" ke katalog produk Anda?')) return;
    fetch(API_URL + '?endpoint=master-products', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({master_product_id: id})
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('Produk "' + name + '" berhasil diimport!');
            location.reload();
        } else {
            alert('Gagal: ' + (res.message || 'Unknown error'));
        }
    })
    .catch(err => alert('Error: ' + err));
}
</script>

<!-- Master Catalog Modal -->
<div class="modal fade" id="masterCatalogModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-cloud-download"></i> Master Catalog Panglong</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3 g-2">
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="masterSearch" placeholder="Cari produk..." onkeyup="if(event.key==='Enter')loadMasterProducts(1)">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="masterCategoryFilter" onchange="loadMasterProducts(1)">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-primary w-100" onclick="loadMasterProducts(1)"><i class="bi bi-search"></i> Cari</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Produk</th>
                                <th>Merek</th>
                                <th>Kategori</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="masterProductsBody"></tbody>
                    </table>
                </div>
                <div id="masterPagination" class="d-flex gap-1"></div>
            </div>
        </div>
    </div>
</div>

<?php renderFoot(); ?>
