<?php
require_once __DIR__ . '/auth.php';

// Cek apakah user login
requireLogin();

// Cek apakah user adalah Super Admin
if (currentUser()['role_slug'] !== 'super_admin') {
    header('Location: index.php');
    exit;
}

$db = db();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $tenant_id = $_POST['tenant_id'] ?? 0;
    
    if ($action === 'approve' && $tenant_id) {
        $stmt = $db->prepare("UPDATE tenants SET status = 'active', updated_at = ? WHERE id = ?");
        $stmt->execute([date('Y-m-d H:i:s'), $tenant_id]);
    } elseif ($action === 'reject' && $tenant_id) {
        $stmt = $db->prepare("UPDATE tenants SET status = 'rejected', updated_at = ? WHERE id = ?");
        $stmt->execute([date('Y-m-d H:i:s'), $tenant_id]);
    } elseif ($action === 'suspend' && $tenant_id) {
        $stmt = $db->prepare("UPDATE tenants SET status = 'suspended', updated_at = ? WHERE id = ?");
        $stmt->execute([date('Y-m-d H:i:s'), $tenant_id]);
    } elseif ($action === 'activate' && $tenant_id) {
        $stmt = $db->prepare("UPDATE tenants SET status = 'active', updated_at = ? WHERE id = ?");
        $stmt->execute([date('Y-m-d H:i:s'), $tenant_id]);
    }
    
    header('Location: tenants.php');
    exit;
}

// Get all tenants
$tenants = $db->query("
    SELECT t.*, 
           (SELECT COUNT(*) FROM users WHERE tenant_id = t.id) as user_count,
           (SELECT COUNT(*) FROM products WHERE tenant_id = t.id) as product_count
    FROM tenants t
    ORDER BY t.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
$theme = $_SESSION['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="<?= htmlspecialchars($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Kelola Tenant - Panglong ERP</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.css">
    <style>
      body{background:#f8f9fa}
      [data-bs-theme="dark"] body{background:#0d1117}
      [data-bs-theme="eyecare"] body{background:#faf3e3}
    </style>
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-buildings"></i> Kelola Tenant</h2>
            <a href="register.php" class="btn btn-primary"><i class="bi bi-plus"></i> Tambah Tenant</a>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Perusahaan</th>
                                <th>Subdomain</th>
                                <th>Status</th>
                                <th>Trial Ends</th>
                                <th>Users</th>
                                <th>Produk</th>
                                <th>Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tenants as $tenant): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($tenant['code']) ?></code></td>
                                <td>
                                    <strong><?= htmlspecialchars($tenant['company_name']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($tenant['company_address']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($tenant['subdomain']) ?>.panglong.com</td>
                                <td>
                                    <?php
                                    $status_badge = [
                                        'trial' => 'warning',
                                        'active' => 'success',
                                        'suspended' => 'danger',
                                        'rejected' => 'secondary'
                                    ];
                                    $status_text = [
                                        'trial' => 'Trial',
                                        'active' => 'Active',
                                        'suspended' => 'Suspended',
                                        'rejected' => 'Rejected'
                                    ];
                                    ?>
                                    <span class="badge bg-<?= $status_badge[$tenant['status']] ?>">
                                        <?= $status_text[$tenant['status']] ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($tenant['trial_ends_at'])) ?></td>
                                <td><?= $tenant['user_count'] ?></td>
                                <td><?= $tenant['product_count'] ?></td>
                                <td><?= date('d/m/Y', strtotime($tenant['created_at'])) ?></td>
                                <td>
                                    <?php if ($tenant['status'] === 'trial'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="tenant_id" value="<?= $tenant['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                <i class="bi bi-check"></i>
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="tenant_id" value="<?= $tenant['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Reject">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </form>
                                    <?php elseif ($tenant['status'] === 'active'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="suspend">
                                            <input type="hidden" name="tenant_id" value="<?= $tenant['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-warning" title="Suspend">
                                                <i class="bi bi-pause"></i>
                                            </button>
                                        </form>
                                    <?php elseif ($tenant['status'] === 'suspended'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="activate">
                                            <input type="hidden" name="tenant_id" value="<?= $tenant['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-success" title="Activate">
                                                <i class="bi bi-play"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($tenants)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox"></i> Belum ada tenant
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
