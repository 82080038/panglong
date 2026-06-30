<?php
/**
 * Page Audit Script - Cycle 5
 * Fetches every frontend page for each role and checks for errors, warnings,
 * notices, broken HTML, and inconsistent data patterns.
 */

$baseUrl = 'http://localhost/panglong/frontend';
$roles = [
    'admin' => 'password123',
    'manager1' => 'password123',
    'kasir1' => 'password123',
    'gudang1' => 'password123',
    'accounting1' => 'password123',
    'supervisor1' => 'password123',
];

$pages = [
    'index', 'products', 'customers', 'suppliers', 'warehouses',
    'sales', 'sales_orders', 'quotations', 'deliveries', 'purchase-orders',
    'stock', 'stock_opname', 'stock_transfers', 'batches', 'reorder', 'iot',
    'fleet', 'routes', 'accounting', 'cashbook', 'cash_flow', 'fixed_assets',
    'e_faktur', 'closing', 'reports', 'ai_insights', 'marketplace', 'landed_cost',
    'pricing', 'settings', 'saas', 'users', 'tenants', 'returns', 'whatsapp',
    'salesman_app', 'product_detail', 'customer_detail', 'sale_detail',
    'print_nota', 'qr_generator'
];

function getCookieFile($role) {
    return '/tmp/audit_' . $role . '.txt';
}

function login($role, $password) {
    global $baseUrl;
    $cookieFile = getCookieFile($role);
    if (file_exists($cookieFile)) unlink($cookieFile);
    $ch = curl_init($baseUrl . '/login.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "username={$role}&password={$password}");
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode;
}

function fetchPage($role, $page) {
    global $baseUrl;
    $ch = curl_init($baseUrl . '/' . $page . '.php');
    curl_setopt($ch, CURLOPT_COOKIEJAR, getCookieFile($role));
    curl_setopt($ch, CURLOPT_COOKIEFILE, getCookieFile($role));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['html' => $html, 'http' => $httpCode];
}

function checkHtml($html, $httpCode) {
    $issues = [];
    if (strpos($html, 'Fatal error') !== false) $issues[] = 'Fatal error';
    if (strpos($html, 'Parse error') !== false) $issues[] = 'Parse error';
    if (strpos($html, 'Warning:') !== false) $issues[] = 'PHP Warning';
    if (strpos($html, 'Notice:') !== false) $issues[] = 'PHP Notice';
    if (strpos($html, 'Deprecated:') !== false) $issues[] = 'PHP Deprecated';
    if (strpos($html, 'Uncaught') !== false) $issues[] = 'Uncaught exception';
    // 403/400 pages often return plain text or minimal HTML; only flag HTML issues on 200 pages
    if ($httpCode === 200) {
        // Accept full pages or valid HTML fragments (e.g., <p>, <div>, etc.)
        if (strpos($html, '<') === false) {
            $issues[] = 'No HTML content';
        } elseif (strpos($html, '<html') === false && strpos($html, '<!DOCTYPE') === false && !preg_match('/<[a-z][a-z0-9]*\b/i', $html)) {
            $issues[] = 'No HTML tag';
        }
        if (strpos($html, '<body') !== false && substr_count($html, '<body') !== substr_count($html, '</body>')) {
            $issues[] = 'Unmatched body tag';
        }
    }
    return $issues;
}

$results = [];
foreach ($roles as $role => $password) {
    login($role, $password);
    foreach ($pages as $page) {
        $res = fetchPage($role, $page);
        $issues = checkHtml($res['html'], $res['http']);
        $results[] = [
            'role' => $role,
            'page' => $page,
            'http' => $res['http'],
            'size' => strlen($res['html']),
            'issues' => $issues
        ];
    }
}

$failed = array_filter($results, function($r) {
    return $r['http'] >= 500 || !empty($r['issues']);
});

// Re-check for 200 pages with HTML issues
$htmlIssueCount = 0;
foreach ($results as $r) {
    if ($r['http'] === 200 && !empty($r['issues'])) $htmlIssueCount++;
}

// Print summary
$total = count($results);
$failCount = count($failed);
echo "Total checks: {$total}\n";
echo "Failed: {$failCount}\n\n";

foreach ($results as $r) {
    $status = $r['http'] >= 500 || !empty($r['issues']) ? 'FAIL' : 'OK';
    $issueStr = empty($r['issues']) ? '-' : implode(', ', $r['issues']);
    echo sprintf("%-12s %-20s HTTP %3d  %s  %s\n", $r['role'], $r['page'], $r['http'], $status, $issueStr);
}

// Save JSON report
$reportPath = __DIR__ . '/../docs/page_audit_report.json';
file_put_contents($reportPath, json_encode($results, JSON_PRETTY_PRINT));
echo "\nReport saved to: {$reportPath}\n";
