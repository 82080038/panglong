<?php

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
header('Content-Type: application/json');
// CORS: restrict to same origin only
$allowedOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
$serverHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
if ($allowedOrigin && parse_url($allowedOrigin, PHP_URL_HOST) === parse_url('http://' . $serverHost, PHP_URL_HOST)) {
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// Test mode only allowed on localhost for development/testing
$isLocalhost = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']);
$testMode = $isLocalhost && isset($_GET['test_mode']) && $_GET['test_mode'] === 'true';
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
    exit;
}

// CSRF validation for write operations (POST, PUT, DELETE)
// Skip CSRF validation for test mode to allow Playwright tests to work
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE']) && !$testMode) {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCsrfToken($token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
        exit;
    }
}

// Rate limiting for write operations
$user = $_SESSION['user'] ?? null;
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE']) && !$testMode) {
    $rateLimitKey = 'rate_limit_' . ($user['id'] ?? 'guest');
    if (!checkRateLimit($rateLimitKey, 30, 60)) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many requests. Please slow down.']);
        exit;
    }
}

$endpoint = $_GET['endpoint'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$d = db();

$tenantId = isset($user['tenant_id']) ? (int)$user['tenant_id'] : null;
$branchId = isset($user['branch_id']) ? (int)$user['branch_id'] : null;
$isSuperAdmin = ($user['role_slug'] ?? '') === 'super_admin';

// Role-based endpoint permission map
$endpointRoles = [
    'products' => ['owner','manager','gudang','kasir'],
    'master-products' => ['owner','manager','gudang','kasir'],
    'categories' => ['owner','manager','gudang'],
    'brands' => ['owner','manager','gudang'],
    'product-units' => ['owner','manager','gudang'],
    'barcode-lookup' => ['owner','manager','gudang','kasir'],
    'sales-price' => ['owner','manager','kasir'],
    'customers' => ['owner','manager','kasir','accounting'],
    'customer-groups' => ['owner','manager','kasir','accounting'],
    'customer-prices' => ['owner','manager','kasir'],
    'suppliers' => ['owner','manager','gudang'],
    'supplier-price-history' => ['owner','manager','gudang'],
    'warehouses' => ['owner','manager','gudang'],
    'warehouse-locations' => ['owner','manager','gudang'],
    'sales' => ['owner','manager','kasir'],
    'sale-payment' => ['owner','manager','kasir'],
    'sales-orders' => ['owner','manager','kasir'],
    'sales-returns' => ['owner','manager','kasir','gudang'],
    'purchase-orders' => ['owner','manager','gudang'],
    'purchase-returns' => ['owner','manager','gudang'],
    'quotations' => ['owner','manager','kasir'],
    'deliveries' => ['owner','manager','gudang','kasir'],
    'partial-deliveries' => ['owner','manager','gudang','kasir'],
    'stock' => ['owner','manager','gudang'],
    'stock-adjustments' => ['owner','manager','gudang'],
    'stock-transfers' => ['owner','manager','gudang'],
    'stock-valuation-fifo' => ['owner','manager','gudang'],
    'product-batches' => ['owner','manager','gudang'],
    'landed-cost' => ['owner','manager','gudang'],
    'vehicles' => ['owner','manager','gudang'],
    'vehicle-maintenance' => ['owner','manager','gudang'],
    'delivery-routes' => ['owner','manager','gudang'],
    'delivery-methods' => ['owner','manager','gudang'],
    'cash-transactions' => ['owner','manager','accounting'],
    'bank-statements' => ['owner','manager','accounting'],
    'fixed-assets' => ['owner','manager','accounting'],
    'e-faktur' => ['owner','manager','accounting'],
    'e-faktur-types' => ['owner','manager','accounting'],
    'cash-flow' => ['owner','manager','accounting'],
    'period-closings' => ['owner','manager','accounting'],
    'check-period-locked' => ['owner','manager','accounting'],
    'reports' => ['owner','manager','accounting','supervisor'],
    'marketplace' => ['owner','manager'],
    'whatsapp-templates' => ['owner','manager','kasir'],
    'whatsapp-messages' => ['owner','manager','kasir'],
    'whatsapp-template-types' => ['owner','manager','kasir'],
    'tier-prices' => ['owner','manager'],
    'settings' => ['owner','manager'],
    'users' => ['super_admin','owner','manager'],
    'tenants' => ['super_admin'],
    'subscriptions' => ['super_admin','owner'],
    'subscription-invoices' => ['super_admin','owner'],
    'saas-revenue' => ['super_admin','owner'],
    'branches' => ['owner','manager'],
    'payment-methods' => ['owner','manager','gudang','kasir','accounting'],
    'adjustment-types' => ['owner','manager','gudang'],
    'unit-measurements' => ['owner','manager','gudang'],
    'tax-rates' => ['owner','manager','accounting'],
    'status-codes' => ['owner','manager'],
];

// Check endpoint permission
if (isset($endpointRoles[$endpoint]) && !$isSuperAdmin) {
    $userRole = $user['role_slug'] ?? '';
    if (!in_array($userRole, $endpointRoles[$endpoint])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied for role: ' . $userRole]);
        exit;
    }
}


function getDefaultTaxRate($d, $tenantId) {
    $stmt = $d->prepare("SELECT rate FROM tax_rates WHERE (tenant_id IS NULL OR tenant_id = ?) AND is_active = 1 ORDER BY tenant_id IS NULL DESC, id DESC LIMIT 1");
    $stmt->execute([$tenantId]);
    $rate = $stmt->fetchColumn();
    if ($rate === false) return 0.11;
    $rate = (float)$rate;
    return $rate > 1 ? $rate / 100 : $rate;
}
// Audit logging function
function logAudit($action, $table, $record_id = null, $before = null, $after = null) {
    global $d;
    $user = $_SESSION['user'];
    try {
        $stmt = $d->prepare('INSERT INTO audit_logs (user_id, action, model_type, model_id, old_values, new_values, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, datetime("now"))');
        $stmt->execute([
            $user['id'],
            $action,
            $table,
            $record_id,
            $before ? json_encode($before) : null,
            $after ? json_encode($after) : null,
            $_SERVER['REMOTE_ADDR'] ?? 'CLI'
        ]);
    } catch (Exception $e) {
        // Log error but don't break the operation
        error_log('Audit log failed: ' . $e->getMessage());
    }
}

function ok($data = null, $meta = null) {
    $res = ['success' => true];
    if ($data !== null) $res['data'] = $data;
    if ($meta !== null) $res['meta'] = $meta;
    echo json_encode($res);
    exit;
}

function fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

function created($data = null) {
    $res = ['success' => true];
    if ($data !== null) $res['data'] = $data;
    http_response_code(201);
    echo json_encode($res);
    exit;
}

// Helper: add tenant filter to SQL query
function addTenantFilter($sql, $alias, $tenantId, $isSuperAdmin, &$params) {
    if (!$isSuperAdmin && $tenantId) {
        if (preg_match('/\bWHERE\b/i', $sql)) {
            $sql .= " AND {$alias}.tenant_id = ?";
        } else {
            $sql .= " WHERE {$alias}.tenant_id = ?";
        }
        $params[] = $tenantId;
    }
    return $sql;
}

// Helper: add tenant filter with master catalog (tenant_id = ? OR tenant_id IS NULL)
function addTenantFilterWithMaster($sql, $alias, $tenantId, $isSuperAdmin, &$params) {
    if (!$isSuperAdmin && $tenantId) {
        if (preg_match('/\bWHERE\b/i', $sql)) {
            $sql .= " AND ({$alias}.tenant_id = ? OR {$alias}.tenant_id IS NULL)";
        } else {
            $sql .= " WHERE ({$alias}.tenant_id = ? OR {$alias}.tenant_id IS NULL)";
        }
        $params[] = $tenantId;
    }
    return $sql;
}

// Helper: add branch filter to SQL query
function addBranchFilter($sql, $alias, $branchId, $isSuperAdmin, &$params) {
    if (!$isSuperAdmin && $branchId) {
        if (preg_match('/\bWHERE\b/i', $sql)) {
            $sql .= " AND {$alias}.branch_id = ?";
        } else {
            $sql .= " WHERE {$alias}.branch_id = ?";
        }
        $params[] = $branchId;
    }
    return $sql;
}

// Referential integrity check function
function checkReferences($table, $id, $field = 'id') {
    global $d;
    
    // Define reference mappings for reference tables
    $referenceMap = [
        'categories' => [
            'table' => 'products',
            'field' => 'category_id',
            'message' => 'kategori ini masih digunakan oleh produk'
        ],
        'warehouse_locations' => [
            'table' => 'products',
            'field' => 'location',
            'message' => 'lokasi ini masih digunakan oleh produk'
        ],
        'payment_methods' => [
            'table' => 'sales',
            'field' => 'payment_method',
            'message' => 'metode pembayaran ini masih digunakan oleh penjualan'
        ],
        'unit_measurements' => [
            'table' => 'product_units',
            'field' => 'unit_id',
            'message' => 'satuan ini masih digunakan oleh produk'
        ],
        'tax_rates' => [
            'table' => 'sales',
            'field' => 'tax_rate_id',
            'message' => 'tarif pajak ini masih digunakan oleh penjualan'
        ],
        'delivery_methods' => [
            'table' => 'deliveries',
            'field' => 'delivery_method_id',
            'message' => 'metode pengiriman ini masih digunakan oleh pengiriman'
        ],
        'status_codes' => [
            'table' => 'sales',
            'field' => 'status',
            'message' => 'status ini masih digunakan oleh penjualan'
        ],
        'customer_groups' => [
            'table' => 'customers',
            'field' => 'group_id',
            'message' => 'grup pelanggan ini masih digunakan oleh pelanggan'
        ],
        // Main entities
        'customers' => [
            'tables' => [
                ['table' => 'sales', 'field' => 'customer_id', 'message' => 'penjualan'],
                ['table' => 'quotations', 'field' => 'customer_id', 'message' => 'quotation'],
                ['table' => 'customer_product_prices', 'field' => 'customer_id', 'message' => 'harga produk pelanggan']
            ]
        ],
        'suppliers' => [
            'tables' => [
                ['table' => 'purchase_orders', 'field' => 'supplier_id', 'message' => 'purchase order'],
                ['table' => 'purchase_returns', 'field' => 'supplier_id', 'message' => 'purchase return'],
                ['table' => 'supplier_price_history', 'field' => 'supplier_id', 'message' => 'riwayat harga supplier'],
                ['table' => 'product_batches', 'field' => 'supplier_id', 'message' => 'batch produk']
            ]
        ],
        'products' => [
            'tables' => [
                ['table' => 'sale_items', 'field' => 'product_id', 'message' => 'item penjualan'],
                ['table' => 'purchase_items', 'field' => 'product_id', 'message' => 'item purchase'],
                ['table' => 'quotation_items', 'field' => 'product_id', 'message' => 'item quotation'],
                ['table' => 'stock_movements', 'field' => 'product_id', 'message' => 'pergerakan stok'],
                ['table' => 'stock_adjustments', 'field' => 'product_id', 'message' => 'penyesuaian stok'],
                ['table' => 'product_units', 'field' => 'product_id', 'message' => 'satuan produk'],
                ['table' => 'product_batches', 'field' => 'product_id', 'message' => 'batch produk'],
                ['table' => 'customer_product_prices', 'field' => 'product_id', 'message' => 'harga produk pelanggan'],
                ['table' => 'supplier_price_history', 'field' => 'product_id', 'message' => 'riwayat harga supplier'],
                ['table' => 'barcodes', 'field' => 'product_id', 'message' => 'barcode'],
                ['table' => 'product_tier_prices', 'field' => 'product_id', 'message' => 'harga tier produk'],
                ['table' => 'sales_order_items', 'field' => 'product_id', 'message' => 'item sales order'],
                ['table' => 'sales_return_items', 'field' => 'product_id', 'message' => 'item sales return'],
                ['table' => 'stock_transfer_items', 'field' => 'product_id', 'message' => 'item transfer stok']
            ]
        ],
        'warehouses' => [
            'tables' => [
                ['table' => 'warehouse_locations', 'field' => 'warehouse_id', 'message' => 'lokasi gudang'],
                ['table' => 'stock_transfers', 'field' => 'to_warehouse_id', 'message' => 'transfer stok']
            ]
        ],
        'vehicles' => [
            'tables' => [
                ['table' => 'deliveries', 'field' => 'vehicle_plate', 'message' => 'pengiriman'],
                ['table' => 'delivery_routes', 'field' => 'vehicle_id', 'message' => 'rute pengiriman'],
                ['table' => 'vehicle_maintenance', 'field' => 'vehicle_id', 'message' => 'maintenance kendaraan'],
                ['table' => 'employees', 'field' => 'vehicle_plate', 'message' => 'karyawan']
            ]
        ]
    ];
    
    if (!isset($referenceMap[$table])) {
        return ['has_references' => false];
    }
    
    $ref = $referenceMap[$table];
    
    // Handle single table reference (old format)
    if (isset($ref['table'])) {
        $stmt = $d->prepare("SELECT COUNT(*) as count FROM {$ref['table']} WHERE {$ref['field']} = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            return [
                'has_references' => true,
                'count' => $result['count'],
                'message' => $ref['message'] . " ({$result['count']} referensi)"
            ];
        }
    }
    
    // Handle multiple table references (new format)
    if (isset($ref['tables'])) {
        $totalRefs = 0;
        $messages = [];
        
        foreach ($ref['tables'] as $tableRef) {
            $stmt = $d->prepare("SELECT COUNT(*) as count FROM {$tableRef['table']} WHERE {$tableRef['field']} = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                $totalRefs += $result['count'];
                $messages[] = "{$result['count']} {$tableRef['message']}";
            }
        }
        
        if ($totalRefs > 0) {
            return [
                'has_references' => true,
                'count' => $totalRefs,
                'message' => 'data ini masih digunakan oleh: ' . implode(', ', $messages)
            ];
        }
    }
    
    return ['has_references' => false];
}

// === HEARTBEAT (session keep-alive) ===
if ($endpoint === 'heartbeat') {
    $_SESSION['last_activity'] = time();
    ok(['alive' => true, 'time' => time()]);
}

// === PRODUCTS ===
if ($endpoint === 'products') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        $search = $_GET['search'] ?? '';
        $per_page = (int)($_GET['per_page'] ?? 50);
        $page = (int)($_GET['page'] ?? 1);
        $offset = ($page - 1) * $per_page;

        if ($id) {
            $stmt = $d->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?" . ($isSuperAdmin ? "" : " AND (p.tenant_id = ? OR p.tenant_id IS NULL)"));
            $stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
            $product = $stmt->fetch();
            if (!$product) fail('Product not found', 404);

            $units = $d->prepare("SELECT * FROM product_units WHERE product_id = ?");
            $units->execute([$id]);
            $product['units'] = $units->fetchAll();
            $product['category'] = ['id' => $product['category_id'], 'name' => $product['category_name'] ?? 'N/A'];

            $baseUnit = $d->prepare("SELECT * FROM product_units WHERE product_id = ? AND is_base_unit = 1 LIMIT 1");
            $baseUnit->execute([$id]);
            $product['base_unit'] = $baseUnit->fetch();

            ok($product);
        }

        $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id";
        $params = [];
        if ($search) {
            $sql .= " WHERE (p.name LIKE ? OR p.code LIKE ? OR p.brand LIKE ?)";
            $q = "%$search%";
            $params = [$q, $q, $q];
        }
        $sql = addTenantFilterWithMaster($sql, 'p', $tenantId, $isSuperAdmin, $params);
        $sql .= " ORDER BY p.id DESC LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        
        // Apply caching only for non-search queries (5 min TTL)
        $cacheKey = "products_list_{$tenantId}_{$per_page}_{$page}";
        if (!$search) {
            $cached = getCache($cacheKey, 300);
            if ($cached !== null) {
                ok($cached['data'], $cached['meta']);
            }
        }
        
        $stmt = $d->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        $countParams = [];
        $countSql = "SELECT COUNT(*) FROM products p";
        if ($search) {
            $countSql .= " WHERE (p.name LIKE ? OR p.code LIKE ? OR p.brand LIKE ?)";
            $q = "%$search%";
            $countParams = [$q, $q, $q];
        }
        $countSql = addTenantFilterWithMaster($countSql, 'p', $tenantId, $isSuperAdmin, $countParams);
        $countStmt = $d->prepare($countSql);
        $countStmt->execute($countParams);
        $total = $countStmt->fetchColumn();

        foreach ($products as &$p) {
            $p['category'] = ['id' => $p['category_id'], 'name' => $p['category_name'] ?? 'N/A'];
        }

        $meta = ['total' => (int)$total, 'per_page' => $per_page, 'current_page' => $page, 'last_page' => (int)ceil($total / $per_page)];
        
        // Cache the result for non-search queries
        if (!$search) {
            setCache($cacheKey, ['data' => $products, 'meta' => $meta]);
        }
        
        ok($products, $meta);
    }

    if ($method === 'POST') {
        // Input validation
        if (empty($input['name']) || !validateStringLength($input['name'], 1, 255)) {
            fail('Name is required and must be 1-255 characters');
        }
        if (!empty($input['code']) && !validateStringLength($input['code'], 1, 50)) {
            fail('Code must be 1-50 characters');
        }
        if (!validateNumeric($input['buy_price'] ?? 0, 0, 999999999)) {
            fail('Buy price must be a positive number');
        }
        if (!validateNumeric($input['sell_price'] ?? 0, 0, 999999999)) {
            fail('Sell price must be a positive number');
        }
        if (!validateNumeric($input['min_stock'] ?? 0, 0, 999999999)) {
            fail('Min stock must be a positive number');
        }
        if (!validateNumeric($input['max_stock'] ?? 0, 0, 999999999)) {
            fail('Max stock must be a positive number');
        }

        $now = date('Y-m-d H:i:s');
        $code = $input['code'] ?? '';
        
        // Auto-generate code if not provided
        if (empty($code)) {
            $code = 'PRD' . str_pad(time() % 1000000, 6, '0', STR_PAD_LEFT);
        }
        
        try {
            $d->beginTransaction();
            $stmt = $d->prepare("INSERT INTO products (code, name, alias, category_id, brand, min_stock, max_stock, location, buy_price, sell_price, is_active, created_at, updated_at, weight_kg, length_cm, width_cm, height_cm, tenant_id) VALUES (?,?,?,?,?,?,?,'',?,?,1,?,?,0,0,0,0,?)");
            $stmt->execute([
                $code, $input['name'] ?? '', $input['alias'] ?? null,
                $input['category_id'] ?? null, $input['brand'] ?? null,
                $input['min_stock'] ?? 0, $input['max_stock'] ?? 0,
                $input['buy_price'] ?? 0, $input['sell_price'] ?? 0, $now, $now, $tenantId
            ]);
            $pid = $d->lastInsertId();

            if (!empty($input['units'])) {
                foreach ($input['units'] as $i => $u) {
                    $stmt = $d->prepare("INSERT INTO product_units (product_id, unit_name, conversion_factor, is_base_unit, price_per_unit, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?,?)");
                    $stmt->execute([
                        $pid, $u['unit_name'] ?? '', $u['conversion_factor'] ?? 1,
                        $i === 0 ? 1 : 0, $u['price_per_unit'] ?? 0, $now, $now, $tenantId
                    ]);
                }
            }

            $qrData = "PROD:$pid:$code";
            $stmt = $d->prepare("INSERT INTO barcodes (product_id, barcode, is_primary, created_at, tenant_id) VALUES (?, ?, 1, ?, ?)");
            $stmt->execute([$pid, $qrData, $now, $tenantId]);

            $d->commit();
        } catch (PDOException $e) {
            if ($d->inTransaction()) {
                $d->rollBack();
            }
            if ($e->getCode() == 23000) {
                fail('Product with this name or code already exists', 409);
            }
            fail('Database error: ' . $e->getMessage(), 500);
        }
        
        logAudit('create', 'products', $pid, null, ['code' => $code, 'name' => $input['name'] ?? '']);
        
        // Sync to master catalog if product doesn't exist there
        if (!$isSuperAdmin && $tenantId) {
            $masterCheck = $d->prepare("SELECT id FROM products WHERE name = ? AND tenant_id IS NULL");
            $masterCheck->execute([$input['name'] ?? '']);
            if (!$masterCheck->fetchColumn()) {
                $masterCode = 'MST-' . str_pad(time() % 1000000, 6, '0', STR_PAD_LEFT);
                $d->prepare("INSERT INTO products (code, name, alias, category_id, brand, min_stock, max_stock, location, buy_price, sell_price, is_active, created_at, updated_at, weight_kg, length_cm, width_cm, height_cm, tenant_id) VALUES (?,?,?,?,?,?,?,'',?,?,1,?,?,0,0,0,0,NULL)")
                  ->execute([$masterCode, $input['name'] ?? '', $input['alias'] ?? null,
                      $input['category_id'] ?? null, $input['brand'] ?? null,
                      $input['min_stock'] ?? 0, $input['max_stock'] ?? 0,
                      $input['buy_price'] ?? 0, $input['sell_price'] ?? 0, $now, $now]);
            }
        }
        
        // Clear product cache
        clearCache('products_list');
        
        created(['id' => $pid, 'code' => $code, 'qr_data' => $qrData]);
    }

    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        
        // Get before data
        $before = $d->prepare("SELECT * FROM products WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
        $before->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
        $beforeData = $before->fetch();
        if (!$beforeData) fail('Product not found or access denied', 404);
        
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("UPDATE products SET name=?, category_id=?, brand=?, min_stock=?, max_stock=?, buy_price=?, sell_price=?, is_active=?, updated_at=? WHERE id=?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
        $stmt->execute($isSuperAdmin ? [
            $input['name'] ?? '', $input['category_id'] ?? null, $input['brand'] ?? null,
            $input['min_stock'] ?? 0, $input['max_stock'] ?? 0,
            $input['buy_price'] ?? 0, $input['sell_price'] ?? 0,
            isset($input['is_active']) ? 1 : 0, $now, $id
        ] : [
            $input['name'] ?? '', $input['category_id'] ?? null, $input['brand'] ?? null,
            $input['min_stock'] ?? 0, $input['max_stock'] ?? 0,
            $input['buy_price'] ?? 0, $input['sell_price'] ?? 0,
            isset($input['is_active']) ? 1 : 0, $now, $id, $tenantId
        ]);
        
        // Get after data
        $after = $d->prepare("SELECT * FROM products WHERE id = ?");
        $after->execute([$id]);
        $afterData = $after->fetch();
        
        logAudit('update', 'products', $id, $beforeData, $afterData);
        
        // Clear product cache
        clearCache('products_list');
        
        ok(['id' => $id]);
    }

    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        
        // Check for references
        $refCheck = checkReferences('products', $id);
        if ($refCheck['has_references']) {
            fail($refCheck['message']);
        }
        
        // Get before data
        $before = $d->prepare("SELECT * FROM products WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
        $before->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
        $beforeData = $before->fetch();
        if (!$beforeData) fail('Product not found or access denied', 404);
        
        $d->prepare("DELETE FROM product_units WHERE product_id = ?")->execute([$id]);
        $d->prepare("DELETE FROM products WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"))->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
        
        logAudit('delete', 'products', $id, $beforeData, null);
        
        // Clear product cache
        clearCache('products_list');
        
        ok(['id' => $id]);
    }
}

