<?php
/**
 * Perbaiki konsep: Platform Owner vs Tenant Owner
 * Platform Owner (Anda) = Pemilik aplikasi, tenant_id = NULL
 * Tenant Owner = Pemilik toko panglong yang menyewa
 */

$db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== PERBAIKAN STRUKTUR USER ===\n\n";

// Hapus user admin yang salah (tenant_id=1)
echo "Menghapus user admin yang salah...\n";
$db->exec("DELETE FROM users WHERE username = 'admin'");
echo "✓ User admin dihapus\n";

// Hapus tenant HQ yang salah
echo "\nMenghapus tenant HQ yang salah...\n";
$db->exec("DELETE FROM tenants WHERE code = 'HQ'");
echo "✓ Tenant HQ dihapus\n";

// Tambah role Super Admin untuk Platform Owner
echo "\nMenambahkan role Super Admin...\n";
$db->exec("INSERT INTO roles (name, slug, description) VALUES ('Super Admin', 'super_admin', 'Platform Owner - Full access to all tenants')");
$super_admin_role_id = $db->lastInsertId();
echo "✓ Role Super Admin dibuat dengan ID: $super_admin_role_id\n";

// Buat user Platform Owner (tanpa tenant_id)
$now = date('Y-m-d H:i:s');
$password_hash = password_hash('password123', PASSWORD_DEFAULT);
$stmt = $db->prepare("
    INSERT INTO users (tenant_id, username, password, full_name, email, phone, role_id, is_active, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    NULL,  // tenant_id = NULL untuk Platform Owner
    'admin',
    $password_hash,
    'Platform Owner',
    'admin@panglong-erp.com',
    '081234567890',
    $super_admin_role_id,
    1,
    $now,
    $now
]);

$user_id = $db->lastInsertId();
echo "✓ User Platform Owner dibuat dengan ID: $user_id (tenant_id = NULL)\n";

echo "\n=== VERIFIKASI ===\n";
echo "Roles:\n";
$roles = $db->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);
foreach ($roles as $role) {
    echo "- ID: {$role['id']}, Name: {$role['name']}, Slug: {$role['slug']}\n";
}

echo "\nUsers:\n";
$users = $db->query("SELECT id, username, full_name, tenant_id, role_id FROM users")->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $user) {
    echo "- ID: {$user['id']}, Username: {$user['username']}, Tenant ID: " . ($user['tenant_id'] ?? 'NULL') . ", Role ID: {$user['role_id']}\n";
}

echo "\nTenants:\n";
$count = $db->query("SELECT COUNT(*) FROM tenants")->fetchColumn();
echo "- Jumlah tenants: $count\n";

echo "\n=== SETUP SELESAI ===\n";
echo "Login credentials:\n";
echo "Username: admin\n";
echo "Password: password123\n";
echo "Role: Super Admin (Platform Owner)\n";
echo "Tenant ID: NULL (akses ke semua tenants)\n";
