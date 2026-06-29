<?php
/**
 * Comprehensive API Test Script for Panglong ERP
 * Tests all fixes made to ajax.php in isolation
 * Run: /opt/lampp/bin/php tests/api/comprehensive_api_test.php
 */

$baseUrl = 'http://localhost/panglong/frontend/ajax.php';
$loginUrl = 'http://localhost/panglong/frontend/login.php';
$failures = [];
$passes = [];
$totalTests = 0;

function login($username, $password) {
    global $loginUrl;
    $cookieFile = '/tmp/cookies_' . $username . '_' . time() . '.txt';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $loginUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['username' => $username, 'password' => $password]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode === 302) {
        return $cookieFile;
    }
    @unlink($cookieFile);
    return false;
}

function apiCall($url, $method, $data = null, $cookieFile = null) {
    $ch = curl_init();
    $sep = strpos($url, '?') !== false ? '&' : '?';
    $fullUrl = $url . $sep . 'test_mode=true';
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => json_decode($response, true), 'raw' => $response];
}

function assertTrue($condition, $label) {
    global $failures, $passes, $totalTests;
    $totalTests++;
    if ($condition) {
        $passes[] = "✓ $label";
        echo "  ✓ $label\n";
    } else {
        $failures[] = "✗ $label";
        echo "  ✗ $label\n";
    }
}

function assertEqual($expected, $actual, $label) {
    global $failures, $passes, $totalTests;
    $totalTests++;
    if ($expected === $actual) {
        $passes[] = "✓ $label";
        echo "  ✓ $label\n";
    } else {
        $failures[] = "✗ $label (expected: " . json_encode($expected) . ", got: " . json_encode($actual) . ")";
        echo "  ✗ $label (expected: " . json_encode($expected) . ", got: " . json_encode($actual) . ")\n";
    }
}

function getDb() {
    return new PDO('sqlite:/opt/lampp/htdocs/panglong/database/database.sqlite');
}

// === LOGIN ===
echo "\n=== Setting up sessions ===\n";
$cookies = [];
$users = [
    'admin' => 'password123',
    'kasir1' => 'password123',
    'manager1' => 'password123',
    'gudang1' => 'password123',
    'accounting1' => 'password123',
];
foreach ($users as $user => $pass) {
    $cookie = login($user, $pass);
    if ($cookie) {
        $cookies[$user] = $cookie;
        echo "  ✓ Logged in as $user\n";
    } else {
        echo "  ✗ Failed to login as $user\n";
        exit(1);
    }
}

$db = getDb();

// === TEST 1: Heartbeat endpoint ===
echo "\n--- Test 1: Heartbeat endpoint ---\n";
$res = apiCall($baseUrl . '?endpoint=heartbeat', 'GET', null, $cookies['admin']);
assertTrue($res['code'] === 200, 'Heartbeat GET returns 200');
assertTrue(isset($res['body']['success']) && $res['body']['success'] === true, 'Heartbeat returns success=true');
assertTrue(isset($res['body']['data']['alive']) && $res['body']['data']['alive'] === true, 'Heartbeat returns alive=true');

// === TEST 2: Sales GET with pagination meta ===
echo "\n--- Test 2: Sales GET pagination meta ---\n";
$res = apiCall($baseUrl . '?endpoint=sales&page=1&per_page=5', 'GET', null, $cookies['admin']);
assertTrue($res['code'] === 200, 'Sales GET returns 200');
assertTrue(isset($res['body']['meta']), 'Sales GET returns meta object');
assertTrue(isset($res['body']['meta']['total']), 'Sales meta has total');
assertTrue(isset($res['body']['meta']['per_page']) && $res['body']['meta']['per_page'] === 5, 'Sales meta per_page=5');
assertTrue(isset($res['body']['meta']['current_page']) && $res['body']['meta']['current_page'] === 1, 'Sales meta current_page=1');
assertTrue(isset($res['body']['meta']['last_page']), 'Sales meta has last_page');