// === MASTER CATALOG ===
if ($endpoint === 'master-products') {
    if ($method === 'GET') {
        $search = $_GET['search'] ?? '';
        $categoryId = $_GET['category_id'] ?? null;
        $per_page = (int)($_GET['per_page'] ?? 50);
        $page = (int)($_GET['page'] ?? 1);
        $offset = ($page - 1) * $per_page;

        $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.tenant_id IS NULL";
        $params = [];
        if ($search) {
            $sql .= " AND (p.name LIKE ? OR p.code LIKE ? OR p.brand LIKE ?)";
            $q = "%$search%";
            $params = [$q, $q, $q];
        }
        if ($categoryId) {
            $sql .= " AND p.category_id = ?";
            $params[] = $categoryId;
        }
        $sql .= " ORDER BY p.name LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;

        $stmt = $d->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        $countSql = "SELECT COUNT(*) FROM products p WHERE p.tenant_id IS NULL";
        $countParams = [];
        if ($search) {
            $countSql .= " AND (p.name LIKE ? OR p.code LIKE ? OR p.brand LIKE ?)";
            $q = "%$search%";
            $countParams = [$q, $q, $q];
        }
        if ($categoryId) {
            $countSql .= " AND p.category_id = ?";
            $countParams[] = $categoryId;
        }
        $total = $d->prepare($countSql);
        $total->execute($countParams);
        $totalCount = $total->fetchColumn();

        foreach ($products as &$p) {
            $p['category'] = ['id' => $p['category_id'], 'name' => $p['category_name'] ?? 'N/A'];
        }

        $meta = ['total' => (int)$totalCount, 'per_page' => $per_page, 'current_page' => $page, 'last_page' => (int)ceil($totalCount / $per_page)];
        ok($products, $meta);
    }

    // Import master product to tenant's catalog
    if ($method === 'POST') {
        $masterId = $input['master_product_id'] ?? null;
        if (!$masterId) fail('Master product ID required');

        $master = $d->prepare("SELECT * FROM products WHERE id = ? AND tenant_id IS NULL");
        $master->execute([$masterId]);
        $mp = $master->fetch();
        if (!$mp) fail('Master product not found', 404);

        // Check if tenant already has this product (by name)
        $existing = $d->prepare("SELECT id FROM products WHERE name = ? AND tenant_id = ?");
        $existing->execute([$mp['name'], $tenantId]);
        if ($existing->fetchColumn()) {
            fail('Product already exists in your catalog', 409);
        }

        $now = date('Y-m-d H:i:s');
        $code = $input['code'] ?? $mp['code'];
        $sellPrice = $input['sell_price'] ?? $mp['sell_price'];
        $buyPrice = $input['buy_price'] ?? $mp['buy_price'];

        try {
            $d->beginTransaction();
            $stmt = $d->prepare("INSERT INTO products (code, name, alias, category_id, brand, min_stock, max_stock, location, buy_price, sell_price, is_active, created_at, updated_at, weight_kg, length_cm, width_cm, height_cm, tenant_id) VALUES (?,?,?,?,?,?,?,'',?,?,1,?,?,0,0,0,0,?)");
            $stmt->execute([$code, $mp['name'], $mp['alias'], $mp['category_id'], $mp['brand'], $mp['min_stock'], $mp['max_stock'], $buyPrice, $sellPrice, $now, $now, $tenantId]);
            $pid = $d->lastInsertId();

            // Copy units from master
            $masterUnits = $d->prepare("SELECT * FROM product_units WHERE product_id = ?");
            $masterUnits->execute([$masterId]);
            foreach ($masterUnits->fetchAll() as $mu) {
                $d->prepare("INSERT INTO product_units (product_id, unit_name, conversion_factor, is_base_unit, price_per_unit, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?,?)")
                  ->execute([$pid, $mu['unit_name'], $mu['conversion_factor'], $mu['is_base_unit'], $mu['price_per_unit'], $now, $now, $tenantId]);
            }

            // Create barcode
            $qrData = "PROD:$pid:$code";
            $d->prepare("INSERT INTO barcodes (product_id, barcode, is_primary, created_at, tenant_id) VALUES (?, ?, 1, ?, ?)")
              ->execute([$pid, $qrData, $now, $tenantId]);

            $d->commit();
        } catch (PDOException $e) {
            if ($d->inTransaction()) $d->rollBack();
            if ($e->getCode() == 23000) fail('Product with this name or code already exists', 409);
            fail('Database error: ' . $e->getMessage(), 500);
        }

        logAudit('create', 'products', $pid, null, ['code' => $code, 'name' => $mp['name'], 'imported_from_master' => $masterId]);
        clearCache('products_list');
        created(['id' => $pid, 'code' => $code, 'name' => $mp['name'], 'qr_data' => $qrData]);
    }
}

// === CATEGORIES ===
if ($endpoint === 'categories') {
    if ($method === 'GET') {
        // Apply caching (30 min TTL)
        $cacheKey = "categories_list";
        $cached = getCache($cacheKey, 1800);
        if ($cached !== null) {
            ok($cached);
        }
        
        $catParams = [];
        $catSql = "SELECT * FROM categories";
        $catSql = addTenantFilter($catSql, 'categories', $tenantId, $isSuperAdmin, $catParams);
        $catSql .= " ORDER BY name";
        $catStmt = $d->prepare($catSql);
        $catStmt->execute($catParams);
        $cats = $catStmt->fetchAll();
        
        // Cache the result
        setCache($cacheKey, $cats);
        
        ok($cats);
    }
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['name'])) fail('Name is required');
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO categories (name, created_at, updated_at, tenant_id) VALUES (?,?,?,?)");
        $stmt->execute([$input['name'], $now, $now, $tenantId]);
        
        // Clear category cache
        clearCache('categories_list');
        
        logAudit('create', 'categories', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        ok(['id' => $d->lastInsertId(), 'name' => $input['name']]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) fail('ID is required');
        
        // Check for references
        $refCheck = checkReferences('categories', $id);
        if ($refCheck['has_references']) {
            fail($refCheck['message']);
        }
        
        // Soft delete
        $stmt = $d->prepare("UPDATE categories SET is_active = 0, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
        $stmt->execute($isSuperAdmin ? [date('Y-m-d H:i:s'), $id] : [date('Y-m-d H:i:s'), $id, $tenantId]);
        
        // Clear category cache
        clearCache('categories_list');
        
        logAudit('delete', 'categories', $id, null, null);
        ok(['message' => 'Category deleted']);
    }
}

// === BRANDS ===
if ($endpoint === 'brands') {
    if ($method === 'GET') {
        $brandParams = [];
        $brandSql = "SELECT DISTINCT brand as name FROM products WHERE brand IS NOT NULL AND brand != ''";
        $brandSql = addTenantFilterWithMaster($brandSql, 'products', $tenantId, $isSuperAdmin, $brandParams);
        $brandSql .= " ORDER BY brand";
        $brandStmt = $d->prepare($brandSql);
        $brandStmt->execute($brandParams);
        $brands = $brandStmt->fetchAll();
        ok($brands);
    }
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['name'])) fail('Name is required');
        // Brands are stored as distinct values in products table
        // Create a temporary product to establish the brand, then delete it
        $now = date('Y-m-d H:i:s');
        try {
            $d->beginTransaction();
            $tempCode = 'TEMP-BRAND-' . time();
            $stmt = $d->prepare("INSERT INTO products (code, name, brand, is_active, created_at, updated_at) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$tempCode, 'TEMP-' . $input['name'], $input['name'], 0, $now, $now]);
            $tempId = $d->lastInsertId();
            // Delete the temporary product
            $d->prepare("DELETE FROM products WHERE id = ?")->execute([$tempId]);
            $d->commit();
        } catch (PDOException $e) {
            if ($d->inTransaction()) {
                $d->rollBack();
            }
            fail('Database error: ' . $e->getMessage(), 500);
        }
        logAudit('create', 'brands', $tempId, null, ['id' => $tempId]);
        ok(['name' => $input['name']]);
    }
}

// === REFERENCE TABLES ===
if ($endpoint === 'payment-methods') {
    if ($method === 'GET') {
        // Apply caching (30 min TTL)
        $cacheKey = "payment_methods_list";
        $cached = getCache($cacheKey, 1800);
        if ($cached !== null) {
            ok($cached);
        }
        
        $pmParams = [];
        $pmSql = "SELECT * FROM payment_methods WHERE is_active = 1";
        $pmSql = addTenantFilter($pmSql, 'payment_methods', $tenantId, $isSuperAdmin, $pmParams);
        $pmSql .= " ORDER BY name";
        $pmStmt = $d->prepare($pmSql);
        $pmStmt->execute($pmParams);
        $methods = $pmStmt->fetchAll();
        
        // Cache the result
        setCache($cacheKey, $methods);
        
        ok($methods);
    }
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['name']) || empty($input['code'])) fail('Name and code are required');
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO payment_methods (code, name, is_active, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$input['code'], $input['name'], 1, $now, $now, $tenantId]);
        
        // Clear payment methods cache
        clearCache('payment_methods_list');
        
        logAudit('create', 'payment_methods', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId(), 'code' => $input['code'], 'name' => $input['name']]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) fail('ID is required');

        // Check for references
        $refCheck = checkReferences('payment_methods', $id);
        if ($refCheck['has_references']) {
            fail($refCheck['message']);
        }

        // Soft delete
        $stmt = $d->prepare("UPDATE payment_methods SET is_active = 0, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
        $stmt->execute($isSuperAdmin ? [date('Y-m-d H:i:s'), $id] : [date('Y-m-d H:i:s'), $id, $tenantId]);
        
        // Clear payment methods cache
        clearCache('payment_methods_list');
        
        logAudit('delete', 'payment_methods', $id, null, null);
        ok(['message' => 'Payment method deleted']);
    }
}

if ($endpoint === 'adjustment-types') {
    if ($method === 'GET') {
        $atParams = [];
        $atSql = "SELECT * FROM adjustment_types WHERE is_active = 1";
        $atSql = addTenantFilter($atSql, 'adjustment_types', $tenantId, $isSuperAdmin, $atParams);
        $atSql .= " ORDER BY name";
        $atStmt = $d->prepare($atSql);
        $atStmt->execute($atParams);
        ok($atStmt->fetchAll());
    }
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['name']) || empty($input['code'])) fail('Name and code are required');
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO adjustment_types (code, name, description, is_active, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$input['code'], $input['name'], $input['description'] ?? null, 1, $now, $now, $tenantId]);
        logAudit('create', 'adjustment_types', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId(), 'code' => $input['code'], 'name' => $input['name']]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) fail('ID is required');
        $stmt = $d->prepare("UPDATE adjustment_types SET is_active = 0, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
        $stmt->execute($isSuperAdmin ? [date('Y-m-d H:i:s'), $id] : [date('Y-m-d H:i:s'), $id, $tenantId]);
        logAudit('delete', 'adjustment_types', $id, null, null);
        ok(['message' => 'Adjustment type deleted']);
    }
}

if ($endpoint === 'e-faktur-types') {
    if ($method === 'GET') {
        $eftParams = [];
        $eftSql = "SELECT * FROM e_faktur_types WHERE is_active = 1";
        $eftSql = addTenantFilter($eftSql, 'e_faktur_types', $tenantId, $isSuperAdmin, $eftParams);
        $eftSql .= " ORDER BY name";
        $eftStmt = $d->prepare($eftSql);
        $eftStmt->execute($eftParams);
        ok($eftStmt->fetchAll());
    }
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['name']) || empty($input['code'])) fail('Name and code are required');
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO e_faktur_types (code, name, description, is_active, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$input['code'], $input['name'], $input['description'] ?? null, 1, $now, $now, $tenantId]);
        logAudit('create', 'e_faktur_types', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId(), 'code' => $input['code'], 'name' => $input['name']]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) fail('ID is required');
        $stmt = $d->prepare("UPDATE e_faktur_types SET is_active = 0, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
        $stmt->execute($isSuperAdmin ? [date('Y-m-d H:i:s'), $id] : [date('Y-m-d H:i:s'), $id, $tenantId]);
        logAudit('delete', 'e_faktur_types', $id, null, null);
        ok(['message' => 'E-Faktur type deleted']);
    }
}

if ($endpoint === 'whatsapp-template-types') {
    if ($method === 'GET') {
        $wttParams = [];
        $wttSql = "SELECT * FROM whatsapp_template_types WHERE is_active = 1";
        $wttSql = addTenantFilter($wttSql, 'whatsapp_template_types', $tenantId, $isSuperAdmin, $wttParams);
        $wttSql .= " ORDER BY name";
        $wttStmt = $d->prepare($wttSql);
        $wttStmt->execute($wttParams);
        ok($wttStmt->fetchAll());
    }
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['name']) || empty($input['code'])) fail('Name and code are required');
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO whatsapp_template_types (code, name, description, is_active, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$input['code'], $input['name'], $input['description'] ?? null, 1, $now, $now, $tenantId]);
        logAudit('create', 'whatsapp_template_types', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId(), 'code' => $input['code'], 'name' => $input['name']]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) fail('ID is required');
        $stmt = $d->prepare("UPDATE whatsapp_template_types SET is_active = 0, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
        $stmt->execute($isSuperAdmin ? [date('Y-m-d H:i:s'), $id] : [date('Y-m-d H:i:s'), $id, $tenantId]);
        logAudit('delete', 'whatsapp_template_types', $id, null, null);
        ok(['message' => 'WhatsApp template type deleted']);
    }
}

if ($endpoint === 'unit-measurements') {
    if ($method === 'GET') {
        $umParams = [];
        $umSql = "SELECT * FROM unit_measurements WHERE is_active = 1";
        $umSql = addTenantFilter($umSql, 'unit_measurements', $tenantId, $isSuperAdmin, $umParams);
        $umSql .= " ORDER BY name";
        $umStmt = $d->prepare($umSql);
        $umStmt->execute($umParams);
        ok($umStmt->fetchAll());
    }
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['name']) || empty($input['code'])) fail('Name and code are required');
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO unit_measurements (code, name, is_active, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$input['code'], $input['name'], 1, $now, $now, $tenantId]);
        logAudit('create', 'unit_measurements', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId(), 'code' => $input['code'], 'name' => $input['name']]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) fail('ID is required');
        
        // Check for references
        $refCheck = checkReferences('unit_measurements', $id);
        if ($refCheck['has_references']) {
            fail($refCheck['message']);
        }
        
        // Soft delete
        $stmt = $d->prepare("UPDATE unit_measurements SET is_active = 0, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
        $stmt->execute($isSuperAdmin ? [date('Y-m-d H:i:s'), $id] : [date('Y-m-d H:i:s'), $id, $tenantId]);
        logAudit('delete', 'unit_measurements', $id, null, null);
        ok(['message' => 'Unit measurement deleted']);
    }
}

if ($endpoint === 'tax-rates') {
    if ($method === 'GET') {
        $trParams = [];
        $trSql = "SELECT * FROM tax_rates WHERE is_active = 1";
        $trSql = addTenantFilter($trSql, 'tax_rates', $tenantId, $isSuperAdmin, $trParams);
        $trSql .= " ORDER BY name";
        $trStmt = $d->prepare($trSql);
        $trStmt->execute($trParams);
        ok($trStmt->fetchAll());
    }
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['name']) || empty($input['code']) || !isset($input['rate'])) fail('Name, code, and rate are required');
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO tax_rates (code, name, rate, is_active, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$input['code'], $input['name'], $input['rate'], 1, $now, $now, $tenantId]);
        logAudit('create', 'tax_rates', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        ok(['id' => $d->lastInsertId(), 'code' => $input['code'], 'name' => $input['name'], 'rate' => $input['rate']]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) fail('ID is required');
        
        // Check for references
        $refCheck = checkReferences('tax_rates', $id);
        if ($refCheck['has_references']) {
            fail($refCheck['message']);
        }
        
        // Soft delete
        $stmt = $d->prepare("UPDATE tax_rates SET is_active = 0, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
        $stmt->execute($isSuperAdmin ? [date('Y-m-d H:i:s'), $id] : [date('Y-m-d H:i:s'), $id, $tenantId]);
        logAudit('delete', 'tax_rates', $id, null, null);
        ok(['message' => 'Tax rate deleted']);
    }
}

if ($endpoint === 'delivery-methods') {
    if ($method === 'GET') {
        $dmParams = [];
        $dmSql = "SELECT * FROM delivery_methods WHERE is_active = 1";
        $dmSql = addTenantFilter($dmSql, 'delivery_methods', $tenantId, $isSuperAdmin, $dmParams);
        $dmSql .= " ORDER BY name";
        $dmStmt = $d->prepare($dmSql);
        $dmStmt->execute($dmParams);
        ok($dmStmt->fetchAll());
    }
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['name']) || empty($input['code'])) fail('Name and code are required');
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO delivery_methods (code, name, is_active, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$input['code'], $input['name'], 1, $now, $now, $tenantId]);
        logAudit('create', 'delivery_methods', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        ok(['id' => $d->lastInsertId(), 'code' => $input['code'], 'name' => $input['name']]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) fail('ID is required');
        
        // Check for references
        $refCheck = checkReferences('delivery_methods', $id);
        if ($refCheck['has_references']) {
            fail($refCheck['message']);
        }
        
        // Soft delete
        $stmt = $d->prepare("UPDATE delivery_methods SET is_active = 0, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
        $stmt->execute($isSuperAdmin ? [date('Y-m-d H:i:s'), $id] : [date('Y-m-d H:i:s'), $id, $tenantId]);
        logAudit('delete', 'delivery_methods', $id, null, null);
        ok(['message' => 'Delivery method deleted']);
    }
}

// === PRODUCT UNITS ===
if ($endpoint === 'product-units') {
    if ($method === 'GET') {
        $product_id = $_GET['product_id'] ?? null;
        if (!$product_id) fail('Product ID required');
        
        $stmt = $d->prepare("SELECT * FROM product_units WHERE product_id = ? ORDER BY is_base_unit DESC, id ASC");
        $stmt->execute([$product_id]);
        $units = $stmt->fetchAll();
        ok($units);
    }
}

