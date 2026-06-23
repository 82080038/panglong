<?php
session_start();

define('API_URL', 'http://127.0.0.1:8000/api/v1');

if (!isset($_SESSION['token'])) {
    header('Location: login.php');
    exit;
}

// Session timeout: 30 minutes idle
$timeoutMinutes = 30;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > ($timeoutMinutes * 60)) {
    session_unset();
    session_destroy();
    header('Location: login.php?msg=timeout');
    exit;
}
$_SESSION['last_activity'] = time();

function apiCall($endpoint, $method = 'GET', $data = null) {
    $ch = curl_init();
    $url = API_URL . $endpoint;
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $_SESSION['token']
    ]);
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'body' => json_decode($response, true),
        'code' => $httpCode,
    ];
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}

function userRole() {
    $user = currentUser();
    return $user['role']['slug'] ?? 'guest';
}

function userFullName() {
    $user = currentUser();
    return $user['full_name'] ?? 'User';
}

function renderNav($active = '') {
    $role = userRole();
    $name = htmlspecialchars(userFullName());
    
    $links = [
        'dashboard' => ['index.php', 'bi-speedometer2', 'Dashboard'],
        'products' => ['products.php', 'bi-box', 'Products'],
        'customers' => ['customers.php', 'bi-people', 'Customers'],
        'sales' => ['sales.php', 'bi-cart', 'Sales'],
        'deliveries' => ['deliveries.php', 'bi-truck-front', 'Deliveries'],
        'stock' => ['stock.php', 'bi-box-seam', 'Stock'],
        'stock-opname' => ['stock_opname.php', 'bi-clipboard-check', 'Opname'],
        'suppliers' => ['suppliers.php', 'bi-truck', 'Suppliers'],
        'purchase-orders' => ['purchase-orders.php', 'bi-bag-check', 'Purchase Orders'],
        'reports' => ['reports.php', 'bi-graph-up', 'Reports'],
        'accounting' => ['accounting.php', 'bi-journal-text', 'Accounting'],
        'warehouses' => ['warehouses.php', 'bi-building', 'Warehouses'],
        'reorder' => ['reorder.php', 'bi-lightbulb', 'Reorder AI'],
    ];
    
    if ($role === 'owner' || $role === 'manager') {
        $links['users'] = ['users.php', 'bi-person-gear', 'Users'];
        $links['settings'] = ['settings.php', 'bi-gear', 'Settings'];
    }
    if ($role === 'owner') {
        $links['saas'] = ['saas.php', 'bi-cloud', 'SaaS'];
    }
    
    $navHtml = '<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Panglong ERP</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMain">
                <ul class="navbar-nav me-auto">';
    
    foreach ($links as $key => $link) {
        $activeClass = $active === $key ? 'active' : '';
        $navHtml .= "<li class=\"nav-item\"><a class=\"nav-link {$activeClass}\" href=\"{$link[0]}\"><i class=\"bi {$link[1]}\"></i> {$link[2]}</a></li>";
    }
    
    $navHtml .= '</ul>
                <div class="navbar-nav ms-auto">
                    <span class="navbar-text me-3">
                        <i class="bi bi-person-circle"></i> ' . $name . '
                        <span class="badge bg-light text-primary ms-1">' . ucfirst($role) . '</span>
                    </span>
                    <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
                </div>
            </div>
        </div>
    </nav>';
    
    echo $navHtml;
}

function renderHead($title) {
    echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>';
}

function renderFoot() {
    echo '
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
}