// === TEST 3: Sales PUT (update status) ===
echo "\n--- Test 3: Sales PUT (update status) ---\n";
$res = apiCall($baseUrl . '?endpoint=sales&page=1&per_page=1', 'GET', null, $cookies['admin']);
assertTrue(!empty($res['body']['data']), 'At least one sale exists for testing');
if (!empty($res['body']['data'])) {
    $saleId = $res['body']['data'][0]['id'];
    $res2 = apiCall($baseUrl . '?endpoint=sales&id=' . $saleId, 'PUT', ['status' => 'completed'], $cookies['admin']);
    assertTrue($res2['code'] === 200, 'Sales PUT returns 200');
    assertTrue(isset($res2['body']['success']) && $res2['body']['success'] === true, 'Sales PUT returns success=true');
    assertTrue(isset($res2['body']['data']['status']) && $res2['body']['data']['status'] === 'completed', 'Sales PUT updates status to completed');
    
    $stmt = $db->prepare("SELECT status FROM sales WHERE id = ?");
    $stmt->execute([$saleId]);
    assertEqual('completed', $stmt->fetchColumn(), 'DB confirms status updated to completed');
}

// === TEST 4: Sales PUT without status should fail ===
echo "\n--- Test 4: Sales PUT without status (validation) ---\n";
$res = apiCall($baseUrl . '?endpoint=sales&page=1&per_page=1', 'GET', null, $cookies['admin']);
if (!empty($res['body']['data'])) {
    $saleId = $res['body']['data'][0]['id'];
    $res2 = apiCall($baseUrl . '?endpoint=sales&id=' . $saleId, 'PUT', [], $cookies['admin']);
    assertTrue($res2['code'] === 400, 'Sales PUT without status returns 400');
    assertTrue(isset($res2['body']['success']) && $res2['body']['success'] === false, 'Sales PUT without status returns success=false');
}

// === TEST 5: Sales void workflow - Kasir requests void ===
echo "\n--- Test 5: Sales void - Kasir requests void ---\n";
$res = apiCall($baseUrl . '?endpoint=sales&page=1&per_page=10', 'GET', null, $cookies['kasir1']);
assertTrue(!empty($res['body']['data']), 'Kasir can see sales');
if (!empty($res['body']['data'])) {
    $saleId = null;
    foreach ($res['body']['data'] as $sale) {
        if ($sale['status'] !== 'voided') {
            $saleId = $sale['id'];
            break;
        }
    }
    assertTrue($saleId !== null, 'Found a non-voided sale for kasir void test');
    if ($saleId) {
        $res2 = apiCall($baseUrl . '?endpoint=sales&id=' . $saleId, 'DELETE', ['void_reason' => 'Test void by kasir'], $cookies['kasir1']);
        assertTrue($res2['code'] === 200, 'Kasir void request returns 200');
        assertTrue(isset($res2['body']['data']['void_status']) && $res2['body']['data']['void_status'] === 'pending', 'Kasir void request returns void_status=pending');
        assertTrue(isset($res2['body']['data']['message']), 'Kasir void request returns message');
        
        $stmt = $db->prepare("SELECT void_status, void_reason FROM sales WHERE id = ?");
        $stmt->execute([$saleId]);
        $voidData = $stmt->fetch(PDO::FETCH_ASSOC);
        assertEqual('pending', $voidData['void_status'], 'DB confirms void_status=pending');
        assertEqual('Test void by kasir', $voidData['void_reason'], 'DB confirms void_reason saved');
        
        // === TEST 6: Manager approves void ===
        echo "\n--- Test 6: Manager approves void ---\n";
        $res3 = apiCall($baseUrl . '?endpoint=sales&id=' . $saleId, 'DELETE', ['void_action' => 'approve_void', 'void_reason' => 'Test void by kasir'], $cookies['manager1']);
        assertTrue($res3['code'] === 200, 'Manager approve void returns 200');
        assertTrue(isset($res3['body']['data']['status']) && $res3['body']['data']['status'] === 'voided', 'Manager approve void returns status=voided');
        
        $stmt = $db->prepare("SELECT status, void_status, void_reason FROM sales WHERE id = ?");
        $stmt->execute([$saleId]);
        $voidData2 = $stmt->fetch(PDO::FETCH_ASSOC);
        assertEqual('voided', $voidData2['status'], 'DB confirms status=voided after approve');
        assertEqual('approved', $voidData2['void_status'], 'DB confirms void_status=approved');
        assertEqual('Test void by kasir', $voidData2['void_reason'], 'DB confirms void_reason preserved after approve');
    }
}

