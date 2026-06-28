<?php

date_default_timezone_set('Asia/Jakarta');
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

// Enforce HTTPS and secure session cookies outside of localhost
$isLocalhost = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']);
if (!$isLocalhost && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')) {
    $redirectUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/');
    header('Location: ' . $redirectUrl, true, 301);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => !$isLocalhost,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

require_once __DIR__ . '/auth.php';

// === FUNGSI BAHASA INDONESIA ===

/** Format tanggal Indonesia: 26 Juni 2026 */
function tglIndo($date, $withDay = false) {
    if (!$date) return '-';
    $ts = strtotime($date);
    if ($ts === false) return $date;
    $hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $d = date('j', $ts); $m = (int)date('n', $ts); $y = date('Y', $ts);
    $hasil = $withDay ? $hari[(int)date('w', $ts)] . ', ' : '';
    return $hasil . $d . ' ' . $bulan[$m] . ' ' . $y;
}

/** Format tanggal+jam Indonesia: 26 Jun 2026, 14:30 */
function tglJamIndo($datetime) {
    if (!$datetime) return '-';
    $ts = strtotime($datetime);
    if ($ts === false) return $datetime;
    $bulan = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
    return date('j', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y H:i', $ts);
}

/** Format rupiah: Rp 1.250.000 */
function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

/** Format rupiah dengan desimal: Rp 1.250.000,50 */
function rupiahDetail($angka) {
    return 'Rp ' . number_format((float)$angka, 2, ',', '.');
}

/** Terbilang: angka ke kata bahasa Indonesia */
function terbilang($angka) {
    $angka = abs((float)$angka);
    $huruf = ['','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas'];
    if ($angka < 12) return $huruf[(int)$angka];
    if ($angka < 20) return terbilang($angka - 10) . ' belas';
    if ($angka < 100) return terbilang($angka / 10) . ' puluh' . ($angka % 10 > 0 ? ' ' . terbilang($angka % 10) : '');
    if ($angka < 200) return 'seratus' . ($angka - 100 > 0 ? ' ' . terbilang($angka - 100) : '');
    if ($angka < 1000) return terbilang($angka / 100) . ' ratus' . ($angka % 100 > 0 ? ' ' . terbilang($angka % 100) : '');
    if ($angka < 2000) return 'seribu' . ($angka - 1000 > 0 ? ' ' . terbilang($angka - 1000) : '');
    if ($angka < 1000000) return terbilang($angka / 1000) . ' ribu' . ($angka % 1000 > 0 ? ' ' . terbilang($angka % 1000) : '');
    if ($angka < 1000000000) return terbilang($angka / 1000000) . ' juta' . ($angka % 1000000 > 0 ? ' ' . terbilang($angka % 1000000) : '');
    if ($angka < 1000000000000) return terbilang($angka / 1000000000) . ' miliar' . ($angka % 1000000000 > 0 ? ' ' . terbilang($angka % 1000000000) : '');
    return terbilang($angka / 1000000000000) . ' triliun' . ($angka % 1000000000000 > 0 ? ' ' . terbilang($angka % 1000000000000) : '');
}

/** Terbilang lengkap dengan suffix */
function terbilangRupiah($angka) {
    if ($angka < 0) return 'minus ' . terbilang(abs($angka)) . ' rupiah';
    return terbilang($angka) . ' rupiah';
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

requireLogin();

// === TEMA: dark mode / eye-care (session-based) ===
$theme = $_SESSION['theme'] ?? 'light';
if (isset($_GET['set_theme'])) {
    $t = $_GET['set_theme'];
    if (in_array($t, ['light', 'dark', 'eyecare'])) {
        $_SESSION['theme'] = $t;
        $theme = $t;
    }
    $back = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header("Location: $back");
    exit;
}

// === RBAC NAV: definisi menu per role ===
function getNavLinks() {
    $role = userRole();
    $all = [
        'dashboard'      => ['index.php',           'bi-speedometer2',          'Beranda',          ['owner','manager','kasir','gudang','accounting','supervisor','super_admin']],
        'platform'       => ['dropdown',            'bi-cloud',                 'Platform',         ['super_admin']],
        'tenants'        => ['tenants.php',         'bi-buildings',              'Tenant',           ['super_admin']],
        'users'          => ['users.php',           'bi-people',                'User',             ['super_admin','owner','manager']],
        'master'         => ['dropdown',            'bi-database',              'Master Data',      ['owner','manager','gudang']],
        'products'       => ['products.php',        'bi-box',                   'Produk',           ['owner','manager','gudang']],
        'customers'      => ['customers.php',       'bi-people',                'Pelanggan',        ['owner','manager','kasir','accounting']],
        'suppliers'      => ['suppliers.php',       'bi-truck',                 'Supplier',         ['owner','manager','gudang']],
        'warehouses'     => ['warehouses.php',      'bi-building',              'Gudang',           ['owner','manager','gudang']],
        'penjualan'      => ['dropdown',            'bi-cart',                  'Penjualan',        ['owner','manager','kasir']],
        'sales'          => ['sales.php',           'bi-receipt',                'Transaksi',        ['owner','manager','kasir']],
        'sales-orders'   => ['sales_orders.php',    'bi-clipboard-data',        'Sales Order',      ['owner','manager','kasir']],
        'quotations'     => ['quotations.php',      'bi-file-earmark-text',     'Penawaran',        ['owner','manager','kasir']],
        'deliveries'     => ['deliveries.php',      'bi-truck-front',           'Pengiriman',       ['owner','manager','gudang','kasir']],
        'whatsapp'       => ['whatsapp.php',        'bi-whatsapp',              'WhatsApp',         ['owner','manager','kasir']],
        'salesman'       => ['salesman_app.php',    'bi-phone',                 'Salesman',         ['owner','manager','kasir']],
        'purchase-orders'=> ['purchase-orders.php', 'bi-bag',                   'PO',               ['owner','manager','gudang']],
        'inventory'      => ['dropdown',            'bi-box-seam',              'Inventaris',       ['owner','manager','gudang']],
        'stock'          => ['stock.php',           'bi-boxes',                 'Stok',             ['owner','manager','gudang']],
        'stock-opname'   => ['stock_opname.php',    'bi-clipboard-check',       'Opname',           ['owner','manager','gudang']],
        'stock-transfers'=> ['stock_transfers.php', 'bi-arrow-left-right',      'Mutasi',           ['owner','manager','gudang']],
        'batches'        => ['batches.php',         'bi-layers',                'Batch/FIFO',       ['owner','manager','gudang']],
        'reorder'        => ['reorder.php',         'bi-lightbulb',             'Reorder AI',       ['owner','manager','gudang']],
        'iot'            => ['iot.php',             'bi-thermometer-half',      'IoT',              ['owner','manager','gudang']],
        'logistik'       => ['dropdown',            'bi-truck',                 'Logistik',         ['owner','manager','gudang']],
        'fleet'          => ['fleet.php',           'bi-car-front',             'Kendaraan',        ['owner','manager','gudang']],
        'routes'         => ['routes.php',          'bi-map',                   'Rute',             ['owner','manager','gudang']],
        'keuangan'       => ['dropdown',            'bi-cash-coin',             'Keuangan',         ['owner','manager','accounting']],
        'accounting'     => ['accounting.php',      'bi-journal-text',          'Akuntansi',        ['owner','manager','accounting']],
        'cashbook'       => ['cashbook.php',        'bi-wallet2',                'Kas Buku',         ['owner','manager','accounting']],
        'cash_flow'      => ['cash_flow.php',       'bi-graph-up-arrow',         'Arus Kas',         ['owner','manager','accounting']],
        'fixed-assets'   => ['fixed_assets.php',    'bi-building-gear',         'Aset Tetap',       ['owner','manager','accounting']],
        'e-faktur'       => ['e_faktur.php',        'bi-file-earmark-spreadsheet','e-Faktur',       ['owner','manager','accounting']],
        'closing'        => ['closing.php',         'bi-lock',                  'Tutup Buku',       ['owner','manager','accounting']],
        'reports'        => ['reports.php',         'bi-graph-up',              'Laporan',          ['owner','manager','accounting','supervisor']],
        'ai_marketplace' => ['dropdown',            'bi-stars',                 'AI & Marketplace', ['owner','manager']],
        'ai-insights'    => ['ai_insights.php',     'bi-cpu',                   'AI Insights',      ['owner','manager']],
        'marketplace'    => ['marketplace.php',     'bi-shop',                  'Marketplace',      ['owner','manager']],
        'landed_cost'    => ['landed_cost.php',     'bi-box-seam',              'Landed Cost',      ['owner','manager','gudang']],
        'pricing'        => ['pricing.php',         'bi-tag',                   'Harga',            ['owner','manager']],
        'pengaturan'     => ['dropdown',            'bi-gear',                  'Pengaturan',       ['owner','manager']],
        'settings'       => ['settings.php',        'bi-sliders',               'Konfigurasi',      ['owner','manager']],
        'saas'           => ['saas.php',            'bi-cloud',                 'SaaS',             ['owner']],
        'register'       => ['register.php',       'bi-person-plus',            'Daftar Tenant',    ['super_admin']],
        'returns'        => ['returns.php',         'bi-arrow-left-right',      'Retur',            ['owner','manager','kasir','gudang']],
    ];

    $links = [];
    foreach ($all as $key => $item) {
        if (in_array($role, $item[3])) {
            $links[$key] = [$item[0], $item[1], $item[2]];
        }
    }
    return $links;
}

function renderNav($active = '') {
    $role = userRole();
    $name = htmlspecialchars(userFullName());
    $links = getNavLinks();
    $roleLabels = [
        'owner'=>'Owner','manager'=>'Manager','kasir'=>'Kasir',
        'gudang'=>'Gudang','accounting'=>'Akuntansi','supervisor'=>'Supervisor',
        'super_admin'=>'Super Admin',
    ];
    $roleLabel = $roleLabels[$role] ?? ucfirst($role);

    // Define dropdown groups
    $dropdownGroups = [
        'platform' => ['tenants', 'users', 'register'],
        'master' => ['products', 'customers', 'suppliers', 'warehouses'],
        'penjualan' => ['sales', 'sales-orders', 'quotations', 'deliveries', 'returns', 'whatsapp', 'salesman'],
        'inventory' => ['stock', 'stock-opname', 'stock-transfers', 'batches', 'reorder', 'iot'],
        'logistik' => ['fleet', 'routes'],
        'keuangan' => ['accounting', 'cashbook', 'cash_flow', 'fixed-assets', 'e-faktur', 'closing', 'reports'],
        'ai_marketplace' => ['ai-insights', 'marketplace', 'landed_cost', 'pricing'],
        'pengaturan' => ['users', 'settings', 'saas'],
    ];

    // Role-specific standalone items (items that should be standalone for specific roles even if normally in dropdown)
    $roleSpecificStandalone = [
        'kasir' => ['customers', 'purchase-orders', 'returns', 'whatsapp', 'salesman'],
        'accounting' => ['customers', 'reports'],
        'supervisor' => ['reports'],
        'gudang' => ['returns'],
    ];

    // Get all child keys from dropdown groups
    $allChildKeys = [];
    foreach ($dropdownGroups as $group) {
        $allChildKeys = array_merge($allChildKeys, $group);
    }

    $navHtml = '<nav class="navbar navbar-expand-lg navbar-dark sticky-top app-navbar">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
                <i class="bi bi-box-seam-fill fs-4"></i>
                <span class="d-none d-sm-inline">Panglong ERP</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-label="Menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMain">
                <ul class="navbar-nav me-auto">';

    foreach ($links as $key => $link) {
        // Check if this is a dropdown parent
        if ($link[0] === 'dropdown' && isset($dropdownGroups[$key])) {
            $hasActive = false;
            $dropdownItems = '';
            foreach ($dropdownGroups[$key] as $subKey) {
                if (isset($links[$subKey])) {
                    // Check if this child should be standalone for current role
                    $isRoleSpecificStandalone = isset($roleSpecificStandalone[$role]) && in_array($subKey, $roleSpecificStandalone[$role]);
                    
                    if (!$isRoleSpecificStandalone) {
                        $subActive = $active === $subKey ? 'active' : '';
                        if ($subActive) $hasActive = true;
                        $dropdownItems .= "<li><a class=\"dropdown-item {$subActive}\" href=\"{$links[$subKey][0]}\"><i class=\"bi {$links[$subKey][1]} me-2\"></i>{$links[$subKey][2]}</a></li>";
                    }
                }
            }
            if ($dropdownItems) {
                $dropdownClass = $hasActive ? 'active' : '';
                $navHtml .= "<li class=\"nav-item dropdown\">
                    <a class=\"nav-link dropdown-toggle {$dropdownClass}\" href=\"#\" role=\"button\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\">
                        <i class=\"bi {$link[1]}\"></i><span class=\"nav-label d-xl-inline\"> {$link[2]}</span>
                    </a>
                    <ul class=\"dropdown-menu\">{$dropdownItems}</ul>
                </li>";
            }
        } elseif (!in_array($key, $allChildKeys) || (isset($roleSpecificStandalone[$role]) && in_array($key, $roleSpecificStandalone[$role]))) {
            // Regular menu item (not part of any dropdown as child, OR role-specific standalone)
            $activeClass = $active === $key ? 'active' : '';
            $tip = $link[2];
            $navHtml .= "<li class=\"nav-item\"><a class=\"nav-link {$activeClass}\" href=\"{$link[0]}\" title=\"{$tip}\"><i class=\"bi {$link[1]}\"></i><span class=\"nav-label d-lg-inline\"> {$link[2]}</span></a></li>";
        }
    }

    $navHtml .= '</ul>
                <div class="navbar-nav ms-auto d-flex align-items-center gap-2 flex-row">
                    <button class="btn btn-sm btn-outline-light nav-btn-icon" onclick="toggleFullscreen()" title="Layar Penuh" data-bs-toggle="tooltip" data-bs-title="Layar Penuh">
                        <i class="bi bi-fullscreen"></i>
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-light nav-btn-icon dropdown-toggle" data-bs-toggle="dropdown" title="Tema" data-bs-toggle="tooltip" data-bs-title="Tema tampilan">
                            <i class="bi bi-palette"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="?set_theme=light"><i class="bi bi-sun"></i> Terang</a></li>
                            <li><a class="dropdown-item" href="?set_theme=dark"><i class="bi bi-moon-stars"></i> Gelap</a></li>
                            <li><a class="dropdown-item" href="?set_theme=eyecare"><i class="bi bi-eye"></i> Mode Mata (Sepia)</a></li>
                        </ul>
                    </div>
                    <span class="navbar-text d-none d-md-inline-flex align-items-center gap-1">
                        <i class="bi bi-person-circle"></i> ' . $name . '
                        <span class="badge bg-light text-primary ms-1">' . $roleLabel . '</span>
                    </span>
                    <a href="logout.php" class="btn btn-sm btn-outline-light" title="Keluar"><i class="bi bi-box-arrow-right"></i><span class="d-none d-lg-inline"> Keluar</span></a>
                </div>
            </div>
        </div>
    </nav>';

    echo $navHtml;
}

function renderHead($title) {
    global $theme;
    $themeAttr = htmlspecialchars($theme);
    $csrfToken = generateCsrfToken();
    $isLocalhost = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']);
    
    // Security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    
    echo '<!DOCTYPE html>
<html lang="id" data-bs-theme="' . $themeAttr . '">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . ' - Panglong ERP</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.css">
    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script>const API_URL="ajax.php";const API_TOKEN="";const CSRF_TOKEN="' . htmlspecialchars($csrfToken) . '";const TEST_MODE=' . ($isLocalhost ? 'true' : 'false') . ';</script>
    <meta name="csrf-token" content="' . htmlspecialchars($csrfToken) . '">
    <script>
    // Screen detection and dynamic layout adjustment
    function adjustLayout() {
        var width = window.innerWidth;
        var body = document.body;
        
        // Remove all view classes
        body.classList.remove("mobile-view", "tablet-view", "desktop-view");
        
        // Add appropriate view class
        if (width < 768) {
            body.classList.add("mobile-view");
        } else if (width < 1200) {
            body.classList.add("tablet-view");
        } else {
            body.classList.add("desktop-view");
        }
        
        // Adjust navbar labels based on screen size
        adjustNavbarLabels();
    }
    
    function adjustNavbarLabels() {
        var width = window.innerWidth;
        var navLabels = document.querySelectorAll(".nav-label");
        
        navLabels.forEach(function(label) {
            if (width < 1200) {
                label.classList.add("d-none");
            } else {
                label.classList.remove("d-none");
            }
        });
    }
    
    // Listen for resize
    window.addEventListener("resize", adjustLayout);
    window.addEventListener("DOMContentLoaded", adjustLayout);
    </script>
    <style>
      .tooltip-inner{max-width:300px}
      .app-navbar{background:linear-gradient(135deg,#1a4d8f 0%,#0d6efd 100%);box-shadow:0 2px 8px rgba(0,0,0,.15)}
      .nav-btn-icon{width:36px;height:36px;padding:0;display:inline-flex;align-items:center;justify-content:center}
      .dropdown-menu{max-height:400px;overflow-y:auto}
      .dropdown-item{padding:0.5rem 1rem}
      .dropdown-item.active{background:rgba(13,110,253,.9)}
      .card{border:none;border-radius:.5rem;box-shadow:0 1px 3px rgba(0,0,0,.08)}
      .card-header{border-radius:.5rem .5rem 0 0!important;font-weight:600}
      .table > :not(caption) > * > *{padding:.6rem .5rem}
      .table-responsive{margin-bottom:1rem}
      .btn{border-radius:.375rem;font-weight:500}
      body{font-size:0.925rem;line-height:1.6;overflow-x:hidden}
      h1{font-size:1.75rem;font-weight:700;margin-bottom:0.5rem}
      h2{font-size:1.5rem;font-weight:600;margin-bottom:0.5rem}
      h3{font-size:1.25rem;font-weight:600;margin-bottom:0.5rem}
      h4{font-size:1.1rem;font-weight:600;margin-bottom:0.5rem}
      h5{font-size:1rem;font-weight:600;margin-bottom:0.5rem}
      h6{font-size:0.925rem;font-weight:600;margin-bottom:0.5rem}
      @media(min-width:768px){
        body{font-size:0.95rem}
        h1{font-size:2rem}
        h2{font-size:1.75rem}
        h3{font-size:1.5rem}
      }
      .nav-label{display:none}
      @media(min-width:1200px){
        .nav-label{display:inline}
      }
      [data-bs-theme="dark"]{--bs-body-bg:#1a1d24;--bs-body-color:#d8dde6}
      [data-bs-theme="dark"] .card{background:#232730;box-shadow:0 1px 3px rgba(0,0,0,.3)}
      [data-bs-theme="dark"] .card-header{background:#2a2f3a;border-bottom:1px solid #3a3f4a}
      [data-bs-theme="dark"] .table{--bs-table-bg:#232730;color:#d8dde6}
      [data-bs-theme="dark"] .table > :not(caption) > * > *{background-color:transparent}
      [data-bs-theme="dark"] .modal-content{background:#232730;color:#d8dde6}
      [data-bs-theme="dark"] .form-control{background:#2a2f3a;border-color:#3a3f4a;color:#d8dde6}
      [data-bs-theme="dark"] .form-select{background:#2a2f3a;border-color:#3a3f4a;color:#d8dde6}
      [data-bs-theme="eyecare"]{--bs-body-bg:#f5ecd9;--bs-body-color:#3d3320}
      [data-bs-theme="eyecare"] .card{background:#faf3e3;box-shadow:0 1px 3px rgba(60,40,0,.08)}
      [data-bs-theme="eyecare"] .card-header{background:#f0e6cc}
      [data-bs-theme="eyecare"] .app-navbar{background:linear-gradient(135deg,#5a4a2a 0%,#8a6a3a 100%)}
      [data-bs-theme="eyecare"] .table{--bs-table-bg:#faf3e3}
      [data-bs-theme="eyecare"] .form-control{background:#fff8e8;border-color:#d4c4a0}
      [data-bs-theme="eyecare"] .form-select{background:#fff8e8;border-color:#d4c4a0}
      [data-bs-theme="eyecare"] .modal-content{background:#faf3e3}
      .fullscreen-active{padding-top:0!important}
      .fullscreen-active .app-navbar{display:none}
      .fs-exit-btn{position:fixed;top:8px;right:8px;z-index:9999;display:none}
      .fullscreen-active .fs-exit-btn{display:inline-flex}
    </style>
</head>
<body>';
}

function renderFoot() {
    echo '
    <button class="btn btn-sm btn-danger fs-exit-btn nav-btn-icon" onclick="toggleFullscreen()" title="Keluar Layar Penuh"><i class="bi bi-fullscreen-exit"></i></button>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
    // Fullscreen toggle
    function toggleFullscreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(function(){});
        } else {
            if (document.exitFullscreen) document.exitFullscreen();
        }
    }
    document.addEventListener("fullscreenchange", function() {
        if (document.fullscreenElement) {
            document.body.classList.add("fullscreen-active");
        } else {
            document.body.classList.remove("fullscreen-active");
        }
    });

    // Inisialisasi tooltip Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll(\'[data-bs-toggle="tooltip"]\'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Setup AJAX to include CSRF token
    $.ajaxSetup({
        headers: {
            \'X-CSRF-Token\': CSRF_TOKEN
        }
    });

    // Ensure all same-origin fetch() calls include CSRF token for mutating methods
    (function() {
        var originalFetch = window.fetch;
        window.fetch = function(url, options) {
            options = options || {};
            var method = (options.method || \'GET\').toUpperCase();
            var targetUrl;
            try {
                targetUrl = new URL(url, window.location.href);
            } catch (e) {
                return originalFetch(url, options);
            }
            if ([\'POST\', \'PUT\', \'DELETE\', \'PATCH\'].indexOf(method) !== -1 && targetUrl.origin === window.location.origin) {
                if (!options.headers) {
                    options.headers = {};
                } else if (options.headers instanceof Headers) {
                    var newHeaders = {};
                    options.headers.forEach(function(value, key) {
                        newHeaders[key] = value;
                    });
                    options.headers = newHeaders;
                }
                if (!options.headers[\'X-CSRF-Token\'] && !options.headers[\'x-csrf-token\']) {
                    options.headers[\'X-CSRF-Token\'] = CSRF_TOKEN;
                }
            }
            return originalFetch(url, options);
        };
    })();

    // Anti-double-click: disable submit buttons on form submit, re-enable after
    (function() {
        document.addEventListener(\'submit\', function(e) {
            var form = e.target;
            if (form.dataset.submitting === \'1\') {
                e.preventDefault();
                return;
            }
            form.dataset.submitting = \'1\';
            var btns = form.querySelectorAll(\'button[type="submit"], input[type="submit"]\');
            btns.forEach(function(btn) {
                btn.dataset.origHtml = btn.innerHTML;
                btn.disabled = true;
                if (btn.tagName === \'BUTTON\') {
                    btn.innerHTML = \'<span class="spinner-border spinner-border-sm" role="status"></span> Memproses...\';
                }
            });
            setTimeout(function() {
                form.dataset.submitting = \'0\';
                btns.forEach(function(btn) {
                    btn.disabled = false;
                    if (btn.dataset.origHtml) {
                        btn.innerHTML = btn.dataset.origHtml;
                        delete btn.dataset.origHtml;
                    }
                });
            }, 10000);
        }, true);
    })();

    // Anti-double-click for AJAX buttons with data-loading attribute
    (function() {
        document.addEventListener(\'click\', function(e) {
            var btn = e.target.closest(\'[data-loading="true"]\');
            if (!btn || btn.disabled) return;
            btn.disabled = true;
            btn.dataset.origHtml = btn.innerHTML;
            if (btn.tagName === \'BUTTON\') {
                btn.innerHTML = \'<span class="spinner-border spinner-border-sm" role="status"></span>\';
            }
            setTimeout(function() {
                btn.disabled = false;
                if (btn.dataset.origHtml) {
                    btn.innerHTML = btn.dataset.origHtml;
                    delete btn.dataset.origHtml;
                }
            }, 15000);
        }, true);
    })();

    // Session heartbeat: ping server every 5 minutes to keep session alive
    (function() {
        var heartbeatInterval = 5 * 60 * 1000;
        setInterval(function() {
            fetch(\'ajax.php\', {
                method: \'POST\',
                headers: {
                    \'Content-Type\': \'application/x-www-form-urlencoded\',
                    \'X-CSRF-Token\': CSRF_TOKEN
                },
                body: \'endpoint=heartbeat\'
            }).catch(function(){});
        }, heartbeatInterval);
    })();

    // Auto-refresh session timer (warn 2 min before timeout)
    (function() {
        var idleTime = 0;
        document.addEventListener("mousemove", function(){ idleTime = 0; });
        document.addEventListener("keypress", function(){ idleTime = 0; });
        setInterval(function(){ idleTime += 1; }, 60000);
    })();

    // P1 #45: Global AJAX error handler
    (function() {
        var origFetch = window.fetch;
        window.fetch = function() {
            return origFetch.apply(this, arguments).then(function(response) {
                if (!response.ok && response.status === 403) {
                    console.error("AJAX 403: Akses ditolak. Session mungkin berakhir.");
                }
                return response;
            }).catch(function(err) {
                console.error("Network error:", err.message);
                throw err;
            });
        };

        // P1 #35: Connection status indicator
        var statusDiv = document.createElement("div");
        statusDiv.id = "conn-status";
        statusDiv.style.cssText = "position:fixed;bottom:0;left:0;right:0;text-align:center;padding:4px;font-size:11px;z-index:9999;display:none;background:#dc3545;color:#fff";
        statusDiv.textContent = "Koneksi terputus - perubahan mungkin tidak tersimpan";
        document.body.appendChild(statusDiv);
        window.addEventListener("online", function() {
            statusDiv.style.display = "none";
        });
        window.addEventListener("offline", function() {
            statusDiv.style.display = "block";
        });
    })();
    </script>
</body>
</html>';
}
