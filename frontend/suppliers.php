<?php
require_once __DIR__ . '/config.php';

$suppliers = apiCall('/suppliers');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $data = [
            'name' => $_POST['name'],
            'address' => $_POST['address'] ?? null,
            'phone' => $_POST['phone'] ?? null,
            'email' => $_POST['email'] ?? null,
            'payment_terms' => $_POST['payment_terms'] ?? 30,
            'credit_limit' => $_POST['credit_limit'] ?? 0,
            'is_active' => true,
        ];
        $result = apiCall('/suppliers', 'POST', $data);
        header('Location: suppliers.php?msg=' . ($result['code'] === 201 ? 'created' : 'error'));
        exit;
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        $result = apiCall('/suppliers/' . $id, 'DELETE');
        header('Location: suppliers.php?msg=deleted');
        exit;
    }
}
$msg = $_GET['msg'] ?? '';
?>
<?php renderHead('Suppliers - Panglong ERP'); ?>
<?php renderNav('suppliers'); ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Suppliers</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus"></i> Add Supplier</button>
    </div>
    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show">Supplier <?php echo htmlspecialchars($msg); ?>. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <div class="card"><div class="card-body">
        <table class="table table-striped">
            <thead><tr><th>ID</th><th>Name</th><th>Phone</th><th>Email</th><th>Terms</th><th>Credit Limit</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if (isset($suppliers['body']['data']) && is_array($suppliers['body']['data'])): ?>
                    <?php foreach ($suppliers['body']['data'] as $s): ?>
                        <tr>
                            <td><?php echo $s['id']; ?></td>
                            <td><?php echo htmlspecialchars($s['name']); ?></td>
                            <td><?php echo htmlspecialchars($s['phone'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($s['email'] ?? '-'); ?></td>
                            <td><?php echo $s['payment_terms'] ?? 30; ?> days</td>
                            <td>Rp <?php echo number_format($s['credit_limit'] ?? 0, 0, ',', '.'); ?></td>
                            <td>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')">
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
        </table>
    </div></div>
</div>

<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="suppliers.php"><input type="hidden" name="action" value="create">
        <div class="modal-header"><h5 class="modal-title">Add Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
            <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
            <div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control"></textarea></div>
            <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Payment Terms (days)</label><input type="number" name="payment_terms" class="form-control" value="30"></div>
            <div class="col-md-6 mb-3"><label class="form-label">Credit Limit</label><input type="number" name="credit_limit" class="form-control" value="0" min="0"></div></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
    </form>
</div></div></div>
<?php renderFoot(); ?>