// === CUSTOMERS ===
if ($endpoint === 'customers') {
    if ($method === 'GET') {
        $search = $_GET['search'] ?? '';
        $per_page = (int)($_GET['per_page'] ?? 50);
        $page = (int)($_GET['page'] ?? 1);
        $offset = ($page - 1) * $per_page;
        $per_page = min(max($per_page, 1), 100); // Clamp between 1-100
        
        $sql = "SELECT c.*, g.name as group_name FROM customers c LEFT JOIN customer_groups g ON c.group_id = g.id";
        $params = [];
        if ($search) {
            $sql .= " WHERE (c.name LIKE ? OR c.phone LIKE ?)";
            $q = "%$search%";
            $params = [$q, $q];
        }
        $sql = addTenantFilter($sql, 'c', $tenantId, $isSuperAdmin, $params);
        $sql .= " ORDER BY c.id DESC LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        
        // Apply caching only for non-search queries (5 min TTL)
        $cacheKey = "customers_list_{$tenantId}_{$per_page}_{$page}";
        if (!$search) {
            $cached = getCache($cacheKey, 300);
            if ($cached !== null) {
                ok($cached['data'], $cached['meta']);
            }
        }
        
        $stmt = $d->prepare($sql);
        $stmt->execute($params);
        $customers = $stmt->fetchAll();
        foreach ($customers as &$c) {
            $c['group'] = ['id' => $c['group_id'], 'name' => $c['group_name'] ?? 'N/A'];
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM customers c";
        $countParams = [];
        if ($search) {
            $countSql .= " WHERE (c.name LIKE ? OR c.phone LIKE ?)";
            $countParams = [$q, $q];
        }
        $countSql = addTenantFilter($countSql, 'c', $tenantId, $isSuperAdmin, $countParams);
        $totalStmt = $d->prepare($countSql);
        $totalStmt->execute($countParams);
        $total = $totalStmt->fetchColumn();
        
        $meta = ['total' => (int)$total, 'per_page' => $per_page, 'current_page' => $page, 'last_page' => (int)ceil($total / $per_page)];
        
        // Cache the result for non-search queries
        if (!$search) {
            setCache($cacheKey, ['data' => $customers, 'meta' => $meta]);
        }
        
        ok($customers, $meta);
    }
    if ($method === 'POST') {
        // Input validation
        if (empty($input['name']) || !validateStringLength($input['name'], 1, 255)) {
            fail('Name is required and must be 1-255 characters');
        }
        if (!empty($input['email']) && !validateEmail($input['email'])) {
            fail('Invalid email format');
        }
        if (!empty($input['phone']) && !validatePhone($input['phone'])) {
            fail('Invalid phone number format');
        }
        if (!validateNumeric($input['credit_limit'] ?? 0, 0, 999999999)) {
            fail('Credit limit must be a positive number');
        }
        if (!validateNumeric($input['payment_terms'] ?? 30, 0, 365)) {
            fail('Payment terms must be between 0 and 365 days');
        }

        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO customers (name, address, phone, email, group_id, credit_limit, payment_terms, is_active, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?,1,?,?,?)");
        $stmt->execute([
            $input['name'] ?? '', $input['address'] ?? null, $input['phone'] ?? null,
            $input['email'] ?? null, $input['group_id'] ?? null,
            $input['credit_limit'] ?? 0, $input['payment_terms'] ?? 30, $now, $now, $tenantId
        ]);
        
        // Clear customer cache
        clearCache('customers_list');
        
        $custId = $d->lastInsertId();
        logAudit('create', 'customers', $custId, null, ['name' => $input['name']]);
        created(['id' => $custId]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("UPDATE customers SET name=?, address=?, phone=?, email=?, group_id=?, credit_limit=?, payment_terms=?, is_active=?, updated_at=? WHERE id=?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
        $stmt->execute($isSuperAdmin ? [
            $input['name'] ?? '', $input['address'] ?? null, $input['phone'] ?? null,
            $input['email'] ?? null, $input['group_id'] ?? null,
            $input['credit_limit'] ?? 0, $input['payment_terms'] ?? 30,
            isset($input['is_active']) ? 1 : 0, $now, $id
        ] : [
            $input['name'] ?? '', $input['address'] ?? null, $input['phone'] ?? null,
            $input['email'] ?? null, $input['group_id'] ?? null,
            $input['credit_limit'] ?? 0, $input['payment_terms'] ?? 30,
            isset($input['is_active']) ? 1 : 0, $now, $id, $tenantId
        ]);
        
        // Clear customer cache
        clearCache('customers_list');
        
        logAudit('update', 'customers', $id, null, ['name' => $input['name'] ?? '']);
        ok(['id' => $id]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        
        // Check for references
        $refCheck = checkReferences('customers', $id);
        if ($refCheck['has_references']) {
            fail($refCheck['message']);
        }
        
        // Soft delete
        $stmt = $d->prepare("UPDATE customers SET is_active = 0, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
        $stmt->execute($isSuperAdmin ? [date('Y-m-d H:i:s'), $id] : [date('Y-m-d H:i:s'), $id, $tenantId]);
        logAudit('delete', 'customers', $id, null, null);
        ok(['id' => $id]);
    }
}

// === CUSTOMER GROUPS ===
if ($endpoint === 'customer-groups') {
    if ($method === 'GET') {
        $cgParams = [];
        $cgSql = "SELECT * FROM customer_groups";
        $cgSql = addTenantFilter($cgSql, 'customer_groups', $tenantId, $isSuperAdmin, $cgParams);
        $cgSql .= " ORDER BY name";
        $cgStmt = $d->prepare($cgSql);
        $cgStmt->execute($cgParams);
        ok($cgStmt->fetchAll());
    }
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['name'])) fail('Name is required');
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO customer_groups (name, created_at, updated_at, tenant_id) VALUES (?,?,?,?)");
        $stmt->execute([$input['name'], $now, $now, $tenantId]);
        logAudit('create', 'customer_groups', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        ok(['id' => $d->lastInsertId(), 'name' => $input['name']]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) fail('ID is required');
        
        // Check for references
        $refCheck = checkReferences('customer_groups', $id);
        if ($refCheck['has_references']) {
            fail($refCheck['message']);
        }
        
        // Soft delete
        $stmt = $d->prepare("UPDATE customer_groups SET is_active = 0, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
        $stmt->execute($isSuperAdmin ? [date('Y-m-d H:i:s'), $id] : [date('Y-m-d H:i:s'), $id, $tenantId]);
        logAudit('delete', 'customer_groups', $id, null, null);
        ok(['message' => 'Customer group deleted']);
    }
}

// === SUPPLIERS ===
if ($endpoint === 'suppliers') {
    if ($method === 'GET') {
        $search = $_GET['search'] ?? '';
        $per_page = (int)($_GET['per_page'] ?? 50);
        $page = (int)($_GET['page'] ?? 1);
        $offset = ($page - 1) * $per_page;
        $per_page = min(max($per_page, 1), 100); // Clamp between 1-100
        
        $sql = "SELECT * FROM suppliers";
        $params = [];
        if ($search) {
            $sql .= " WHERE (name LIKE ? OR phone LIKE ?)";
            $q = "%$search%";
            $params = [$q, $q];
        }
        $sql = addTenantFilter($sql, 'suppliers', $tenantId, $isSuperAdmin, $params);
        $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        
        // Apply caching only for non-search queries (5 min TTL)
        $cacheKey = "suppliers_list_{$tenantId}_{$per_page}_{$page}";
        if (!$search) {
            $cached = getCache($cacheKey, 300);
            if ($cached !== null) {
                ok($cached['data'], $cached['meta']);
            }
        }
        
        $stmt = $d->prepare($sql);
        $stmt->execute($params);
        $suppliers = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM suppliers";
        $countParams = [];
        if ($search) {
            $countSql .= " WHERE (name LIKE ? OR phone LIKE ?)";
            $countParams = [$q, $q];
        }
        $countSql = addTenantFilter($countSql, 'suppliers', $tenantId, $isSuperAdmin, $countParams);
        $totalStmt = $d->prepare($countSql);
        $totalStmt->execute($countParams);
        $total = $totalStmt->fetchColumn();
        
        $meta = ['total' => (int)$total, 'per_page' => $per_page, 'current_page' => $page, 'last_page' => (int)ceil($total / $per_page)];
        
        // Cache the result for non-search queries
        if (!$search) {
            setCache($cacheKey, ['data' => $suppliers, 'meta' => $meta]);
        }
        
        ok($suppliers, $meta);
    }
    if ($method === 'POST') {
        // Input validation
        if (empty($input['name']) || !validateStringLength($input['name'], 1, 255)) {
            fail('Name is required and must be 1-255 characters');
        }
        if (!empty($input['email']) && !validateEmail($input['email'])) {
            fail('Invalid email format');
        }
        if (!empty($input['phone']) && !validatePhone($input['phone'])) {
            fail('Invalid phone number format');
        }
        if (!validateNumeric($input['credit_limit'] ?? 0, 0, 999999999)) {
            fail('Credit limit must be a positive number');
        }
        if (!validateNumeric($input['payment_terms'] ?? 30, 0, 365)) {
            fail('Payment terms must be between 0 and 365 days');
        }

        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO suppliers (name, address, phone, email, payment_terms, credit_limit, is_active, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,1,?,?,?)");
        $stmt->execute([
            $input['name'] ?? '', $input['address'] ?? null, $input['phone'] ?? null,
            $input['email'] ?? null, $input['payment_terms'] ?? 30,
            $input['credit_limit'] ?? 0, $now, $now, $tenantId
        ]);
        
        // Clear supplier cache
        clearCache('suppliers_list');
        
        $supId = $d->lastInsertId();
        logAudit('create', 'suppliers', $supId, null, ['name' => $input['name']]);
        created(['id' => $supId]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        
        // Check for references
        $refCheck = checkReferences('suppliers', $id);
        if ($refCheck['has_references']) {
            fail($refCheck['message']);
        }
        
        // Soft delete
        $stmt = $d->prepare("UPDATE suppliers SET is_active = 0, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
        $stmt->execute($isSuperAdmin ? [date('Y-m-d H:i:s'), $id] : [date('Y-m-d H:i:s'), $id, $tenantId]);
        
        // Clear supplier cache
        clearCache('suppliers_list');
        
        logAudit('delete', 'suppliers', $id, null, null);
        ok(['id' => $id]);
    }
}

// === SALES ===
if ($endpoint === 'sales') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT s.*, c.name as customer_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.id = ?" . ($isSuperAdmin ? "" : " AND s.tenant_id = ? AND s.branch_id = ?"));
            $stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId, $branchId]);
            $sale = $stmt->fetch();
            if (!$sale) fail('Sale not found', 404);
            $sale['customer'] = ['name' => $sale['customer_name'] ?? 'Walk-in'];
            $sale['customer_name_snapshot'] = $sale['customer_name'] ?? 'Walk-in';

            $items = $d->prepare("SELECT si.*, p.name as product_name, p.code as product_code FROM sale_items si LEFT JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?");
            $items->execute([$id]);
            $sale['items'] = $items->fetchAll();
            foreach ($sale['items'] as &$i) {
                $i['product'] = ['name' => $i['product_name'] ?? '', 'code' => $i['product_code'] ?? ''];
            }

            $pays = $d->prepare("SELECT * FROM sale_payments WHERE sale_id = ?");
            $pays->execute([$id]);
            $sale['payments'] = $pays->fetchAll();
            ok($sale);
        }

        $search = $_GET['search'] ?? '';
        $per_page = (int)($_GET['per_page'] ?? 20);
        $page = (int)($_GET['page'] ?? 1);
        $offset = ($page - 1) * $per_page;

        $sql = "SELECT s.*, c.name as customer_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.id";
        $params = [];
        if ($search) {
            $sql .= " WHERE (s.invoice_no LIKE ? OR c.name LIKE ?)";
            $q = "%$search%";
            $params = [$q, $q];
        }
        $sql = addTenantFilter($sql, 's', $tenantId, $isSuperAdmin, $params);
        $sql = addBranchFilter($sql, 's', $branchId, $isSuperAdmin, $params);
        $sql .= " ORDER BY s.id DESC LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        $stmt = $d->prepare($sql);
        $stmt->execute($params);
        $sales = $stmt->fetchAll();
        foreach ($sales as &$s) {
            $s['customer'] = ['name' => $s['customer_name'] ?? 'Walk-in'];
            $s['customer_name_snapshot'] = $s['customer_name'] ?? 'Walk-in';
        }
        ok($sales);
    }

    if ($method === 'POST') {
        // Input validation
        if (empty($input['items']) || !is_array($input['items'])) {
            fail('Items are required');
        }
        if (count($input['items']) === 0) {
            fail('At least one item is required');
        }
        foreach ($input['items'] as $item) {
            if (!validateNumeric($item['quantity'] ?? 0, 0.01, 999999)) {
                fail('Item quantity must be a positive number');
            }
            if (!validateNumeric($item['unit_price'] ?? 0, 0, 999999999)) {
                fail('Item unit price must be a positive number');
            }
            if (!validateNumeric($item['discount'] ?? 0, 0, 999999999)) {
                fail('Item discount must be a positive number');
            }
        }
        if (!validateNumeric($input['discount'] ?? 0, 0, 999999999)) {
            fail('Discount must be a positive number');
        }
        // Validate payment method against database (tenants may have custom methods)
        $pmCode = $input['payment_method'] ?? 'cash';
        $pmStmt = $d->prepare("SELECT id FROM payment_methods WHERE code = ? AND is_active = 1 AND (tenant_id = ? OR tenant_id IS NULL) LIMIT 1");
        $pmStmt->execute([$pmCode, $tenantId]);
        if (!$pmStmt->fetchColumn()) {
            fail('Invalid payment method: ' . $pmCode);
        }
        if (!empty($input['sale_date']) && !strtotime($input['sale_date'])) {
            fail('Invalid sale date');
        }

        // P0 #3: Idempotency key check - prevent duplicate sale submissions
        $idempotencyKey = $input['idempotency_key'] ?? null;
        if ($idempotencyKey) {
            $checkStmt = $d->prepare("SELECT id, invoice_no FROM sales WHERE idempotency_key = ? LIMIT 1");
            $checkStmt->execute([$idempotencyKey]);
            $existing = $checkStmt->fetch();
            if ($existing) {
                // Return the existing sale instead of creating a duplicate
                created(['id' => $existing['id'], 'invoice_no' => $existing['invoice_no'], 'duplicate' => true]);
            }
        }

        $now = date('Y-m-d H:i:s');
        $invoiceNo = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $subtotal = 0;
        foreach ($input['items'] ?? [] as $item) {
            $subtotal += ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
        }
        $globalDiscount = $input['discount'] ?? 0;
        // Get tax rate from database or use provided tax rate
        $taxRate = $input['tax_rate'] ?? getDefaultTaxRate($d, $tenantId);
        $taxable = $subtotal - $globalDiscount;
        $tax = $taxable * $taxRate;
        $total = $taxable + $tax;

        // P1 #24: Discount approval threshold - warn if discount > 10% for non-managers
        $userRole = $_SESSION['user']['role_slug'] ?? '';
        $canApproveDiscount = in_array($userRole, ['owner', 'super_admin', 'manager']);
        $discountPercent = $subtotal > 0 ? ($globalDiscount / $subtotal) * 100 : 0;
        if ($discountPercent > 10 && !$canApproveDiscount) {
            fail('Diskon melebihi 10% memerlukan persetujuan manager. Diskon saat ini: ' . round($discountPercent, 1) . '%');
        }

        // P1 #26: Customer credit limit enforcement
        $customerId = $input['customer_id'] ?? null;
        if ($customerId && ($input['payment_method'] ?? 'cash') === 'credit') {
            $custStmt = $d->prepare("SELECT name, credit_limit, COALESCE((SELECT SUM(total) FROM sales WHERE customer_id = c.id AND payment_status != 'paid' AND status != 'voided'), 0) as outstanding FROM customers c WHERE c.id = ?" . ($isSuperAdmin ? "" : " AND c.tenant_id = ?"));
            $custStmt->execute($isSuperAdmin ? [$customerId] : [$customerId, $tenantId]);
            $custData = $custStmt->fetch();
            if ($custData) {
                $creditLimit = (float)($custData['credit_limit'] ?? 0);
                $outstanding = (float)$custData['outstanding'];
                if ($creditLimit > 0 && ($outstanding + $total) > $creditLimit) {
                    fail('Kredit limit terlampaui untuk "' . $custData['name'] . '". Limit: ' . $creditLimit . ', Outstanding: ' . $outstanding . ', Penjualan ini: ' . $total);
                }
            }
        }

        try {
            $d->beginTransaction();

            // P0 #2: Stock validation before sale
            foreach ($input['items'] as $item) {
                if (empty($item['product_id'])) continue;
                $stockStmt = $d->prepare("SELECT COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id = p.id), 0) as current_stock, p.name, p.allow_negative_stock FROM products p WHERE p.id = ?" . ($isSuperAdmin ? "" : " AND (p.tenant_id = ? OR p.tenant_id IS NULL)"));
                $stockStmt->execute($isSuperAdmin ? [$item['product_id']] : [$item['product_id'], $tenantId]);
                $product = $stockStmt->fetch();
                if (!$product) {
                    $d->rollBack();
                    fail('Product ID ' . $item['product_id'] . ' not found', 404);
                }
                $currentStock = (float)$product['current_stock'];
                $qtyNeeded = abs((float)$item['quantity']);
                $allowNegative = (int)($product['allow_negative_stock'] ?? 0);
                if (!$allowNegative && $currentStock < $qtyNeeded) {
                    $d->rollBack();
                    fail('Stok tidak cukup untuk "' . $product['name'] . '". Stok tersedia: ' . $currentStock . ', diminta: ' . $qtyNeeded);
                }
            }

            $stmt = $d->prepare("INSERT INTO sales (invoice_no, customer_id, customer_name_snapshot, sale_date, subtotal, discount, tax, total, delivery_cost, payment_method, payment_status, status, notes, created_at, updated_at, tenant_id, branch_id, idempotency_key) VALUES (?,?,?,?,?,?,?,?,0,?,?,'completed',?,?,?,?,?,?)");
            $stmt->execute([
                $invoiceNo, $input['customer_id'] ?? null, $input['customer_name'] ?? 'Walk-in Customer',
                $input['sale_date'] ?? date('Y-m-d'),
                $subtotal, $globalDiscount, $tax, $total,
                $input['payment_method'] ?? 'cash', 'unpaid',
                $input['notes'] ?? null, $now, $now, $tenantId, $branchId, $idempotencyKey
            ]);
            $saleId = $d->lastInsertId();

            foreach ($input['items'] ?? [] as $item) {
                if (empty($item['product_id'])) continue;
                $lineSubtotal = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
                $stmt = $d->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, bonus_qty, unit_id, unit_price, discount, subtotal, created_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([
                    $saleId, $item['product_id'], $item['quantity'],
                    $item['bonus_qty'] ?? 0, $item['unit_id'] ?? 1,
                    $item['unit_price'], $item['discount'] ?? 0, $lineSubtotal, $now, $tenantId
                ]);

                $stmt = $d->prepare("INSERT INTO stock_movements (product_id, quantity, unit_id, movement_type, reference_id, reference_type, notes, created_by, created_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([
                    $item['product_id'], -abs((float)$item['quantity']),
                    $item['unit_id'] ?? 1, 'sale', $saleId, 'sale', 'Sale ' . $invoiceNo, $_SESSION['user']['id'] ?? null, $now, $tenantId
                ]);
            }

            $d->commit();
            logAudit('create', 'sales', $saleId, null, ['invoice_no' => $invoiceNo, 'total' => $total]);
            created(['id' => $saleId, 'invoice_no' => $invoiceNo]);
        } catch (Exception $e) {
            if ($d->inTransaction()) {
                $d->rollBack();
            }
            fail('Gagal membuat penjualan: ' . $e->getMessage(), 500);
        }
    }

    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $voidReason = $input['void_reason'] ?? $_GET['void_reason'] ?? null;
        $action = $input['void_action'] ?? $_GET['void_action'] ?? 'void';

        // P0 #7: Void approval workflow
        $userRole = $_SESSION['user']['role_slug'] ?? '';
        $canDirectVoid = in_array($userRole, ['owner', 'super_admin', 'manager']);

        if ($action === 'request_void' && !$canDirectVoid) {
            // Kasir requests void - needs approval
            $d->prepare("UPDATE sales SET void_status='pending', void_requested_by=?, void_reason=?, void_requested_at=?, updated_at=? WHERE id=?" . ($isSuperAdmin ? "" : " AND tenant_id=? AND branch_id=?"))
                ->execute($isSuperAdmin ? [$_SESSION['user']['id'], $voidReason, $now, $now, $id] : [$_SESSION['user']['id'], $voidReason, $now, $now, $id, $tenantId, $branchId]);
            logAudit('void_request', 'sales', $id, null, ['void_status' => 'pending']);
            ok(['id' => $id, 'void_status' => 'pending', 'message' => 'Void request submitted for approval']);
        }

        if ($action === 'approve_void') {
            if (!$canDirectVoid) fail('Only managers and above can approve voids');
            // Approve and execute void
            try {
                $d->beginTransaction();
                $items = $d->prepare("SELECT product_id, quantity FROM sale_items WHERE sale_id = ?");
                $items->execute([$id]);
                foreach ($items->fetchAll() as $item) {
                    $stmt = $d->prepare("INSERT INTO stock_movements (product_id, quantity, unit_id, movement_type, reference_id, reference_type, notes, created_by, created_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([$item['product_id'], abs((float)$item['quantity']), 1, 'adjustment', $id, 'sale_void', 'Void sale #' . $id, $_SESSION['user']['id'] ?? null, $now, $tenantId]);
                }
                $d->prepare("UPDATE sales SET status='voided', void_status='approved', void_approved_by=?, void_approved_at=?, updated_at=? WHERE id=?" . ($isSuperAdmin ? "" : " AND tenant_id=? AND branch_id=?"))
                    ->execute($isSuperAdmin ? [$_SESSION['user']['id'], $now, $now, $id] : [$_SESSION['user']['id'], $now, $now, $id, $tenantId, $branchId]);
                $d->commit();
            } catch (PDOException $e) {
                if ($d->inTransaction()) {
                    $d->rollBack();
                }
                fail('Database error: ' . $e->getMessage(), 500);
            }
            logAudit('void_approve', 'sales', $id, null, ['status' => 'voided', 'void_status' => 'approved']);
            ok(['id' => $id, 'status' => 'voided', 'message' => 'Void approved and executed']);
        }

        if ($action === 'reject_void') {
            if (!$canDirectVoid) fail('Only managers and above can reject voids');
            $d->prepare("UPDATE sales SET void_status='rejected', void_approved_by=?, void_approved_at=?, updated_at=? WHERE id=?" . ($isSuperAdmin ? "" : " AND tenant_id=? AND branch_id=?"))
                ->execute($isSuperAdmin ? [$_SESSION['user']['id'], $now, $now, $id] : [$_SESSION['user']['id'], $now, $now, $id, $tenantId, $branchId]);
            logAudit('void_reject', 'sales', $id, null, ['void_status' => 'rejected']);
            ok(['id' => $id, 'void_status' => 'rejected', 'message' => 'Void request rejected']);
        }

        // Direct void for owners/managers
        if ($canDirectVoid) {
            try {
                $d->beginTransaction();
                $items = $d->prepare("SELECT product_id, quantity FROM sale_items WHERE sale_id = ?");
                $items->execute([$id]);
                foreach ($items->fetchAll() as $item) {
                    $stmt = $d->prepare("INSERT INTO stock_movements (product_id, quantity, unit_id, movement_type, reference_id, reference_type, notes, created_by, created_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([$item['product_id'], abs((float)$item['quantity']), 1, 'adjustment', $id, 'sale_void', 'Void sale #' . $id, $_SESSION['user']['id'] ?? null, $now, $tenantId]);
                }
                $d->prepare("UPDATE sales SET status='voided', void_status='approved', void_approved_by=?, void_approved_at=?, updated_at=? WHERE id=?" . ($isSuperAdmin ? "" : " AND tenant_id=? AND branch_id=?"))
                    ->execute($isSuperAdmin ? [$_SESSION['user']['id'], $now, $now, $id] : [$_SESSION['user']['id'], $now, $now, $id, $tenantId, $branchId]);
                $d->commit();
            } catch (PDOException $e) {
                if ($d->inTransaction()) {
                    $d->rollBack();
                }
                fail('Database error: ' . $e->getMessage(), 500);
            }
            logAudit('void', 'sales', $id, null, ['status' => 'voided']);
            ok(['id' => $id, 'status' => 'voided']);
        } else {
            fail('You do not have permission to directly void sales. Use request_void action.');
        }
    }
}

// === SALE PAYMENT ===
if ($endpoint === 'sale-payment') {
    if ($method === 'POST') {
        $id = $_GET['id'] ?? $input['sale_id'] ?? null;
        if (!$id) fail('Sale ID required');
        $now = date('Y-m-d H:i:s');

        $stmt = $d->prepare("SELECT total, COALESCE((SELECT SUM(amount) FROM sale_payments WHERE sale_id=s.id),0) as paid FROM sales s WHERE s.id = ?" . ($isSuperAdmin ? "" : " AND s.tenant_id = ? AND s.branch_id = ?"));
        $stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId, $branchId]);
        $sale = $stmt->fetch();
        if (!$sale) fail('Sale not found', 404);

        $amount = (float)($input['amount'] ?? 0);
        try {
            $d->beginTransaction();
            $stmt = $d->prepare("INSERT INTO sale_payments (sale_id, amount, payment_method, payment_date, created_at, tenant_id) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$id, $amount, $input['payment_method'] ?? 'cash', $input['payment_date'] ?? date('Y-m-d'), $now, $tenantId]);

            $newPaid = (float)$sale['paid'] + $amount;
            $status = $newPaid >= (float)$sale['total'] ? 'paid' : 'partial';
            $d->prepare("UPDATE sales SET payment_status=?, updated_at=? WHERE id=?" . ($isSuperAdmin ? "" : " AND tenant_id=? AND branch_id=?"))->execute($isSuperAdmin ? [$status, $now, $id] : [$status, $now, $id, $tenantId, $branchId]);
            $d->commit();
        } catch (PDOException $e) {
            if ($d->inTransaction()) {
                $d->rollBack();
            }
            fail('Database error: ' . $e->getMessage(), 500);
        }

        logAudit('payment', 'sale_payments', $d->lastInsertId(), null, ['sale_id' => $id, 'amount' => $amount, 'status' => $status]);
        ok(['sale_id' => $id, 'paid' => $newPaid, 'status' => $status]);
    }
}

// === STOCK ===
if ($endpoint === 'stock') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT p.id, p.name, p.code, p.min_stock, p.max_stock, p.buy_price,
                COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) as current_stock,
                pu.unit_name as base_unit
                FROM products p LEFT JOIN product_units pu ON pu.product_id = p.id AND pu.is_base_unit = 1
                WHERE p.id = ?");
            $stmt->execute([$id]);
            $stock = $stmt->fetch();
            if (!$stock) fail('Product not found', 404);
            $stock['status'] = 'normal';
            if ((float)$stock['current_stock'] <= (float)$stock['min_stock']) $stock['status'] = 'low_stock';
            elseif ((float)$stock['current_stock'] >= (float)$stock['max_stock'] && (float)$stock['max_stock'] > 0) $stock['status'] = 'overstock';
            ok($stock);
        }

        $params = [];
        $sql = "SELECT p.id, p.name, p.code, p.min_stock, p.max_stock,
            COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) as current_stock,
            pu.unit_name as base_unit
            FROM products p LEFT JOIN product_units pu ON pu.product_id = p.id AND pu.is_base_unit = 1
            WHERE p.is_active = 1";
        $sql = addTenantFilter($sql, 'p', $tenantId, $isSuperAdmin, $params);
        $sql .= " ORDER BY p.id DESC LIMIT 200";
        $stmt = $d->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();
        foreach ($items as &$item) {
            $item['product_name'] = $item['name'];
            $item['product_code'] = $item['code'];
            $item['status'] = 'normal';
            if ((float)$item['current_stock'] <= (float)$item['min_stock'] && (float)$item['min_stock'] > 0) $item['status'] = 'low_stock';
            elseif ((float)$item['current_stock'] >= (float)$item['max_stock'] && (float)$item['max_stock'] > 0) $item['status'] = 'overstock';
        }
        ok($items);
    }

    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $productId = $input['product_id'] ?? null;
        $quantity = (float)($input['quantity'] ?? 0);
        $adjType = $input['adjustment_type'] ?? 'correction';
        $reason = $input['reason'] ?? '';

        $stmt = $d->prepare("INSERT INTO stock_movements (product_id, quantity, unit_id, movement_type, notes, created_at) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$productId, $quantity, $input['unit_id'] ?? 1, $adjType, $reason, $now]);
        logAudit('create', 'stock_movements', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['product_id' => $productId, 'quantity' => $quantity]);
    }
}

// === BARCODE LOOKUP ===
if ($endpoint === 'barcode-lookup') {
    if ($method === 'GET') {
        $barcode = $_GET['barcode'] ?? '';
        $stmt = $d->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.code = ?" . ($isSuperAdmin ? "" : " AND (p.tenant_id = ? OR p.tenant_id IS NULL)") . " LIMIT 1");
        $stmt->execute($isSuperAdmin ? [$barcode] : [$barcode, $tenantId]);
        $product = $stmt->fetch();
        if (!$product) fail('Product not found', 404);

        $units = $d->prepare("SELECT * FROM product_units WHERE product_id = ?");
        $units->execute([$product['id']]);
        $product['units'] = $units->fetchAll();
        $product['category'] = ['id' => $product['category_id'], 'name' => $product['category_name'] ?? 'N/A'];
        ok($product);
    }
}

