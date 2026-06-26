<?php
require_once __DIR__ . '/config.php';

$d = db();
$user = currentUser();
$tenantId = $user['tenant_id'] ?? null;
$isSuperAdmin = $user['role_slug'] === 'super_admin';

$search = $_GET['search'] ?? '';
$searchSql = '';
$searchParams = [];
if ($search) {
    $searchSql = "WHERE (name LIKE ? OR phone LIKE ?)";
    $q = '%' . $search . '%';
    $searchParams = [$q, $q];
    if (!$isSuperAdmin && $tenantId) {
        $searchSql .= " AND tenant_id = $tenantId";
    }
} else {
    if (!$isSuperAdmin && $tenantId) {
        $searchSql = "WHERE tenant_id = $tenantId";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO suppliers (name, address, phone, email, payment_terms, credit_limit, is_active, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $_POST['name'], $_POST['address'] ?? null, $_POST['phone'] ?? null,
            $_POST['email'] ?? null, $_POST['payment_terms'] ?? 30,
            $_POST['credit_limit'] ?? 0, $_POST['is_active'] ?? 1, $now, $now, $tenantId
        ]);
        header('Location: suppliers.php?msg=created');
        exit;
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        $d->prepare("DELETE FROM suppliers WHERE id = ?")->execute([$id]);
        header('Location: suppliers.php?msg=deleted');
        exit;
    }
}

$sql = "SELECT * FROM suppliers $searchSql ORDER BY id DESC LIMIT 100";
$stmt = $d->prepare($sql);
$stmt->execute($searchParams);
$suppliers = $stmt->fetchAll();

$msg = $_GET['msg'] ?? '';
?>
<?php renderHead('Suppliers - Panglong ERP'); ?>
<?php renderNav('suppliers'); ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Supplier</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus"></i> Tambah Supplier</button>
    </div>
    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show">Supplier <?php echo htmlspecialchars($msg); ?>. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="row g-2" id="searchForm">
            <div class="col-md-8"><input type="text" class="form-control" name="search" id="searchInput" value="<?= htmlspecialchars($search) ?>" placeholder="Cari supplier..." autocomplete="off"></div>
            <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100">Cari</button></div>
            <div class="col-md-2"><a href="suppliers.php" class="btn btn-outline-secondary w-100">Reset</a></div>
        </form>
    </div></div>
    <div class="card"><div class="card-body">
        <div class="table-responsive"><table class="table table-striped">
            <thead><tr><th>ID</th><th>Nama</th><th>Telepon</th><th>Email</th><th>Terms</th><th>Credit Limit</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php if (is_array($suppliers)): ?>
                    <?php foreach ($suppliers as $s): ?>
                        <tr>
                            <td><?php echo $s['id']; ?></td>
                            <td><?php echo htmlspecialchars($s['name']); ?></td>
                            <td><?php echo htmlspecialchars($s['phone'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($s['email'] ?? '-'); ?></td>
                            <td><?php echo $s['payment_terms'] ?? 30; ?> days</td>
                            <td><?php echo rupiah($s['credit_limit'] ?? 0) ?></td>
                            <td>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus?')">
                                    <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                    <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">No suppliers found</td></tr>
                <?php endif; ?>
            </tbody>
        </table></div>
    </div></div>
</div>

<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="suppliers.php"><input type="hidden" name="action" value="create">
        <div class="modal-header"><h5 class="modal-title">Tambah Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <!-- Basic Information -->
            <h6 class="mb-3">Informasi Dasar</h6>
            <div class="mb-3"><label class="form-label">Nama Supplier *</label><input type="text" name="name" class="form-control" required id="supplierName" onkeydown="handleEnter(event, 'supplierPhone')"></div>
            <div class="mb-3"><label class="form-label">Telepon</label><input type="text" name="phone" class="form-control" id="supplierPhone" onkeydown="handleEnter(event, 'supplierEmail')"></div>
            <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" id="supplierEmail" onkeydown="handleEnter(event, 'supplierAddress')"></div>
            <div class="mb-3"><label class="form-label">Alamat</label><textarea name="address" class="form-control" rows="3" id="supplierAddress" onkeydown="handleEnter(event, 'paymentTerms')"></textarea></div>
            
            <hr>
            
            <!-- Payment Terms -->
            <h6 class="mb-3">Syarat Pembayaran</h6>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Terms (hari)</label><input type="number" name="payment_terms" class="form-control" value="30" min="0" id="paymentTerms" onkeydown="handleEnter(event, 'creditLimit')"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Limit Kredit (Rp)</label><input type="number" name="credit_limit" class="form-control" value="0" min="0" step="0.01" id="creditLimit" onkeydown="handleEnter(event, 'isActive')"></div>
            </div>
            
            <hr>
            
            <!-- Status -->
            <h6 class="mb-3">Status</h6>
            <div class="mb-3">
                <select name="is_active" class="form-select" id="isActive" onchange="handleEnter(event, 'submitSupplierBtn')">
                    <option value="1">Aktif</option>
                    <option value="0">Tidak Aktif</option>
                </select>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary" id="submitSupplierBtn"><i class="bi bi-save"></i> Simpan Supplier</button></div>
    </form>
</div></div></div>

<script>
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
        document.getElementById('supplierName').focus();
    });
}

$(document).ready(function() {
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
});
</script>
<?php renderFoot(); ?>
