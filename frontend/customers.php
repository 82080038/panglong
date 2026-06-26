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
    $searchSql = "WHERE (c.name LIKE ? OR c.phone LIKE ?)";
    $q = '%' . $search . '%';
    $searchParams = [$q, $q];
    if (!$isSuperAdmin && $tenantId) {
        $searchSql .= " AND c.tenant_id = $tenantId";
    }
} else {
    if (!$isSuperAdmin && $tenantId) {
        $searchSql = "WHERE c.tenant_id = $tenantId";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO customers (name, address, phone, email, group_id, credit_limit, payment_terms, is_active, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $_POST['name'], $_POST['address'] ?? null, $_POST['phone'] ?? null,
            $_POST['email'] ?? null, $_POST['group_id'] ?? null,
            $_POST['credit_limit'] ?? 0, $_POST['payment_terms'] ?? 30, $_POST['is_active'] ?? 1, $now, $now, $tenantId
        ]);
        header('Location: customers.php?msg=created');
        exit;
    } elseif ($action === 'update') {
        $id = $_POST['id'];
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("UPDATE customers SET name=?, address=?, phone=?, email=?, group_id=?, credit_limit=?, payment_terms=?, is_active=?, updated_at=? WHERE id=?");
        $stmt->execute([
            $_POST['name'], $_POST['address'] ?? null, $_POST['phone'] ?? null,
            $_POST['email'] ?? null, $_POST['group_id'] ?? null,
            $_POST['credit_limit'] ?? 0, $_POST['payment_terms'] ?? 30,
            isset($_POST['is_active']) ? 1 : 0, $now, $id
        ]);
        header('Location: customers.php?msg=updated');
        exit;
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        $d->prepare("DELETE FROM customers WHERE id = ?")->execute([$id]);
        header('Location: customers.php?msg=deleted');
        exit;
    }
}

$sql = "SELECT c.*, g.name as group_name FROM customers c LEFT JOIN customer_groups g ON c.group_id = g.id $searchSql ORDER BY c.id DESC LIMIT 100";
$stmt = $d->prepare($sql);
$stmt->execute($searchParams);
$customers = $stmt->fetchAll();

$groups = $d->query("SELECT id, name FROM customer_groups ORDER BY name")->fetchAll();