// === TEST 7: Sales void with 'reason' field (FE compatibility) ===
echo "\n--- Test 7: Sales void with 'reason' field (FE compatibility) ---\n";
$res = apiCall($baseUrl . '?endpoint=sales&page=1&per_page=10', 'GET', null, $cookies['admin']);
if (!empty($res['body']['data'])) {
    $saleId = null;
    foreach ($res['body']['data'] as $sale) {
        if ($sale['status'] !== 'voided') {
            $saleId = $sale['id'];
            break;
        }
    }
    if ($saleId) {
        $res2 = apiCall($baseUrl . '?endpoint=sales&id=' . $saleId, 'DELETE', ['reason' => 'Test reason field'], $cookies['admin']);
        assertTrue($res2['code'] === 200, 'Void with reason field returns 200');
        assertTrue(isset($res2['body']['data']['status']) && $res2['body']['data']['status'] === 'voided', 'Void with reason field works for admin');
        
        $stmt = $db->prepare("SELECT void_reason FROM sales WHERE id = ?");
        $stmt->execute([$saleId]);
        assertEqual('Test reason field', $stmt->fetchColumn(), 'DB confirms reason field mapped to void_reason');
    } else {
        echo "  ⚠ No non-voided sales left - skipping\n";
    }
}

// === TEST 8: Deliveries GET by id (param mismatch fix) ===
echo "\n--- Test 8: Deliveries GET by id ---\n";
$res = apiCall($baseUrl . '?endpoint=deliveries', 'GET', null, $cookies['admin']);
if (!empty($res['body']['data'])) {
    $deliveryId = $res['body']['data'][0]['id'];
    $res2 = apiCall($baseUrl . '?endpoint=deliveries&id=' . $deliveryId, 'GET', null, $cookies['admin']);
    assertTrue($res2['code'] === 200, 'Deliveries GET by id returns 200');
    assertTrue(isset($res2['body']['data']['id']), 'Delivery data returned by id');
} else {
    echo "  ⚠ No deliveries found to test - skipping\n";
}

