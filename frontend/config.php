<?php

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
        'dashboard'      => ['index.php',           'bi-speedometer2',          'Beranda',          ['owner','manager','kasir','gudang','accounting','supervisor']],
        'products'       => ['products.php',        'bi-box',                   'Produk',           ['owner','manager','gudang']],
        'customers'      => ['customers.php',       'bi-people',                'Pelanggan',        ['owner','manager','kasir','accounting']],
        'sales'          => ['sales.php',           'bi-cart',                  'Penjualan',        ['owner','manager','kasir']],
        'sales-orders'   => ['sales_orders.php',    'bi-clipboard-data',        'SO',               ['owner','manager','kasir']],
        'quotations'     => ['quotations.php',      'bi-file-earmark-text',     'Penawaran',        ['owner','manager','kasir']],
        'deliveries'     => ['deliveries.php',      'bi-truck-front',           'Pengiriman',       ['owner','manager','gudang','kasir']],
        'returns'        => ['returns.php',         'bi-arrow-left-right',      'Retur',            ['owner','manager','kasir','gudang']],
        'stock'          => ['stock.php',           'bi-box-seam',              'Stok',             ['owner','manager','gudang']],
        'stock-opname'   => ['stock_opname.php',    'bi-clipboard-check',       'Opname',           ['owner','manager','gudang']],
        'stock-transfers'=> ['stock_transfers.php', 'bi-arrow-left-right',      'Mutasi',           ['owner','manager','gudang']],
        'suppliers'      => ['suppliers.php',       'bi-truck',                 'Supplier',         ['owner','manager','gudang']],
        'purchase-orders'=> ['purchase-orders.php', 'bi-bag-check',             'PO',               ['owner','manager','gudang']],
        'pricing'        => ['pricing.php',         'bi-tag',                   'Harga',            ['owner','manager']],
        'reports'        => ['reports.php',         'bi-graph-up',              'Laporan',          ['owner','manager','accounting','supervisor']],
        'accounting'     => ['accounting.php',      'bi-journal-text',          'Akuntansi',        ['owner','manager','accounting']],
        'cashbook'       => ['cashbook.php',        'bi-cash-coin',             'Kas Buku',         ['owner','manager','accounting']],
        'fixed-assets'   => ['fixed_assets.php',    'bi-building-gear',         'Aset Tetap',       ['owner','manager','accounting']],
        'warehouses'     => ['warehouses.php',      'bi-building',              'Gudang',           ['owner','manager','gudang']],
        'reorder'        => ['reorder.php',         'bi-lightbulb',             'Reorder AI',       ['owner','manager','gudang']],
        'ai-insights'    => ['ai_insights.php',     'bi-cpu',                   'AI Insights',      ['owner','manager']],
        'marketplace'    => ['marketplace.php',     'bi-shop',                  'Marketplace',      ['owner','manager']],
        'fleet'          => ['fleet.php',           'bi-truck',                 'Kendaraan',        ['owner','manager','gudang']],
        'routes'         => ['routes.php',          'bi-map',                   'Rute',             ['owner','manager','gudang']],
        'whatsapp'       => ['whatsapp.php',        'bi-whatsapp',              'WhatsApp',         ['owner','manager','kasir']],
        'e-faktur'       => ['e_faktur.php',        'bi-file-earmark-spreadsheet','e-Faktur',       ['owner','manager','accounting']],
        'iot'            => ['iot.php',             'bi-thermometer-half',      'IoT',              ['owner','manager','gudang']],
        'landed_cost'    => ['landed_cost.php',     'bi-box-seam',              'Landed Cost',      ['owner','manager','gudang']],
        'batches'        => ['batches.php',         'bi-layers',                'Batch/FIFO',       ['owner','manager','gudang']],
        'cash_flow'      => ['cash_flow.php',       'bi-cash-coin',             'Arus Kas',         ['owner','manager','accounting']],
        'closing'        => ['closing.php',         'bi-lock',                  'Tutup Buku',       ['owner','manager','accounting']],
        'salesman_app'   => ['salesman_app.php',    'bi-phone',                 'Salesman',         ['owner','manager','kasir']],
        'users'          => ['users.php',           'bi-person-gear',           'Pengguna',         ['owner','manager']],
        'settings'       => ['settings.php',        'bi-gear',                  'Pengaturan',       ['owner','manager']],
        'saas'           => ['saas.php',            'bi-cloud',                 'SaaS',             ['owner']],
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
    ];
    $roleLabel = $roleLabels[$role] ?? ucfirst($role);

    $navHtml = '<nav class="navbar navbar-expand-xl navbar-dark sticky-top app-navbar">
        <div class="container-fluid px-lg-3">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
                <i class="bi bi-box-seam-fill fs-4"></i>
                <span class="d-none d-sm-inline">Panglong ERP</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-label="Menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMain">
                <ul class="navbar-nav me-auto nav-scroll">';

    foreach ($links as $key => $link) {
        $activeClass = $active === $key ? 'active' : '';
        $tip = $link[2];
        $navHtml .= "<li class=\"nav-item\"><a class=\"nav-link {$activeClass}\" href=\"{$link[0]}\" title=\"{$tip}\"><i class=\"bi {$link[1]}\"></i><span class=\"nav-label d-xl-inline\"> {$link[2]}</span></a></li>";
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
    echo '<!DOCTYPE html>
<html lang="id" data-bs-theme="' . $themeAttr . '">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>' . htmlspecialchars($title) . ' - Panglong ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>const API_URL="ajax.php";const API_TOKEN="";</script>
    <style>
      .tooltip-inner{max-width:300px}
      .app-navbar{background:linear-gradient(135deg,#1a4d8f 0%,#0d6efd 100%);box-shadow:0 2px 8px rgba(0,0,0,.15)}
      .nav-scroll{overflow-x:auto;scrollbar-width:thin;max-width:100%}
      .nav-scroll::-webkit-scrollbar{height:4px}
      .nav-scroll::-webkit-scrollbar-thumb{background:rgba(255,255,255,.3);border-radius:2px}
      .nav-btn-icon{width:36px;height:36px;padding:0;display:inline-flex;align-items:center;justify-content:center}
      .card{border:none;border-radius:.5rem;box-shadow:0 1px 3px rgba(0,0,0,.08)}
      .card-header{border-radius:.5rem .5rem 0 0!important;font-weight:600}
      .table > :not(caption) > * > *{padding:.6rem .5rem}
      .btn{border-radius:.375rem;font-weight:500}
      .container{max-width:1400px}
      @media(min-width:1600px){.container{max-width:1560px}}
      @media(min-width:1900px){.container{max-width:1800px}}
      @media(max-width:575.98px){.nav-label{display:inline!important}}
      @media(min-width:1200px){.nav-label{display:inline!important}}
      body{font-size:.9rem}
      @media(min-width:768px){body{font-size:.875rem}}
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

    // Auto-refresh session timer (warn 2 min before timeout)
    (function() {
        var idleTime = 0;
        document.addEventListener("mousemove", function(){ idleTime = 0; });
        document.addEventListener("keypress", function(){ idleTime = 0; });
        setInterval(function(){ idleTime += 1; }, 60000);
    })();
    </script>
</body>
</html>';
}
