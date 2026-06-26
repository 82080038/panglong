<?php
require_once __DIR__ . '/auth.php';

$error = '';
$msg = $_GET['msg'] ?? '';
if ($msg === 'timeout') {
    $error = 'Sesi berakhir. Silakan masuk kembali.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = login($username, $password);
    
    if ($result['success']) {
        header('Location: index.php');
        exit;
    } else {
        $error = $result['message'] ?? 'Gagal masuk. Periksa nama pengguna dan kata sandi.';
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
    <title>Masuk - Panglong ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
      body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1a4d8f 0%,#0d6efd 50%,#1a4d8f 100%);padding:1rem}
      .login-card{max-width:400px;width:100%;border:none;border-radius:1rem;box-shadow:0 8px 32px rgba(0,0,0,.2)}
      .login-card .card-body{padding:2rem}
      .login-logo{width:64px;height:64px;margin:0 auto 1rem;background:linear-gradient(135deg,#0d6efd,#1a4d8f);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.75rem}
      [data-bs-theme="dark"] body{background:linear-gradient(135deg,#0d1117 0%,#1a1d24 50%,#0d1117 100%)}
      [data-bs-theme="dark"] .login-card{background:#232730;color:#d8dde6}
      [data-bs-theme="dark"] .form-control{background:#2a2f3a;border-color:#3a3f4a;color:#d8dde6}
      [data-bs-theme="eyecare"] body{background:linear-gradient(135deg,#5a4a2a 0%,#8a6a3a 50%,#5a4a2a 100%)}
      [data-bs-theme="eyecare"] .login-card{background:#faf3e3}
      [data-bs-theme="eyecare"] .form-control{background:#fff8e8;border-color:#d4c4a0}
      .quick-btn{font-size:.8rem}
      @media(max-width:575.98px){.login-card .card-body{padding:1.5rem}.login-logo{width:56px;height:56px;font-size:1.5rem}}
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="card-body">
            <div class="login-logo"><i class="bi bi-box-seam-fill"></i></div>
            <h3 class="text-center mb-1 fw-bold">Panglong ERP</h3>
            <p class="text-center text-muted mb-4 small">Sistem Manajemen Distribusi</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger py-2">
                    <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-person"></i> Nama Pengguna</label>
                    <input type="text" name="username" class="form-control form-control-lg" required autocomplete="username" autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-lock"></i> Kata Sandi</label>
                    <input type="password" name="password" class="form-control form-control-lg" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100 mb-3"><i class="bi bi-box-arrow-in-right"></i> Masuk</button>
            </form>
            
            <hr>
            <p class="text-muted small text-center mb-2">Login Cepat (Demo):</p>
            <div class="row g-2 mb-3">
                <div class="col-6"><button onclick="quickLogin('admin', 'password123')" class="btn btn-outline-primary btn-sm quick-btn w-100"><i class="bi bi-shield-check"></i> Admin</button></div>
                <div class="col-6"><button onclick="quickLogin('manager1', 'password123')" class="btn btn-outline-success btn-sm quick-btn w-100"><i class="bi bi-person-badge"></i> Manager</button></div>
                <div class="col-6"><button onclick="quickLogin('kasir1', 'password123')" class="btn btn-outline-warning btn-sm quick-btn w-100"><i class="bi bi-cash-stack"></i> Kasir</button></div>
                <div class="col-6"><button onclick="quickLogin('gudang1', 'password123')" class="btn btn-outline-info btn-sm quick-btn w-100"><i class="bi bi-box-seam"></i> Gudang</button></div>
            </div>
            
            <hr>
            <p class="text-muted small text-center mb-0">
                <i class="bi bi-info-circle"></i> Pengguna default: admin / password123
            </p>
        </div>
    </div>

    <script>
        function quickLogin(username, password) {
            document.querySelector('input[name="username"]').value = username;
            document.querySelector('input[name="password"]').value = password;
            document.querySelector('form').submit();
        }
    </script>
</body>
</html>