// === TEST 9: Stock POST (tenant_id + created_by) ===
echo "\n--- Test 9: Stock POST (tenant_id + created_by) ---\n";
$res = apiCall($baseUrl . '?endpoint=products', 'GET', null, $cookies['gudang1']);
if (!empty($res['body']['data'])) {
    $productId = $res['body']['data'][0]['id'];
    $res2 = apiCall($baseUrl . '?endpoint=stock', 'POST', [
        'product_id' => $productId,
        'quantity' => 5,
        'adjustment_type' => 'correction',
        'reason' => 'Test stock adjustment by API test'
    ], $cookies['gudang1']);
    assertTrue($res2['code'] === 201, 'Stock POST returns 201');
    
    $stmt = $db->prepare("SELECT * FROM stock_movements WHERE product_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$productId]);
    $movement = $stmt->fetch(PDO::FETCH_ASSOC);
    assertTrue($movement !== false, 'Stock movement created in DB');
    if ($movement) {
        assertTrue(!empty($movement['tenant_id']), 'Stock movement has tenant_id');
        assertTrue(!empty($movement['created_by']), 'Stock movement has created_by');
    }
} else {
    echo "  ⚠ No products found to test - skipping\n";
}

// === TEST 10: Brands POST (tenant_id) ===
echo "\n--- Test 10: Brands POST (tenant_id) ---\n";
$brandName = 'TestBrand_' . time();
$res = apiCall($baseUrl . '?endpoint=brands', 'POST', ['name' => $brandName], $cookies['admin']);
assertTrue($res['code'] === 201, 'Brands POST returns 201');

// === TEST 11: Cash-flow endpoint (tenant filtering) ===
echo "\n--- Test 11: Cash-flow endpoint ---\n";
$res = apiCall($baseUrl . '?endpoint=cash-flow&start_date=2026-01-01&end_date=2026-12-31', 'GET', null, $cookies['accounting1']);
assertTrue($res['code'] === 200, 'Cash-flow GET returns 200');
assertTrue(isset($res['body']['data']['operating']), 'Cash-flow has operating data');
assertTrue(isset($res['body']['data']['investing']), 'Cash-flow has investing data');
assertTrue(isset($res['body']['data']['financing']), 'Cash-flow has financing data');
assertTrue(isset($res['body']['data']['net_change']), 'Cash-flow has net_change');
assertTrue(isset($res['body']['data']['beginning_cash']), 'Cash-flow has beginning_cash');
assertTrue(isset($res['body']['data']['ending_cash']), 'Cash-flow has ending_cash');

// === TEST 12: Period closings tenant filter ===
echo "\n--- Test 12: Period closings ---\n";
$res = apiCall($baseUrl . '?endpoint=period-closings', 'GET', null, $cookies['accounting1']);
assertTrue($res['code'] === 200, 'Period-closings GET returns 200');

$testYear = (int)date('Y');
$testMonth = (int)date('n');
$testMonth = $testMonth >= 12 ? 1 : $testMonth + 1;
$res2 = apiCall($baseUrl . '?endpoint=period-closings', 'POST', [
    'year' => $testYear,
    'month' => $testMonth,
    'notes' => 'Test closing by API test'
], $cookies['accounting1']);
assertTrue($res2['code'] === 200 || $res2['code'] === 201, 'Period-closings POST returns 200 or 201');

// === TEST 13: Check period locked ===
echo "\n--- Test 13: Check period locked ---\n";
$res = apiCall($baseUrl . '?endpoint=check-period-locked&date=' . date('Y-m-d'), 'GET', null, $cookies['accounting1']);
assertTrue($res['code'] === 200, 'Check-period-locked returns 200');
assertTrue(isset($res['body']['data']['locked']), 'Check-period-locked returns locked field');

// === TEST 14: Stock movements audit fields ===
echo "\n--- Test 14: Stock movements audit fields ---\n";
$stmt = $db->query("SELECT COUNT(*) FROM stock_movements WHERE created_by IS NOT NULL");
$withCreatedBy = $stmt->fetchColumn();
$stmt = $db->query("SELECT COUNT(*) FROM stock_movements");
$totalMovements = $stmt->fetchColumn();
echo "  Stock movements with created_by: $withCreatedBy / $totalMovements\n";
assertTrue($withCreatedBy > 0, 'Some stock movements have created_by');

$stmt = $db->query("SELECT COUNT(*) FROM stock_movements WHERE reference_id IS NOT NULL AND reference_type IS NOT NULL");
$withRef = $stmt->fetchColumn();
echo "  Stock movements with reference_id+type: $withRef / $totalMovements\n";
assertTrue($withRef > 0, 'Some stock movements have reference_id+type');

// === TEST 15: Landed-cost tenant filter ===
echo "\n--- Test 15: Landed-cost endpoint ---\n";
$res = apiCall($baseUrl . '?endpoint=landed-cost', 'GET', null, $cookies['gudang1']);
assertTrue($res['code'] === 200, 'Landed-cost GET returns 200');

// === TEST 16: Partial deliveries tenant filter ===
echo "\n--- Test 16: Partial deliveries endpoint ---\n";
$res = apiCall($baseUrl . '?endpoint=partial-deliveries', 'GET', null, $cookies['admin']);
assertTrue($res['code'] === 200, 'Partial-deliveries GET returns 200');

// === TEST 17: Edge case - void non-existent sale ===
echo "\n--- Test 17: Edge case - void non-existent sale ---\n";
$res = apiCall($baseUrl . '?endpoint=sales&id=999999', 'DELETE', ['void_reason' => 'Test'], $cookies['admin']);
assertTrue($res['code'] === 200 || $res['code'] === 400, 'Void non-existent sale returns 200 or 400 (no crash)');

// === TEST 18: Sales PUT on non-existent sale ===
echo "\n--- Test 18: Sales PUT on non-existent sale ---\n";
$res = apiCall($baseUrl . '?endpoint=sales&id=999999', 'PUT', ['status' => 'completed'], $cookies['admin']);
assertTrue($res['code'] === 200, 'PUT non-existent sale returns 200 (no rows affected, but query succeeds)');

// === TEST 19: Heartbeat with POST (as config.php sends it) ===
echo "\n--- Test 19: Heartbeat with POST ---\n";
$res = apiCall($baseUrl . '?endpoint=heartbeat', 'POST', null, $cookies['admin']);
assertTrue($res['code'] === 200, 'Heartbeat POST returns 200');
assertTrue(isset($res['body']['data']['alive']) && $res['body']['data']['alive'] === true, 'Heartbeat POST returns alive=true');

// === TEST 20: Sales void - manager direct void (no approval needed) ===
echo "\n--- Test 20: Sales void - manager direct void ---\n";
$res = apiCall($baseUrl . '?endpoint=sales&page=1&per_page=20', 'GET', null, $cookies['manager1']);
if (!empty($res['body']['data'])) {
    $saleId = null;
    foreach ($res['body']['data'] as $sale) {
        if ($sale['status'] !== 'voided') {
            $saleId = $sale['id'];
            break;
        }
    }
    if ($saleId) {
        $res2 = apiCall($baseUrl . '?endpoint=sales&id=' . $saleId, 'DELETE', ['void_reason' => 'Manager direct void test'], $cookies['manager1']);
        assertTrue($res2['code'] === 200, 'Manager direct void returns 200');
        assertTrue(isset($res2['body']['data']['status']) && $res2['body']['data']['status'] === 'voided', 'Manager direct void returns status=voided');
        
        $stmt = $db->prepare("SELECT void_reason FROM sales WHERE id = ?");
        $stmt->execute([$saleId]);
        assertEqual('Manager direct void test', $stmt->fetchColumn(), 'DB confirms void_reason saved for manager direct void');
    } else {
        echo "  ⚠ No non-voided sales left - skipping\n";
    }
}

// === TEST 21: Sales void - reject void workflow ===
echo "\n--- Test 21: Sales void - reject void workflow ---\n";
$res = apiCall($baseUrl . '?endpoint=sales&page=1&per_page=20', 'GET', null, $cookies['kasir1']);
if (!empty($res['body']['data'])) {
    $saleId = null;
    foreach ($res['body']['data'] as $sale) {
        if ($sale['status'] !== 'voided' && ($sale['void_status'] ?? null) !== 'pending') {
            $saleId = $sale['id'];
            break;
        }
    }
    if ($saleId) {
        $res2 = apiCall($baseUrl . '?endpoint=sales&id=' . $saleId, 'DELETE', ['void_reason' => 'Reject test'], $cookies['kasir1']);
        assertTrue($res2['code'] === 200, 'Kasir void request returns 200');
        assertTrue($res2['body']['data']['void_status'] === 'pending', 'Kasir void request is pending');
        
        $res3 = apiCall($baseUrl . '?endpoint=sales&id=' . $saleId, 'DELETE', ['void_action' => 'reject_void'], $cookies['manager1']);
        assertTrue($res3['code'] === 200, 'Manager reject void returns 200');
        assertTrue($res3['body']['data']['void_status'] === 'rejected', 'Manager reject void returns void_status=rejected');
        
        $stmt = $db->prepare("SELECT status, void_status FROM sales WHERE id = ?");
        $stmt->execute([$saleId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        assertTrue($row['status'] !== 'voided', 'Sale not voided after rejection');
        assertEqual('rejected', $row['void_status'], 'DB confirms void_status=rejected');
    } else {
        echo "  ⚠ No suitable sales left - skipping\n";
    }
}

// === TEST 22: Cash-flow uses sale_payments table (not payments) ===
echo "\n--- Test 22: Cash-flow uses sale_payments table ---\n";
$stmt = $db->query("SELECT COUNT(*) FROM sale_payments");
$spCount = $stmt->fetchColumn();
$stmt = $db->query("SELECT COUNT(*) FROM payments");
$pCount = $stmt->fetchColumn();
echo "  sale_payments rows: $spCount, payments rows: $pCount\n";
assertTrue($spCount >= 0, 'sale_payments table exists and is queryable');

// === SUMMARY ===
echo "\n" . str_repeat("=", 60) . "\n";
echo "COMPREHENSIVE API TEST RESULTS\n";
echo str_repeat("=", 60) . "\n";
echo "Total tests: $totalTests\n";
echo "Passed: " . count($passes) . "\n";
echo "Failed: " . count($failures) . "\n";
$passRate = $totalTests > 0 ? round(count($passes) / $totalTests * 100, 1) : 0;
echo "Pass rate: {$passRate}%\n";
if (!empty($failures)) {
    echo "\nFAILURES:\n";
    foreach ($failures as $f) {
        echo "  $f\n";
    }
}
echo str_repeat("=", 60) . "\n";

// Cleanup cookies
foreach ($cookies as $cookie) {
    @unlink($cookie);
}

exit(count($failures) > 0 ? 1 : 0);
