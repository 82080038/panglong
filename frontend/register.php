<?php
require_once __DIR__ . '/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = $_POST['company_name'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $subdomain = $_POST['subdomain'] ?? '';
    $tax_id = $_POST['tax_id'] ?? '';
    
    // Setup akun owner
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $owner_email = $_POST['owner_email'] ?? '';
    $owner_phone = $_POST['owner_phone'] ?? '';
    
    // Validasi
    if (empty($company_name) || empty($address) || empty($phone) || empty($email) || empty($subdomain)) {
        $error = 'Data perusahaan wajib diisi lengkap';
    } elseif (empty($username) || empty($password) || empty($full_name)) {
        $error = 'Data akun owner wajib diisi lengkap';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak sama';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter';
    } else {
        // Cek subdomain availability
        $db = new PDO('sqlite:' . __DIR__ . '/../database/database.sqlite');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $db->prepare("SELECT id FROM tenants WHERE subdomain = ?");
        $stmt->execute([$subdomain]);
        if ($stmt->fetch()) {
            $error = 'Subdomain sudah digunakan';
        } else {
            // Cek username availability
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username sudah digunakan';
            } else {
                // Buat tenant
                $now = date('Y-m-d H:i:s');
                $trial_ends = date('Y-m-d H:i:s', strtotime('+30 days'));
                $subscription_ends = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                $stmt = $db->prepare("
                    INSERT INTO tenants (code, name, subdomain, company_name, company_address, 
                                       company_phone, company_email, tax_id, status, 
                                       trial_ends_at, subscription_ends_at, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $code = strtoupper(substr($subdomain, 0, 10));
                $stmt->execute([
                    $code,
                    $company_name,
                    $subdomain,
                    $company_name,
                    $address,
                    $phone,
                    $email,
                    $tax_id,
                    'trial',
                    $trial_ends,
                    $subscription_ends,
                    $now,
                    $now
                ]);
                
                $tenant_id = $db->lastInsertId();
                
                // Buat user owner
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $role_id = $db->query("SELECT id FROM roles WHERE slug = 'owner'")->fetchColumn();
                
                $stmt = $db->prepare("
                    INSERT INTO users (tenant_id, username, password, full_name, email, phone, 
                                     role_id, is_active, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $tenant_id,
                    $username,
                    $password_hash,
                    $full_name,
                    $owner_email,
                    $owner_phone,
                    $role_id,
                    1,
                    $now,
                    $now
                ]);
                
                // Set default app settings
                $default_settings = [
                    'company_name' => $company_name,
                    'company_address' => $address,
                    'company_phone' => $phone,
                    'company_email' => $email,
                    'currency' => 'IDR',
                    'timezone' => 'Asia/Jakarta',
                    'date_format' => 'd/m/Y',
                    'decimal_separator' => ',',
                    'thousand_separator' => '.',
                    'stock_minus_policy' => 'strict',
                    'min_stock_alert' => '10',
                    'default_unit' => 'pcs',
                    'enable_barcode' => '1',
                    'enable_multi_unit' => '1',
                    'enable_product_alias' => '1',
                    'enable_credit' => '1',
                    'default_credit_limit' => '1000000',
                    'default_payment_terms' => '30',
                    'enable_tax' => '0',
                    'tax_rate' => '11',
                    'enable_invoice' => '1',
                    'invoice_prefix' => 'INV',
                    'invoice_start_number' => '1',
                ];
                
                foreach ($default_settings as $key => $value) {
                    $stmt = $db->prepare("
                        INSERT INTO app_settings (tenant_id, key, value, type, created_at, updated_at)
                        VALUES (?, ?, ?, 'string', ?, ?)
                    ");
                    $stmt->execute([$tenant_id, $key, $value, $now, $now]);
                }
                
                $success = 'Pendaftaran berhasil! Silakan login dengan username: ' . $username;
            }
        }
    }
}
?>
<?php
$theme = $_SESSION['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="<?= htmlspecialchars($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Daftar - Panglong ERP</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.css">
    <style>
      body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1a4d8f 0%,#0d6efd 50%,#1a4d8f 100%);padding:1rem}
      .register-card{max-width:600px;width:100%;border:none;border-radius:1rem;box-shadow:0 8px 32px rgba(0,0,0,.2)}
      .register-card .card-body{padding:2rem}
      .register-logo{width:64px;height:64px;margin:0 auto 1rem;background:linear-gradient(135deg,#0d6efd,#1a4d8f);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.75rem}
      [data-bs-theme="dark"] body{background:linear-gradient(135deg,#0d1117 0%,#1a1d24 50%,#0d1117 100%)}
      [data-bs-theme="dark"] .register-card{background:#232730;color:#d8dde6}
      [data-bs-theme="dark"] .form-control{background:#2a2f3a;border-color:#3a3f4a;color:#d8dde6}
      [data-bs-theme="eyecare"] body{background:linear-gradient(135deg,#5a4a2a 0%,#8a6a3a 50%,#5a4a2a 100%)}
      [data-bs-theme="eyecare"] .register-card{background:#faf3e3}
      [data-bs-theme="eyecare"] .form-control{background:#fff8e8;border-color:#d4c4a0}
      .section-title{font-size:0.9rem;font-weight:600;color:#6c757d;margin-bottom:0.5rem}
      @media(max-width:575.98px){.register-card .card-body{padding:1.5rem}.register-logo{width:56px;height:56px;font-size:1.5rem}}
    </style>
</head>
<body>
    <div class="card register-card">
        <div class="card-body">
            <div class="register-logo"><i class="bi bi-box-seam-fill"></i></div>
            <h3 class="text-center mb-1 fw-bold">Panglong ERP</h3>
            <p class="text-center text-muted mb-4 small">Daftar Tenant Baru</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger py-2">
                    <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success py-2">
                    <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
                <div class="text-center mt-3">
                    <a href="login.php" class="btn btn-primary">Masuk</a>
                </div>
            <?php else: ?>
            
            <form method="POST">
                <!-- Info Perusahaan -->
                <div class="section-title">INFORMASI PERUSAHAAN</div>
                <div class="mb-3">
                    <label class="form-label small">Nama Perusahaan/Toko *</label>
                    <input type="text" name="company_name" class="form-control" required value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label small">Alamat *</label>
                    <textarea name="address" class="form-control" rows="2" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small">No. Telepon *</label>
                        <input type="text" name="phone" class="form-control" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small">Email *</label>
                        <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small">Subdomain *</label>
                        <div class="input-group">
                            <input type="text" name="subdomain" class="form-control" required pattern="[a-z0-9-]+" value="<?= htmlspecialchars($_POST['subdomain'] ?? '') ?>">
                            <span class="input-group-text">.panglong.com</span>
                        </div>
                        <small class="text-muted">Hanya huruf kecil, angka, dan tanda strip (-)</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small">NPWP</label>
                        <input type="text" name="tax_id" class="form-control" value="<?= htmlspecialchars($_POST['tax_id'] ?? '') ?>">
                    </div>
                </div>
                
                <hr>
                
                <!-- Akun Owner -->
                <div class="section-title">AKUN OWNER</div>
                <div class="mb-3">
                    <label class="form-label small">Username *</label>
                    <input type="text" name="username" class="form-control" required pattern="[a-zA-Z0-9_]+" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    <small class="text-muted">Hanya huruf, angka, dan underscore (_)</small>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small">Password *</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                        <small class="text-muted">Minimal 8 karakter</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small">Konfirmasi Password *</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="8">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Nama Lengkap *</label>
                    <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small">Email Owner</label>
                        <input type="email" name="owner_email" class="form-control" value="<?= htmlspecialchars($_POST['owner_email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small">No. HP Owner</label>
                        <input type="text" name="owner_phone" class="form-control" value="<?= htmlspecialchars($_POST['owner_phone'] ?? '') ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg w-100 mt-3"><i class="bi bi-person-plus"></i> Daftar</button>
            </form>
            
            <hr>
            <div class="text-center">
                <small class="text-muted">Sudah punya akun? <a href="login.php">Masuk</a></small>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