// === SALES PRICE ===
if ($endpoint === 'sales-price') {
    if ($method === 'GET') {
        $productId = $_GET['product_id'] ?? null;
        $stmt = $d->prepare("SELECT sell_price FROM products WHERE id = ?" . ($isSuperAdmin ? "" : " AND (tenant_id = ? OR tenant_id IS NULL)"));
        $stmt->execute($isSuperAdmin ? [$productId] : [$productId, $tenantId]);
        $row = $stmt->fetch();
        ok(['unit_price' => $row['sell_price'] ?? 0]);
    }
}

// === DELIVERIES ===
if ($endpoint === 'deliveries') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT * FROM deliveries WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
            $stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId, $branchId]);
            $delivery = $stmt->fetch();
            if (!$delivery) fail('Delivery not found', 404);
            ok($delivery);
        }
        $search = $_GET['search'] ?? '';
        $sql = "SELECT * FROM deliveries";
        $params = [];
        if ($search) {
            $sql .= " WHERE (delivery_no LIKE ? OR customer_name LIKE ?)";
            $q = "%$search%";
            $params = [$q, $q];
        }
        $sql = addTenantFilter($sql, 'deliveries', $tenantId, $isSuperAdmin, $params);
        $per_page = (int)($_GET['per_page'] ?? 50);
        $page = (int)($_GET['page'] ?? 1);
        $offset = ($page - 1) * $per_page;
        $per_page = min(max($per_page, 1), 100); // Clamp between 1-100
        
        $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        $stmt = $d->prepare($sql);
        $stmt->execute($params);
        $deliveries = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM deliveries";
        $countParams = [];
        if ($search) {
            $countSql .= " WHERE (delivery_no LIKE ? OR customer_name LIKE ?)";
            $countParams = [$q, $q];
        }
        $countSql = addTenantFilter($countSql, 'deliveries', $tenantId, $isSuperAdmin, $countParams);
        $totalStmt = $d->prepare($countSql);
        $totalStmt->execute($countParams);
        $total = $totalStmt->fetchColumn();
        
        $meta = ['total' => (int)$total, 'per_page' => $per_page, 'current_page' => $page, 'last_page' => (int)ceil($total / $per_page)];
        
        ok($deliveries, $meta);
    }
    if ($method === 'POST') {
        // Input validation
        if (empty($input['customer_name']) || !validateStringLength($input['customer_name'], 1, 255)) {
            fail('Customer name is required and must be 1-255 characters');
        }
        if (!empty($input['phone']) && !validatePhone($input['phone'])) {
            fail('Invalid phone number format');
        }
        if (!empty($input['delivery_date']) && !strtotime($input['delivery_date'])) {
            fail('Invalid delivery date');
        }
        if (!empty($input['delivery_time']) && !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $input['delivery_time'])) {
            fail('Invalid delivery time format (use HH:MM)');
        }

        $now = date('Y-m-d H:i:s');
        $deliveryNo = 'SJ-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $stmt = $d->prepare("INSERT INTO deliveries (delivery_no, sale_id, customer_name, delivery_address, phone, delivery_date, delivery_time, driver_name, vehicle_plate, notes, status, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?,'pending',?,?,?)");
        $stmt->execute([
            $deliveryNo, $input['sale_id'] ?? null, $input['customer_name'] ?? '',
            $input['delivery_address'] ?? null, $input['phone'] ?? null,
            $input['delivery_date'] ?? date('Y-m-d'), $input['delivery_time'] ?? null,
            $input['driver_name'] ?? null, $input['vehicle_plate'] ?? null,
            $input['notes'] ?? null, $now, $now, $tenantId
        ]);
        logAudit('create', 'deliveries', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId(), 'delivery_no' => $deliveryNo]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $status = $input['status'] ?? 'pending';
        $deliveredAt = $status === 'delivered' ? $now : null;
        if ($deliveredAt) {
            $stmt = $d->prepare("UPDATE deliveries SET status=?, delivered_at=?, updated_at=? WHERE id=?" . ($isSuperAdmin ? "" : " AND tenant_id=?"));
            $stmt->execute($isSuperAdmin ? [$status, $deliveredAt, $now, $id] : [$status, $deliveredAt, $now, $id, $tenantId]);
        } else {
            $stmt = $d->prepare("UPDATE deliveries SET status=?, updated_at=? WHERE id=?" . ($isSuperAdmin ? "" : " AND tenant_id=?"));
            $stmt->execute($isSuperAdmin ? [$status, $now, $id] : [$status, $now, $id, $tenantId]);
        }
        logAudit('update', 'deliveries', $id, null, ['id' => $id]);
        ok(['id' => $id, 'status' => $status]);
    }
}

// === PURCHASE ORDERS ===
if ($endpoint === 'purchase-orders') {
    $action = $_GET['action'] ?? '';

    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT po.*, s.name as supplier_name FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.id WHERE po.id = ?" . ($isSuperAdmin ? "" : " AND po.tenant_id = ? AND po.branch_id = ?"));
            $stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId, $branchId]);
            $po = $stmt->fetch();
            if (!$po) fail('PO not found', 404);
            $po['supplier'] = ['name' => $po['supplier_name'] ?? ''];

            $items = $d->prepare("SELECT pi.*, p.name as product_name, p.code as product_code FROM purchase_items pi LEFT JOIN products p ON pi.product_id = p.id WHERE pi.po_id = ?");
            $items->execute([$id]);
            $po['items'] = $items->fetchAll();
            foreach ($po['items'] as &$i) {
                $i['product'] = ['name' => $i['product_name'] ?? '', 'code' => $i['product_code'] ?? ''];
            }

            $pays = $d->prepare("SELECT * FROM purchase_payments WHERE po_id = ?");
            $pays->execute([$id]);
            $po['payments'] = $pays->fetchAll();
            ok($po);
        }
        
        $per_page = (int)($_GET['per_page'] ?? 50);
        $page = (int)($_GET['page'] ?? 1);
        $offset = ($page - 1) * $per_page;
        $per_page = min(max($per_page, 1), 100); // Clamp between 1-100
        
        $params = [];
        $sql = "SELECT po.*, s.name as supplier_name FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.id";
        $sql = addTenantFilter($sql, 'po', $tenantId, $isSuperAdmin, $params);
        $sql = addBranchFilter($sql, 'po', $branchId, $isSuperAdmin, $params);
        $sql .= " ORDER BY po.id DESC LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        $stmt = $d->prepare($sql);
        $stmt->execute($params);
        $pos = $stmt->fetchAll();
        foreach ($pos as &$po) {
            $po['supplier'] = ['name' => $po['supplier_name'] ?? ''];
        }
        
        // Get total count
        $countParams = [];
        $countSql = "SELECT COUNT(*) FROM purchase_orders po";
        $countSql = addTenantFilter($countSql, 'po', $tenantId, $isSuperAdmin, $countParams);
        $countSql = addBranchFilter($countSql, 'po', $branchId, $isSuperAdmin, $countParams);
        $countStmt = $d->prepare($countSql);
        $countStmt->execute($countParams);
        $total = $countStmt->fetchColumn();
        
        $meta = ['total' => (int)$total, 'per_page' => $per_page, 'current_page' => $page, 'last_page' => (int)ceil($total / $per_page)];
        
        ok($pos, $meta);
    }

    if ($method === 'POST' && $action === 'receive') {
        $id = $_GET['id'] ?? null;
        if (!$id) fail('PO ID required');
        $now = date('Y-m-d H:i:s');

        try {
            $d->beginTransaction();
            foreach ($input['items'] ?? [] as $recv) {
                $itemId = $recv['purchase_item_id'] ?? null;
                $qty = (float)($recv['received_quantity'] ?? 0);
                if (!$itemId || $qty <= 0) continue;

                $stmt = $d->prepare("SELECT pi.*, p.id as pid FROM purchase_items pi JOIN products p ON pi.product_id = p.id WHERE pi.id = ?" . ($isSuperAdmin ? "" : " AND pi.tenant_id = ?"));
                $stmt->execute($isSuperAdmin ? [$itemId] : [$itemId, $tenantId]);
                $item = $stmt->fetch();
                if (!$item) continue;

                $newReceived = (float)$item['received_quantity'] + $qty;
                $d->prepare("UPDATE purchase_items SET received_quantity = ? WHERE id = ?")->execute([$newReceived, $itemId]);

                $d->prepare("INSERT INTO stock_movements (product_id, quantity, unit_id, movement_type, reference_id, reference_type, notes, created_by, created_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?)")->execute([
                    $item['pid'], $qty, $item['unit_id'] ?? 1, 'purchase', $id, 'purchase_order', 'PO receive #' . $id, $_SESSION['user']['id'] ?? null, $now, $tenantId
                ]);
            }

            $stmt = $d->prepare("SELECT SUM(quantity) as total_qty, SUM(received_quantity) as total_recv FROM purchase_items WHERE po_id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
            $stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
            $totals = $stmt->fetch();
            $status = 'partially_received';
            if ((float)$totals['total_recv'] >= (float)$totals['total_qty']) $status = 'received';
            $stmt = $d->prepare("UPDATE purchase_orders SET status = ?, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ? AND branch_id = ?"));
            $stmt->execute($isSuperAdmin ? [$status, $now, $id] : [$status, $now, $id, $tenantId, $branchId]);
            $d->commit();
        } catch (PDOException $e) {
            if ($d->inTransaction()) {
                $d->rollBack();
            }
            fail('Database error: ' . $e->getMessage(), 500);
        }

        logAudit('update', 'purchase_orders', $id, null, ['status' => $status]);
        ok(['id' => $id, 'status' => $status]);
    }

    if ($method === 'POST' && $action === 'payment') {
        $id = $_GET['id'] ?? null;
        if (!$id) fail('PO ID required');
        $now = date('Y-m-d H:i:s');

        $amount = (float)($input['amount'] ?? 0);
        try {
            $d->beginTransaction();
            $stmt = $d->prepare("INSERT INTO purchase_payments (po_id, amount, payment_method, payment_date, created_at, tenant_id) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$id, $amount, $input['payment_method'] ?? 'cash', $input['payment_date'] ?? date('Y-m-d'), $now, $tenantId]);

            $stmt = $d->prepare("SELECT total, COALESCE((SELECT SUM(amount) FROM purchase_payments WHERE po_id=po.id),0) as paid FROM purchase_orders po WHERE po.id = ?" . ($isSuperAdmin ? "" : " AND po.tenant_id = ? AND po.branch_id = ?"));
            $stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId, $branchId]);
            $po = $stmt->fetch();
            $newPaid = (float)$po['paid'] + $amount;
            $payStatus = $newPaid >= (float)$po['total'] ? 'paid' : 'partial';
            $stmt = $d->prepare("UPDATE purchase_orders SET payment_status = ?, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ? AND branch_id = ?"));
            $stmt->execute($isSuperAdmin ? [$payStatus, $now, $id] : [$payStatus, $now, $id, $tenantId, $branchId]);
            $d->commit();
        } catch (PDOException $e) {
            if ($d->inTransaction()) {
                $d->rollBack();
            }
            fail('Database error: ' . $e->getMessage(), 500);
        }

        logAudit('payment', 'purchase_orders', $id, null, ['paid' => $newPaid, 'status' => $payStatus]);
        ok(['po_id' => $id, 'paid' => $newPaid, 'status' => $payStatus]);
    }

    if ($method === 'POST') {
        // Input validation
        if (empty($input['items']) || !is_array($input['items'])) {
            fail('Items are required');
        }
        if (count($input['items']) === 0) {
            fail('At least one item is required');
        }
        foreach ($input['items'] as $item) {
            if (empty($item['product_id'])) {
                fail('Product ID is required for each item');
            }
            if (!validateNumeric($item['quantity'] ?? 0, 0.01, 999999)) {
                fail('Item quantity must be a positive number');
            }
            if (!validateNumeric($item['unit_price'] ?? 0, 0, 999999999)) {
                fail('Item unit price must be a positive number');
            }
            if (!validateNumeric($item['bonus_qty'] ?? 0, 0, 999999)) {
                fail('Item bonus quantity must be a positive number');
            }
        }
        if (!validateNumeric($input['discount'] ?? 0, 0, 999999999)) {
            fail('Discount must be a positive number');
        }
        if (!empty($input['po_date']) && !strtotime($input['po_date'])) {
            fail('Invalid PO date');
        }

        $now = date('Y-m-d H:i:s');
        $poNumber = 'PO-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $subtotal = 0;
        foreach ($input['items'] ?? [] as $item) {
            $subtotal += ($item['quantity'] * $item['unit_price']);
        }
        $globalDiscount = $input['discount'] ?? 0;
        // Get tax rate from database or use provided tax rate
        $taxRate = $input['tax_rate'] ?? getDefaultTaxRate($d, $tenantId);
        $taxable = $subtotal - $globalDiscount;
        $tax = $taxable * $taxRate;
        $total = $taxable + $tax;

        try {
            $d->beginTransaction();
            $stmt = $d->prepare("INSERT INTO purchase_orders (po_number, supplier_id, po_date, subtotal, discount, tax, total, payment_status, status, notes, created_at, updated_at, tenant_id, branch_id) VALUES (?,?,?,?,?,?,?,?,'pending',?,?,?,?,?)");
            $stmt->execute([
                $poNumber, $input['supplier_id'] ?? null, $input['po_date'] ?? date('Y-m-d'),
                $subtotal, $globalDiscount, $tax, $total,
                'unpaid', $input['notes'] ?? null, $now, $now, $tenantId, $branchId
            ]);
            $poId = $d->lastInsertId();

            foreach ($input['items'] ?? [] as $item) {
                if (empty($item['product_id'])) continue;
                $lineSubtotal = ($item['quantity'] * $item['unit_price']);
                $stmt = $d->prepare("INSERT INTO purchase_items (po_id, product_id, quantity, bonus_qty, received_quantity, unit_id, unit_price, subtotal, created_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([
                    $poId, $item['product_id'], $item['quantity'],
                    $item['bonus_qty'] ?? 0, 0,
                    $item['unit_id'] ?? 1, $item['unit_price'], $lineSubtotal, $now, $tenantId
                ]);
            }
            $d->commit();
        } catch (PDOException $e) {
            if ($d->inTransaction()) {
                $d->rollBack();
            }
            fail('Database error: ' . $e->getMessage(), 500);
        }
        logAudit('create', 'purchase_orders', $poId, null, ['id' => $poId]);
        created(['id' => $poId, 'po_number' => $poNumber]);
    }
}

// === MARKETPLACE ===
if ($endpoint === 'marketplace') {
    if ($method === 'GET') {
        $params = [];
        $sql = "SELECT * FROM marketplace_integrations";
        $sql = addTenantFilter($sql, 'marketplace_integrations', $tenantId, $isSuperAdmin, $params);
        $sql .= " ORDER BY id DESC";
        $stmt = $d->prepare($sql);
        $stmt->execute($params);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $action = $_GET['action'] ?? '';
        $id = $_GET['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');

        if ($action === 'sync-stock' || $action === 'sync-products') {
            $d->prepare("UPDATE marketplace_integrations SET last_synced_at = ?, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"))->execute($isSuperAdmin ? [$now, $now, $id] : [$now, $now, $id, $tenantId]);
            ok(['id' => $id, 'message' => ucfirst(str_replace('-', ' ', $action)) . ' completed']);
        }
        logAudit('create', 'marketplace_integrations', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        ok(['message' => 'Unknown action']);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) fail('ID required');
        $d->prepare("UPDATE marketplace_integrations SET status = 'disconnected' WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"))->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
        logAudit('delete', 'marketplace_integrations', $id, null, null);
        ok(['id' => $id, 'message' => 'Disconnected']);
    }
}

// === REPORTS ===
if ($endpoint === 'reports') {
    $type = $_GET['type'] ?? 'daily';
    $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');

    if ($type === 'daily') {
        $params = [];
        $dailySql = "SELECT COUNT(*) as total_sales, COALESCE(SUM(total),0) as total_revenue, COALESCE(SUM(CASE WHEN payment_method='cash' THEN total ELSE 0 END),0) as total_cash, COALESCE(SUM(CASE WHEN payment_method='credit' THEN total ELSE 0 END),0) as total_credit FROM sales WHERE sale_date = date('now') AND status != 'voided'";
        $dailySql = addTenantFilter($dailySql, 'sales', $tenantId, $isSuperAdmin, $params);
        $dailySql = addBranchFilter($dailySql, 'sales', $branchId, $isSuperAdmin, $params);
        $stmt = $d->prepare($dailySql);
        $stmt->execute($params);
        $data = $stmt->fetch();
        $data['date'] = date('Y-m-d');
        ok($data);
    }

    if ($type === 'monthly') {
        $params = [];
        $monthlySql = "SELECT COUNT(*) as total_sales, COALESCE(SUM(total),0) as total_revenue, COALESCE(SUM(CASE WHEN payment_method='cash' THEN total ELSE 0 END),0) as total_cash, COALESCE(SUM(CASE WHEN payment_method='credit' THEN total ELSE 0 END),0) as total_credit FROM sales WHERE sale_date >= date('now','start of month') AND status != 'voided'";
        $monthlySql = addTenantFilter($monthlySql, 'sales', $tenantId, $isSuperAdmin, $params);
        $monthlySql = addBranchFilter($monthlySql, 'sales', $branchId, $isSuperAdmin, $params);
        $stmt = $d->prepare($monthlySql);
        $stmt->execute($params);
        $data = $stmt->fetch();
        $data['year'] = date('Y');
        $data['month'] = date('m');
        ok($data);
    }

    if ($type === 'low-stock') {
        $params = [];
        $lowStockSql = "SELECT p.code as product_code, p.name as product_name, COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) as current_stock, p.min_stock, (p.min_stock - COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0)) as shortage FROM products p WHERE p.is_active=1 AND CAST(p.min_stock AS REAL) > 0 AND COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) <= CAST(p.min_stock AS REAL)";
        $lowStockSql = addTenantFilter($lowStockSql, 'p', $tenantId, $isSuperAdmin, $params);
        $stmt = $d->prepare($lowStockSql);
        $stmt->execute($params);
        ok($stmt->fetchAll());
    }

    if ($type === 'stock-valuation') {
        $params = [];
        $stockValuationSql = "SELECT p.code as product_code, p.name as product_name, COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) as current_stock, p.buy_price as avg_cost, (COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) * p.buy_price) as stock_value, p.sell_price, (COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) * p.sell_price) as potential_revenue FROM products p WHERE p.is_active=1";
        $stockValuationSql = addTenantFilter($stockValuationSql, 'p', $tenantId, $isSuperAdmin, $params);
        $stmt = $d->prepare($stockValuationSql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();
        $totalValue = 0;
        foreach ($items as $i) {
            $totalValue += (float)$i['stock_value'];
        }
        ok(['total_stock_value' => $totalValue, 'total_products' => count($items), 'items' => $items]);
    }

    if ($type === 'by-product') {
        $params = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];
        $byProductSql = "SELECT p.name as product_name, SUM(si.quantity) as quantity_sold, SUM(si.subtotal) as revenue, SUM((si.subtotal - (si.quantity * p.buy_price))) as profit FROM sale_items si JOIN sales s ON si.sale_id = s.id JOIN products p ON si.product_id = p.id WHERE s.sale_date >= ? AND s.sale_date <= ? AND s.status != 'voided'";
        $byProductSql = addTenantFilter($byProductSql, 's', $tenantId, $isSuperAdmin, $params);
        $byProductSql = addBranchFilter($byProductSql, 's', $branchId, $isSuperAdmin, $params);
        $byProductSql .= " GROUP BY p.id ORDER BY revenue DESC";
        $stmt = $d->prepare($byProductSql);
        $stmt->execute($params);
        ok($stmt->fetchAll());
    }

    if ($type === 'by-customer') {
        $params = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];
        $byCustomerSql = "SELECT c.name as customer_name, COUNT(s.id) as total_sales, SUM(s.total) as total_revenue, COALESCE((SELECT SUM(sp.amount) FROM sale_payments sp WHERE sp.sale_id IN (SELECT id FROM sales WHERE customer_id=c.id)),0) as total_paid, SUM(s.total) - COALESCE((SELECT SUM(sp.amount) FROM sale_payments sp WHERE sp.sale_id IN (SELECT id FROM sales WHERE customer_id=c.id)),0) as total_unpaid FROM sales s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.sale_date >= ? AND s.sale_date <= ? AND s.status != 'voided'";
        $byCustomerSql = addTenantFilter($byCustomerSql, 's', $tenantId, $isSuperAdmin, $params);
        $byCustomerSql = addBranchFilter($byCustomerSql, 's', $branchId, $isSuperAdmin, $params);
        $byCustomerSql .= " GROUP BY c.id ORDER BY total_revenue DESC";
        $stmt = $d->prepare($byCustomerSql);
        $stmt->execute($params);
        ok($stmt->fetchAll());
    }

    if ($type === 'profit-loss') {
        $params = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];
        $profitLossSql = "SELECT COALESCE(SUM(s.total),0) as revenue, COALESCE(SUM(si.quantity * p.buy_price),0) as cogs, COALESCE(SUM(s.total),0) - COALESCE(SUM(si.quantity * p.buy_price),0) as gross_profit, COALESCE(SUM(s.tax),0) as tax, COUNT(s.id) as total_sales FROM sales s LEFT JOIN sale_items si ON si.sale_id = s.id LEFT JOIN products p ON si.product_id = p.id WHERE s.sale_date >= ? AND s.sale_date <= ? AND s.status != 'voided'";
        $profitLossSql = addTenantFilter($profitLossSql, 's', $tenantId, $isSuperAdmin, $params);
        $profitLossSql = addBranchFilter($profitLossSql, 's', $branchId, $isSuperAdmin, $params);
        $stmt = $d->prepare($profitLossSql);
        $stmt->execute($params);
        $data = $stmt->fetch();
        $data['net_profit'] = (float)$data['gross_profit'];
        $data['date_from'] = $dateFrom;
        $data['date_to'] = $dateTo;
        ok($data);
    }

    if ($type === 'stock-movement') {
        try {
            $params = [];
            $stockMovementSql = "SELECT sm.created_at as date, sm.product_id, sm.quantity, sm.movement_type, sm.notes FROM stock_movements sm";
            if (!$isSuperAdmin && $tenantId) {
                $stockMovementSql .= " JOIN products p ON sm.product_id = p.id WHERE p.tenant_id = ?";
                $params[] = $tenantId;
            }
            $stockMovementSql .= " ORDER BY sm.created_at DESC LIMIT 200";
            $stmt = $d->prepare($stockMovementSql);
            $stmt->execute($params);
            ok($stmt->fetchAll());
        } catch (Exception $e) {
            ok([]);
        }
    }

    if ($type === 'dead-stock') {
        $params = [];
        $deadStockSql = "SELECT p.code as product_code, p.name as product_name, COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) as current_stock, (COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) * p.buy_price) as stock_value, CAST((julianday('now') - julianday(p.updated_at)) AS INTEGER) as days_inactive FROM products p WHERE p.is_active=1 AND p.id NOT IN (SELECT DISTINCT product_id FROM sale_items WHERE sale_id IN (SELECT id FROM sales WHERE sale_date >= date('now','-90 days')))";
        $deadStockSql = addTenantFilter($deadStockSql, 'p', $tenantId, $isSuperAdmin, $params);
        $deadStockSql .= " ORDER BY days_inactive DESC";
        $stmt = $d->prepare($deadStockSql);
        $stmt->execute($params);
        ok($stmt->fetchAll());
    }

    if ($type === 'ar-aging') {
        $params = [];
        $arAgingSql = "SELECT c.name as customer_name, s.total - COALESCE((SELECT SUM(sp.amount) FROM sale_payments sp WHERE sp.sale_id=s.id),0) as outstanding, CAST(julianday('now') - julianday(s.sale_date) AS INTEGER) as days_overdue FROM sales s JOIN customers c ON s.customer_id = c.id WHERE s.payment_status != 'paid' AND s.status != 'voided'";
        $arAgingSql = addTenantFilter($arAgingSql, 's', $tenantId, $isSuperAdmin, $params);
        $arAgingSql = addBranchFilter($arAgingSql, 's', $branchId, $isSuperAdmin, $params);
        $arAgingSql .= " ORDER BY days_overdue DESC";
        $stmt = $d->prepare($arAgingSql);
        $stmt->execute($params);
        $details = $stmt->fetchAll();
        $data = ['0_30_days' => 0, '31_60_days' => 0, '61_90_days' => 0, 'over_90_days' => 0, 'total_outstanding' => 0, 'details' => $details];
        foreach ($details as $dt) {
            $out = (float)$dt['outstanding'];
            $data['total_outstanding'] += $out;
            if ($dt['days_overdue'] <= 30) $data['0_30_days'] += $out;
            elseif ($dt['days_overdue'] <= 60) $data['31_60_days'] += $out;
            elseif ($dt['days_overdue'] <= 90) $data['61_90_days'] += $out;
            else $data['over_90_days'] += $out;
        }
        ok($data);
    }

    if ($type === 'ap-aging') {
        $data = ['0_30_days' => 0, '31_60_days' => 0, '61_90_days' => 0, 'over_90_days' => 0, 'total_outstanding' => 0, 'details' => []];
        ok($data);
    }
}