$msg = $_GET['msg'] ?? '';
?>
<?php renderHead('Customers - Panglong ERP'); ?>
<?php renderNav('customers'); ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Pelanggan</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus"></i> Tambah Pelanggan
        </button>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show">
            Customer <?php echo htmlspecialchars($msg); ?> Berhasil. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2" id="searchForm">
                <div class="col-md-8"><input type="text" class="form-control" name="search" id="searchInput" value="<?= htmlspecialchars($search) ?>" placeholder="Cari berdasarkan nama atau telepon..." autocomplete="off"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Search</button></div>
                <div class="col-md-2"><a href="customers.php" class="btn btn-outline-secondary w-100">Reset</a></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive"><table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Group</th>
                        <th>Telepon</th>
                        <th>Email</th>
                        <th>Limit Kredit</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (is_array($customers)): ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($customer['id']); ?></td>
                                <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                <td><?php echo htmlspecialchars($customer['group_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($customer['phone'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($customer['email'] ?? '-'); ?></td>
                                <td><?php echo rupiah($customer['credit_limit'] ?? 0) ?></td>
                                <td>
                                    <a href="customer_detail.php?id=<?= $customer['id'] ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                                    <button class="btn btn-sm btn-warning edit-btn"
                                        data-id="<?php echo $customer['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($customer['name']); ?>"
                                        data-phone="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>"
                                        data-email="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>"
                                        data-address="<?php echo htmlspecialchars($customer['address'] ?? ''); ?>"
                                        data-group="<?php echo $customer['group_id'] ?? ''; ?>"
                                        data-credit="<?php echo $customer['credit_limit'] ?? 0; ?>"
                                        data-terms="<?php echo $customer['payment_terms'] ?? 30; ?>"
                                        data-active="<?php echo $customer['is_active'] ?? 1; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Hapus this customer?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $customer['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">No customers found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="customers.php">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Pelanggan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Basic Information -->
                    <h6 class="mb-3">Informasi Dasar</h6>
                    <div class="mb-3"><label class="form-label">Nama Pelanggan *</label><input type="text" name="name" class="form-control" required id="customerName" onkeydown="handleEnter(event, 'customerPhone')"></div>
                    <div class="mb-3"><label class="form-label">Telepon</label><input type="text" name="phone" class="form-control" id="customerPhone" onkeydown="handleEnter(event, 'customerEmail')"></div>
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" id="customerEmail" onkeydown="handleEnter(event, 'customerAddress')"></div>
                    <div class="mb-3"><label class="form-label">Alamat</label><textarea name="address" class="form-control" rows="3" id="customerAddress" onkeydown="handleEnter(event, 'groupSelect')"></textarea></div>
                    
                    <hr>
                    
                    <!-- Group & Credit -->
                    <h6 class="mb-3">Grup & Kredit</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Grup</label>
                            <div class="input-group">
                                <select name="group_id" class="form-select" id="groupSelect" onchange="handleEnter(event, 'creditLimit')">
                                    <option value="">Pilih Grup</option>
                                    <?php if (is_array($groups)): ?>
                                        <?php foreach ($groups as $g): ?>
                                            <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['name']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <button type="button" class="btn btn-outline-primary" onclick="openQuickAddModal('group')"><i class="bi bi-plus"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3"><label class="form-label">Limit Kredit (Rp)</label><input type="number" name="credit_limit" class="form-control" value="0" min="0" step="0.01" id="creditLimit" onkeydown="handleEnter(event, 'paymentTerms')"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Terms (hari)</label><input type="number" name="payment_terms" class="form-control" value="30" min="0" id="paymentTerms" onkeydown="handleEnter(event, 'isActive')"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Status</label>
                            <select name="is_active" class="form-select" id="isActive" onchange="handleEnter(event, 'submitCustomerBtn')">
                                <option value="1">Aktif</option>
                                <option value="0">Tidak Aktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="submitCustomerBtn"><i class="bi bi-save"></i> Simpan Pelanggan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="customers.php">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Pelanggan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Telepon</label><input type="text" name="phone" id="edit_phone" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" id="edit_email" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Alamat</label><textarea name="address" id="edit_address" class="form-control"></textarea></div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Group</label>
                            <select name="group_id" id="edit_group" class="form-select">
                                <option value="">Select Group</option>
                                <?php if (is_array($groups)): ?>
                                    <?php foreach ($groups as $g): ?>
                                        <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['name']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3"><label class="form-label">Limit Kredit</label><input type="number" name="credit_limit" id="edit_credit" class="form-control" min="0"></div>
                        <div class="col-md-3 mb-3"><label class="form-label">Terms (days)</label><input type="number" name="payment_terms" id="edit_terms" class="form-control" min="0"></div>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_active" id="edit_active" class="form-check-input" value="1">
                        <label class="form-check-label" for="edit_active">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
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
$('.edit-btn').click(function() {
    $('#edit_id').val($(this).data('id'));
    $('#edit_name').val($(this).data('name'));
    $('#edit_phone').val($(this).data('phone'));
    $('#edit_email').val($(this).data('email'));
    $('#edit_address').val($(this).data('address'));
    $('#edit_group').val($(this).data('group'));
    $('#edit_credit').val($(this).data('credit'));
    $('#edit_terms').val($(this).data('terms'));
    $('#edit_active').prop('checked', $(this).data('active') == 1);
    new bootstrap.Modal($('#editModal')[0]).show();
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

// Quick add functionality
var currentQuickAddType = '';

function openQuickAddModal(type) {
    currentQuickAddType = type;
    var title = 'Tambah Grup Baru';
    var label = 'Nama Grup';
    
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
    
    fetch('ajax.php?endpoint=customer-groups', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: value })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            var select = document.getElementById('groupSelect');
            var option = document.createElement('option');
            option.value = res.data.id;
            option.textContent = res.data.name;
            select.appendChild(option);
            select.value = option.value;
            
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
        document.getElementById('customerName').focus();
    });
}

var editModal = document.getElementById('editModal');
if (editModal) {
    editModal.addEventListener('shown.bs.modal', function() {
        document.getElementById('edit_name').focus();
    });
}
</script>
<?php renderFoot(); ?>
