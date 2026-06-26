<?php
/**
 * Setup tenant HQ dan user admin
 */

$db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== MEMBUAT TENANT PANGLONG HQ ===\n\n";

// Cek struktur tabel tenants
$columns = $db->query("PRAGMA table_info(tenants)")->fetchAll(PDO::FETCH_ASSOC);
echo "Struktur tabel tenants:\n";
foreach ($columns as $col) {
    echo "- {$col['name']} ({$col['type']})\n";
}

echo "\n";

// Insert tenant HQ
$now = date('Y-m-d H:i:s');
$stmt = $db->prepare("
    INSERT INTO tenants (code, name, subdomain, logo_url, primary_color, secondary_color, 
                        company_name, company_address, company_phone, company_email, tax_id, 
                        status, trial_ends_at, subscription_ends_at, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    'HQ',
    'Panglong HQ',
    'hq',
    '',
    '#0d6efd',
    '#6c757d',
    'Panglong Material Bangunan HQ',
    'Jl. Raya Panglong No. 1',
    '021-1234567',
    'info@panglong.com',
    '',
    'active',
    null,
    '2027-12-31 23:59:59',
    $now,
    $now
]);

$tenant_id = $db->lastInsertId();
echo "✓ Tenant HQ berhasil dibuat dengan ID: $tenant_id\n";

// Cek hasil
$stmt = $db->query("SELECT * FROM tenants WHERE id = $tenant_id");
$tenant = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\nDetail tenant:\n";
print_r($tenant);

echo "\n\n=== MEMBUAT USER ADMIN (OWNER) ===\n\n";

// Cek role Owner
$stmt = $db->query("SELECT id FROM roles WHERE slug = 'owner'");
$role = $stmt->fetch(PDO::FETCH_ASSOC);
$role_id = $role['id'];
echo "Role Owner ID: $role_id\n";

// Insert user admin
$password_hash = password_hash('password123', PASSWORD_DEFAULT);
$stmt = $db->prepare("
    INSERT INTO users (tenant_id, username, password, full_name, email, phone, role_id, is_active, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $tenant_id,
    'admin',
    $password_hash,
    'Administrator',
    'admin@panglong.com',
    '081234567890',
    $role_id,
    1,
    $now,
    $now
]);

$user_id = $db->lastInsertId();
echo "✓ User admin berhasil dibuat dengan ID: $user_id\n";

// Cek hasil
$stmt = $db->query("SELECT id, tenant_id, username, full_name, email, role_id, is_active FROM users WHERE id = $user_id");
$user = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\nDetail user:\n";
print_r($user);

echo "\n=== SETUP SELESAI ===\n";
echo "Login credentials:\n";
echo "Username: admin\n";
echo "Password: password123\n";
