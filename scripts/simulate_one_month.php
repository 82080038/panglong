<?php
/**
 * Panglong ERP - 1 Month Simulation Script
 * 
 * Simulates a full month of operations for a tenant:
 * 1. Login as owner
 * 2. Create staff users (manager, kasir, gudang, accounting)
 * 3. Create suppliers
 * 4. Create products with units
 * 5. Create customers
 * 6. Purchase orders + receiving (stock in)
 * 7. Sales transactions (stock out)
 * 8. Quotations
 * 9. Sales orders
 * 10. Stock adjustments
 * 11. Stock opname
 * 12. Stock transfers
 * 13. Payments
 * 14. Deliveries
 * 15. Returns
 * 16. Reports verification
 * 
 * Usage: php scripts/simulate_one_month.php
 */

ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

function freshDb() {
    $db = new PDO('sqlite:' . __DIR__ . '/../database/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
}

$db = freshDb();

$now = date('Y-m-d H:i:s');
$baseUrl = 'http://localhost/panglong/frontend';

// Get the latest tenant
$tenant = $db->query('SELECT * FROM tenants ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
$tenantId = $tenant['id'];
echo "=== SIMULASI 1 BULAN: {$tenant['name']} (tenant_id=$tenantId) ===\n\n";

// Get owner credentials
$owner = $db->query("SELECT username FROM users WHERE tenant_id=$tenantId AND role_id=1")->fetch(PDO::FETCH_ASSOC);
$ownerUsername = $owner['username'];
$ownerPassword = 'password123';

// Helper: login and get cookies
function login($baseUrl, $username, $password) {
    $cookieFile = tempnam(sys_get_temp_dir(), 'pw_');
    $ch = curl_init("$baseUrl/login.php");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "username=$username&password=$password");
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    curl_close($ch);
    return $cookieFile;
}

// Helper: AJAX request
function ajax($baseUrl, $cookieFile, $endpoint, $method, $data = []) {
    $url = "$baseUrl/ajax.php?endpoint=$endpoint&test_mode=true";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    if ($method === 'GET') {
        if (!empty($data)) {
            $url .= '&' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
    } else {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = json_decode($response, true);
    return ['code' => $httpCode, 'data' => $json, 'raw' => $response];
}

// Helper: POST form (for non-AJAX pages)
function postForm($baseUrl, $cookieFile, $page, $data) {
    $ch = curl_init("$baseUrl/$page");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => $response];
}

$pass = 0;
$fail = 0;
$results = [];

function check($name, $result, $expected = true) {
    global $pass, $fail, $results;
    $ok = ($result === $expected) || (is_array($result) && ($result['data']['success'] ?? false));
    $status = $ok ? '✓' : '✗';
    $results[] = ['name' => $name, 'ok' => $ok];
    if ($ok) { $pass++; echo "  $status $name\n"; }
    else { $fail++; echo "  $status $name — " . json_encode($result) . "\n"; }
}

// === STEP 1: Login as owner ===
echo "\n[1] LOGIN AS OWNER\n";
$cookies = login($baseUrl, $ownerUsername, $ownerPassword);
$indexPage = postForm($baseUrl, $cookies, 'index.php', []);
check('Owner login', $indexPage['code'] === 200);

// === STEP 2: Create staff users ===
echo "\n[2] CREATE STAFF USERS\n";
$staffRoles = [
    ['manager1', 'Manager', 2],
    ['kasir1', 'Kasir', 3],
    ['gudang1', 'Gudang', 4],
    ['accounting1', 'Accounting', 5],
];
$branchId = $db->query("SELECT id FROM branches WHERE tenant_id=$tenantId LIMIT 1")->fetchColumn();
foreach ($staffRoles as $s) {
    $result = postForm($baseUrl, $cookies, 'users.php', [
        'action' => 'add_user',
        'username' => $s[0],
        'password' => 'password123',
        'full_name' => $s[1],
        'email' => $s[0] . '@makmur.test',
        'phone' => '081234567890',
        'role_id' => $s[2],
    ]);
    $exists = $db->query("SELECT COUNT(*) FROM users WHERE username='{$s[0]}' AND tenant_id=$tenantId")->fetchColumn();
    check("Create user {$s[0]}", $exists > 0);
}

// === STEP 3: Create suppliers ===
echo "\n[3] CREATE SUPPLIERS\n";
$suppliers = [
    ['SUP-001', 'PT Sumber Rezeki', 'Jl. Industri No. 1', '0211111111', 'sumber@rezeki.test'],
    ['SUP-002', 'CV Maju Jaya', 'Jl. Pasar No. 2', '0212222222', 'maju@jaya.test'],
    ['SUP-003', 'UD Berkah', 'Jl. Dagang No. 3', '0213333333', 'berkah@ud.test'],
];
foreach ($suppliers as $sup) {
    $result = ajax($baseUrl, $cookies, 'suppliers', 'POST', [
        'code' => $sup[0], 'name' => $sup[1], 'address' => $sup[2],
        'phone' => $sup[3], 'email' => $sup[4],
    ]);
    check("Create supplier {$sup[0]}", $result);
}

// === STEP 4: Create products with units ===
echo "\n[4] CREATE PRODUCTS\n";
$catId = $db->query("SELECT id FROM categories WHERE tenant_id=$tenantId LIMIT 1")->fetchColumn();
$unitId = $db->query("SELECT id FROM unit_measurements WHERE tenant_id=$tenantId AND code='pcs' LIMIT 1")->fetchColumn();
$products = [
    ['BR-001', 'Beras Premium 5kg', 65000, 55000, 100],
    ['BR-002', 'Minyak Goreng 2L', 38000, 32000, 80],
    ['BR-003', 'Gula Pasir 1kg', 18000, 15000, 150],
    ['BR-004', 'Tepung Terigu 1kg', 15000, 12000, 120],
    ['BR-005', 'Kopi Robusta 250gr', 28000, 23000, 60],
    ['BR-006', 'Teh Celup 25pcs', 12000, 10000, 90],
    ['BR-007', 'Susu UHT 1L', 22000, 18000, 70],
    ['BR-008', 'Mie Instan 1 dus', 45000, 38000, 200],
    ['BR-009', 'Sabun Cuci 1kg', 35000, 28000, 50],
    ['BR-010', 'Air Mineral 600ml', 5000, 3500, 300],
];
$productIds = [];
foreach ($products as $p) {
    $result = ajax($baseUrl, $cookies, 'products', 'POST', [
        'code' => $p[0], 'name' => $p[1], 'category_id' => $catId,
        'sell_price' => $p[2], 'cost_price' => $p[3],
        'min_stock' => 10, 'unit_id' => $unitId,
        'is_active' => 1, 'track_stock' => 1,
    ]);
    if ($result['data']['success'] ?? false) {
        $pid = $result['data']['data']['id'] ?? null;
        if ($pid) $productIds[] = $pid;
    }
    check("Create product {$p[0]} ({$p[1]})", $result);
}

// === STEP 5: Create customers ===
echo "\n[5] CREATE CUSTOMERS\n";
$groupId = $db->query("SELECT id FROM customer_groups WHERE tenant_id=$tenantId LIMIT 1")->fetchColumn();
$customers = [
    ['CUST-001', 'Toko Bahagia', 'Jl. Senang No. 1', '08111111111', 'umum'],
    ['CUST-002', 'Warung Sembako Bu Tini', 'Jl. Bahagia No. 2', '08222222222', 'reseller'],
    ['CUST-003', 'Minimarket Sejahtera', 'Jl. Makmur No. 3', '08333333333', 'korporat'],
    ['CUST-004', 'Ibu Sari', 'Jl. Lestari No. 4', '08444444444', 'umum'],
    ['CUST-005', 'Pak Joko', 'Jl. Damai No. 5', '08555555555', 'vip'],
];
foreach ($customers as $c) {
    $result = ajax($baseUrl, $cookies, 'customers', 'POST', [
        'code' => $c[0], 'name' => $c[1], 'address' => $c[2],
        'phone' => $c[3], 'group_id' => $groupId,
    ]);
    check("Create customer {$c[0]} ({$c[1]})", $result);
}

// === STEP 6: Purchase Orders + Receiving ===
echo "\n[6] PURCHASE ORDERS + RECEIVING\n";
$supplierIds = $db->query("SELECT id FROM suppliers WHERE tenant_id=$tenantId ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
$poCount = 0;
for ($i = 0; $i < 3; $i++) {
    $items = [];
    $startIdx = $i * 3;
    for ($j = 0; $j < 3; $j++) {
        $idx = $startIdx + $j;
        if (!isset($productIds[$idx])) break;
        $items[] = [
            'product_id' => $productIds[$idx],
            'quantity' => 50 + ($j * 10),
            'unit_price' => $products[$idx][3],
            'unit_id' => $unitId,
        ];
    }
    if (empty($items)) break;
    $result = ajax($baseUrl, $cookies, 'purchase-orders', 'POST', [
        'supplier_id' => $supplierIds[$i % count($supplierIds)],
        'po_date' => date('Y-m-d', strtotime("-" . (28 - $i*7) . " days")),
        'items' => $items,
        'notes' => "PO #" . ($i + 1),
    ]);
    if ($result['data']['success'] ?? false) {
        $poId = $result['data']['data']['id'] ?? null;
        if ($poId) {
            $poCount++;
            // Receive the PO - find purchase_item_id for each product
            $piStmt = $db->prepare("SELECT id FROM purchase_items WHERE po_id=? AND product_id=?");
            foreach ($items as $it) {
                $piStmt->execute([$poId, $it['product_id']]);
                $piId = $piStmt->fetchColumn();
                if ($piId) {
                    $recvData = ['items' => [['purchase_item_id' => $piId, 'received_quantity' => $it['quantity']]]];
                    $recvResult = ajax($baseUrl, $cookies, "purchase-orders&id=$poId&action=receive", 'POST', $recvData);
                }
            }
        }
    }
    check("Create PO #" . ($i + 1), $result);
}
echo "  Created and received $poCount purchase orders\n";

// === STEP 7: Sales transactions ===
echo "\n[7] SALES TRANSACTIONS\n";
$customerIds = $db->query("SELECT id FROM customers WHERE tenant_id=$tenantId ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
$payMethodCode = $db->query("SELECT code FROM payment_methods WHERE tenant_id=$tenantId LIMIT 1")->fetchColumn();
$saleCount = 0;
for ($day = 0; $day < 20; $day++) {
    $saleDate = date('Y-m-d', strtotime("-" . (20 - $day) . " days"));
    $numSales = rand(1, 3);
    for ($s = 0; $s < $numSales; $s++) {
        $numItems = rand(1, 4);
        $items = [];
        for ($k = 0; $k < $numItems; $k++) {
            $pIdx = rand(0, count($productIds) - 1);
            $items[] = [
                'product_id' => $productIds[$pIdx],
                'quantity' => rand(1, 10),
                'unit_price' => $products[$pIdx][2],
                'unit_id' => $unitId,
                'discount' => 0,
            ];
        }
        $result = ajax($baseUrl, $cookies, 'sales', 'POST', [
            'customer_id' => $customerIds[array_rand($customerIds)],
            'sale_date' => $saleDate,
            'items' => $items,
            'discount' => 0,
            'payment_method' => $payMethodCode,
            'notes' => "Sale day $day #" . ($s+1),
            'idempotency_key' => 'sim_' . $day . '_' . $s . '_' . rand(1000, 9999),
        ]);
        if ($result['data']['success'] ?? false) $saleCount++;
    }
}
check("Create $saleCount sales transactions", $saleCount > 0);

// === STEP 8: Quotations ===
echo "\n[8] QUOTATIONS\n";
$quoteCount = 0;
for ($i = 0; $i < 3; $i++) {
    $items = [];
    for ($j = 0; $j < 2; $j++) {
        $pIdx = rand(0, count($productIds) - 1);
        $items[] = [
            'product_id' => $productIds[$pIdx],
            'quantity' => rand(5, 20),
            'unit_price' => $products[$pIdx][2],
            'unit_id' => $unitId,
            'discount' => 0,
        ];
    }
    $result = ajax($baseUrl, $cookies, 'quotations', 'POST', [
        'customer_id' => $customerIds[$i % count($customerIds)],
        'quote_date' => date('Y-m-d', strtotime("-" . (15 - $i*5) . " days")),
        'valid_until' => date('Y-m-d', strtotime("+" . (30 - $i*5) . " days")),
        'items' => $items,
        'notes' => "Quotation #" . ($i + 1),
    ]);
    if ($result['data']['success'] ?? false) $quoteCount++;
    check("Create quotation #" . ($i + 1), $result);
}

// === STEP 9: Sales Orders ===
echo "\n[9] SALES ORDERS\n";
$soCount = 0;
for ($i = 0; $i < 2; $i++) {
    $items = [];
    for ($j = 0; $j < 2; $j++) {
        $pIdx = rand(0, count($productIds) - 1);
        $items[] = [
            'product_id' => $productIds[$pIdx],
            'quantity' => rand(3, 15),
            'unit_price' => $products[$pIdx][2],
            'unit_id' => $unitId,
        ];
    }
    $result = ajax($baseUrl, $cookies, 'sales-orders', 'POST', [
        'customer_id' => $customerIds[$i % count($customerIds)],
        'order_date' => date('Y-m-d', strtotime("-" . (10 - $i*3) . " days")),
        'items' => $items,
        'notes' => "SO #" . ($i + 1),
    ]);
    if ($result['data']['success'] ?? false) $soCount++;
    check("Create sales order #" . ($i + 1), $result);
}

// === STEP 10: Stock Adjustments ===
echo "\n[10] STOCK ADJUSTMENTS\n";
$db = freshDb();
$adjTypeCode = $db->query("SELECT code FROM adjustment_types WHERE tenant_id=$tenantId LIMIT 1")->fetchColumn();
$adjCount = 0;
for ($i = 0; $i < 3; $i++) {
    $pIdx = rand(0, count($productIds) - 1);
    $result = postForm($baseUrl, $cookies, 'stock.php', [
        'action' => 'adjustment',
        'product_id' => $productIds[$pIdx],
        'adjustment_type' => $adjTypeCode,
        'quantity' => rand(1, 5),
        'reason' => "Simulasi penyesuaian #" . ($i + 1),
    ]);
    // Check if stock_movement was created
    $db = freshDb();
    $smCount = $db->query("SELECT COUNT(*) FROM stock_movements WHERE tenant_id=$tenantId AND reference_type='manual_adjustment'")->fetchColumn();
    if ($smCount > $adjCount) { $adjCount = $smCount; check("Stock adjustment #" . ($i + 1), true); }
    else check("Stock adjustment #" . ($i + 1), false);
}

// === STEP 11: Stock Opname ===
echo "\n[11] STOCK OPNAME\n";
$db = freshDb();
$opnameResult = postForm($baseUrl, $cookies, 'stock_opname.php', [
    'action' => 'create_opname',
    'opname_date' => date('Y-m-d'),
    'notes' => 'Opname bulanan simulasi',
    'physical_qty' => [
        $productIds[0] => 48,
        $productIds[1] => 30,
    ],
]);
$db = freshDb();
$opnameCount = $db->query("SELECT COUNT(*) FROM stock_movements WHERE tenant_id=$tenantId AND reference_type='opname'")->fetchColumn();
check("Stock opname created", $opnameCount > 0);

// === STEP 12: Stock Transfers ===
echo "\n[12] STOCK TRANSFERS\n";
$db = freshDb();
// Need at least 2 warehouses — create a second one
$wh2 = ajax($baseUrl, $cookies, 'warehouses', 'POST', [
    'code' => 'WH-002', 'name' => 'Gudang Cabang', 'address' => 'Jl. Cabang No. 1',
]);
$whIds = $db->query("SELECT id FROM warehouses WHERE tenant_id=$tenantId ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
if (count($whIds) >= 2) {
    $result = ajax($baseUrl, $cookies, 'stock-transfers', 'POST', [
        'from_warehouse_id' => $whIds[0],
        'to_warehouse_id' => $whIds[1],
        'transfer_date' => date('Y-m-d'),
        'items' => [
            ['product_id' => $productIds[0], 'quantity' => 5, 'unit_id' => $unitId],
        ],
        'notes' => 'Transfer simulasi',
    ]);
    check("Stock transfer", $result);
} else {
    check("Stock transfer (need 2 warehouses)", false);
}

// === STEP 13: Sale Payments ===
echo "\n[13] SALE PAYMENTS\n";
$db = freshDb();
$saleIds = $db->query("SELECT id FROM sales WHERE tenant_id=$tenantId ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
$payCount = 0;
foreach ($saleIds as $sid) {
    $result = ajax($baseUrl, $cookies, "sale-payment&id=$sid", 'POST', [
        'amount' => 50000,
        'payment_method' => $payMethodCode,
        'payment_date' => date('Y-m-d'),
    ]);
    if ($result['data']['success'] ?? false) $payCount++;
}
check("Sale payments ($payCount created)", $payCount > 0);

// === STEP 14: Deliveries ===
echo "\n[14] DELIVERIES\n";
$db = freshDb();
$delCount = 0;
for ($i = 0; $i < 2; $i++) {
    $result = ajax($baseUrl, $cookies, 'deliveries', 'POST', [
        'delivery_date' => date('Y-m-d', strtotime("-$i days")),
        'customer_id' => $customerIds[$i % count($customerIds)],
        'customer_name' => $customers[$i % count($customers)][1],
        'address' => 'Jl. Simulasi No. ' . ($i + 1),
        'items' => [
            ['product_id' => $productIds[$i], 'quantity' => 2, 'unit_id' => $unitId],
        ],
        'notes' => 'Delivery simulasi #' . ($i + 1),
    ]);
    if ($result['data']['success'] ?? false) $delCount++;
    check("Create delivery #" . ($i + 1), $result);
}

// === STEP 15: Reports verification ===
echo "\n[15] REPORTS VERIFICATION\n";
$db = freshDb();
$reportPage = postForm($baseUrl, $cookies, 'reports.php', ['tab' => 'daily', 'date' => date('Y-m-d')]);
check("Reports page accessible", $reportPage['code'] === 200);

// === STEP 16: Verify database integrity ===
echo "\n[16] DATABASE INTEGRITY CHECK\n";
$db = freshDb();
$checks = [
    'tenants' => 1,
    'users' => 5, // owner + 4 staff
    'products' => 10,
    'customers' => 5,
    'suppliers' => 3,
    'sales' => 1,
    'sale_items' => 1,
    'purchase_orders' => 3,
    'stock_movements' => 1,
    'quotations' => 3,
];
foreach ($checks as $table => $minExpected) {
    $cols = $db->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);
    $hasTenant = false;
    foreach ($cols as $c) { if ($c['name'] === 'tenant_id') { $hasTenant = true; break; } }
    if ($hasTenant) {
        $count = $db->query("SELECT COUNT(*) FROM $table WHERE tenant_id=$tenantId")->fetchColumn();
    } else {
        $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    }
    check("$table >= $minExpected (actual: $count)", $count >= $minExpected);
}

// === SUMMARY ===
echo "\n=== SIMULATION SUMMARY ===\n";
echo "Passed: $pass\n";
echo "Failed: $fail\n";
echo "Total: " . ($pass + $fail) . "\n";

if ($fail > 0) {
    echo "\nFAILED TESTS:\n";
    foreach ($results as $r) {
        if (!$r['ok']) echo "  ✗ {$r['name']}\n";
    }
}

// Print database stats
echo "\nDATABASE STATS FOR TENANT $tenantId:\n";
$db = freshDb();
$allTables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name NOT LIKE 'migrations'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($allTables as $t) {
    $cols = $db->query("PRAGMA table_info($t)")->fetchAll(PDO::FETCH_ASSOC);
    $hasTenant = false;
    foreach ($cols as $c) { if ($c['name'] === 'tenant_id') { $hasTenant = true; break; } }
    if ($hasTenant) {
        $count = $db->query("SELECT COUNT(*) FROM $t WHERE tenant_id=$tenantId")->fetchColumn();
        if ($count > 0) echo "  $t: $count\n";
    }
}

// Cleanup cookies
@unlink($cookies);

echo "\nDone.\n";
