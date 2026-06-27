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

    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

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

// CSRF Protection
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

function requireCsrfToken() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCsrfToken($token)) {
        http_response_code(403);
        die('CSRF token validation failed. Please refresh the page and try again.');
    }
}

// Rate Limiting
function checkRateLimit($key, $maxRequests = 60, $windowSeconds = 60) {
    $now = time();
    $windowStart = $now - $windowSeconds;
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }
    
    // Clean old requests
    $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($windowStart) {
        return $timestamp > $windowStart;
    });
    
    // Check if limit exceeded
    if (count($_SESSION[$key]) >= $maxRequests) {
        $retryAfter = $_SESSION[$key][0] + $windowSeconds - $now;
        header('X-RateLimit-Limit: ' . $maxRequests);
        header('X-RateLimit-Remaining: 0');
        header('X-RateLimit-Reset: ' . ($_SESSION[$key][0] + $windowSeconds));
        header('Retry-After: ' . $retryAfter);
        return false;
    }
    
    // Add current request
    $_SESSION[$key][] = $now;
    
    header('X-RateLimit-Limit: ' . $maxRequests);
    header('X-RateLimit-Remaining: ' . ($maxRequests - count($_SESSION[$key])));
    header('X-RateLimit-Reset: ' . ($windowStart + $windowSeconds));
    
    return true;
}

// Input Validation Functions
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    // Allow digits, spaces, +, -, (, )
    return preg_match('/^[\d\s\+\-\(\)]+$/', $phone) && strlen($phone) >= 10;
}

function validateNumeric($value, $min = null, $max = null) {
    if (!is_numeric($value)) return false;
    $num = floatval($value);
    if ($min !== null && $num < $min) return false;
    if ($max !== null && $num > $max) return false;
    return true;
}

function validateStringLength($value, $min = 0, $max = null) {
    $len = strlen($value);
    if ($len < $min) return false;
    if ($max !== null && $len > $max) return false;
    return true;
}

function validateEnum($value, $allowedValues) {
    return in_array($value, $allowedValues, true);
}

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