// === BRANCHES ===
if ($endpoint === 'branches') {
    if ($method === 'GET') {
        $params = [];
        $sql = "SELECT * FROM branches";
        $sql = addTenantFilter($sql, 'branches', $tenantId, $isSuperAdmin, $params);
        $sql .= " ORDER BY id";
        $stmt = $d->prepare($sql);
        $stmt->execute($params);
        ok($stmt->fetchAll());
    }
}

// === SETTINGS ===
if ($endpoint === 'settings') {
    if ($method === 'GET') {
        if ($isSuperAdmin) {
            $rows = $d->query("SELECT key, value FROM app_settings ORDER BY key")->fetchAll();
        } else {
            $stmt = $d->prepare("SELECT key, value FROM app_settings WHERE tenant_id IS NULL OR tenant_id = ? ORDER BY tenant_id IS NULL DESC, key");
            $stmt->execute([$tenantId]);
            $rows = $stmt->fetchAll();
        }
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }
        ok($settings);
    }
}

// === WAREHOUSES ===
if ($endpoint === 'warehouses') {
    if ($method === 'GET') {
        $per_page = (int)($_GET['per_page'] ?? 50);
        $page = (int)($_GET['page'] ?? 1);
        $offset = ($page - 1) * $per_page;
        $per_page = min(max($per_page, 1), 100); // Clamp between 1-100
        
        $params = [];
        $sql = "SELECT * FROM warehouses";
        $sql = addTenantFilter($sql, 'warehouses', $tenantId, $isSuperAdmin, $params);
        $sql = addBranchFilter($sql, 'warehouses', $branchId, $isSuperAdmin, $params);
        $sql .= " ORDER BY id LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        
        // Apply caching (10 min TTL)
        $cacheKey = "warehouses_list_{$tenantId}_{$per_page}_{$page}";
        $cached = getCache($cacheKey, 600);
        if ($cached !== null) {
            ok($cached['data'], $cached['meta']);
        }
        
        $stmt = $d->prepare($sql);
        $stmt->execute($params);
        $warehouses = $stmt->fetchAll();
        
        // Get total count
        $countParams = [];
        $countSql = "SELECT COUNT(*) FROM warehouses";
        $countSql = addTenantFilter($countSql, 'warehouses', $tenantId, $isSuperAdmin, $countParams);
        $countSql = addBranchFilter($countSql, 'warehouses', $branchId, $isSuperAdmin, $countParams);
        $countStmt = $d->prepare($countSql);
        $countStmt->execute($countParams);
        $total = $countStmt->fetchColumn();
        
        $meta = ['total' => (int)$total, 'per_page' => $per_page, 'current_page' => $page, 'last_page' => (int)ceil($total / $per_page)];
        
        // Cache the result
        setCache($cacheKey, ['data' => $warehouses, 'meta' => $meta]);
        
        ok($warehouses, $meta);
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO warehouses (tenant_id, branch_id, code, name, address, phone, is_active, type, capacity_m2, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$tenantId, $branchId, $input['code'] ?? '', $input['name'] ?? '', $input['address'] ?? null, $input['phone'] ?? null, $input['type'] ?? 'main', $input['capacity_m2'] ?? 0, $now, $now]);
        logAudit('create', 'warehouses', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId(), 'code' => $input['code'] ?? '', 'name' => $input['name'] ?? '']);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) fail('ID required');
        
        // Check for references
        $refCheck = checkReferences('warehouses', $id);
        if ($refCheck['has_references']) {
            fail($refCheck['message']);
        }
        
        // Soft delete
        $stmt = $d->prepare("UPDATE warehouses SET is_active = 0, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ? AND branch_id = ?"));
        $stmt->execute($isSuperAdmin ? [date('Y-m-d H:i:s'), $id] : [date('Y-m-d H:i:s'), $id, $tenantId, $branchId]);
        
        // Clear warehouse cache
        clearCache('warehouses_list');
        
        logAudit('delete', 'warehouses', $id, null, null);
        ok(['id' => $id]);
    }
}

// === USERS ===
if ($endpoint === 'users') {
    if ($method === 'GET') {
        $params = [];
        $sql = "SELECT u.id, u.username, u.full_name, u.email, u.phone, u.is_active, r.name as role_name, r.slug as role_slug FROM users u LEFT JOIN roles r ON u.role_id = r.id";
        $sql = addTenantFilter($sql, 'u', $tenantId, $isSuperAdmin, $params);
        $sql = addBranchFilter($sql, 'u', $branchId, $isSuperAdmin, $params);
        $sql .= " ORDER BY u.id";
        $stmt = $d->prepare($sql);
        $stmt->execute($params);
        ok($stmt->fetchAll());
    }
}

// === SALES RETURNS (Sprint 7) ===
if ($endpoint === 'sales-returns') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT sr.*, c.name as customer_name FROM sales_returns sr LEFT JOIN customers c ON sr.customer_id = c.id WHERE sr.id = ?" . ($isSuperAdmin ? "" : " AND sr.tenant_id = ?"));
            $stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
            $ret = $stmt->fetch();
            if (!$ret) fail('Return not found', 404);
            $items = $d->prepare("SELECT sri.*, p.name as product_name, p.code as product_code FROM sales_return_items sri LEFT JOIN products p ON sri.product_id = p.id WHERE sri.sales_return_id = ?");
            $items->execute([$id]);
            $ret['items'] = $items->fetchAll();
            ok($ret);
        }
        $srParams = [];
        $srSql = "SELECT sr.*, c.name as customer_name, s.invoice_no FROM sales_returns sr LEFT JOIN customers c ON sr.customer_id = c.id LEFT JOIN sales s ON sr.sale_id = s.id";
        $srSql = addTenantFilter($srSql, 'sr', $tenantId, $isSuperAdmin, $srParams);
        $srSql .= " ORDER BY sr.id DESC LIMIT 100";
        $stmt = $d->prepare($srSql);
        $stmt->execute($srParams);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $returnNo = 'SR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $saleId = $input['sale_id'] ?? null;
        if (!$saleId) fail('Sale ID required');

        $stmt = $d->prepare("SELECT * FROM sales WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
        $stmt->execute($isSuperAdmin ? [$saleId] : [$saleId, $tenantId]);
        $sale = $stmt->fetch();
        if (!$sale) fail('Sale not found', 404);

        $totalRefund = 0;
        foreach ($input['items'] ?? [] as $item) {
            $totalRefund += ($item['quantity'] * $item['unit_price']);
        }

        try {
            $d->beginTransaction();
            $stmt = $d->prepare("INSERT INTO sales_returns (return_no, sale_id, customer_id, return_date, total_refund, refund_method, status, reason, notes, created_by, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $returnNo, $saleId, $sale['customer_id'], $input['return_date'] ?? date('Y-m-d'),
                $totalRefund, $input['refund_method'] ?? 'cash', 'pending',
                $input['reason'] ?? 'N/A', $input['notes'] ?? null,
                $_SESSION['user']['id'] ?? null, $now, $now, $tenantId
            ]);
            $returnId = $d->lastInsertId();

            foreach ($input['items'] ?? [] as $item) {
                if (empty($item['product_id'])) continue;
                $refundAmt = ($item['quantity'] * $item['unit_price']);
                $saleItemId = $item['sale_item_id'] ?? null;
                if (!$saleItemId) {
                    $si = $d->prepare("SELECT id FROM sale_items WHERE sale_id = ? AND product_id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?") . " LIMIT 1");
                    $si->execute($isSuperAdmin ? [$saleId, $item['product_id']] : [$saleId, $item['product_id'], $tenantId]);
                    $siRow = $si->fetch();
                    $saleItemId = $siRow ? $siRow['id'] : 0;
                }
                $stmt = $d->prepare("INSERT INTO sales_return_items (sales_return_id, sale_item_id, product_id, quantity, unit_id, unit_price, refund_amount, reason, created_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$returnId, $saleItemId, $item['product_id'], $item['quantity'], $item['unit_id'] ?? 1, $item['unit_price'], $refundAmt, $item['reason'] ?? null, $now, $tenantId]);

                $d->prepare("INSERT INTO stock_movements (product_id, quantity, unit_id, movement_type, notes, created_at, tenant_id) VALUES (?,?,?,?,?,?,?)")->execute([
                    $item['product_id'], abs((float)$item['quantity']), $item['unit_id'] ?? 1, 'sale_return', 'Return ' . $returnNo, $now, $tenantId
                ]);
            }
            $d->commit();
        } catch (PDOException $e) {
            if ($d->inTransaction()) {
                $d->rollBack();
            }
            fail('Database error: ' . $e->getMessage(), 500);
        }
        logAudit('create', 'sales_returns', $returnId, null, ['id' => $returnId]);
        created(['id' => $returnId, 'return_no' => $returnNo]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $status = $input['status'] ?? 'approved';
        $d->prepare("UPDATE sales_returns SET status = ?, approved_by = ?, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"))->execute($isSuperAdmin ? [$status, $_SESSION['user']['id'] ?? null, $now, $id] : [$status, $_SESSION['user']['id'] ?? null, $now, $id, $tenantId]);
        logAudit('update', 'sales_returns', $id, null, ['id' => $id]);
        ok(['id' => $id, 'status' => $status]);
    }
}

// === PURCHASE RETURNS (Sprint 7) ===
if ($endpoint === 'purchase-returns') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT pr.*, s.name as supplier_name FROM purchase_returns pr LEFT JOIN suppliers s ON pr.supplier_id = s.id WHERE pr.id = ?" . ($isSuperAdmin ? "" : " AND pr.tenant_id = ?"));
            $stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
            $ret = $stmt->fetch();
            if (!$ret) fail('Return not found', 404);
            $items = $d->prepare("SELECT pri.*, p.name as product_name, p.code as product_code FROM purchase_return_items pri LEFT JOIN products p ON pri.product_id = p.id WHERE pri.purchase_return_id = ?");
            $items->execute([$id]);
            $ret['items'] = $items->fetchAll();
            ok($ret);
        }
        $prParams = [];
        $prSql = "SELECT pr.*, s.name as supplier_name, po.po_number FROM purchase_returns pr LEFT JOIN suppliers s ON pr.supplier_id = s.id LEFT JOIN purchase_orders po ON pr.po_id = po.id";
        $prSql = addTenantFilter($prSql, 'pr', $tenantId, $isSuperAdmin, $prParams);
        $prSql .= " ORDER BY pr.id DESC LIMIT 100";
        $stmt = $d->prepare($prSql);
        $stmt->execute($prParams);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $returnNo = 'PR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $poId = $input['po_id'] ?? null;
        if (!$poId) fail('PO ID required');

        $stmt = $d->prepare("SELECT * FROM purchase_orders WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ? AND branch_id = ?"));
        $stmt->execute($isSuperAdmin ? [$poId] : [$poId, $tenantId, $branchId]);
        $po = $stmt->fetch();
        if (!$po) fail('PO not found', 404);

        $totalRefund = 0;
        foreach ($input['items'] ?? [] as $item) {
            $totalRefund += ($item['quantity'] * $item['unit_price']);
        }

        try {
            $d->beginTransaction();
            $stmt = $d->prepare("INSERT INTO purchase_returns (return_no, po_id, supplier_id, return_date, total_refund, refund_method, status, reason, notes, created_by, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $returnNo, $poId, $po['supplier_id'], $input['return_date'] ?? date('Y-m-d'),
                $totalRefund, $input['refund_method'] ?? 'credit', 'pending',
                $input['reason'] ?? 'N/A', $input['notes'] ?? null,
                $_SESSION['user']['id'] ?? null, $now, $now, $tenantId
            ]);
            $returnId = $d->lastInsertId();

            foreach ($input['items'] ?? [] as $item) {
                if (empty($item['product_id'])) continue;
                $refundAmt = ($item['quantity'] * $item['unit_price']);
                $purchaseItemId = $item['purchase_item_id'] ?? null;
                if (!$purchaseItemId) {
                    $pi = $d->prepare("SELECT id FROM purchase_items WHERE po_id = ? AND product_id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?") . " LIMIT 1");
                    $pi->execute($isSuperAdmin ? [$poId, $item['product_id']] : [$poId, $item['product_id'], $tenantId]);
                    $piRow = $pi->fetch();
                    $purchaseItemId = $piRow ? $piRow['id'] : 0;
                }
                $stmt = $d->prepare("INSERT INTO purchase_return_items (purchase_return_id, purchase_item_id, product_id, quantity, unit_id, unit_price, refund_amount, reason, created_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$returnId, $purchaseItemId, $item['product_id'], $item['quantity'], $item['unit_id'] ?? 1, $item['unit_price'], $refundAmt, $item['reason'] ?? null, $now, $tenantId]);

                $d->prepare("INSERT INTO stock_movements (product_id, quantity, unit_id, movement_type, notes, created_at, tenant_id) VALUES (?,?,?,?,?,?,?)")->execute([
                    $item['product_id'], -abs((float)$item['quantity']), $item['unit_id'] ?? 1, 'purchase_return', 'PR ' . $returnNo, $now, $tenantId
                ]);
            }
            $d->commit();
        } catch (PDOException $e) {
            if ($d->inTransaction()) {
                $d->rollBack();
            }
            fail('Database error: ' . $e->getMessage(), 500);
        }
        logAudit('create', 'purchase_returns', $returnId, null, ['id' => $returnId]);
        created(['id' => $returnId, 'return_no' => $returnNo]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $status = $input['status'] ?? 'approved';
        $d->prepare("UPDATE purchase_returns SET status = ?, approved_by = ?, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"))->execute($isSuperAdmin ? [$status, $_SESSION['user']['id'] ?? null, $now, $id] : [$status, $_SESSION['user']['id'] ?? null, $now, $id, $tenantId]);
        logAudit('update', 'purchase_returns', $id, null, ['id' => $id]);
        ok(['id' => $id, 'status' => $status]);
    }
}

// === QUOTATIONS (Sprint 7) ===
if ($endpoint === 'quotations') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT q.*, c.name as customer_name FROM quotations q LEFT JOIN customers c ON q.customer_id = c.id WHERE q.id = ?" . ($isSuperAdmin ? "" : " AND q.tenant_id = ?"));
            $stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
            $quote = $stmt->fetch();
            if (!$quote) fail('Quotation not found', 404);
            $items = $d->prepare("SELECT qi.*, p.name as product_name, p.code as product_code FROM quotation_items qi LEFT JOIN products p ON qi.product_id = p.id WHERE qi.quotation_id = ?");
            $items->execute([$id]);
            $quote['items'] = $items->fetchAll();
            ok($quote);
        }
        
        $per_page = (int)($_GET['per_page'] ?? 50);
        $page = (int)($_GET['page'] ?? 1);
        $offset = ($page - 1) * $per_page;
        $per_page = min(max($per_page, 1), 100); // Clamp between 1-100
        
        $params = [];
        $quoteSql = "SELECT q.*, c.name as customer_name FROM quotations q LEFT JOIN customers c ON q.customer_id = c.id";
        $quoteSql = addTenantFilter($quoteSql, 'q', $tenantId, $isSuperAdmin, $params);
        $quoteSql .= " ORDER BY q.id DESC LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        $stmt = $d->prepare($quoteSql);
        $stmt->execute($params);
        $quotations = $stmt->fetchAll();
        
        // Get total count
        $countParams = [];
        $countSql = "SELECT COUNT(*) FROM quotations q";
        $countSql = addTenantFilter($countSql, 'q', $tenantId, $isSuperAdmin, $countParams);
        $countStmt = $d->prepare($countSql);
        $countStmt->execute($countParams);
        $total = $countStmt->fetchColumn();
        
        $meta = ['total' => (int)$total, 'per_page' => $per_page, 'current_page' => $page, 'last_page' => (int)ceil($total / $per_page)];
        
        ok($quotations, $meta);
    }
    if ($method === 'POST') {
        // Input validation
        if (empty($input['items']) || !is_array($input['items'])) {
            fail('Items are required');
        }
        if (count($input['items']) === 0) {
            fail('At least one item is required');
        }
        foreach ($input['items'] as $item) {
            if (!validateNumeric($item['quantity'] ?? 0, 0.01, 999999)) {
                fail('Item quantity must be a positive number');
            }
            if (!validateNumeric($item['unit_price'] ?? 0, 0, 999999999)) {
                fail('Item unit price must be a positive number');
            }
            if (!validateNumeric($item['discount'] ?? 0, 0, 999999999)) {
                fail('Item discount must be a positive number');
            }
        }
        if (!validateNumeric($input['discount'] ?? 0, 0, 999999999)) {
            fail('Discount must be a positive number');
        }
        if (!empty($input['quote_date']) && !strtotime($input['quote_date'])) {
            fail('Invalid quote date');
        }
        if (!empty($input['valid_until']) && !strtotime($input['valid_until'])) {
            fail('Invalid valid until date');
        }

        $now = date('Y-m-d H:i:s');
        $quoteNo = 'QT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $subtotal = 0;
        foreach ($input['items'] ?? [] as $item) {
            $subtotal += ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
        }
        $globalDiscount = $input['discount'] ?? 0;
        // Get tax rate from database or use provided tax rate
        $taxRate = $input['tax_rate'] ?? getDefaultTaxRate($d, $tenantId);
        $taxable = $subtotal - $globalDiscount;
        $tax = $taxable * $taxRate;
        $total = $taxable + $tax;

        try {
            $d->beginTransaction();
            $stmt = $d->prepare("INSERT INTO quotations (quote_no, customer_id, customer_name, quote_date, valid_until, subtotal, discount, tax, total, status, notes, delivery_address, created_by, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,'draft',?,?,?,?,?,?)");
            $stmt->execute([
                $quoteNo, $input['customer_id'] ?? null, $input['customer_name'] ?? '',
                $input['quote_date'] ?? date('Y-m-d'), $input['valid_until'] ?? date('Y-m-d', strtotime('+30 days')),
                $subtotal, $globalDiscount, $tax, $total,
                $input['notes'] ?? null, $input['delivery_address'] ?? null,
                $_SESSION['user']['id'] ?? null, $now, $now, $tenantId
            ]);
            $quoteId = $d->lastInsertId();

            foreach ($input['items'] ?? [] as $item) {
                if (empty($item['product_id'])) continue;
                $lineSubtotal = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
                $stmt = $d->prepare("INSERT INTO quotation_items (quotation_id, product_id, quantity, bonus_qty, unit_id, unit_price, discount, subtotal, notes, created_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$quoteId, $item['product_id'], $item['quantity'], $item['bonus_qty'] ?? 0, $item['unit_id'] ?? 1, $item['unit_price'], $item['discount'] ?? 0, $lineSubtotal, $item['notes'] ?? null, $now, $tenantId]);
            }
            $d->commit();
        } catch (PDOException $e) {
            if ($d->inTransaction()) {
                $d->rollBack();
            }
            fail('Database error: ' . $e->getMessage(), 500);
        }
        logAudit('create', 'quotations', $quoteId, null, ['id' => $quoteId]);
        created(['id' => $quoteId, 'quote_no' => $quoteNo]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $status = $input['status'] ?? 'sent';
        $d->prepare("UPDATE quotations SET status = ?, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"))->execute($isSuperAdmin ? [$status, $now, $id] : [$status, $now, $id, $tenantId]);
        logAudit('update', 'quotations', $id, null, ['id' => $id]);
        ok(['id' => $id, 'status' => $status]);
    }
}

// === SALES ORDERS (Sprint 7) ===
if ($endpoint === 'sales-orders') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT so.*, c.name as customer_name FROM sales_orders so LEFT JOIN customers c ON so.customer_id = c.id WHERE so.id = ?" . ($isSuperAdmin ? "" : " AND so.tenant_id = ?"));
            $stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
            $so = $stmt->fetch();
            if (!$so) fail('SO not found', 404);
            $items = $d->prepare("SELECT soi.*, p.name as product_name, p.code as product_code FROM sales_order_items soi LEFT JOIN products p ON soi.product_id = p.id WHERE soi.sales_order_id = ?");
            $items->execute([$id]);
            $so['items'] = $items->fetchAll();
            ok($so);
        }
        $params = [];
        $soSql = "SELECT so.*, c.name as customer_name FROM sales_orders so LEFT JOIN customers c ON so.customer_id = c.id";
        $soSql = addTenantFilter($soSql, 'so', $tenantId, $isSuperAdmin, $params);
        $soSql .= " ORDER BY so.id DESC LIMIT ?";
        $params[] = 100;
        $stmt = $d->prepare($soSql);
        $stmt->execute($params);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $soNumber = 'SO-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $subtotal = 0;
        foreach ($input['items'] ?? [] as $item) {
            $subtotal += ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
        }
        $globalDiscount = $input['discount'] ?? 0;
        // Get tax rate from database or use provided tax rate
        $taxRate = $input['tax_rate'] ?? getDefaultTaxRate($d, $tenantId);
        $taxable = $subtotal - $globalDiscount;
        $tax = $taxable * $taxRate;
        $total = $taxable + $tax;

        try {
            $d->beginTransaction();
            $stmt = $d->prepare("INSERT INTO sales_orders (so_number, customer_id, customer_name, order_date, expected_delivery_date, subtotal, discount, tax, total, payment_method, status, notes, delivery_address, quotation_id, created_by, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?,'open',?,?,?,?,?,?,?)");
            $stmt->execute([
                $soNumber, $input['customer_id'] ?? null, $input['customer_name'] ?? '',
                $input['order_date'] ?? date('Y-m-d'), $input['expected_delivery_date'] ?? null,
                $subtotal, $globalDiscount, $tax, $total,
                $input['payment_method'] ?? 'cash',
                $input['notes'] ?? null, $input['delivery_address'] ?? null,
                $input['quotation_id'] ?? null,
                $_SESSION['user']['id'] ?? null, $now, $now, $tenantId
            ]);
            $soId = $d->lastInsertId();

            foreach ($input['items'] ?? [] as $item) {
                if (empty($item['product_id'])) continue;
                $lineSubtotal = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
                $stmt = $d->prepare("INSERT INTO sales_order_items (sales_order_id, product_id, quantity, bonus_qty, delivered_qty, unit_id, unit_price, discount, subtotal, notes, created_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$soId, $item['product_id'], $item['quantity'], $item['bonus_qty'] ?? 0, 0, $item['unit_id'] ?? 1, $item['unit_price'], $item['discount'] ?? 0, $lineSubtotal, $item['notes'] ?? null, $now, $tenantId]);
            }
            $d->commit();
        } catch (PDOException $e) {
            if ($d->inTransaction()) {
                $d->rollBack();
            }
            fail('Database error: ' . $e->getMessage(), 500);
        }
        logAudit('create', 'sales_orders', $soId, null, ['id' => $soId]);
        created(['id' => $soId, 'so_number' => $soNumber]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $status = $input['status'] ?? 'fulfilled';
        $d->prepare("UPDATE sales_orders SET status = ?, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"))->execute($isSuperAdmin ? [$status, $now, $id] : [$status, $now, $id, $tenantId]);
        logAudit('update', 'sales_orders', $id, null, ['id' => $id]);
        ok(['id' => $id, 'status' => $status]);
    }
}

