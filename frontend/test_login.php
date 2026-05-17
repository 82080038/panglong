<?php
define('API_URL', 'http://127.0.0.1:8000/api/v1');

echo "Testing API Login...\n\n";

// Test 1: Login with admin
echo "Test 1: Login with admin\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, API_URL . '/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'username' => 'admin',
    'password' => 'password123'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['success']) && $result['success']) {
    echo "✓ Login successful\n";
    echo "Token: " . $result['data']['token'] . "\n";
    echo "User: " . $result['data']['user']['username'] . "\n\n";
    
    // Test 2: Get products with token
    echo "Test 2: Get products with token\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, API_URL . '/products');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $result['data']['token']
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    $products = json_decode($response, true);
    
    if ($httpCode === 200 && isset($products['data'])) {
        echo "✓ Products retrieved successfully\n";
        echo "Total products: " . count($products['data']) . "\n\n";
    } else {
        echo "✗ Failed to get products\n";
        echo "Response: $response\n\n";
    }
    
    // Test 3: Get customers with token
    echo "Test 3: Get customers with token\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, API_URL . '/customers');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $result['data']['token']
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    $customers = json_decode($response, true);
    
    if ($httpCode === 200 && isset($customers['data'])) {
        echo "✓ Customers retrieved successfully\n";
        echo "Total customers: " . count($customers['data']) . "\n\n";
    } else {
        echo "✗ Failed to get customers\n";
        echo "Response: $response\n\n";
    }
    
} else {
    echo "✗ Login failed\n";
}

echo "\n=== Test Complete ===\n";
