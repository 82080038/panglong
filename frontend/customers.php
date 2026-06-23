<?php
require_once __DIR__ . '/config.php';

$search = $_GET['search'] ?? '';
$customersEndpoint = '/customers?per_page=50';
if ($search) $customersEndpoint .= '&search=' . urlencode($search);
$customers = apiCall($customersEndpoint);
$groups = apiCall('/customer-groups');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $data = [
            'name' => $_POST['name'],
            'address' => $_POST['address'] ?? null,
            'phone' => $_POST['phone'] ?? null,
            'email' => $_POST['email'] ?? null,
            'group_id' => $_POST['group_id'] ?? null,
            'credit_limit' => $_POST['credit_limit'] ?? 0,
            'payment_terms' => $_POST['payment_terms'] ?? 30,
            'is_active' => true,
        ];
        $result = apiCall('/customers', 'POST', $data);
        if ($result['code'] === 201) {
            header('Location: customers.php?msg=created');
            exit;
        }
    } elseif ($action === 'update') {
        $id = $_POST['id'];
        $data = [
            'name' => $_POST['name'],
            'address' => $_POST['address'] ?? null,
            'phone' => $_POST['phone'] ?? null,
            'email' => $_POST['email'] ?? null,
            'group_id' => $_POST['group_id'] ?? null,
            'credit_limit' => $_POST['credit_limit'] ?? 0,
            'payment_terms' => $_POST['payment_terms'] ?? 30,
            'is_active' => isset($_POST['is_active']),
        ];
        $result = apiCall('/customers/' . $id, 'PUT', $data);
        header('Location: customers.php?msg=updated');
        exit;
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        $result = apiCall('/customers/' . $id, 'DELETE');
        header('Location: customers.php?msg=deleted');
        exit;
    }
}

$msg = $_GET['msg'] ?? '';
?>
<?php renderHead('Customers - Panglong ERP'); ?>
<?php renderNav('customers'); ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Customers</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus"></i> Add Customer
        </button>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show">
            Customer <?php echo htmlspecialchars($msg); ?> successfully. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-8"><input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or phone..."></div>
                <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Search</button></div>
                <div class="col-md-2"><a href="customers.php" class="btn btn-outline-secondary w-100">Clear</a></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Group</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Credit Limit</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($customers['body']['data']) && is_array($customers['body']['data'])): ?>
                        <?php foreach ($customers['body']['data'] as $customer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($customer['id']); ?></td>
                                <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                <td><?php echo htmlspecialchars($customer['group']['name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($customer['phone'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($customer['email'] ?? '-'); ?></td>
                                <td>Rp <?php echo number_format($customer['credit_limit'] ?? 0, 0, ',', '.'); ?></td>
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
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this customer?')">
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
            </table>
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
                    <h5 class="modal-title">Add Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control"></textarea></div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Group</label>
                            <select name="group_id" class="form-select">
                                <option value="">Select Group</option>
                                <?php if (isset($groups['body']['data'])): ?>
                                    <?php foreach ($groups['body']['data'] as $g): ?>
                                        <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['name']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3"><label class="form-label">Credit Limit</label><input type="number" name="credit_limit" class="form-control" value="0" min="0"></div>
                        <div class="col-md-3 mb-3"><label class="form-label">Terms (days)</label><input type="number" name="payment_terms" class="form-control" value="30" min="0"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
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
                    <h5 class="modal-title">Edit Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" id="edit_phone" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" id="edit_email" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Address</label><textarea name="address" id="edit_address" class="form-control"></textarea></div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Group</label>
                            <select name="group_id" id="edit_group" class="form-select">
                                <option value="">Select Group</option>
                                <?php if (isset($groups['body']['data'])): ?>
                                    <?php foreach ($groups['body']['data'] as $g): ?>
                                        <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['name']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3"><label class="form-label">Credit Limit</label><input type="number" name="credit_limit" id="edit_credit" class="form-control" min="0"></div>
                        <div class="col-md-3 mb-3"><label class="form-label">Terms (days)</label><input type="number" name="payment_terms" id="edit_terms" class="form-control" min="0"></div>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_active" id="edit_active" class="form-check-input" value="1">
                        <label class="form-check-label" for="edit_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
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
</script>
<?php renderFoot(); ?>