// === CUSTOMER-SPECIFIC PRICING (Sprint 9) ===
if ($endpoint === 'customer-prices') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT cpp.*, c.name as customer_name, p.name as product_name, p.code as product_code FROM customer_product_prices cpp LEFT JOIN customers c ON cpp.customer_id = c.id LEFT JOIN products p ON cpp.product_id = p.id WHERE cpp.id = ?" . ($isSuperAdmin ? "" : " AND cpp.tenant_id = ?"));
            $stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
            ok($stmt->fetch());
        }
        $customerId = $_GET['customer_id'] ?? null;
        if ($customerId) {
            $stmt = $d->prepare("SELECT cpp.*, p.name as product_name, p.code as product_code FROM customer_product_prices cpp LEFT JOIN products p ON cpp.product_id = p.id WHERE cpp.customer_id = ? AND cpp.is_active = 1" . ($isSuperAdmin ? "" : " AND cpp.tenant_id = ?"));
            $stmt->execute($isSuperAdmin ? [$customerId] : [$customerId, $tenantId]);
            ok($stmt->fetchAll());
        }
        $cppParams = [];
        $cppSql = "SELECT cpp.*, c.name as customer_name, p.name as product_name, p.code as product_code FROM customer_product_prices cpp LEFT JOIN customers c ON cpp.customer_id = c.id LEFT JOIN products p ON cpp.product_id = p.id";
        $cppSql = addTenantFilter($cppSql, 'cpp', $tenantId, $isSuperAdmin, $cppParams);
        $cppSql .= " ORDER BY cpp.id DESC LIMIT 100";
        $stmt = $d->prepare($cppSql);
        $stmt->execute($cppParams);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO customer_product_prices (customer_id, product_id, unit_id, custom_price, min_qty, is_active, notes, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,1,?,?,?,?)");
        $stmt->execute([$input['customer_id'], $input['product_id'], $input['unit_id'] ?? 1, $input['custom_price'] ?? $input['unit_price'], $input['min_qty'] ?? 1, $input['notes'] ?? null, $now, $now, $tenantId]);
        logAudit('create', 'customer_product_prices', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId()]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("UPDATE customer_product_prices SET custom_price = ?, min_qty = ?, is_active = ?, notes = ?, updated_at = ? WHERE id = ?");
        $stmt->execute([$input['custom_price'], $input['min_qty'] ?? 1, $input['is_active'] ?? 1, $input['notes'] ?? null, $now, $id]);
        logAudit('update', 'customer_product_prices', $id, null, ['id' => $id]);
        ok(['id' => $id]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $d->prepare("UPDATE customer_product_prices SET is_active = 0 WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"))->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
        logAudit('delete', 'customer_product_prices', $id, null, null);
        ok(['id' => $id]);
    }
}

// === TIER PRICING (Sprint 9) ===
if ($endpoint === 'tier-prices') {
    if ($method === 'GET') {
        $productId = $_GET['product_id'] ?? null;
        if ($productId) {
            $stmt = $d->prepare("SELECT * FROM product_tier_prices WHERE product_id = ? AND is_active = 1" . ($isSuperAdmin ? "" : " AND tenant_id = ?") . " ORDER BY min_qty");
            $stmt->execute($isSuperAdmin ? [$productId] : [$productId, $tenantId]);
            ok($stmt->fetchAll());
        }
        $ptParams = [];
        $ptSql = "SELECT pt.*, p.name as product_name, p.code as product_code FROM product_tier_prices pt LEFT JOIN products p ON pt.product_id = p.id";
        $ptSql = addTenantFilter($ptSql, 'pt', $tenantId, $isSuperAdmin, $ptParams);
        $ptSql .= " ORDER BY pt.id DESC LIMIT 100";
        $stmt = $d->prepare($ptSql);
        $stmt->execute($ptParams);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO product_tier_prices (product_id, unit_id, min_qty, max_qty, unit_price, is_active, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,1,?,?,?)");
        $stmt->execute([$input['product_id'], $input['unit_id'] ?? 1, $input['min_qty'], $input['max_qty'] ?? null, $input['unit_price'], $now, $now, $tenantId]);
        logAudit('create', 'product_tier_prices', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId()]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $d->prepare("UPDATE product_tier_prices SET is_active = 0 WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"))->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
        logAudit('delete', 'product_tier_prices', $id, null, null);
        ok(['id' => $id]);
    }
}

// === SUPPLIER PRICE HISTORY (Sprint 9) ===
if ($endpoint === 'supplier-price-history') {
    if ($method === 'GET') {
        $productId = $_GET['product_id'] ?? null;
        if ($productId) {
            $stmt = $d->prepare("SELECT sph.*, s.name as supplier_name FROM supplier_price_history sph LEFT JOIN suppliers s ON sph.supplier_id = s.id WHERE sph.product_id = ?" . ($isSuperAdmin ? "" : " AND sph.tenant_id = ?") . " ORDER BY sph.effective_date DESC");
            $stmt->execute($isSuperAdmin ? [$productId] : [$productId, $tenantId]);
            ok($stmt->fetchAll());
        }
        $sphParams = [];
        $sphSql = "SELECT sph.*, s.name as supplier_name, p.name as product_name, p.code as product_code FROM supplier_price_history sph LEFT JOIN suppliers s ON sph.supplier_id = s.id LEFT JOIN products p ON sph.product_id = p.id";
        $sphSql = addTenantFilter($sphSql, 'sph', $tenantId, $isSuperAdmin, $sphParams);
        $sphSql .= " ORDER BY sph.id DESC LIMIT 100";
        $stmt = $d->prepare($sphSql);
        $stmt->execute($sphParams);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO supplier_price_history (supplier_id, product_id, unit_id, unit_price, effective_date, po_reference, notes, created_by, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$input['supplier_id'], $input['product_id'], $input['unit_id'] ?? 1, $input['unit_price'], $input['effective_date'] ?? date('Y-m-d'), $input['po_reference'] ?? null, $input['notes'] ?? null, $_SESSION['user']['id'] ?? null, $now, $now, $tenantId]);
        logAudit('create', 'supplier_price_history', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId()]);
    }
}

// === STOCK ADJUSTMENTS (Sprint 10) ===
if ($endpoint === 'stock-adjustments') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT sa.*, p.name as product_name, p.code as product_code FROM stock_adjustments sa LEFT JOIN products p ON sa.product_id = p.id WHERE sa.id = ?" . ($isSuperAdmin ? "" : " AND sa.tenant_id = ?"));
            $stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
            ok($stmt->fetch());
        }
        
        $per_page = (int)($_GET['per_page'] ?? 50);
        $page = (int)($_GET['page'] ?? 1);
        $offset = ($page - 1) * $per_page;
        $per_page = min(max($per_page, 1), 100); // Clamp between 1-100
        
        $params = [];
        $sql = "SELECT sa.*, p.name as product_name, p.code as product_code FROM stock_adjustments sa LEFT JOIN products p ON sa.product_id = p.id";
        $sql = addTenantFilter($sql, 'sa', $tenantId, $isSuperAdmin, $params);
        $sql .= " ORDER BY sa.id DESC LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        $stmt = $d->prepare($sql);
        $stmt->execute($params);
        $adjustments = $stmt->fetchAll();
        
        // Get total count
        $countParams = [];
        $countSql = "SELECT COUNT(*) FROM stock_adjustments sa";
        $countSql = addTenantFilter($countSql, 'sa', $tenantId, $isSuperAdmin, $countParams);
        $countStmt = $d->prepare($countSql);
        $countStmt->execute($countParams);
        $total = $countStmt->fetchColumn();
        
        $meta = ['total' => (int)$total, 'per_page' => $per_page, 'current_page' => $page, 'last_page' => (int)ceil($total / $per_page)];
        
        ok($adjustments, $meta);
    }
    if ($method === 'POST') {
        // Input validation
        if (empty($input['product_id'])) {
            fail('Product ID is required');
        }
        if (!validateNumeric($input['quantity'] ?? 0, -999999, 999999)) {
            fail('Quantity must be a valid number');
        }
        if (!validateEnum($input['adjustment_type'] ?? 'correction', ['correction', 'damage', 'loss', 'theft', 'return'])) {
            fail('Invalid adjustment type');
        }
        if (!empty($input['reason']) && !validateStringLength($input['reason'], 1, 500)) {
            fail('Reason must be 1-500 characters');
        }

        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO stock_adjustments (product_id, quantity, adjustment_type, reason, status, created_by, created_at, tenant_id) VALUES (?,?,?,?,'pending',?,?,?)");
        $stmt->execute([$input['product_id'], $input['quantity'], $input['adjustment_type'] ?? 'correction', $input['reason'] ?? null, $_SESSION['user']['id'] ?? null, $now, $tenantId]);
        logAudit('create', 'stock_adjustments', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId()]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $status = $input['status'] ?? 'approved';
        try {
            $d->beginTransaction();
            if ($status === 'approved') {
                $stmt = $d->prepare("SELECT * FROM stock_adjustments WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
                $stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
                $adj = $stmt->fetch();
                if ($adj) {
                    $d->prepare("INSERT INTO stock_movements (product_id, quantity, unit_id, movement_type, notes, created_at, tenant_id) VALUES (?,?,?,?,?,?,?)")->execute([
                        $adj['product_id'], $adj['quantity'], $adj['unit_id'] ?? 1, 'adjustment', 'Approved adj #' . $id, $now, $tenantId
                    ]);
                }
            }
            $d->prepare("UPDATE stock_adjustments SET status = ?, approved_by = ?, approved_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"))->execute($isSuperAdmin ? [$status, $_SESSION['user']['id'] ?? null, $now, $id] : [$status, $_SESSION['user']['id'] ?? null, $now, $id, $tenantId]);
            $d->commit();
        } catch (PDOException $e) {
            if ($d->inTransaction()) {
                $d->rollBack();
            }
            fail('Database error: ' . $e->getMessage(), 500);
        }
        logAudit('update', 'stock_adjustments', $id, null, ['id' => $id]);
        ok(['id' => $id, 'status' => $status]);
    }
}

// === STOCK TRANSFERS (Sprint 10) ===
if ($endpoint === 'stock-transfers') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT st.*, wf.name as from_warehouse, wt.name as to_warehouse FROM stock_transfers st LEFT JOIN warehouses wf ON st.from_warehouse_id = wf.id LEFT JOIN warehouses wt ON st.to_warehouse_id = wt.id WHERE st.id = ?" . ($isSuperAdmin ? "" : " AND st.tenant_id = ?"));
            $stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
            $tr = $stmt->fetch();
            if (!$tr) fail('Transfer not found', 404);
            $items = $d->prepare("SELECT sti.*, p.name as product_name, p.code as product_code FROM stock_transfer_items sti LEFT JOIN products p ON sti.product_id = p.id WHERE sti.transfer_id = ?");
            $items->execute([$id]);
            $tr['items'] = $items->fetchAll();
            ok($tr);
        }
        
        $per_page = (int)($_GET['per_page'] ?? 50);
        $page = (int)($_GET['page'] ?? 1);
        $offset = ($page - 1) * $per_page;
        $per_page = min(max($per_page, 1), 100); // Clamp between 1-100
        
        $params = [];
        $transferSql = "SELECT st.*, wf.name as from_warehouse, wt.name as to_warehouse FROM stock_transfers st LEFT JOIN warehouses wf ON st.from_warehouse_id = wf.id LEFT JOIN warehouses wt ON st.to_warehouse_id = wt.id";
        $transferSql = addTenantFilter($transferSql, 'st', $tenantId, $isSuperAdmin, $params);
        $transferSql .= " ORDER BY st.id DESC LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        $stmt = $d->prepare($transferSql);
        $stmt->execute($params);
        $transfers = $stmt->fetchAll();
        
        // Get total count
        $countParams = [];
        $countSql = "SELECT COUNT(*) FROM stock_transfers st";
        $countSql = addTenantFilter($countSql, 'st', $tenantId, $isSuperAdmin, $countParams);
        $countStmt = $d->prepare($countSql);
        $countStmt->execute($countParams);
        $total = $countStmt->fetchColumn();
        
        $meta = ['total' => (int)$total, 'per_page' => $per_page, 'current_page' => $page, 'last_page' => (int)ceil($total / $per_page)];
        
        ok($transfers, $meta);
    }
    if ($method === 'POST') {
        // Input validation
        if (empty($input['from_warehouse_id'])) {
            fail('From warehouse ID is required');
        }
        if (empty($input['to_warehouse_id'])) {
            fail('To warehouse ID is required');
        }
        if ($input['from_warehouse_id'] == $input['to_warehouse_id']) {
            fail('From and to warehouse cannot be the same');
        }
        if (empty($input['items']) || !is_array($input['items'])) {
            fail('Items are required');
        }
        if (count($input['items']) === 0) {
            fail('At least one item is required');
        }
        foreach ($input['items'] as $item) {
            if (empty($item['product_id'])) {
                fail('Product ID is required for each item');
            }
            if (!validateNumeric($item['quantity'] ?? 0, 0.01, 999999)) {
                fail('Item quantity must be a positive number');
            }
        }
        if (!empty($input['transfer_date']) && !strtotime($input['transfer_date'])) {
            fail('Invalid transfer date');
        }

        try {
            $d->beginTransaction();
            $now = date('Y-m-d H:i:s');
            $transferNo = 'TR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $stmt = $d->prepare("INSERT INTO stock_transfers (transfer_no, transfer_date, from_warehouse_id, to_warehouse_id, status, notes, created_by, created_at, updated_at, tenant_id) VALUES (?,?,?,?,'pending',?,?,?,?,?)");
            $stmt->execute([$transferNo, $input['transfer_date'] ?? date('Y-m-d'), $input['from_warehouse_id'], $input['to_warehouse_id'], $input['notes'] ?? null, $_SESSION['user']['id'] ?? null, $now, $now, $tenantId]);
            $trId = $d->lastInsertId();
            foreach ($input['items'] ?? [] as $item) {
                if (empty($item['product_id'])) continue;
                $d->prepare("INSERT INTO stock_transfer_items (transfer_id, product_id, quantity, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?)")->execute([$trId, $item['product_id'], $item['quantity'], $now, $now, $tenantId]);
            }
            $d->commit();
        } catch (PDOException $e) {
            if ($d->inTransaction()) {
                $d->rollBack();
            }
            fail('Database error: ' . $e->getMessage(), 500);
        }
        logAudit('create', 'stock_transfers', $trId, null, ['id' => $trId]);
        created(['id' => $trId, 'transfer_no' => $transferNo]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $status = $input['status'] ?? 'completed';
        try {
            $d->beginTransaction();
            if ($status === 'completed') {
                $items = $d->prepare("SELECT * FROM stock_transfer_items WHERE transfer_id = ?");
                $items->execute([$id]);
                foreach ($items->fetchAll() as $item) {
                    $d->prepare("INSERT INTO stock_movements (product_id, quantity, unit_id, movement_type, notes, created_at, tenant_id) VALUES (?,?,?,?,?,?,?)")->execute([
                        $item['product_id'], -abs((float)$item['quantity']), $item['unit_id'] ?? 1, 'transfer_out', 'Transfer out #' . $id, $now, $tenantId
                    ]);
                    $d->prepare("INSERT INTO stock_movements (product_id, quantity, unit_id, movement_type, notes, created_at, tenant_id) VALUES (?,?,?,?,?,?,?)")->execute([
                        $item['product_id'], abs((float)$item['quantity']), $item['unit_id'] ?? 1, 'transfer_in', 'Transfer in #' . $id, $now, $tenantId
                    ]);
                }
            }
            $d->prepare("UPDATE stock_transfers SET status = ?, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"))->execute($isSuperAdmin ? [$status, $now, $id] : [$status, $now, $id, $tenantId]);
            $d->commit();
        } catch (PDOException $e) {
            if ($d->inTransaction()) {
                $d->rollBack();
            }
            fail('Database error: ' . $e->getMessage(), 500);
        }
        logAudit('update', 'stock_transfers', $id, null, ['id' => $id]);
        ok(['id' => $id, 'status' => $status]);
    }
}

// === WAREHOUSE LOCATIONS (Sprint 10) ===
if ($endpoint === 'warehouse-locations') {
    if ($method === 'GET') {
        $whId = $_GET['warehouse_id'] ?? null;
        if ($whId) {
            $stmt = $d->prepare("SELECT * FROM warehouse_locations WHERE warehouse_id = ? AND is_active = 1" . ($isSuperAdmin ? "" : " AND tenant_id = ?") . " ORDER BY code");
            $stmt->execute($isSuperAdmin ? [$whId] : [$whId, $tenantId]);
            ok($stmt->fetchAll());
        }
        $wlParams = [];
        $wlSql = "SELECT wl.*, w.name as warehouse_name FROM warehouse_locations wl LEFT JOIN warehouses w ON wl.warehouse_id = w.id WHERE wl.is_active = 1";
        $wlSql = addTenantFilter($wlSql, 'wl', $tenantId, $isSuperAdmin, $wlParams);
        $wlSql .= " ORDER BY wl.id DESC";
        $stmt = $d->prepare($wlSql);
        $stmt->execute($wlParams);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO warehouse_locations (warehouse_id, code, name, zone_type, aisle, level, max_weight_kg, capacity_m2, is_active, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,1,?,?,?)");
        $stmt->execute([$input['warehouse_id'], $input['code'], $input['name'], $input['zone_type'] ?? 'storage', $input['aisle'] ?? null, $input['level'] ?? null, $input['max_weight_kg'] ?? 0, $input['capacity_m2'] ?? 0, $now, $now, $tenantId]);
        logAudit('create', 'warehouse_locations', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId(), 'code' => $input['code'], 'name' => $input['name']]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) fail('ID is required');
        
        // Check for references
        $refCheck = checkReferences('warehouse_locations', $id);
        if ($refCheck['has_references']) {
            fail($refCheck['message']);
        }
        
        // Soft delete
        $stmt = $d->prepare("UPDATE warehouse_locations SET is_active = 0, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
        $stmt->execute($isSuperAdmin ? [date('Y-m-d H:i:s'), $id] : [date('Y-m-d H:i:s'), $id, $tenantId]);
        logAudit('delete', 'warehouse_locations', $id, null, null);
        ok(['message' => 'Warehouse location deleted']);
    }
}

// === STATUS CODES ===
if ($endpoint === 'status-codes') {
    if ($method === 'GET') {
        $module = $_GET['module'] ?? '';
        if ($module) {
            $stmt = $d->prepare("SELECT * FROM status_codes WHERE module = ? AND is_active = 1 ORDER BY name");
            $stmt->execute([$module]);
            ok($stmt->fetchAll());
        }
        $scParams = [];
        $scSql = "SELECT * FROM status_codes WHERE is_active = 1";
        $scSql = addTenantFilter($scSql, 'status_codes', $tenantId, $isSuperAdmin, $scParams);
        $scSql .= " ORDER BY module, name";
        $stmt = $d->prepare($scSql);
        $stmt->execute($scParams);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['name']) || empty($input['code']) || empty($input['module'])) fail('Name, code, and module are required');
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO status_codes (module, code, name, is_active, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$input['module'], $input['code'], $input['name'], 1, $now, $now, $tenantId]);
        logAudit('create', 'status_codes', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        ok(['id' => $d->lastInsertId(), 'code' => $input['code'], 'name' => $input['name'], 'module' => $input['module']]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) fail('ID is required');
        
        // Check for references
        $refCheck = checkReferences('status_codes', $id);
        if ($refCheck['has_references']) {
            fail($refCheck['message']);
        }
        
        // Soft delete
        $stmt = $d->prepare("UPDATE status_codes SET is_active = 0, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
        $stmt->execute($isSuperAdmin ? [date('Y-m-d H:i:s'), $id] : [date('Y-m-d H:i:s'), $id, $tenantId]);
        logAudit('delete', 'status_codes', $id, null, null);
        ok(['message' => 'Status code deleted']);
    }
}

// === CASH TRANSACTIONS (Sprint 11) ===
if ($endpoint === 'cash-transactions') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT * FROM cash_transactions WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ? AND branch_id = ?"));
            $stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId, $branchId]);
            ok($stmt->fetch());
        }
        $type = $_GET['type'] ?? '';
        if ($type) {
            $typeSql = "SELECT * FROM cash_transactions WHERE type = ?";
            $params = [$type];
            $typeSql = addTenantFilter($typeSql, 'cash_transactions', $tenantId, $isSuperAdmin, $params);
            $typeSql = addBranchFilter($typeSql, 'cash_transactions', $branchId, $isSuperAdmin, $params);
            $typeSql .= " ORDER BY id DESC LIMIT 100";
            $stmt = $d->prepare($typeSql);
            $stmt->execute($params);
            ok($stmt->fetchAll());
        }
        $cashTxSql = "SELECT * FROM cash_transactions";
        $params = [];
        $cashTxSql = addTenantFilter($cashTxSql, 'cash_transactions', $tenantId, $isSuperAdmin, $params);
        $cashTxSql = addBranchFilter($cashTxSql, 'cash_transactions', $branchId, $isSuperAdmin, $params);
        $cashTxSql .= " ORDER BY id DESC LIMIT 100";
        $stmt = $d->prepare($cashTxSql);
        $stmt->execute($params);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $txNo = 'CT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $stmt = $d->prepare("INSERT INTO cash_transactions (transaction_no, type, account_type, transaction_date, amount, description, category, reference_no, recipient, created_by, created_at, updated_at, tenant_id, branch_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$txNo, $input['type'] ?? 'in', $input['account_type'] ?? 'cash', $input['transaction_date'] ?? date('Y-m-d'), $input['amount'], $input['description'] ?? null, $input['category'] ?? null, $input['reference_no'] ?? null, $input['recipient'] ?? null, $_SESSION['user']['id'] ?? null, $now, $now, $tenantId, $branchId]);
        logAudit('create', 'cash_transactions', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId(), 'transaction_no' => $txNo]);
    }
}

// === BANK STATEMENTS (Sprint 11) ===
if ($endpoint === 'bank-statements') {
    if ($method === 'GET') {
        $status = $_GET['reconciliation_status'] ?? '';
        if ($status) {
            $statusSql = "SELECT * FROM bank_statements WHERE reconciliation_status = ?";
            $params = [$status];
            $statusSql = addTenantFilter($statusSql, 'bank_statements', $tenantId, $isSuperAdmin, $params);
            $statusSql .= " ORDER BY transaction_date DESC LIMIT 100";
            $stmt = $d->prepare($statusSql);
            $stmt->execute($params);
            ok($stmt->fetchAll());
        }
        $bankStmtSql = "SELECT * FROM bank_statements";
        $params = [];
        $bankStmtSql = addTenantFilter($bankStmtSql, 'bank_statements', $tenantId, $isSuperAdmin, $params);
        $bankStmtSql .= " ORDER BY transaction_date DESC LIMIT 100";
        $stmt = $d->prepare($bankStmtSql);
        $stmt->execute($params);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO bank_statements (bank_account, transaction_date, description, debit, credit, balance, reference_no, reconciliation_status, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?,'unreconciled',?,?,?)");
        $stmt->execute([$input['bank_account'], $input['transaction_date'] ?? date('Y-m-d'), $input['description'] ?? null, $input['debit'] ?? 0, $input['credit'] ?? 0, $input['balance'] ?? 0, $input['reference_no'] ?? null, $now, $now, $tenantId]);
        logAudit('create', 'bank_statements', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId()]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $d->prepare("UPDATE bank_statements SET reconciliation_status = 'reconciled', reconciled_at = ?, reconciled_by = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"))->execute($isSuperAdmin ? [$now, $_SESSION['user']['id'] ?? null, $id] : [$now, $_SESSION['user']['id'] ?? null, $id, $tenantId]);
        logAudit('update', 'bank_statements', $id, null, ['id' => $id]);
        ok(['id' => $id, 'status' => 'reconciled']);
    }
}

