<?php
session_start();

define('API_URL', 'http://127.0.0.1:8000/api/v1');

$error = '';
$msg = $_GET['msg'] ?? '';
if ($msg === 'timeout') {
    $error = 'Session expired. Please login again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Call API login
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, API_URL . '/auth/login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'username' => $username,
        'password' => $password
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($httpCode === 200 && isset($result['success']) && $result['success'] && isset($result['data']['token'])) {
        $_SESSION['token'] = $result['data']['token'];
        $_SESSION['user'] = $result['data']['user'];
        header('Location: index.php');
        exit;
    } else {
        $error = isset($result['message']) ? $result['message'] : 'Login failed';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panglong ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h3 class="text-center mb-4">Panglong ERP</h3>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required autocomplete="username">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required autocomplete="current-password">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                        </form>
                        
                        <hr>
                        <p class="text-muted small text-center mb-3">Quick Login:</p>
                        <div class="d-grid gap-2 mb-3">
                            <button onclick="quickLogin('admin', 'password123')" class="btn btn-outline-primary btn-sm">Admin</button>
                            <button onclick="quickLogin('manager1', 'password123')" class="btn btn-outline-success btn-sm">Manager</button>
                            <button onclick="quickLogin('kasir1', 'password123')" class="btn btn-outline-warning btn-sm">Kasir</button>
                            <button onclick="quickLogin('gudang1', 'password123')" class="btn btn-outline-info btn-sm">Gudang</button>
                        </div>
                        
                        <hr>
                        <p class="text-muted small text-center">
                            Default users:<br>
                            admin / password123<br>
                            manager1 / password123<br>
                            kasir1 / password123<br>
                            gudang1 / password123
                        </p>
                    </div>
                </div>
            </div>
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
