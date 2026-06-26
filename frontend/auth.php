<?php

session_start();

require_once __DIR__ . '/db.php';

function login($username, $password) {
    // Check for login attempt lockout
    $lockoutKey = 'login_lockout_' . md5($username);
    if (isset($_SESSION[$lockoutKey]) && $_SESSION[$lockoutKey] > time()) {
        $remaining = ceil(($_SESSION[$lockoutKey] - time()) / 60);
        return ['success' => false, 'message' => "Akun terkunci. Coba lagi dalam {$remaining} menit."];
    }

    $stmt = db()->prepare('SELECT u.*, r.name as role_name, r.slug as role_slug FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.username = ? AND u.is_active = 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        // Track failed attempts
        $attemptKey = 'login_attempts_' . md5($username);
        $attempts = ($_SESSION[$attemptKey] ?? 0) + 1;
        $_SESSION[$attemptKey] = $attempts;

        // Lock after 5 failed attempts for 15 minutes
        if ($attempts >= 5) {
            $_SESSION[$lockoutKey] = time() + (15 * 60); // 15 minutes
            unset($_SESSION[$attemptKey]);
            return ['success' => false, 'message' => 'Terlalu banyak percobaan gagal. Akun terkunci selama 15 menit.'];
        }

        $remaining = 5 - $attempts;
        return ['success' => false, 'message' => "Username atau password salah. Sisa percobaan: {$remaining}"];
    }

    $perms = [];
    $stmt = db()->prepare('SELECT p.name FROM role_permission rp JOIN permissions p ON rp.permission_id = p.id WHERE rp.role_id = ?');
    $stmt->execute([$user['role_id']]);
    foreach ($stmt->fetchAll() as $row) {
        $perms[] = $row['name'];
    }

    $_SESSION['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'role_id' => $user['role_id'],
        'role_name' => $user['role_name'],
        'role_slug' => $user['role_slug'],
        'tenant_id' => $user['tenant_id'],
        'branch_id' => $user['branch_id'],
        'permissions' => $perms,
    ];

    // Clear failed attempts on successful login
    $attemptKey = 'login_attempts_' . md5($username);
    unset($_SESSION[$attemptKey]);

    db()->prepare('UPDATE users SET last_login_at = datetime("now") WHERE id = ?')->execute([$user['id']]);

    return ['success' => true, 'user' => $_SESSION['user']];
}

function logout() {
    session_unset();
    session_destroy();
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}

function userRole() {
    $u = currentUser();
    return $u['role_slug'] ?? 'guest';
}

function userFullName() {
    $u = currentUser();
    return $u['full_name'] ?? 'User';
}

function hasPermission($slug) {
    $u = currentUser();
    if (!$u) return false;
    if ($u['role_slug'] === 'owner') return true;
    return in_array($slug, $u['permissions'] ?? []);
}

function requireLogin() {
    if (!currentUser()) {
        header('Location: login.php?msg=timeout');
        exit;
    }
}

function requirePermission($slug) {
    requireLogin();
    if (!hasPermission($slug)) {
        http_response_code(403);
        die('Akses ditolak. Permission required: ' . htmlspecialchars($slug));
    }
}