// === FIXED ASSETS (Sprint 11) ===
if ($endpoint === 'fixed-assets') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT * FROM fixed_assets WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ? AND branch_id = ?"));
            $stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId, $branchId]);
            $asset = $stmt->fetch();
            if (!$asset) fail('Asset not found', 404);
            $deps = $d->prepare("SELECT * FROM asset_depreciations WHERE fixed_asset_id = ? ORDER BY depreciation_date DESC");
            $deps->execute([$id]);
            $asset['depreciations'] = $deps->fetchAll();
            ok($asset);
        }
        
        $per_page = (int)($_GET['per_page'] ?? 50);
        $page = (int)($_GET['page'] ?? 1);
        $offset = ($page - 1) * $per_page;
        $per_page = min(max($per_page, 1), 100); // Clamp between 1-100
        
        $params = [];
        $assetSql = "SELECT * FROM fixed_assets";
        $assetSql = addTenantFilter($assetSql, 'fixed_assets', $tenantId, $isSuperAdmin, $params);
        $assetSql = addBranchFilter($assetSql, 'fixed_assets', $branchId, $isSuperAdmin, $params);
        $assetSql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        $stmt = $d->prepare($assetSql);
        $stmt->execute($params);
        $assets = $stmt->fetchAll();
        
        // Get total count
        $countParams = [];
        $countSql = "SELECT COUNT(*) FROM fixed_assets";
        $countSql = addTenantFilter($countSql, 'fixed_assets', $tenantId, $isSuperAdmin, $countParams);
        $countSql = addBranchFilter($countSql, 'fixed_assets', $branchId, $isSuperAdmin, $countParams);
        $countStmt = $d->prepare($countSql);
        $countStmt->execute($countParams);
        $total = $countStmt->fetchColumn();
        
        $meta = ['total' => (int)$total, 'per_page' => $per_page, 'current_page' => $page, 'last_page' => (int)ceil($total / $per_page)];
        
        ok($assets, $meta);
    }
    if ($method === 'POST') {
        // Input validation
        if (empty($input['name']) || !validateStringLength($input['name'], 1, 255)) {
            fail('Asset name is required and must be 1-255 characters');
        }
        if (!validateNumeric($input['acquisition_cost'] ?? 0, 0, 999999999)) {
            fail('Acquisition cost must be a positive number');
        }
        if (!validateNumeric($input['salvage_value'] ?? 0, 0, 999999999)) {
            fail('Salvage value must be a positive number');
        }
        if (!validateNumeric($input['useful_life_months'] ?? 60, 1, 600)) {
            fail('Useful life must be between 1 and 600 months');
        }
        if (!empty($input['acquisition_date']) && !strtotime($input['acquisition_date'])) {
            fail('Invalid acquisition date');
        }
        if (!validateEnum($input['depreciation_method'] ?? 'straight_line', ['straight_line', 'declining_balance', 'units_of_production'])) {
            fail('Invalid depreciation method');
        }

        $now = date('Y-m-d H:i:s');
        $assetCode = 'FA-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $cost = (float)($input['acquisition_cost'] ?? 0);
        $salvage = (float)($input['salvage_value'] ?? 0);
        $life = (int)($input['useful_life_months'] ?? 60);
        $monthlyDep = $life > 0 ? ($cost - $salvage) / $life : 0;
        $stmt = $d->prepare("INSERT INTO fixed_assets (asset_code, name, category, serial_no, plate_no, acquisition_date, acquisition_cost, salvage_value, useful_life_months, depreciation_method, monthly_depreciation, accumulated_depreciation, book_value, status, notes, created_at, updated_at, tenant_id, branch_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$assetCode, $input['name'] ?? '', $input['category'] ?? 'equipment', $input['serial_no'] ?? null, $input['plate_no'] ?? null, $input['acquisition_date'] ?? date('Y-m-d'), $cost, $salvage, $life, 'straight_line', $monthlyDep, 0, $cost, 'active', $input['notes'] ?? null, $now, $now, $tenantId, $branchId]);
        $assetId = $d->lastInsertId();
        logAudit('create', 'fixed_assets', $assetId, null, ['id' => $assetId]);
        created(['id' => $assetId, 'asset_code' => $assetCode]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $action = $input['action'] ?? '';

        if ($action === 'depreciate') {
            $stmt = $d->prepare("SELECT * FROM fixed_assets WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ? AND branch_id = ?"));
            $stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId, $branchId]);
            $asset = $stmt->fetch();
            if (!$asset) fail('Asset not found', 404);

            $depAmount = (float)$asset['monthly_depreciation'];
            $newAccum = (float)$asset['accumulated_depreciation'] + $depAmount;
            $newBookValue = (float)$asset['book_value'] - $depAmount;

            $d->prepare("INSERT INTO asset_depreciations (fixed_asset_id, depreciation_date, amount, accumulated_after, book_value_after, notes, created_by, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?)")->execute([$id, date('Y-m-d'), $depAmount, $newAccum, $newBookValue, 'Monthly depreciation', $_SESSION['user']['id'] ?? null, $now, $now, $tenantId]);
            $d->prepare("UPDATE fixed_assets SET accumulated_depreciation = ?, book_value = ?, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ? AND branch_id = ?"))->execute($isSuperAdmin ? [$newAccum, $newBookValue, $now, $id] : [$newAccum, $newBookValue, $now, $id, $tenantId, $branchId]);
            ok(['id' => $id, 'depreciation' => $depAmount, 'book_value' => $newBookValue]);
        }

        $status = $input['status'] ?? 'active';
        $d->prepare("UPDATE fixed_assets SET status = ?, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ? AND branch_id = ?"))->execute($isSuperAdmin ? [$status, $now, $id] : [$status, $now, $id, $tenantId, $branchId]);
        logAudit('update', 'fixed_assets', $id, null, ['id' => $id]);
        ok(['id' => $id, 'status' => $status]);
    }
}

// === VEHICLES (Sprint 12) ===
if ($endpoint === 'vehicles') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT * FROM vehicles WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
            $stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
            ok($stmt->fetch());
        }
        $vParams = [];
        $vSql = "SELECT * FROM vehicles";
        $vSql = addTenantFilter($vSql, 'vehicles', $tenantId, $isSuperAdmin, $vParams);
        $vSql .= " ORDER BY id DESC LIMIT 100";
        $stmt = $d->prepare($vSql);
        $stmt->execute($vParams);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO vehicles (plate_no, vehicle_type, brand, model, capacity_kg, fuel_type, acquisition_date, status, notes, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$input['plate_no'], $input['vehicle_type'] ?? 'truck', $input['brand'] ?? null, $input['model'] ?? null, $input['capacity_kg'] ?? null, $input['fuel_type'] ?? 'diesel', $input['acquisition_date'] ?? date('Y-m-d'), 'active', $input['notes'] ?? null, $now, $now, $tenantId]);
        logAudit('create', 'vehicles', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId()]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $d->prepare("UPDATE vehicles SET plate_no=?, vehicle_type=?, brand=?, model=?, capacity_kg=?, fuel_type=?, status=?, notes=?, updated_at=? WHERE id=?" . ($isSuperAdmin ? "" : " AND tenant_id=?"))->execute($isSuperAdmin ? [$input['plate_no'], $input['vehicle_type'] ?? 'truck', $input['brand'] ?? null, $input['model'] ?? null, $input['capacity_kg'] ?? null, $input['fuel_type'] ?? 'diesel', $input['status'] ?? 'active', $input['notes'] ?? null, $now, $id] : [$input['plate_no'], $input['vehicle_type'] ?? 'truck', $input['brand'] ?? null, $input['model'] ?? null, $input['capacity_kg'] ?? null, $input['fuel_type'] ?? 'diesel', $input['status'] ?? 'active', $input['notes'] ?? null, $now, $id, $tenantId]);
        logAudit('update', 'vehicles', $id, null, ['id' => $id]);
        ok(['id' => $id]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        
        // Check for references
        $refCheck = checkReferences('vehicles', $id);
        if ($refCheck['has_references']) {
            fail($refCheck['message']);
        }
        
        // Soft delete
        $d->prepare("UPDATE vehicles SET status='inactive' WHERE id=?" . ($isSuperAdmin ? "" : " AND tenant_id=?"))->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
        logAudit('delete', 'vehicles', $id, null, null);
        ok(['id' => $id]);
    }
}

// === VEHICLE MAINTENANCE (Sprint 12) ===
if ($endpoint === 'vehicle-maintenance') {
    if ($method === 'GET') {
        $vehicleId = $_GET['vehicle_id'] ?? null;
        if ($vehicleId) {
            $stmt = $d->prepare("SELECT vm.*, v.plate_no FROM vehicle_maintenance vm LEFT JOIN vehicles v ON vm.vehicle_id = v.id WHERE vm.vehicle_id = ?" . ($isSuperAdmin ? "" : " AND vm.tenant_id = ?") . " ORDER BY vm.maintenance_date DESC");
            $stmt->execute($isSuperAdmin ? [$vehicleId] : [$vehicleId, $tenantId]);
            ok($stmt->fetchAll());
        }
        $vmParams = [];
        $vmSql = "SELECT vm.*, v.plate_no FROM vehicle_maintenance vm LEFT JOIN vehicles v ON vm.vehicle_id = v.id";
        $vmSql = addTenantFilter($vmSql, 'vm', $tenantId, $isSuperAdmin, $vmParams);
        $vmSql .= " ORDER BY vm.id DESC LIMIT 100";
        $stmt = $d->prepare($vmSql);
        $stmt->execute($vmParams);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO vehicle_maintenance (vehicle_id, maintenance_date, maintenance_type, cost, odometer_km, description, next_maintenance_date, created_by, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$input['vehicle_id'], $input['maintenance_date'] ?? date('Y-m-d'), $input['maintenance_type'] ?? 'service', $input['cost'] ?? 0, $input['odometer_km'] ?? null, $input['description'] ?? null, $input['next_maintenance_date'] ?? null, $_SESSION['user']['id'] ?? null, $now, $now, $tenantId]);
        logAudit('create', 'vehicle_maintenance', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId()]);
    }
}

// === DELIVERY ROUTES (Sprint 12) ===
if ($endpoint === 'delivery-routes') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT dr.*, v.plate_no FROM delivery_routes dr LEFT JOIN vehicles v ON dr.vehicle_id = v.id WHERE dr.id = ?" . ($isSuperAdmin ? "" : " AND dr.tenant_id = ?"));
            $stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
            $route = $stmt->fetch();
            if (!$route) fail('Route not found', 404);
            $stops = $d->prepare("SELECT rs.*, dl.delivery_no FROM route_stops rs LEFT JOIN deliveries dl ON rs.delivery_id = dl.id WHERE rs.route_id = ? ORDER BY rs.stop_order");
            $stops->execute([$id]);
            $route['stops'] = $stops->fetchAll();
            ok($route);
        }
        $drParams = [];
        $drSql = "SELECT dr.*, v.plate_no FROM delivery_routes dr LEFT JOIN vehicles v ON dr.vehicle_id = v.id";
        $drSql = addTenantFilter($drSql, 'dr', $tenantId, $isSuperAdmin, $drParams);
        $drSql .= " ORDER BY dr.id DESC LIMIT 100";
        $stmt = $d->prepare($drSql);
        $stmt->execute($drParams);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $routeNo = 'RT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $stmt = $d->prepare("INSERT INTO delivery_routes (route_no, route_date, vehicle_id, driver_name, status, total_distance_km, estimated_time_minutes, notes, created_by, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,'planned',?,?,?,?,?,?)");
        $stmt->execute([$routeNo, $input['route_date'] ?? date('Y-m-d'), $input['vehicle_id'] ?? null, $input['driver_name'] ?? null, $input['total_distance_km'] ?? null, $input['estimated_time_minutes'] ?? null, $input['notes'] ?? null, $_SESSION['user']['id'] ?? null, $now, $now, $tenantId]);
        $routeId = $d->lastInsertId();
        foreach ($input['stops'] ?? [] as $i => $stop) {
            $d->prepare("INSERT INTO route_stops (route_id, delivery_id, stop_order, customer_name, address, phone, status, created_at, tenant_id) VALUES (?,?,?,?,?,?,'pending',?,?)")->execute([$routeId, $stop['delivery_id'] ?? null, $i + 1, $stop['customer_name'] ?? null, $stop['address'] ?? null, $stop['phone'] ?? null, $now, $tenantId]);
        }
        logAudit('create', 'delivery_routes', $routeId, null, ['id' => $routeId]);
        created(['id' => $routeId, 'route_no' => $routeNo]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $status = $input['status'] ?? 'in_progress';
        if (isset($input['stop_id'])) {
            $stopStatus = $input['stop_status'] ?? 'completed';
            $d->prepare("UPDATE route_stops SET status = ?, arrived_at = ? WHERE id = ? AND route_id = ?")->execute([$stopStatus, $now, $input['stop_id'], $id]);
            ok(['id' => $id, 'stop_id' => $input['stop_id'], 'stop_status' => $stopStatus]);
        } else {
            $d->prepare("UPDATE delivery_routes SET status = ?, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"))->execute($isSuperAdmin ? [$status, $now, $id] : [$status, $now, $id, $tenantId]);
            logAudit('update', 'delivery_routes', $id, null, ['id' => $id]);
            ok(['id' => $id, 'status' => $status]);
        }
    }
}

// === WHATSAPP TEMPLATES (Sprint 12) ===
if ($endpoint === 'whatsapp-templates') {
    if ($method === 'GET') {
        $wtParams = [];
        $wtSql = "SELECT * FROM whatsapp_templates WHERE is_active = 1";
        $wtSql = addTenantFilter($wtSql, 'whatsapp_templates', $tenantId, $isSuperAdmin, $wtParams);
        $wtSql .= " ORDER BY id";
        $stmt = $d->prepare($wtSql);
        $stmt->execute($wtParams);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO whatsapp_templates (template_name, template_type, message_body, variables, is_active, created_at, updated_at, tenant_id) VALUES (?,?,?,?,1,?,?,?)");
        $stmt->execute([$input['template_name'], $input['template_type'] ?? 'notification', $input['message_body'], $input['variables'] ?? null, $now, $now, $tenantId]);
        logAudit('create', 'whatsapp_templates', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId()]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $d->prepare("UPDATE whatsapp_templates SET message_body = ?, variables = ?, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"))->execute($isSuperAdmin ? [$input['message_body'], $input['variables'] ?? null, $now, $id] : [$input['message_body'], $input['variables'] ?? null, $now, $id, $tenantId]);
        logAudit('update', 'whatsapp_templates', $id, null, ['id' => $id]);
        ok(['id' => $id]);
    }
}

// === WHATSAPP MESSAGES (Sprint 12) ===
if ($endpoint === 'whatsapp-messages') {
    if ($method === 'GET') {
        $wmParams = [];
        $wmSql = "SELECT * FROM whatsapp_messages";
        $wmSql = addTenantFilter($wmSql, 'whatsapp_messages', $tenantId, $isSuperAdmin, $wmParams);
        $wmSql .= " ORDER BY id DESC LIMIT 100";
        $stmt = $d->prepare($wmSql);
        $stmt->execute($wmParams);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $phone = $input['phone_number'] ?? '';
        $msg = $input['message_body'] ?? '';
        $templateName = $input['template_name'] ?? null;
        if (!$phone || !$msg) fail('Phone and message required');

        // Log the message (in production, this would call WhatsApp API)
        $stmt = $d->prepare("INSERT INTO whatsapp_messages (phone_number, message_body, template_name, reference_type, reference_id, status, sent_at, created_by, created_at, tenant_id) VALUES (?,?,?,?,?,?,'sent',?,?,?)");
        $stmt->execute([$phone, $msg, $templateName, $input['reference_type'] ?? null, $input['reference_id'] ?? null, $_SESSION['user']['id'] ?? null, $now, $now, $tenantId]);
        logAudit('create', 'whatsapp_messages', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId(), 'status' => 'sent']);
    }
}

// === E-FAKTUR (Sprint 12) ===
if ($endpoint === 'e-faktur') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT * FROM e_faktur WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
            $stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
            ok($stmt->fetch());
        }
        $type = $_GET['type'] ?? '';
        if ($type) {
            $stmt = $d->prepare("SELECT * FROM e_faktur WHERE faktur_type = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?") . " ORDER BY transaction_date DESC LIMIT 100");
            $stmt->execute($isSuperAdmin ? [$type] : [$type, $tenantId]);
            ok($stmt->fetchAll());
        }
        $export = $_GET['export'] ?? '';
        if ($export === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="efaktur_' . date('Ymd') . '.csv"');
            $efParams = [];
            $efSql = "SELECT * FROM e_faktur";
            $efSql = addTenantFilter($efSql, 'e_faktur', $tenantId, $isSuperAdmin, $efParams);
            $efSql .= " ORDER BY transaction_date DESC";
            $stmt = $d->prepare($efSql);
            $stmt->execute($efParams);
            $rows = $stmt->fetchAll();
            echo "FK;Jenis;FG Pengganti;Masa;Tahun;No Seri Faktur;Tanggal Faktur;NPWP;Nama;Alamat;DPP;PPN;Tarif PPN;PPnBM;Keterangan\n";
            foreach ($rows as $r) {
                echo "FK;" . ($r['faktur_type'] === 'keluaran' ? '0' : '1') . ";0;" . date('n', strtotime($r['transaction_date'])) . ";" . date('Y', strtotime($r['transaction_date'])) . ";" . $r['faktur_no'] . ";" . $r['transaction_date'] . ";" . $r['counterparty_npwp'] . ";" . str_replace(';', ',', $r['counterparty_name']) . ";;" . $r['dpp'] . ";" . $r['ppn'] . ";11;0;" . str_replace(';', ',', $r['description'] ?? '') . "\n";
            }
            exit;
        }
        $efParams = [];
        $efSql = "SELECT * FROM e_faktur";
        $efSql = addTenantFilter($efSql, 'e_faktur', $tenantId, $isSuperAdmin, $efParams);
        $efSql .= " ORDER BY transaction_date DESC LIMIT 100";
        $stmt = $d->prepare($efSql);
        $stmt->execute($efParams);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $fakturNo = date('Y') . sprintf('%03d', date('n')) . '-' . str_pad(rand(1, 999999999), 9, '0', STR_PAD_LEFT);
        $dpp = (float)($input['dpp'] ?? 0);
        $ppn = $dpp * 0.11;
        $stmt = $d->prepare("INSERT INTO e_faktur (faktur_no, faktur_type, transaction_date, counterparty_name, counterparty_npwp, dpp, ppn, description, reference_type, reference_id, export_status, created_by, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?,'pending',?,?,?,?)");
        $stmt->execute([$fakturNo, $input['faktur_type'] ?? 'keluaran', $input['transaction_date'] ?? date('Y-m-d'), $input['counterparty_name'] ?? '', $input['counterparty_npwp'] ?? '', $dpp, $ppn, $input['description'] ?? null, $input['reference_type'] ?? null, $input['reference_id'] ?? null, $_SESSION['user']['id'] ?? null, $now, $now, $tenantId]);
        logAudit('create', 'e_faktur', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId(), 'faktur_no' => $fakturNo]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $d->prepare("UPDATE e_faktur SET export_status = 'exported', updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"))->execute($isSuperAdmin ? [$now, $id] : [$now, $id, $tenantId]);
        logAudit('update', 'e_faktur', $id, null, ['id' => $id]);
        ok(['id' => $id, 'status' => 'exported']);
    }
}

// === LANDED COST DISTRIBUTION (Gap 1) ===
if ($endpoint === 'landed-cost') {
    if ($method === 'GET') {
        $poId = $_GET['po_id'] ?? null;
        if ($poId) {
            $stmt = $d->prepare("SELECT lcd.*, p.name as product_name, p.code as product_code FROM landed_cost_distributions lcd JOIN products p ON lcd.product_id = p.id WHERE lcd.purchase_order_id = ?" . ($isSuperAdmin ? "" : " AND lcd.tenant_id = ?"));
            $stmt->execute($isSuperAdmin ? [$poId] : [$poId, $tenantId]);
            ok($stmt->fetchAll());
        }
        $lcdParams = [];
        $lcdSql = "SELECT lcd.*, p.name as product_name, p.code as product_code, po.po_number FROM landed_cost_distributions lcd JOIN products p ON lcd.product_id = p.id JOIN purchase_orders po ON lcd.purchase_order_id = po.id";
        $lcdSql = addTenantFilter($lcdSql, 'lcd', $tenantId, $isSuperAdmin, $lcdParams);
        $lcdSql .= " ORDER BY lcd.id DESC LIMIT 100";
        $stmt = $d->prepare($lcdSql);
        $stmt->execute($lcdParams);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $poId = $input['po_id'] ?? null;
        if (!$poId) fail('PO ID required');
        
        $po = $d->prepare("SELECT * FROM purchase_orders WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ? AND branch_id = ?"));
        $po->execute($isSuperAdmin ? [$poId] : [$poId, $tenantId, $branchId]);
        $poData = $po->fetch();
        if (!$poData) fail('PO not found');
        
        $freight = (float)($poData['freight_cost'] ?? 0);
        $insurance = (float)($poData['insurance_cost'] ?? 0);
        $handling = (float)($poData['handling_cost'] ?? 0);
        $totalLanded = $freight + $insurance + $handling;
        
        if ($totalLanded <= 0) fail('No landed cost to distribute (freight/insurance/handling all 0)');
        
        // Get PO items with their subtotal
        $items = $d->prepare("SELECT pi.*, p.name as product_name FROM purchase_items pi JOIN products p ON pi.product_id = p.id WHERE pi.po_id = ?" . ($isSuperAdmin ? "" : " AND pi.tenant_id = ?"));
        $items->execute($isSuperAdmin ? [$poId] : [$poId, $tenantId]);
        $items = $items->fetchAll();
        
        if (empty($items)) fail('No PO items found');
        
        // Calculate total subtotal for proportional distribution
        $totalSubtotal = 0;
        foreach ($items as $i) {
            $totalSubtotal += (float)$i['subtotal'];
        }
        if ($totalSubtotal <= 0) fail('Total subtotal is 0');
        
        $now = date('Y-m-d H:i:s');
        $d->prepare("DELETE FROM landed_cost_distributions WHERE purchase_order_id = ?")->execute([$poId]);
        
        foreach ($items as $item) {
            $ratio = (float)$item['subtotal'] / $totalSubtotal;
            $freightAlloc = $freight * $ratio;
            $insuranceAlloc = $insurance * $ratio;
            $handlingAlloc = $handling * $ratio;
            $totalItemLanded = $freightAlloc + $insuranceAlloc + $handlingAlloc;
            $qty = (float)$item['quantity'];
            $landedUnitCost = $totalItemLanded / $qty;
            $baseUnitCost = (float)$item['unit_price'];
            $fullLandedCost = $baseUnitCost + $landedUnitCost;
            
            $d->prepare("INSERT INTO landed_cost_distributions (purchase_order_id, product_id, freight_allocated, insurance_allocated, handling_allocated, total_landed_cost, quantity, landed_unit_cost, distribution_method, created_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$poId, $item['product_id'], $freightAlloc, $insuranceAlloc, $handlingAlloc, $totalItemLanded, $qty, $landedUnitCost, 'by_value', $now, $tenantId]);
            
            // Update product landed_cost
            $d->prepare("UPDATE products SET landed_cost = ? WHERE id = ?")->execute([$fullLandedCost, $item['product_id']]);
            
            // Update product_batches if exists
            $d->prepare("UPDATE product_batches SET landed_unit_cost = ? WHERE purchase_order_id = ? AND product_id = ?")
                ->execute([$fullLandedCost, $poId, $item['product_id']]);
        }
        
        // Update PO landed_total
        $d->prepare("UPDATE purchase_orders SET landed_total = subtotal + ? WHERE id = ?")->execute([$totalLanded, $poId]);
        
        $result = $d->prepare("SELECT lcd.*, p.name as product_name FROM landed_cost_distributions lcd JOIN products p ON lcd.product_id = p.id WHERE lcd.purchase_order_id = ?" . ($isSuperAdmin ? "" : " AND lcd.tenant_id = ?"));
        $result->execute($isSuperAdmin ? [$poId] : [$poId, $tenantId]);
        logAudit('create', 'landed_cost_distributions', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['distributions' => $result->fetchAll(), 'total_landed_cost' => $totalLanded]);
    }
}

