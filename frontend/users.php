<?php
require_once __DIR__ . '/config.php';

$role = userRole();
if ($role !== 'owner' && $role !== 'manager') {
    echo '<div class="alert alert-danger m-4">Access denied. Owner/Manager only.</div>';
    exit;
}

$roles = apiCall('/roles');
?>
<?php renderHead('User Management - Panglong ERP'); ?>
<?php renderNav('users'); ?>

<div class="container mt-4">
    <h1>User Management</h1>
    <p class="text-muted">Current user: <?php echo htmlspecialchars(userFullName()); ?> (<?php echo ucfirst($role); ?>)</p>
    
    <div class="card mt-3"><div class="card-body">
        <h5>System Roles</h5>
        <table class="table table-sm">
            <thead><tr><th>Role</th><th>Slug</th><th>Description</th></tr></thead>
            <tbody>
                <?php if (isset($roles['body']['data'])): ?>
                    <?php foreach ($roles['body']['data'] as $r): ?>
                        <tr><td><?php echo htmlspecialchars($r['name']); ?></td><td><code><?php echo htmlspecialchars($r['slug']); ?></code></td><td><?php echo htmlspecialchars($r['description'] ?? ''); ?></td></tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div></div>
    
    <div class="alert alert-info mt-3">
        <i class="bi bi-info-circle"></i> Full user CRUD requires a User Management API controller. 
        Currently, users are managed via database seeders. A dedicated UsersController can be added in Sprint 4.
    </div>
</div>
<?php renderFoot(); ?>
