<?php
require_once __DIR__ . '/config.php';

requirePermission('manage_users');

$d = db();

$tenant_id = $_SESSION['user']['tenant_id'];
$branch_id = $_SESSION['user']['branch_id'] ?? null;
$is_super_admin = $_SESSION['user']['role_slug'] === 'super_admin';

// Super Admin bisa akses semua tenant, Owner hanya tenant sendiri
if ($is_super_admin) {
    $tenant_id = $_GET['tenant_id'] ?? $_SESSION['user']['tenant_id'] ?? null;
    $branch_id = null;
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_user') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $role_id = $_POST['role_id'] ?? 0;
        
        // Validasi
        if (empty($username) || empty($password) || empty($full_name) || empty($role_id)) {
            $error = 'Data wajib diisi lengkap';
        } else {
            // Cek username availability (tenant scope)
            $checkParams = [$username, $tenant_id];
            $checkSql = "SELECT id FROM users WHERE username = ? AND tenant_id = ?";
            $stmt = $d->prepare($checkSql);
            $stmt->execute($checkParams);
            if ($stmt->fetch()) {
                $error = 'Username sudah digunakan';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $now = date('Y-m-d H:i:s');
                
                $stmt = $d->prepare("
                    INSERT INTO users (tenant_id, branch_id, username, password, full_name, email, phone, role_id, is_active, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$tenant_id, $branch_id, $username, $password_hash, $full_name, $email, $phone, $role_id, 1, $now, $now]);
                
                $success = 'User berhasil ditambahkan';
            }
        }
    } elseif ($action === 'delete_user') {
        $user_id = $_POST['user_id'] ?? 0;
        
        // Jangan hapus diri sendiri
        if ($user_id == $_SESSION['user']['id']) {
            $error = 'Tidak bisa menghapus akun sendiri';
        } else {
            $delParams = [$user_id, $tenant_id];
            $delSql = "DELETE FROM users WHERE id = ? AND tenant_id = ?";
            if ($branch_id) {
                $delSql .= " AND branch_id = ?";
                $delParams[] = $branch_id;
            }
            $stmt = $d->prepare($delSql);
            $stmt->execute($delParams);
            $success = 'User berhasil dihapus';
        }
    } elseif ($action === 'toggle_status') {
        $user_id = $_POST['user_id'] ?? 0;
        
        // Jangan nonaktifkan diri sendiri
        if ($user_id == $_SESSION['user']['id']) {
            $error = 'Tidak bisa menonaktifkan akun sendiri';
        } else {
            $toggleParams = [$user_id, $tenant_id];
            $toggleSql = "UPDATE users SET is_active = NOT is_active WHERE id = ? AND tenant_id = ?";
            if ($branch_id) {
                $toggleSql .= " AND branch_id = ?";
                $toggleParams[] = $branch_id;
            }
            $stmt = $d->prepare($toggleSql);
            $stmt->execute($toggleParams);
            $success = 'Status user berhasil diubah';
        }
    }
    
    $redirectParams = [];
    if ($is_super_admin && $tenant_id) {
        $redirectParams['tenant_id'] = $tenant_id;
    }
    if (isset($success)) {
        $redirectParams['msg'] = 'created';
    } elseif (isset($error)) {
        $redirectParams['err'] = urlencode($error);
    }
    $queryString = !empty($redirectParams) ? '?' . http_build_query($redirectParams) : '';
    header('Location: users.php' . $queryString);
    exit;
}

$msg = $_GET['msg'] ?? '';
$errMsg = $_GET['err'] ?? '';

// Get users
$userParams = [$tenant_id];
$userSql = "
    SELECT u.*, r.name as role_name, r.slug as role_slug
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE u.tenant_id = ?
";
if ($branch_id) {
    $userSql .= " AND (u.branch_id = ? OR u.branch_id IS NULL)";
    $userParams[] = $branch_id;
}
$userSql .= " ORDER BY u.created_at DESC";
$users = $d->prepare($userSql);
$users->execute($userParams);
$users = $users->fetchAll(PDO::FETCH_ASSOC);

// Get roles
$roles = $d->query("SELECT * FROM roles WHERE slug != 'super_admin' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php renderHead('Kelola User - Panglong ERP'); ?>
<?php renderNav('users'); ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-people"></i> Kelola User</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-plus"></i> Tambah User
            </button>
        </div>
        
        <?php if (!empty($errMsg)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($errMsg) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($msg) && $msg === 'created'): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> User berhasil ditambahkan
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>No. HP</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                                <td><?= htmlspecialchars($user['full_name']) ?></td>
                                <td><?= htmlspecialchars($user['email'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($user['role_name']) ?></span>
                                </td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user']['id']): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-<?= $user['is_active'] ? 'warning' : 'success' ?>" title="<?= $user['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                <i class="bi bi-<?= $user['is_active'] ? 'pause' : 'play' ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus user ini?')">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">(Anda)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox"></i> Belum ada user
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah User Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_user">
                        
                        <div class="mb-3">
                            <label class="form-label">Username *</label>
                            <input type="text" name="username" class="form-control" required pattern="[a-zA-Z0-9_]+">
                            <small class="text-muted">Hanya huruf, angka, dan underscore (_)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password *</label>
                            <input type="password" name="password" class="form-control" required minlength="8">
                            <small class="text-muted">Minimal 8 karakter</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap *</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">No. HP</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role *</label>
                            <select name="role_id" class="form-select" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