// === PARTIAL DELIVERIES (Gap 2) ===
if ($endpoint === 'partial-deliveries') {
    if ($method === 'GET') {
        $saleId = $_GET['sale_id'] ?? null;
        if ($saleId) {
            $stmt = $d->prepare("SELECT pd.*, p.name as product_name, p.code as product_code FROM partial_deliveries pd JOIN products p ON pd.product_id = p.id WHERE pd.sale_id = ?" . ($isSuperAdmin ? "" : " AND pd.tenant_id = ?") . " ORDER BY pd.delivery_date DESC");
            $stmt->execute($isSuperAdmin ? [$saleId] : [$saleId, $tenantId]);
            ok($stmt->fetchAll());
        }
        $pdParams = [];
        $pdSql = "SELECT pd.*, p.name as product_name, p.code as product_code, s.invoice_no FROM partial_deliveries pd JOIN products p ON pd.product_id = p.id JOIN sales s ON pd.sale_id = s.id";
        $pdSql = addTenantFilter($pdSql, 'pd', $tenantId, $isSuperAdmin, $pdParams);
        $pdSql .= " ORDER BY pd.id DESC LIMIT 100";
        $stmt = $d->prepare($pdSql);
        $stmt->execute($pdParams);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $saleId = $input['sale_id'] ?? null;
        if (!$saleId) fail('Sale ID required');
        
        $deliveryDate = $input['delivery_date'] ?? date('Y-m-d');
        $deliveryId = $input['delivery_id'] ?? null;
        $notes = $input['notes'] ?? null;
        
        try {
            $d->beginTransaction();
            foreach ($input['items'] ?? [] as $item) {
                $saleItemId = $item['sale_item_id'] ?? null;
                $deliveredQty = (float)($item['delivered_qty'] ?? 0);
                if (!$saleItemId || $deliveredQty <= 0) continue;
                
                $si = $d->prepare("SELECT * FROM sale_items WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
                $si->execute($isSuperAdmin ? [$saleItemId] : [$saleItemId, $tenantId]);
                $siData = $si->fetch();
                if (!$siData) continue;
                
                $orderedQty = (float)$siData['quantity'];
                $alreadyDelivered = (float)($siData['remaining_qty'] !== null ? $orderedQty - (float)$siData['remaining_qty'] : 0);
                $remaining = $orderedQty - $alreadyDelivered - $deliveredQty;
                
                if ($remaining < 0) $remaining = 0;
                
                $status = $remaining == 0 ? 'completed' : 'partial';
                
                $d->prepare("INSERT INTO partial_deliveries (sale_id, delivery_id, sale_item_id, product_id, ordered_qty, delivered_qty, remaining_qty, delivery_date, status, notes, created_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
                    ->execute([$saleId, $deliveryId, $saleItemId, $siData['product_id'], $orderedQty, $deliveredQty, $remaining, $deliveryDate, $status, $notes, $now, $tenantId]);
                
                $d->prepare("UPDATE sale_items SET remaining_qty = ? WHERE id = ?")->execute([$remaining, $saleItemId]);
            }
            $d->commit();
        } catch (PDOException $e) {
            if ($d->inTransaction()) {
                $d->rollBack();
            }
            fail('Database error: ' . $e->getMessage(), 500);
        }
        
        logAudit('create', 'partial_deliveries', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['status' => 'ok']);
    }
}

// === PRODUCT BATCHES (Gap 3) ===
if ($endpoint === 'product-batches') {
    if ($method === 'GET') {
        $productId = $_GET['product_id'] ?? null;
        if ($productId) {
            $stmt = $d->prepare("SELECT pb.*, p.name as product_name, p.code as product_code, s.name as supplier_name FROM product_batches pb JOIN products p ON pb.product_id = p.id LEFT JOIN suppliers s ON pb.supplier_id = s.id WHERE pb.product_id = ? AND pb.quantity_remaining > 0" . ($isSuperAdmin ? "" : " AND pb.tenant_id = ?") . " ORDER BY pb.received_date ASC");
            $stmt->execute($isSuperAdmin ? [$productId] : [$productId, $tenantId]);
            ok($stmt->fetchAll());
        }
        $pbParams = [];
        $pbSql = "SELECT pb.*, p.name as product_name, p.code as product_code, s.name as supplier_name FROM product_batches pb JOIN products p ON pb.product_id = p.id LEFT JOIN suppliers s ON pb.supplier_id = s.id";
        $pbSql = addTenantFilter($pbSql, 'pb', $tenantId, $isSuperAdmin, $pbParams);
        $pbSql .= " ORDER BY pb.id DESC LIMIT 100";
        $stmt = $d->prepare($pbSql);
        $stmt->execute($pbParams);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO product_batches (product_id, batch_no, lot_no, received_date, expiry_date, quantity_received, quantity_remaining, unit_cost, landed_unit_cost, supplier_id, purchase_order_id, status, notes, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'active',?,?,?)");
        $stmt->execute([
            $input['product_id'], $input['batch_no'] ?? null, $input['lot_no'] ?? null,
            $input['received_date'] ?? date('Y-m-d'), $input['expiry_date'] ?? null,
            $input['quantity_received'], $input['quantity_received'],
            $input['unit_cost'] ?? 0, $input['landed_unit_cost'] ?? null,
            $input['supplier_id'] ?? null, $input['purchase_order_id'] ?? null,
            $input['notes'] ?? null, $now, $now, $tenantId
        ]);
        logAudit('create', 'product_batches', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId()]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $qty = $input['quantity_remaining'] ?? null;
        if ($qty !== null) {
            $d->prepare("UPDATE product_batches SET quantity_remaining = ?, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"))->execute($isSuperAdmin ? [$qty, $now, $id] : [$qty, $now, $id, $tenantId]);
        }
        $status = $input['status'] ?? null;
        if ($status) {
            $d->prepare("UPDATE product_batches SET status = ?, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"))->execute($isSuperAdmin ? [$status, $now, $id] : [$status, $now, $id, $tenantId]);
        }
        logAudit('update', 'product_batches', $id, null, ['id' => $id]);
        ok(['id' => $id]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $d->prepare("UPDATE product_batches SET status = 'inactive' WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"))->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
        logAudit('delete', 'product_batches', $id, null, null);
        ok(['id' => $id]);
    }
}

// === STOCK VALUATION FIFO (Gap 3) ===
if ($endpoint === 'stock-valuation-fifo') {
    if ($method === 'GET') {
        $productId = $_GET['product_id'] ?? null;
        if ($productId) {
            $stmt = $d->prepare("SELECT * FROM product_batches WHERE product_id = ? AND quantity_remaining > 0" . ($isSuperAdmin ? "" : " AND tenant_id = ?") . " ORDER BY received_date ASC, id ASC");
            $stmt->execute($isSuperAdmin ? [$productId] : [$productId, $tenantId]);
            $batches = $stmt->fetchAll();
            $totalValue = 0;
            $totalQty = 0;
            foreach ($batches as $b) {
                $cost = (float)($b['landed_unit_cost'] ?? $b['unit_cost']);
                $value = $cost * (float)$b['quantity_remaining'];
                $totalValue += $value;
                $totalQty += (float)$b['quantity_remaining'];
            }
            ok(['batches' => $batches, 'total_qty' => $totalQty, 'total_value' => $totalValue, 'avg_cost' => $totalQty > 0 ? $totalValue / $totalQty : 0]);
        }
        // All products FIFO valuation
        $prodParams = [];
        $prodSql = "SELECT id, code, name FROM products WHERE is_active = 1";
        $prodSql = addTenantFilter($prodSql, 'products', $tenantId, $isSuperAdmin, $prodParams);
        $prodSql .= " ORDER BY name";
        $stmt = $d->prepare($prodSql);
        $stmt->execute($prodParams);
        $products = $stmt->fetchAll();
        $results = [];
        $grandTotal = 0;
        foreach ($products as $p) {
            $stmt = $d->prepare("SELECT * FROM product_batches WHERE product_id = ? AND quantity_remaining > 0" . ($isSuperAdmin ? "" : " AND tenant_id = ?") . " ORDER BY received_date ASC, id ASC");
            $stmt->execute($isSuperAdmin ? [$p['id']] : [$p['id'], $tenantId]);
            $batches = $stmt->fetchAll();
            $totalValue = 0;
            $totalQty = 0;
            foreach ($batches as $b) {
                $cost = (float)($b['landed_unit_cost'] ?? $b['unit_cost']);
                $totalValue += $cost * (float)$b['quantity_remaining'];
                $totalQty += (float)$b['quantity_remaining'];
            }
            if ($totalQty > 0) {
                $results[] = ['product_id' => $p['id'], 'product_code' => $p['code'], 'product_name' => $p['name'], 'total_qty' => $totalQty, 'total_value' => $totalValue, 'avg_cost' => $totalValue / $totalQty];
                $grandTotal += $totalValue;
            }
        }
        ok(['products' => $results, 'grand_total' => $grandTotal]);
    }
}

// === CASH FLOW STATEMENT (Gap 4) ===
if ($endpoint === 'cash-flow') {
    if ($method === 'GET') {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        
        // Operating: cash transactions
        $stmt = $d->prepare("SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='in' AND account_type='cash' AND transaction_date BETWEEN ? AND ?");
        $stmt->execute([$startDate, $endDate]);
        $operatingIn = (float)$stmt->fetchColumn();
        $stmt = $d->prepare("SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='out' AND account_type='cash' AND transaction_date BETWEEN ? AND ?");
        $stmt->execute([$startDate, $endDate]);
        $operatingOut = (float)$stmt->fetchColumn();
        
        // Sales cash received
        $stmt = $d->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_date BETWEEN ? AND ?");
        $stmt->execute([$startDate, $endDate]);
        $salesCash = (float)$stmt->fetchColumn();
        
        // Purchase cash paid
        $stmt = $d->prepare("SELECT COALESCE(SUM(amount),0) FROM purchase_payments WHERE payment_date BETWEEN ? AND ?");
        $stmt->execute([$startDate, $endDate]);
        $purchaseCash = (float)$stmt->fetchColumn();
        
        // Investing: fixed asset purchases
        $stmt = $d->prepare("SELECT COALESCE(SUM(acquisition_cost),0) FROM fixed_assets WHERE acquisition_date BETWEEN ? AND ?");
        $stmt->execute([$startDate, $endDate]);
        $assetPurchases = (float)$stmt->fetchColumn();
        
        // Financing: loan payments (from cash_transactions with category=loan)
        $stmt = $d->prepare("SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='in' AND category LIKE '%loan%' AND transaction_date BETWEEN ? AND ?");
        $stmt->execute([$startDate, $endDate]);
        $financingIn = (float)$stmt->fetchColumn();
        $stmt = $d->prepare("SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='out' AND category LIKE '%loan%' AND transaction_date BETWEEN ? AND ?");
        $stmt->execute([$startDate, $endDate]);
        $financingOut = (float)$stmt->fetchColumn();
        
        $operatingNet = $operatingIn + $salesCash - $operatingOut - $purchaseCash;
        $investingNet = -$assetPurchases;
        $financingNet = $financingIn - $financingOut;
        $netChange = $operatingNet + $investingNet + $financingNet;
        
        // Beginning cash balance
        $stmt = $d->prepare("SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='in' AND transaction_date < ?");
        $stmt->execute([$startDate]);
        $beginningIn = (float)$stmt->fetchColumn();
        $stmt = $d->prepare("SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='out' AND transaction_date < ?");
        $stmt->execute([$startDate]);
        $beginningCash = $beginningIn - (float)$stmt->fetchColumn();
        $endingCash = $beginningCash + $netChange;
        
        ok([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'operating' => ['inflows' => $operatingIn + $salesCash, 'outflows' => $operatingOut + $purchaseCash, 'net' => $operatingNet],
            'investing' => ['outflows' => $assetPurchases, 'net' => $investingNet],
            'financing' => ['inflows' => $financingIn, 'outflows' => $financingOut, 'net' => $financingNet],
            'net_change' => $netChange,
            'beginning_cash' => $beginningCash,
            'ending_cash' => $endingCash,
        ]);
    }
}

// === PERIOD CLOSINGS (Gap 5) ===
if ($endpoint === 'period-closings') {
    if ($method === 'GET') {
        $pcParams = [];
        $pcSql = "SELECT * FROM period_closings";
        $pcSql = addTenantFilter($pcSql, 'period_closings', $tenantId, $isSuperAdmin, $pcParams);
        $pcSql .= " ORDER BY period_year DESC, period_month DESC";
        $stmt = $d->prepare($pcSql);
        $stmt->execute($pcParams);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $year = (int)($input['year'] ?? date('Y'));
        $month = (int)($input['month'] ?? date('n'));
        
        $existing = $d->prepare("SELECT * FROM period_closings WHERE period_year = ? AND period_month = ?");
        $existing->execute([$year, $month]);
        $existingRow = $existing->fetch();
        if ($existingRow) ok(['id' => $existingRow['id'], 'period' => "$year-$month", 'message' => "Period $year-$month already exists"]);
        
        $d->prepare("INSERT INTO period_closings (period_year, period_month, status, closed_by, closed_at, notes, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([$year, $month, 'closed', $_SESSION['user']['id'] ?? null, $now, $input['notes'] ?? null, $now, $now, $tenantId]);
        logAudit('create', 'period_closings', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId(), 'period' => "$year-$month"]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $status = $input['status'] ?? 'open';
        $d->prepare("UPDATE period_closings SET status = ?, notes = ?, updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"))->execute($isSuperAdmin ? [$status, $input['notes'] ?? null, $now, $id] : [$status, $input['notes'] ?? null, $now, $id, $tenantId]);
        logAudit('update', 'period_closings', $id, null, ['id' => $id]);
        ok(['id' => $id, 'status' => $status]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $d->prepare("UPDATE period_closings SET status = 'open', updated_at = ? WHERE id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"))->execute($isSuperAdmin ? [date('Y-m-d H:i:s'), $id] : [date('Y-m-d H:i:s'), $id, $tenantId]);
        logAudit('delete', 'period_closings', $id, null, null);
        ok(['id' => $id, 'status' => 'open']);
    }
}

// === CHECK PERIOD LOCKED (helper) ===
if ($endpoint === 'check-period-locked') {
    if ($method === 'GET') {
        $date = $_GET['date'] ?? date('Y-m-d');
        $year = (int)date('Y', strtotime($date));
        $month = (int)date('n', strtotime($date));
        $stmt = $d->prepare("SELECT status FROM period_closings WHERE period_year = ? AND period_month = ? AND status = 'closed'");
        $stmt->execute([$year, $month]);
        $locked = $stmt->fetch();
        ok(['locked' => (bool)$locked, 'period' => "$year-$month"]);
    }
}

// === SaaS TENANTS (Owner manages multiple stores) ===
if ($endpoint === 'tenants') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT * FROM tenants WHERE id = ?");
            $stmt->execute([$id]);
            ok($stmt->fetch());
        }
        ok($d->query("SELECT * FROM tenants ORDER BY id DESC")->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $input['subdomain'] ?? ''), 0, 6));
        $trialEnds = date('Y-m-d H:i:s', strtotime('+14 days'));
        $stmt = $d->prepare("INSERT INTO tenants (code, name, subdomain, company_name, company_address, company_phone, company_email, tax_id, status, trial_ends_at, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,'trial',?,?,?)");
        $stmt->execute([$code, $input['name'], $input['subdomain'], $input['company_name'] ?? null, $input['company_address'] ?? null, $input['company_phone'] ?? null, $input['company_email'] ?? null, $input['tax_id'] ?? null, $trialEnds, $now, $now]);
        logAudit('create', 'tenants', $d->lastInsertId(), null, ['id' => $d->lastInsertId()]);
        created(['id' => $d->lastInsertId(), 'code' => $code]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $status = $input['status'] ?? 'active';
        $subEnds = $input['subscription_ends_at'] ?? null;
        $d->prepare("UPDATE tenants SET status = ?, subscription_ends_at = ?, updated_at = ? WHERE id = ?")->execute([$status, $subEnds, $now, $id]);
        logAudit('update', 'tenants', $id, null, ['id' => $id]);
        ok(['id' => $id, 'status' => $status]);
    }
}

// === SaaS SUBSCRIPTIONS ===
if ($endpoint === 'subscriptions') {
    if ($method === 'GET') {
        $tenantId = $_GET['tenant_id'] ?? null;
        if ($tenantId) {
            $stmt = $d->prepare("SELECT s.*, sp.name as plan_name, sp.code as plan_code, t.name as tenant_name FROM subscriptions s LEFT JOIN subscription_plans sp ON s.plan_id = sp.id LEFT JOIN tenants t ON s.tenant_id = t.id WHERE s.tenant_id = ? ORDER BY s.id DESC");
            $stmt->execute([$tenantId]);
            ok($stmt->fetchAll());
        }
        ok($d->query("SELECT s.*, sp.name as plan_name, sp.code as plan_code, t.name as tenant_name FROM subscriptions s LEFT JOIN subscription_plans sp ON s.plan_id = sp.id LEFT JOIN tenants t ON s.tenant_id = t.id ORDER BY s.id DESC")->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $tenantId = $input['tenant_id'] ?? null;
        $planId = $input['plan_id'] ?? null;
        $billingCycle = $input['billing_cycle'] ?? 'monthly';
        if (!$tenantId || !$planId) fail('tenant_id and plan_id required');

        $plan = $d->prepare("SELECT * FROM subscription_plans WHERE id = ?");
        $plan->execute([$planId]);
        $planData = $plan->fetch();
        if (!$planData) fail('Plan not found');

        $amount = $billingCycle === 'yearly' ? $planData['price_yearly'] : $planData['price_monthly'];
        $startDate = $input['start_date'] ?? date('Y-m-d');
        $endDate = $billingCycle === 'yearly' ? date('Y-m-d', strtotime('+1 year', strtotime($startDate))) : date('Y-m-d', strtotime('+1 month', strtotime($startDate)));

        $stmt = $d->prepare("INSERT INTO subscriptions (tenant_id, plan_id, billing_cycle, start_date, end_date, status, amount, payment_method, created_at, updated_at) VALUES (?,?,?,?,?,'active',?,?,?,?)");
        $stmt->execute([$tenantId, $planId, $billingCycle, $startDate, $endDate, $amount, $input['payment_method'] ?? 'bank_transfer', $now, $now]);
        $subId = $d->lastInsertId();

        $d->prepare("UPDATE tenants SET status = 'active', subscription_ends_at = ? WHERE id = ?")->execute([$endDate, $tenantId]);

        $invNo = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $dueDate = date('Y-m-d', strtotime('+7 days', strtotime($startDate)));
        $d->prepare("INSERT INTO subscription_invoices (invoice_no, tenant_id, subscription_id, invoice_date, due_date, amount, status, created_at, updated_at) VALUES (?,?,?,?,?,?,'unpaid',?,?)")
            ->execute([$invNo, $tenantId, $subId, $startDate, $dueDate, $amount, $now, $now]);

        logAudit('create', 'subscriptions', $subId, null, ['id' => $subId]);
        created(['id' => $subId, 'invoice_no' => $invNo, 'amount' => $amount, 'end_date' => $endDate]);
    }
}

// === SaaS SUBSCRIPTION INVOICES ===
if ($endpoint === 'subscription-invoices') {
    if ($method === 'GET') {
        $tenantId = $_GET['tenant_id'] ?? null;
        if ($tenantId) {
            $stmt = $d->prepare("SELECT si.*, t.name as tenant_name FROM subscription_invoices si LEFT JOIN tenants t ON si.tenant_id = t.id WHERE si.tenant_id = ? ORDER BY si.id DESC");
            $stmt->execute([$tenantId]);
            ok($stmt->fetchAll());
        }
        ok($d->query("SELECT si.*, t.name as tenant_name FROM subscription_invoices si LEFT JOIN tenants t ON si.tenant_id = t.id ORDER BY si.id DESC")->fetchAll());
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $action = $input['action'] ?? '';
        if ($action === 'pay') {
            $d->prepare("UPDATE subscription_invoices SET status = 'paid', paid_at = ?, payment_method = ?, updated_at = ? WHERE id = ?")
                ->execute([$now, $input['payment_method'] ?? 'bank_transfer', $now, $id]);
            ok(['id' => $id, 'status' => 'paid']);
        }
        $d->prepare("UPDATE subscription_invoices SET status = ?, updated_at = ? WHERE id = ?")->execute([$input['status'] ?? 'unpaid', $now, $id]);
        logAudit('update', 'subscription_invoices', $id, null, ['id' => $id]);
        ok(['id' => $id, 'status' => $input['status'] ?? 'unpaid']);
    }
}

// === SaaS REVENUE SUMMARY (Owner dashboard) ===
if ($endpoint === 'saas-revenue') {
    if ($method === 'GET') {
        $totalTenants = $d->query("SELECT COUNT(*) as cnt FROM tenants")->fetch()['cnt'];
        $activeTenants = $d->query("SELECT COUNT(*) as cnt FROM tenants WHERE status = 'active'")->fetch()['cnt'];
        $trialTenants = $d->query("SELECT COUNT(*) as cnt FROM tenants WHERE status = 'trial'")->fetch()['cnt'];
        $suspendedTenants = $d->query("SELECT COUNT(*) as cnt FROM tenants WHERE status = 'suspended'")->fetch()['cnt'];
        $totalRevenue = $d->query("SELECT COALESCE(SUM(amount),0) as total FROM subscription_invoices WHERE status = 'paid'")->fetch()['total'];
        $pendingRevenue = $d->query("SELECT COALESCE(SUM(amount),0) as total FROM subscription_invoices WHERE status = 'unpaid'")->fetch()['total'];
        $monthlyRevenue = $d->query("SELECT COALESCE(SUM(amount),0) as total FROM subscription_invoices WHERE status = 'paid' AND strftime('%Y-%m', invoice_date) = strftime('%Y-%m', 'now')")->fetch()['total'];
        ok([
            'total_tenants' => (int)$totalTenants,
            'active_tenants' => (int)$activeTenants,
            'trial_tenants' => (int)$trialTenants,
            'suspended_tenants' => (int)$suspendedTenants,
            'total_revenue' => (float)$totalRevenue,
            'pending_revenue' => (float)$pendingRevenue,
            'monthly_revenue' => (float)$monthlyRevenue,
        ]);
    }
}

fail('Endpoint not found: ' . $endpoint, 404);
