<?php
// Script to add test users for Playwright testing
require_once __DIR__ . '/frontend/db.php';

$db = db();

// Get tenant_id for ownertest (existing tenant)
$stmt = $db->prepare("SELECT id FROM tenants WHERE code LIKE '%TEST%' OR name LIKE '%Test%'");
$stmt->execute();
$tenant = $stmt->fetch();

if (!$tenant) {
    // Create a test tenant if none exists
    $now = date('Y-m-d H:i:s');
    $stmt = $db->prepare("INSERT INTO tenants (code, name, subdomain, company_name, company_address, company_phone, company_email, tax_id, status, trial_ends_at, subscription_ends_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute(['TEST01', 'Test Company', 'test01', 'Test Company', 'Test Address', '123456789', 'test@test.com', '123456789', 'active', date('Y-m-d H:i:s', strtotime('+30 days')), date('Y-m-d H:i:s', strtotime('+30 days')), $now, $now]);
    $tenantId = $db->lastInsertId();
} else {
    $tenantId = $tenant['id'];
}

echo "Using tenant_id: $tenantId\n";

// Get role IDs
$roles = [
    'manager' => null,
    'kasir' => null,
    'gudang' => null,
    'accounting' => null,
    'supervisor' => null,
];

foreach ($roles as $slug => &$id) {
    $stmt = $db->prepare("SELECT id FROM roles WHERE slug = ?");
    $stmt->execute([$slug]);
    $id = $stmt->fetchColumn();
    echo "Role $slug: $id\n";
}

// Create test users
$testUsers = [
    [
        'username' => 'manager1',
        'password' => 'password123',
        'full_name' => 'Test Manager',
        'email' => 'manager1@test.com',
        'phone' => '1234567890',
        'role_slug' => 'manager'
    ],
    [
        'username' => 'kasir1',
        'password' => 'password123',
        'full_name' => 'Test Kasir',
        'email' => 'kasir1@test.com',
        'phone' => '1234567891',
        'role_slug' => 'kasir'
    ],
    [
        'username' => 'gudang1',
        'password' => 'password123',
        'full_name' => 'Test Gudang',
        'email' => 'gudang1@test.com',
        'phone' => '1234567892',
        'role_slug' => 'gudang'
    ],
    [
        'username' => 'accounting1',
        'password' => 'password123',
        'full_name' => 'Test Accounting',
        'email' => 'accounting1@test.com',
        'phone' => '1234567893',
        'role_slug' => 'accounting'
    ],
    [
        'username' => 'supervisor1',
        'password' => 'password123',
        'full_name' => 'Test Supervisor',
        'email' => 'supervisor1@test.com',
        'phone' => '1234567894',
        'role_slug' => 'supervisor'
    ],
];

$now = date('Y-m-d H:i:s');

foreach ($testUsers as $user) {
    // Check if user already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$user['username']]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo "User {$user['username']} already exists, skipping...\n";
        continue;
    }
    
    $roleId = $roles[$user['role_slug']];
    $passwordHash = password_hash($user['password'], PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO users (tenant_id, username, password, full_name, email, phone, role_id, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $tenantId,
        $user['username'],
        $passwordHash,
        $user['full_name'],
        $user['email'],
        $user['phone'],
        $roleId,
        1,
        $now,
        $now
    ]);
    
    echo "Created user: {$user['username']} ({$user['full_name']})\n";
}

echo "\nTest users created successfully!\n";
