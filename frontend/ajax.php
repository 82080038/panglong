<?php

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
    exit;
}

$endpoint = $_GET['endpoint'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$d = db();

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

// === PRODUCTS ===
if ($endpoint === 'products') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        $search = $_GET['search'] ?? '';
        $per_page = (int)($_GET['per_page'] ?? 50);
        $page = (int)($_GET['page'] ?? 1);
        $offset = ($page - 1) * $per_page;

        if ($id) {
            $stmt = $d->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
            $stmt->execute([$id]);
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
            $sql .= " WHERE p.name LIKE ? OR p.code LIKE ? OR p.brand LIKE ?";
            $q = "%$search%";
            $params = [$q, $q, $q];
        }
        $sql .= " ORDER BY p.id DESC LIMIT $per_page OFFSET $offset";
        $stmt = $d->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        $countSql = "SELECT COUNT(*) FROM products p";
        if ($search) {
            $countSql .= " WHERE p.name LIKE ? OR p.code LIKE ? OR p.brand LIKE ?";
            $total = $d->prepare($countSql);
            $total->execute($params);
        } else {
            $total = $d->query($countSql);
        }
        $total = $total->fetchColumn();

        foreach ($products as &$p) {
            $p['category'] = ['id' => $p['category_id'], 'name' => $p['category_name'] ?? 'N/A'];
        }

        ok($products, ['total' => (int)$total, 'per_page' => $per_page, 'current_page' => $page, 'last_page' => (int)ceil($total / $per_page)]);
    }

    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO products (code, name, alias, category_id, brand, min_stock, max_stock, location, buy_price, sell_price, is_active, created_at, updated_at, weight_kg, length_cm, width_cm, height_cm) VALUES (?,?,?,?,?,?,?,?,'',?,?,1,?,?,0,0,0)");
        $stmt->execute([
            $input['code'] ?? '', $input['name'] ?? '', $input['alias'] ?? null,
            $input['category_id'] ?? null, $input['brand'] ?? null,
            $input['min_stock'] ?? 0, $input['max_stock'] ?? 0,
            $input['buy_price'] ?? 0, $input['sell_price'] ?? 0, $now, $now
        ]);
        $pid = $d->lastInsertId();

        if (!empty($input['units'])) {
            foreach ($input['units'] as $i => $u) {
                $stmt = $d->prepare("INSERT INTO product_units (product_id, unit_name, conversion_factor, is_base_unit, price_per_unit, created_at, updated_at) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([
                    $pid, $u['unit_name'] ?? '', $u['conversion_factor'] ?? 1,
                    $i === 0 ? 1 : 0, $u['price_per_unit'] ?? 0, $now, $now
                ]);
            }
        }
        created(['id' => $pid, 'code' => $input['code'] ?? '']);
    }

    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("UPDATE products SET name=?, category_id=?, brand=?, min_stock=?, max_stock=?, buy_price=?, sell_price=?, is_active=?, updated_at=? WHERE id=?");
        $stmt->execute([
            $input['name'] ?? '', $input['category_id'] ?? null, $input['brand'] ?? null,
            $input['min_stock'] ?? 0, $input['max_stock'] ?? 0,
            $input['buy_price'] ?? 0, $input['sell_price'] ?? 0,
            isset($input['is_active']) ? 1 : 0, $now, $id
        ]);
        ok(['id' => $id]);
    }

    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $d->prepare("DELETE FROM product_units WHERE product_id = ?")->execute([$id]);
        $d->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        ok(['id' => $id]);
    }
}

// === CATEGORIES ===
if ($endpoint === 'categories') {
    if ($method === 'GET') {
        $cats = $d->query("SELECT * FROM categories ORDER BY name")->fetchAll();
        ok($cats);
    }
}

// === CUSTOMERS ===
if ($endpoint === 'customers') {
    if ($method === 'GET') {
        $search = $_GET['search'] ?? '';
        $sql = "SELECT c.*, g.name as group_name FROM customers c LEFT JOIN customer_groups g ON c.group_id = g.id";
        $params = [];
        if ($search) {
            $sql .= " WHERE c.name LIKE ? OR c.phone LIKE ?";
            $q = "%$search%";
            $params = [$q, $q];
        }
        $sql .= " ORDER BY c.id DESC LIMIT 100";
        $stmt = $d->prepare($sql);
        $stmt->execute($params);
        $customers = $stmt->fetchAll();
        foreach ($customers as &$c) {
            $c['group'] = ['id' => $c['group_id'], 'name' => $c['group_name'] ?? 'N/A'];
        }
        ok($customers);
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO customers (name, address, phone, email, group_id, credit_limit, payment_terms, is_active, created_at, updated_at) VALUES (?,?,?,?,?,?,?,1,?,?)");
        $stmt->execute([
            $input['name'] ?? '', $input['address'] ?? null, $input['phone'] ?? null,
            $input['email'] ?? null, $input['group_id'] ?? null,
            $input['credit_limit'] ?? 0, $input['payment_terms'] ?? 30, $now, $now
        ]);
        created(['id' => $d->lastInsertId()]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("UPDATE customers SET name=?, address=?, phone=?, email=?, group_id=?, credit_limit=?, payment_terms=?, is_active=?, updated_at=? WHERE id=?");
        $stmt->execute([
            $input['name'] ?? '', $input['address'] ?? null, $input['phone'] ?? null,
            $input['email'] ?? null, $input['group_id'] ?? null,
            $input['credit_limit'] ?? 0, $input['payment_terms'] ?? 30,
            isset($input['is_active']) ? 1 : 0, $now, $id
        ]);
        ok(['id' => $id]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $d->prepare("DELETE FROM customers WHERE id = ?")->execute([$id]);
        ok(['id' => $id]);
    }
}

// === CUSTOMER GROUPS ===
if ($endpoint === 'customer-groups') {
    if ($method === 'GET') {
        ok($d->query("SELECT * FROM customer_groups ORDER BY name")->fetchAll());
    }
}

// === SUPPLIERS ===
if ($endpoint === 'suppliers') {
    if ($method === 'GET') {
        $search = $_GET['search'] ?? '';
        $sql = "SELECT * FROM suppliers";
        $params = [];
        if ($search) {
            $sql .= " WHERE name LIKE ? OR phone LIKE ?";
            $q = "%$search%";
            $params = [$q, $q];
        }
        $sql .= " ORDER BY id DESC LIMIT 100";
        $stmt = $d->prepare($sql);
        $stmt->execute($params);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO suppliers (name, address, phone, email, payment_terms, credit_limit, is_active, created_at, updated_at) VALUES (?,?,?,?,?,?,1,?,?)");
        $stmt->execute([
            $input['name'] ?? '', $input['address'] ?? null, $input['phone'] ?? null,
            $input['email'] ?? null, $input['payment_terms'] ?? 30,
            $input['credit_limit'] ?? 0, $now, $now
        ]);
        created(['id' => $d->lastInsertId()]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $d->prepare("DELETE FROM suppliers WHERE id = ?")->execute([$id]);
        ok(['id' => $id]);
    }
}

// === SALES ===
if ($endpoint === 'sales') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT s.*, c.name as customer_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.id = ?");
            $stmt->execute([$id]);
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
            $sql .= " WHERE s.invoice_no LIKE ? OR c.name LIKE ?";
            $q = "%$search%";
            $params = [$q, $q];
        }
        $sql .= " ORDER BY s.id DESC LIMIT $per_page OFFSET $offset";
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
        $now = date('Y-m-d H:i:s');
        $invoiceNo = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $subtotal = 0;
        foreach ($input['items'] ?? [] as $item) {
            $subtotal += ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
        }
        $globalDiscount = $input['discount'] ?? 0;
        $taxRate = 0.11;
        $taxable = $subtotal - $globalDiscount;
        $tax = $taxable * $taxRate;
        $total = $taxable + $tax;

        $stmt = $d->prepare("INSERT INTO sales (invoice_no, customer_id, sale_date, subtotal, discount, tax, total, payment_method, payment_status, status, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,'completed',?,?,?,?)");
        $stmt->execute([
            $invoiceNo, $input['customer_id'] ?? null, $input['sale_date'] ?? date('Y-m-d'),
            $subtotal, $globalDiscount, $tax, $total,
            $input['payment_method'] ?? 'cash', 'unpaid',
            $input['notes'] ?? null, $now, $now
        ]);
        $saleId = $d->lastInsertId();

        foreach ($input['items'] ?? [] as $item) {
            if (empty($item['product_id'])) continue;
            $lineSubtotal = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
            $stmt = $d->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, discount, subtotal, created_at) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([
                $saleId, $item['product_id'], $item['quantity'],
                $item['unit_price'], $item['discount'] ?? 0, $lineSubtotal, $now
            ]);

            $stmt = $d->prepare("INSERT INTO stock_movements (product_id, quantity, movement_type, notes, created_at) VALUES (?,?,?,?,?)");
            $stmt->execute([
                $item['product_id'], -abs((float)$item['quantity']),
                'sale', 'Sale ' . $invoiceNo, $now
            ]);
        }
        created(['id' => $saleId, 'invoice_no' => $invoiceNo]);
    }

    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');

        $items = $d->prepare("SELECT product_id, quantity FROM sale_items WHERE sale_id = ?");
        $items->execute([$id]);
        foreach ($items->fetchAll() as $item) {
            $stmt = $d->prepare("INSERT INTO stock_movements (product_id, quantity, movement_type, notes, created_at) VALUES (?,?,?,?,?)");
            $stmt->execute([
                $item['product_id'], abs((float)$item['quantity']),
                'adjustment', 'Void sale #' . $id, $now
            ]);
        }

        $d->prepare("UPDATE sales SET status='voided', updated_at=? WHERE id=?")->execute([$now, $id]);
        ok(['id' => $id]);
    }
}

// === SALE PAYMENT ===
if ($endpoint === 'sale-payment') {
    if ($method === 'POST') {
        $id = $_GET['id'] ?? $input['sale_id'] ?? null;
        if (!$id) fail('Sale ID required');
        $now = date('Y-m-d H:i:s');

        $stmt = $d->prepare("SELECT total, COALESCE((SELECT SUM(amount) FROM sale_payments WHERE sale_id=s.id),0) as paid FROM sales s WHERE s.id = ?");
        $stmt->execute([$id]);
        $sale = $stmt->fetch();
        if (!$sale) fail('Sale not found', 404);

        $amount = (float)($input['amount'] ?? 0);
        $stmt = $d->prepare("INSERT INTO sale_payments (sale_id, amount, payment_method, payment_date, created_at) VALUES (?,?,?,?,?)");
        $stmt->execute([$id, $amount, $input['payment_method'] ?? 'cash', $input['payment_date'] ?? date('Y-m-d'), $now]);

        $newPaid = (float)$sale['paid'] + $amount;
        $status = $newPaid >= (float)$sale['total'] ? 'paid' : 'partial';
        $d->prepare("UPDATE sales SET payment_status=?, updated_at=? WHERE id=?")->execute([$status, $now, $id]);

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

        $sql = "SELECT p.id, p.name, p.code, p.min_stock, p.max_stock,
            COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) as current_stock,
            pu.unit_name as base_unit
            FROM products p LEFT JOIN product_units pu ON pu.product_id = p.id AND pu.is_base_unit = 1
            WHERE p.is_active = 1 ORDER BY p.id DESC LIMIT 200";
        $items = $d->query($sql)->fetchAll();
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

        $stmt = $d->prepare("INSERT INTO stock_movements (product_id, quantity, movement_type, notes, created_at) VALUES (?,?,?,?,?)");
        $stmt->execute([$productId, $quantity, $adjType, $reason, $now]);
        created(['product_id' => $productId, 'quantity' => $quantity]);
    }
}

// === BARCODE LOOKUP ===
if ($endpoint === 'barcode-lookup') {
    if ($method === 'GET') {
        $barcode = $_GET['barcode'] ?? '';
        $stmt = $d->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.code = ? LIMIT 1");
        $stmt->execute([$barcode]);
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
        $stmt = $d->prepare("SELECT sell_price FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $row = $stmt->fetch();
        ok(['unit_price' => $row['sell_price'] ?? 0]);
    }
}

// === DELIVERIES ===
if ($endpoint === 'deliveries') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT * FROM deliveries WHERE id = ?");
            $stmt->execute([$id]);
            $delivery = $stmt->fetch();
            if (!$delivery) fail('Delivery not found', 404);
            ok($delivery);
        }
        $search = $_GET['search'] ?? '';
        $sql = "SELECT * FROM deliveries";
        $params = [];
        if ($search) {
            $sql .= " WHERE delivery_no LIKE ? OR customer_name LIKE ?";
            $q = "%$search%";
            $params = [$q, $q];
        }
        $sql .= " ORDER BY id DESC LIMIT 100";
        $stmt = $d->prepare($sql);
        $stmt->execute($params);
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $deliveryNo = 'SJ-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $stmt = $d->prepare("INSERT INTO deliveries (delivery_no, sale_id, customer_name, delivery_address, phone, delivery_date, delivery_time, driver_name, vehicle_plate, notes, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,'pending',?,?)");
        $stmt->execute([
            $deliveryNo, $input['sale_id'] ?? null, $input['customer_name'] ?? '',
            $input['delivery_address'] ?? null, $input['phone'] ?? null,
            $input['delivery_date'] ?? date('Y-m-d'), $input['delivery_time'] ?? null,
            $input['driver_name'] ?? null, $input['vehicle_plate'] ?? null,
            $input['notes'] ?? null, $now, $now
        ]);
        created(['id' => $d->lastInsertId(), 'delivery_no' => $deliveryNo]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $status = $input['status'] ?? 'pending';
        $deliveredAt = $status === 'delivered' ? $now : null;
        if ($deliveredAt) {
            $stmt = $d->prepare("UPDATE deliveries SET status=?, delivered_at=?, updated_at=? WHERE id=?");
            $stmt->execute([$status, $deliveredAt, $now, $id]);
        } else {
            $stmt = $d->prepare("UPDATE deliveries SET status=?, updated_at=? WHERE id=?");
            $stmt->execute([$status, $now, $id]);
        }
        ok(['id' => $id, 'status' => $status]);
    }
}

// === PURCHASE ORDERS ===
if ($endpoint === 'purchase-orders') {
    $action = $_GET['action'] ?? '';

    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT po.*, s.name as supplier_name FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.id WHERE po.id = ?");
            $stmt->execute([$id]);
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
        $stmt = $d->query("SELECT po.*, s.name as supplier_name FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.id ORDER BY po.id DESC LIMIT 100");
        $pos = $stmt->fetchAll();
        foreach ($pos as &$po) {
            $po['supplier'] = ['name' => $po['supplier_name'] ?? ''];
        }
        ok($pos);
    }

    if ($method === 'POST' && $action === 'receive') {
        $id = $_GET['id'] ?? null;
        if (!$id) fail('PO ID required');
        $now = date('Y-m-d H:i:s');

        foreach ($input['items'] ?? [] as $recv) {
            $itemId = $recv['purchase_item_id'] ?? null;
            $qty = (float)($recv['received_quantity'] ?? 0);
            if (!$itemId || $qty <= 0) continue;

            $stmt = $d->prepare("SELECT pi.*, p.id as pid FROM purchase_items pi JOIN products p ON pi.product_id = p.id WHERE pi.id = ?");
            $stmt->execute([$itemId]);
            $item = $stmt->fetch();
            if (!$item) continue;

            $newReceived = (float)$item['received_quantity'] + $qty;
            $d->prepare("UPDATE purchase_items SET received_quantity = ? WHERE id = ?")->execute([$newReceived, $itemId]);

            $d->prepare("INSERT INTO stock_movements (product_id, quantity, movement_type, notes, created_at) VALUES (?,?,?,?,?)")->execute([
                $item['pid'], $qty, 'purchase', 'PO receive #' . $id, $now
            ]);
        }

        $stmt = $d->prepare("SELECT SUM(quantity) as total_qty, SUM(received_quantity) as total_recv FROM purchase_items WHERE po_id = ?");
        $stmt->execute([$id]);
        $totals = $stmt->fetch();
        $status = 'partially_received';
        if ((float)$totals['total_recv'] >= (float)$totals['total_qty']) $status = 'received';
        $d->prepare("UPDATE purchase_orders SET status = ?, updated_at = ? WHERE id = ?")->execute([$status, $now, $id]);

        ok(['id' => $id, 'status' => $status]);
    }

    if ($method === 'POST' && $action === 'payment') {
        $id = $_GET['id'] ?? null;
        if (!$id) fail('PO ID required');
        $now = date('Y-m-d H:i:s');

        $amount = (float)($input['amount'] ?? 0);
        $stmt = $d->prepare("INSERT INTO purchase_payments (po_id, amount, payment_method, payment_date, created_at) VALUES (?,?,?,?,?)");
        $stmt->execute([$id, $amount, $input['payment_method'] ?? 'cash', $input['payment_date'] ?? date('Y-m-d'), $now]);

        $stmt = $d->prepare("SELECT total, COALESCE((SELECT SUM(amount) FROM purchase_payments WHERE po_id=po.id),0) as paid FROM purchase_orders po WHERE po.id = ?");
        $stmt->execute([$id]);
        $po = $stmt->fetch();
        $newPaid = (float)$po['paid'] + $amount;
        $payStatus = $newPaid >= (float)$po['total'] ? 'paid' : 'partial';
        $d->prepare("UPDATE purchase_orders SET payment_status = ?, updated_at = ? WHERE id = ?")->execute([$payStatus, $now, $id]);

        ok(['po_id' => $id, 'paid' => $newPaid, 'status' => $payStatus]);
    }

    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $poNumber = 'PO-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $subtotal = 0;
        foreach ($input['items'] ?? [] as $item) {
            $subtotal += ($item['quantity'] * $item['unit_price']);
        }
        $globalDiscount = $input['discount'] ?? 0;
        $taxRate = 0.11;
        $taxable = $subtotal - $globalDiscount;
        $tax = $taxable * $taxRate;
        $total = $taxable + $tax;

        $stmt = $d->prepare("INSERT INTO purchase_orders (po_number, supplier_id, po_date, subtotal, discount, tax, total, payment_status, status, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,'pending',?,?,?)");
        $stmt->execute([
            $poNumber, $input['supplier_id'] ?? null, $input['po_date'] ?? date('Y-m-d'),
            $subtotal, $globalDiscount, $tax, $total,
            'unpaid', $input['notes'] ?? null, $now, $now
        ]);
        $poId = $d->lastInsertId();

        foreach ($input['items'] ?? [] as $item) {
            if (empty($item['product_id'])) continue;
            $lineSubtotal = ($item['quantity'] * $item['unit_price']);
            $stmt = $d->prepare("INSERT INTO purchase_items (po_id, product_id, quantity, unit_price, subtotal, received_quantity, created_at) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([
                $poId, $item['product_id'], $item['quantity'],
                $item['unit_price'], $lineSubtotal, 0, $now
            ]);
        }
        created(['id' => $poId, 'po_number' => $poNumber]);
    }
}

// === MARKETPLACE ===
if ($endpoint === 'marketplace') {
    if ($method === 'GET') {
        ok($d->query("SELECT * FROM marketplace_integrations ORDER BY id DESC")->fetchAll());
    }
    if ($method === 'POST') {
        $action = $_GET['action'] ?? '';
        $id = $_GET['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');

        if ($action === 'sync-stock' || $action === 'sync-products') {
            $d->prepare("UPDATE marketplace_integrations SET last_synced_at = ?, updated_at = ? WHERE id = ?")->execute([$now, $now, $id]);
            ok(['id' => $id, 'message' => ucfirst(str_replace('-', ' ', $action)) . ' completed']);
        }
        ok(['message' => 'Unknown action']);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) fail('ID required');
        $d->prepare("UPDATE marketplace_integrations SET status = 'disconnected' WHERE id = ?")->execute([$id]);
        ok(['id' => $id, 'message' => 'Disconnected']);
    }
}

// === REPORTS ===
if ($endpoint === 'reports') {
    $type = $_GET['type'] ?? 'daily';
    $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');

    if ($type === 'daily') {
        $stmt = $d->query("SELECT COUNT(*) as total_sales, COALESCE(SUM(total),0) as total_revenue, COALESCE(SUM(CASE WHEN payment_method='cash' THEN total ELSE 0 END),0) as total_cash, COALESCE(SUM(CASE WHEN payment_method='credit' THEN total ELSE 0 END),0) as total_credit FROM sales WHERE sale_date = date('now') AND status != 'voided'");
        $data = $stmt->fetch();
        $data['date'] = date('Y-m-d');
        ok($data);
    }

    if ($type === 'monthly') {
        $stmt = $d->query("SELECT COUNT(*) as total_sales, COALESCE(SUM(total),0) as total_revenue, COALESCE(SUM(CASE WHEN payment_method='cash' THEN total ELSE 0 END),0) as total_cash, COALESCE(SUM(CASE WHEN payment_method='credit' THEN total ELSE 0 END),0) as total_credit FROM sales WHERE sale_date >= date('now','start of month') AND status != 'voided'");
        $data = $stmt->fetch();
        $data['year'] = date('Y');
        $data['month'] = date('m');
        ok($data);
    }

    if ($type === 'low-stock') {
        $stmt = $d->query("SELECT p.code as product_code, p.name as product_name, COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) as current_stock, p.min_stock, (p.min_stock - COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0)) as shortage FROM products p WHERE p.is_active=1 AND CAST(p.min_stock AS REAL) > 0 AND COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) <= CAST(p.min_stock AS REAL)");
        ok($stmt->fetchAll());
    }

    if ($type === 'stock-valuation') {
        $stmt = $d->query("SELECT p.code as product_code, p.name as product_name, COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) as current_stock, p.buy_price as avg_cost, (COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) * p.buy_price) as stock_value, p.sell_price, (COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) * p.sell_price) as potential_revenue FROM products p WHERE p.is_active=1");
        $items = $stmt->fetchAll();
        $totalValue = array_sum(array_map(fn($i) => (float)$i['stock_value'], $items));
        ok(['total_stock_value' => $totalValue, 'total_products' => count($items), 'items' => $items]);
    }

    if ($type === 'by-product') {
        $stmt = $d->prepare("SELECT p.name as product_name, SUM(si.quantity) as quantity_sold, SUM(si.subtotal) as revenue, SUM((si.subtotal - (si.quantity * p.buy_price))) as profit FROM sale_items si JOIN sales s ON si.sale_id = s.id JOIN products p ON si.product_id = p.id WHERE s.sale_date BETWEEN ? AND ? AND s.status != 'voided' GROUP BY p.id ORDER BY revenue DESC");
        $stmt->execute([$dateFrom, $dateTo]);
        ok($stmt->fetchAll());
    }

    if ($type === 'by-customer') {
        $stmt = $d->prepare("SELECT c.name as customer_name, COUNT(s.id) as total_sales, SUM(s.total) as total_revenue, COALESCE((SELECT SUM(sp.amount) FROM sale_payments sp WHERE sp.sale_id IN (SELECT id FROM sales WHERE customer_id=c.id)),0) as total_paid, SUM(s.total) - COALESCE((SELECT SUM(sp.amount) FROM sale_payments sp WHERE sp.sale_id IN (SELECT id FROM sales WHERE customer_id=c.id)),0) as total_unpaid FROM sales s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.sale_date BETWEEN ? AND ? AND s.status != 'voided' GROUP BY c.id ORDER BY total_revenue DESC");
        $stmt->execute([$dateFrom, $dateTo]);
        ok($stmt->fetchAll());
    }

    if ($type === 'profit-loss') {
        $stmt = $d->prepare("SELECT COALESCE(SUM(s.total),0) as revenue, COALESCE(SUM(si.quantity * p.buy_price),0) as cogs, COALESCE(SUM(s.total),0) - COALESCE(SUM(si.quantity * p.buy_price),0) as gross_profit, COALESCE(SUM(s.tax),0) as tax, COUNT(s.id) as total_sales FROM sales s LEFT JOIN sale_items si ON si.sale_id = s.id LEFT JOIN products p ON si.product_id = p.id WHERE s.sale_date BETWEEN ? AND ? AND s.status != 'voided'");
        $stmt->execute([$dateFrom, $dateTo]);
        $data = $stmt->fetch();
        $data['net_profit'] = (float)$data['gross_profit'];
        $data['date_from'] = $dateFrom;
        $data['date_to'] = $dateTo;
        ok($data);
    }

    if ($type === 'stock-movement') {
        $stmt = $d->prepare("SELECT sm.created_at as date, p.name as product_name, sm.quantity, sm.movement_type, sm.reason as notes FROM stock_movements sm JOIN products p ON sm.product_id = p.id WHERE DATE(sm.created_at) BETWEEN ? AND ? ORDER BY sm.created_at DESC LIMIT 200");
        $stmt->execute([$dateFrom, $dateTo]);
        ok($stmt->fetchAll());
    }

    if ($type === 'dead-stock') {
        $stmt = $d->query("SELECT p.code as product_code, p.name as product_name, COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) as current_stock, (COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE product_id=p.id),0) * p.buy_price) as stock_value, CAST((julianday('now') - julianday(p.updated_at)) AS INTEGER) as days_inactive FROM products p WHERE p.is_active=1 AND p.id NOT IN (SELECT DISTINCT product_id FROM sale_items WHERE sale_id IN (SELECT id FROM sales WHERE sale_date >= date('now','-90 days'))) ORDER BY days_inactive DESC");
        ok($stmt->fetchAll());
    }

    if ($type === 'ar-aging') {
        $stmt = $d->query("SELECT c.name as customer_name, s.total - COALESCE((SELECT SUM(sp.amount) FROM sale_payments sp WHERE sp.sale_id=s.id),0) as outstanding, CAST(julianday('now') - julianday(s.sale_date) AS INTEGER) as days_overdue FROM sales s JOIN customers c ON s.customer_id = c.id WHERE s.payment_status != 'paid' AND s.status != 'voided' ORDER BY days_overdue DESC");
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
        ok($d->query("SELECT * FROM branches ORDER BY id")->fetchAll());
    }
}

// === SETTINGS ===
if ($endpoint === 'settings') {
    if ($method === 'GET') {
        $rows = $d->query("SELECT key, value FROM app_settings")->fetchAll();
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
        ok($d->query("SELECT * FROM warehouses ORDER BY id")->fetchAll());
    }
}

// === USERS ===
if ($endpoint === 'users') {
    if ($method === 'GET') {
        $users = $d->query("SELECT u.id, u.username, u.full_name, u.email, u.phone, u.is_active, r.name as role_name, r.slug as role_slug FROM users u LEFT JOIN roles r ON u.role_id = r.id ORDER BY u.id")->fetchAll();
        ok($users);
    }
}

// === SALES RETURNS (Sprint 7) ===
if ($endpoint === 'sales-returns') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT sr.*, c.name as customer_name FROM sales_returns sr LEFT JOIN customers c ON sr.customer_id = c.id WHERE sr.id = ?");
            $stmt->execute([$id]);
            $ret = $stmt->fetch();
            if (!$ret) fail('Return not found', 404);
            $items = $d->prepare("SELECT sri.*, p.name as product_name, p.code as product_code FROM sales_return_items sri LEFT JOIN products p ON sri.product_id = p.id WHERE sri.sales_return_id = ?");
            $items->execute([$id]);
            $ret['items'] = $items->fetchAll();
            ok($ret);
        }
        $stmt = $d->query("SELECT sr.*, c.name as customer_name, s.invoice_no FROM sales_returns sr LEFT JOIN customers c ON sr.customer_id = c.id LEFT JOIN sales s ON sr.sale_id = s.id ORDER BY sr.id DESC LIMIT 100");
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $returnNo = 'SR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $saleId = $input['sale_id'] ?? null;
        if (!$saleId) fail('Sale ID required');

        $stmt = $d->prepare("SELECT * FROM sales WHERE id = ?");
        $stmt->execute([$saleId]);
        $sale = $stmt->fetch();
        if (!$sale) fail('Sale not found', 404);

        $totalRefund = 0;
        foreach ($input['items'] ?? [] as $item) {
            $totalRefund += ($item['quantity'] * $item['unit_price']);
        }

        $stmt = $d->prepare("INSERT INTO sales_returns (return_no, sale_id, customer_id, return_date, total_refund, refund_method, status, reason, notes, created_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,'pending',?,?,?,?)");
        $stmt->execute([
            $returnNo, $saleId, $sale['customer_id'], $input['return_date'] ?? date('Y-m-d'),
            $totalRefund, $input['refund_method'] ?? 'cash',
            $input['reason'] ?? null, $input['notes'] ?? null,
            $_SESSION['user']['id'] ?? null, $now, $now
        ]);
        $returnId = $d->lastInsertId();

        foreach ($input['items'] ?? [] as $item) {
            if (empty($item['product_id'])) continue;
            $refundAmt = ($item['quantity'] * $item['unit_price']);
            $stmt = $d->prepare("INSERT INTO sales_return_items (sales_return_id, sale_item_id, product_id, quantity, unit_price, refund_amount, reason, created_at) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$returnId, $item['sale_item_id'] ?? null, $item['product_id'], $item['quantity'], $item['unit_price'], $refundAmt, $item['reason'] ?? null, $now]);

            $d->prepare("INSERT INTO stock_movements (product_id, quantity, movement_type, notes, created_at) VALUES (?,?,?,?,?)")->execute([
                $item['product_id'], abs((float)$item['quantity']), 'sale_return', 'Return ' . $returnNo, $now
            ]);
        }
        created(['id' => $returnId, 'return_no' => $returnNo]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $status = $input['status'] ?? 'approved';
        $d->prepare("UPDATE sales_returns SET status = ?, approved_by = ?, updated_at = ? WHERE id = ?")->execute([$status, $_SESSION['user']['id'] ?? null, $now, $id]);
        ok(['id' => $id, 'status' => $status]);
    }
}

// === PURCHASE RETURNS (Sprint 7) ===
if ($endpoint === 'purchase-returns') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT pr.*, s.name as supplier_name FROM purchase_returns pr LEFT JOIN suppliers s ON pr.supplier_id = s.id WHERE pr.id = ?");
            $stmt->execute([$id]);
            $ret = $stmt->fetch();
            if (!$ret) fail('Return not found', 404);
            $items = $d->prepare("SELECT pri.*, p.name as product_name, p.code as product_code FROM purchase_return_items pri LEFT JOIN products p ON pri.product_id = p.id WHERE pri.purchase_return_id = ?");
            $items->execute([$id]);
            $ret['items'] = $items->fetchAll();
            ok($ret);
        }
        $stmt = $d->query("SELECT pr.*, s.name as supplier_name, po.po_number FROM purchase_returns pr LEFT JOIN suppliers s ON pr.supplier_id = s.id LEFT JOIN purchase_orders po ON pr.po_id = po.id ORDER BY pr.id DESC LIMIT 100");
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $returnNo = 'PR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $poId = $input['po_id'] ?? null;
        if (!$poId) fail('PO ID required');

        $stmt = $d->prepare("SELECT * FROM purchase_orders WHERE id = ?");
        $stmt->execute([$poId]);
        $po = $stmt->fetch();
        if (!$po) fail('PO not found', 404);

        $totalRefund = 0;
        foreach ($input['items'] ?? [] as $item) {
            $totalRefund += ($item['quantity'] * $item['unit_price']);
        }

        $stmt = $d->prepare("INSERT INTO purchase_returns (return_no, po_id, supplier_id, return_date, total_refund, refund_method, status, reason, notes, created_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,'pending',?,?,?,?)");
        $stmt->execute([
            $returnNo, $poId, $po['supplier_id'], $input['return_date'] ?? date('Y-m-d'),
            $totalRefund, $input['refund_method'] ?? 'credit',
            $input['reason'] ?? null, $input['notes'] ?? null,
            $_SESSION['user']['id'] ?? null, $now, $now
        ]);
        $returnId = $d->lastInsertId();

        foreach ($input['items'] ?? [] as $item) {
            if (empty($item['product_id'])) continue;
            $refundAmt = ($item['quantity'] * $item['unit_price']);
            $stmt = $d->prepare("INSERT INTO purchase_return_items (purchase_return_id, purchase_item_id, product_id, quantity, unit_price, refund_amount, reason, created_at) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$returnId, $item['purchase_item_id'] ?? null, $item['product_id'], $item['quantity'], $item['unit_price'], $refundAmt, $item['reason'] ?? null, $now]);

            $d->prepare("INSERT INTO stock_movements (product_id, quantity, movement_type, notes, created_at) VALUES (?,?,?,?,?)")->execute([
                $item['product_id'], -abs((float)$item['quantity']), 'purchase_return', 'PR ' . $returnNo, $now
            ]);
        }
        created(['id' => $returnId, 'return_no' => $returnNo]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $status = $input['status'] ?? 'approved';
        $d->prepare("UPDATE purchase_returns SET status = ?, approved_by = ?, updated_at = ? WHERE id = ?")->execute([$status, $_SESSION['user']['id'] ?? null, $now, $id]);
        ok(['id' => $id, 'status' => $status]);
    }
}

// === QUOTATIONS (Sprint 7) ===
if ($endpoint === 'quotations') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT q.*, c.name as customer_name FROM quotations q LEFT JOIN customers c ON q.customer_id = c.id WHERE q.id = ?");
            $stmt->execute([$id]);
            $quote = $stmt->fetch();
            if (!$quote) fail('Quotation not found', 404);
            $items = $d->prepare("SELECT qi.*, p.name as product_name, p.code as product_code FROM quotation_items qi LEFT JOIN products p ON qi.product_id = p.id WHERE qi.quotation_id = ?");
            $items->execute([$id]);
            $quote['items'] = $items->fetchAll();
            ok($quote);
        }
        $stmt = $d->query("SELECT q.*, c.name as customer_name FROM quotations q LEFT JOIN customers c ON q.customer_id = c.id ORDER BY q.id DESC LIMIT 100");
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $quoteNo = 'QT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $subtotal = 0;
        foreach ($input['items'] ?? [] as $item) {
            $subtotal += ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
        }
        $globalDiscount = $input['discount'] ?? 0;
        $taxRate = 0.11;
        $taxable = $subtotal - $globalDiscount;
        $tax = $taxable * $taxRate;
        $total = $taxable + $tax;

        $stmt = $d->prepare("INSERT INTO quotations (quote_no, customer_id, customer_name, quote_date, valid_until, subtotal, discount, tax, total, status, notes, delivery_address, created_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,'draft',?,?,?,?)");
        $stmt->execute([
            $quoteNo, $input['customer_id'] ?? null, $input['customer_name'] ?? '',
            $input['quote_date'] ?? date('Y-m-d'), $input['valid_until'] ?? date('Y-m-d', strtotime('+30 days')),
            $subtotal, $globalDiscount, $tax, $total,
            $input['notes'] ?? null, $input['delivery_address'] ?? null,
            $_SESSION['user']['id'] ?? null, $now, $now
        ]);
        $quoteId = $d->lastInsertId();

        foreach ($input['items'] ?? [] as $item) {
            if (empty($item['product_id'])) continue;
            $lineSubtotal = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
            $stmt = $d->prepare("INSERT INTO quotation_items (quotation_id, product_id, quantity, bonus_qty, unit_price, discount, subtotal, notes, created_at) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$quoteId, $item['product_id'], $item['quantity'], $item['bonus_qty'] ?? 0, $item['unit_price'], $item['discount'] ?? 0, $lineSubtotal, $item['notes'] ?? null, $now]);
        }
        created(['id' => $quoteId, 'quote_no' => $quoteNo]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $status = $input['status'] ?? 'sent';
        $d->prepare("UPDATE quotations SET status = ?, updated_at = ? WHERE id = ?")->execute([$status, $now, $id]);
        ok(['id' => $id, 'status' => $status]);
    }
}

// === SALES ORDERS (Sprint 7) ===
if ($endpoint === 'sales-orders') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT so.*, c.name as customer_name FROM sales_orders so LEFT JOIN customers c ON so.customer_id = c.id WHERE so.id = ?");
            $stmt->execute([$id]);
            $so = $stmt->fetch();
            if (!$so) fail('SO not found', 404);
            $items = $d->prepare("SELECT soi.*, p.name as product_name, p.code as product_code FROM sales_order_items soi LEFT JOIN products p ON soi.product_id = p.id WHERE soi.sales_order_id = ?");
            $items->execute([$id]);
            $so['items'] = $items->fetchAll();
            ok($so);
        }
        $stmt = $d->query("SELECT so.*, c.name as customer_name FROM sales_orders so LEFT JOIN customers c ON so.customer_id = c.id ORDER BY so.id DESC LIMIT 100");
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
        $taxRate = 0.11;
        $taxable = $subtotal - $globalDiscount;
        $tax = $taxable * $taxRate;
        $total = $taxable + $tax;

        $stmt = $d->prepare("INSERT INTO sales_orders (so_number, customer_id, customer_name, order_date, expected_delivery_date, subtotal, discount, tax, total, payment_method, status, notes, delivery_address, quotation_id, created_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,'open',?,?,?,?)");
        $stmt->execute([
            $soNumber, $input['customer_id'] ?? null, $input['customer_name'] ?? '',
            $input['order_date'] ?? date('Y-m-d'), $input['expected_delivery_date'] ?? null,
            $subtotal, $globalDiscount, $tax, $total,
            $input['payment_method'] ?? 'cash',
            $input['notes'] ?? null, $input['delivery_address'] ?? null,
            $input['quotation_id'] ?? null,
            $_SESSION['user']['id'] ?? null, $now, $now
        ]);
        $soId = $d->lastInsertId();

        foreach ($input['items'] ?? [] as $item) {
            if (empty($item['product_id'])) continue;
            $lineSubtotal = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
            $stmt = $d->prepare("INSERT INTO sales_order_items (sales_order_id, product_id, quantity, bonus_qty, delivered_qty, unit_price, discount, subtotal, notes, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$soId, $item['product_id'], $item['quantity'], $item['bonus_qty'] ?? 0, 0, $item['unit_price'], $item['discount'] ?? 0, $lineSubtotal, $item['notes'] ?? null, $now]);
        }
        created(['id' => $soId, 'so_number' => $soNumber]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $status = $input['status'] ?? 'fulfilled';
        $d->prepare("UPDATE sales_orders SET status = ?, updated_at = ? WHERE id = ?")->execute([$status, $now, $id]);
        ok(['id' => $id, 'status' => $status]);
    }
}

// === CUSTOMER-SPECIFIC PRICING (Sprint 9) ===
if ($endpoint === 'customer-prices') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT cpp.*, c.name as customer_name, p.name as product_name, p.code as product_code FROM customer_product_prices cpp LEFT JOIN customers c ON cpp.customer_id = c.id LEFT JOIN products p ON cpp.product_id = p.id WHERE cpp.id = ?");
            $stmt->execute([$id]);
            ok($stmt->fetch());
        }
        $customerId = $_GET['customer_id'] ?? null;
        if ($customerId) {
            $stmt = $d->prepare("SELECT cpp.*, p.name as product_name, p.code as product_code FROM customer_product_prices cpp LEFT JOIN products p ON cpp.product_id = p.id WHERE cpp.customer_id = ? AND cpp.is_active = 1");
            $stmt->execute([$customerId]);
            ok($stmt->fetchAll());
        }
        $stmt = $d->query("SELECT cpp.*, c.name as customer_name, p.name as product_name, p.code as product_code FROM customer_product_prices cpp LEFT JOIN customers c ON cpp.customer_id = c.id LEFT JOIN products p ON cpp.product_id = p.id ORDER BY cpp.id DESC LIMIT 100");
        ok($stmt->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO customer_product_prices (customer_id, product_id, custom_price, min_qty, is_active, notes, created_at, updated_at) VALUES (?,?,?,?,1,?,?,?)");
        $stmt->execute([$input['customer_id'], $input['product_id'], $input['custom_price'], $input['min_qty'] ?? 1, $input['notes'] ?? null, $now, $now]);
        created(['id' => $d->lastInsertId()]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("UPDATE customer_product_prices SET custom_price = ?, min_qty = ?, is_active = ?, notes = ?, updated_at = ? WHERE id = ?");
        $stmt->execute([$input['custom_price'], $input['min_qty'] ?? 1, $input['is_active'] ?? 1, $input['notes'] ?? null, $now, $id]);
        ok(['id' => $id]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $d->prepare("UPDATE customer_product_prices SET is_active = 0 WHERE id = ?")->execute([$id]);
        ok(['id' => $id]);
    }
}

// === TIER PRICING (Sprint 9) ===
if ($endpoint === 'tier-prices') {
    if ($method === 'GET') {
        $productId = $_GET['product_id'] ?? null;
        if ($productId) {
            $stmt = $d->prepare("SELECT * FROM product_tier_prices WHERE product_id = ? AND is_active = 1 ORDER BY min_qty");
            $stmt->execute([$productId]);
            ok($stmt->fetchAll());
        }
        ok($d->query("SELECT pt.*, p.name as product_name, p.code as product_code FROM product_tier_prices pt LEFT JOIN products p ON pt.product_id = p.id ORDER BY pt.id DESC LIMIT 100")->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO product_tier_prices (product_id, min_qty, max_qty, unit_price, is_active, created_at, updated_at) VALUES (?,?,?,?,1,?,?)");
        $stmt->execute([$input['product_id'], $input['min_qty'], $input['max_qty'] ?? null, $input['unit_price'], $now, $now]);
        created(['id' => $d->lastInsertId()]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $d->prepare("UPDATE product_tier_prices SET is_active = 0 WHERE id = ?")->execute([$id]);
        ok(['id' => $id]);
    }
}

// === SUPPLIER PRICE HISTORY (Sprint 9) ===
if ($endpoint === 'supplier-price-history') {
    if ($method === 'GET') {
        $productId = $_GET['product_id'] ?? null;
        if ($productId) {
            $stmt = $d->prepare("SELECT sph.*, s.name as supplier_name FROM supplier_price_history sph LEFT JOIN suppliers s ON sph.supplier_id = s.id WHERE sph.product_id = ? ORDER BY sph.effective_date DESC");
            $stmt->execute([$productId]);
            ok($stmt->fetchAll());
        }
        ok($d->query("SELECT sph.*, s.name as supplier_name, p.name as product_name, p.code as product_code FROM supplier_price_history sph LEFT JOIN suppliers s ON sph.supplier_id = s.id LEFT JOIN products p ON sph.product_id = p.id ORDER BY sph.id DESC LIMIT 100")->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO supplier_price_history (supplier_id, product_id, unit_price, effective_date, po_reference, notes, created_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$input['supplier_id'], $input['product_id'], $input['unit_price'], $input['effective_date'] ?? date('Y-m-d'), $input['po_reference'] ?? null, $input['notes'] ?? null, $_SESSION['user']['id'] ?? null, $now, $now]);
        created(['id' => $d->lastInsertId()]);
    }
}

// === STOCK ADJUSTMENTS (Sprint 10) ===
if ($endpoint === 'stock-adjustments') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT sa.*, p.name as product_name, p.code as product_code FROM stock_adjustments sa LEFT JOIN products p ON sa.product_id = p.id WHERE sa.id = ?");
            $stmt->execute([$id]);
            ok($stmt->fetch());
        }
        ok($d->query("SELECT sa.*, p.name as product_name, p.code as product_code FROM stock_adjustments sa LEFT JOIN products p ON sa.product_id = p.id ORDER BY sa.id DESC LIMIT 100")->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO stock_adjustments (product_id, quantity, adjustment_type, reason, status, created_by, created_at, tenant_id) VALUES (?,?,?,?,'pending',?,?)");
        $stmt->execute([$input['product_id'], $input['quantity'], $input['adjustment_type'] ?? 'correction', $input['reason'] ?? null, $_SESSION['user']['id'] ?? null, $now, null]);
        created(['id' => $d->lastInsertId()]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $status = $input['status'] ?? 'approved';
        if ($status === 'approved') {
            $stmt = $d->prepare("SELECT * FROM stock_adjustments WHERE id = ?");
            $stmt->execute([$id]);
            $adj = $stmt->fetch();
            if ($adj) {
                $d->prepare("INSERT INTO stock_movements (product_id, quantity, movement_type, notes, created_at) VALUES (?,?,?,?,?)")->execute([
                    $adj['product_id'], $adj['quantity'], 'adjustment', 'Approved adj #' . $id, $now
                ]);
            }
        }
        $d->prepare("UPDATE stock_adjustments SET status = ?, approved_by = ?, approved_at = ? WHERE id = ?")->execute([$status, $_SESSION['user']['id'] ?? null, $now, $id]);
        ok(['id' => $id, 'status' => $status]);
    }
}

// === STOCK TRANSFERS (Sprint 10) ===
if ($endpoint === 'stock-transfers') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT st.*, wf.name as from_warehouse, wt.name as to_warehouse FROM stock_transfers st LEFT JOIN warehouses wf ON st.from_warehouse_id = wf.id LEFT JOIN warehouses wt ON st.to_warehouse_id = wt.id WHERE st.id = ?");
            $stmt->execute([$id]);
            $tr = $stmt->fetch();
            if (!$tr) fail('Transfer not found', 404);
            $items = $d->prepare("SELECT sti.*, p.name as product_name, p.code as product_code FROM stock_transfer_items sti LEFT JOIN products p ON sti.product_id = p.id WHERE sti.transfer_id = ?");
            $items->execute([$id]);
            $tr['items'] = $items->fetchAll();
            ok($tr);
        }
        ok($d->query("SELECT st.*, wf.name as from_warehouse, wt.name as to_warehouse FROM stock_transfers st LEFT JOIN warehouses wf ON st.from_warehouse_id = wf.id LEFT JOIN warehouses wt ON st.to_warehouse_id = wt.id ORDER BY st.id DESC LIMIT 100")->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $transferNo = 'TR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $stmt = $d->prepare("INSERT INTO stock_transfers (transfer_no, transfer_date, from_warehouse_id, to_warehouse_id, status, notes, created_by, created_at, updated_at) VALUES (?,?,?,?,'pending',?,?,?,?)");
        $stmt->execute([$transferNo, $input['transfer_date'] ?? date('Y-m-d'), $input['from_warehouse_id'], $input['to_warehouse_id'], $input['notes'] ?? null, $_SESSION['user']['id'] ?? null, $now, $now]);
        $trId = $d->lastInsertId();
        foreach ($input['items'] ?? [] as $item) {
            if (empty($item['product_id'])) continue;
            $d->prepare("INSERT INTO stock_transfer_items (transfer_id, product_id, quantity, created_at, updated_at) VALUES (?,?,?,?,?)")->execute([$trId, $item['product_id'], $item['quantity'], $now, $now]);
        }
        created(['id' => $trId, 'transfer_no' => $transferNo]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $status = $input['status'] ?? 'completed';
        if ($status === 'completed') {
            $items = $d->prepare("SELECT * FROM stock_transfer_items WHERE transfer_id = ?");
            $items->execute([$id]);
            foreach ($items->fetchAll() as $item) {
                $d->prepare("INSERT INTO stock_movements (product_id, quantity, movement_type, notes, created_at) VALUES (?,?,?,?,?)")->execute([
                    $item['product_id'], -abs((float)$item['quantity']), 'transfer_out', 'Transfer out #' . $id, $now
                ]);
                $d->prepare("INSERT INTO stock_movements (product_id, quantity, movement_type, notes, created_at) VALUES (?,?,?,?,?)")->execute([
                    $item['product_id'], abs((float)$item['quantity']), 'transfer_in', 'Transfer in #' . $id, $now
                ]);
            }
        }
        $d->prepare("UPDATE stock_transfers SET status = ?, updated_at = ? WHERE id = ?")->execute([$status, $now, $id]);
        ok(['id' => $id, 'status' => $status]);
    }
}

// === WAREHOUSE LOCATIONS (Sprint 10) ===
if ($endpoint === 'warehouse-locations') {
    if ($method === 'GET') {
        $whId = $_GET['warehouse_id'] ?? null;
        if ($whId) {
            $stmt = $d->prepare("SELECT * FROM warehouse_locations WHERE warehouse_id = ? AND is_active = 1 ORDER BY code");
            $stmt->execute([$whId]);
            ok($stmt->fetchAll());
        }
        ok($d->query("SELECT wl.*, w.name as warehouse_name FROM warehouse_locations wl LEFT JOIN warehouses w ON wl.warehouse_id = w.id WHERE wl.is_active = 1 ORDER BY wl.id DESC")->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO warehouse_locations (warehouse_id, code, name, zone_type, aisle, level, max_weight_kg, capacity_m2, is_active, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,1,?,?)");
        $stmt->execute([$input['warehouse_id'], $input['code'], $input['name'], $input['zone_type'] ?? 'storage', $input['aisle'] ?? null, $input['level'] ?? null, $input['max_weight_kg'] ?? null, $input['capacity_m2'] ?? null, $now, $now]);
        created(['id' => $d->lastInsertId()]);
    }
}

// === CASH TRANSACTIONS (Sprint 11) ===
if ($endpoint === 'cash-transactions') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT * FROM cash_transactions WHERE id = ?");
            $stmt->execute([$id]);
            ok($stmt->fetch());
        }
        $type = $_GET['type'] ?? '';
        if ($type) {
            $stmt = $d->prepare("SELECT * FROM cash_transactions WHERE type = ? ORDER BY id DESC LIMIT 100");
            $stmt->execute([$type]);
            ok($stmt->fetchAll());
        }
        ok($d->query("SELECT * FROM cash_transactions ORDER BY id DESC LIMIT 100")->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $txNo = 'CT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $stmt = $d->prepare("INSERT INTO cash_transactions (transaction_no, type, account_type, transaction_date, amount, description, category, reference_no, recipient, created_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$txNo, $input['type'] ?? 'in', $input['account_type'] ?? 'cash', $input['transaction_date'] ?? date('Y-m-d'), $input['amount'], $input['description'] ?? null, $input['category'] ?? null, $input['reference_no'] ?? null, $input['recipient'] ?? null, $_SESSION['user']['id'] ?? null, $now, $now]);
        created(['id' => $d->lastInsertId(), 'transaction_no' => $txNo]);
    }
}

// === BANK STATEMENTS (Sprint 11) ===
if ($endpoint === 'bank-statements') {
    if ($method === 'GET') {
        $status = $_GET['reconciliation_status'] ?? '';
        if ($status) {
            $stmt = $d->prepare("SELECT * FROM bank_statements WHERE reconciliation_status = ? ORDER BY transaction_date DESC LIMIT 100");
            $stmt->execute([$status]);
            ok($stmt->fetchAll());
        }
        ok($d->query("SELECT * FROM bank_statements ORDER BY transaction_date DESC LIMIT 100")->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO bank_statements (bank_account, transaction_date, description, debit, credit, balance, reference_no, reconciliation_status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,'unreconciled',?,?)");
        $stmt->execute([$input['bank_account'], $input['transaction_date'] ?? date('Y-m-d'), $input['description'] ?? null, $input['debit'] ?? 0, $input['credit'] ?? 0, $input['balance'] ?? 0, $input['reference_no'] ?? null, $now, $now]);
        created(['id' => $d->lastInsertId()]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $d->prepare("UPDATE bank_statements SET reconciliation_status = 'reconciled', reconciled_at = ?, reconciled_by = ? WHERE id = ?")->execute([$now, $_SESSION['user']['id'] ?? null, $id]);
        ok(['id' => $id, 'status' => 'reconciled']);
    }
}

// === FIXED ASSETS (Sprint 11) ===
if ($endpoint === 'fixed-assets') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT * FROM fixed_assets WHERE id = ?");
            $stmt->execute([$id]);
            $asset = $stmt->fetch();
            if (!$asset) fail('Asset not found', 404);
            $deps = $d->prepare("SELECT * FROM asset_depreciations WHERE fixed_asset_id = ? ORDER BY depreciation_date DESC");
            $deps->execute([$id]);
            $asset['depreciations'] = $deps->fetchAll();
            ok($asset);
        }
        ok($d->query("SELECT * FROM fixed_assets ORDER BY id DESC LIMIT 100")->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $assetCode = 'FA-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $cost = (float)($input['acquisition_cost'] ?? 0);
        $salvage = (float)($input['salvage_value'] ?? 0);
        $life = (int)($input['useful_life_months'] ?? 60);
        $monthlyDep = $life > 0 ? ($cost - $salvage) / $life : 0;
        $stmt = $d->prepare("INSERT INTO fixed_assets (asset_code, name, category, serial_no, plate_no, acquisition_date, acquisition_cost, salvage_value, useful_life_months, depreciation_method, monthly_depreciation, accumulated_depreciation, book_value, status, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,'straight_line',?,?,?,?,'active',?,?,?)");
        $stmt->execute([$assetCode, $input['name'], $input['category'] ?? 'equipment', $input['serial_no'] ?? null, $input['plate_no'] ?? null, $input['acquisition_date'] ?? date('Y-m-d'), $cost, $salvage, $life, $monthlyDep, 0, $cost, $input['notes'] ?? null, $now, $now]);
        created(['id' => $d->lastInsertId(), 'asset_code' => $assetCode]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $action = $input['action'] ?? '';

        if ($action === 'depreciate') {
            $stmt = $d->prepare("SELECT * FROM fixed_assets WHERE id = ?");
            $stmt->execute([$id]);
            $asset = $stmt->fetch();
            if (!$asset) fail('Asset not found', 404);

            $depAmount = (float)$asset['monthly_depreciation'];
            $newAccum = (float)$asset['accumulated_depreciation'] + $depAmount;
            $newBookValue = (float)$asset['book_value'] - $depAmount;

            $d->prepare("INSERT INTO asset_depreciations (fixed_asset_id, depreciation_date, amount, accumulated_after, book_value_after, notes, created_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)")->execute([$id, date('Y-m-d'), $depAmount, $newAccum, $newBookValue, 'Monthly depreciation', $_SESSION['user']['id'] ?? null, $now, $now]);
            $d->prepare("UPDATE fixed_assets SET accumulated_depreciation = ?, book_value = ?, updated_at = ? WHERE id = ?")->execute([$newAccum, $newBookValue, $now, $id]);
            ok(['id' => $id, 'depreciation' => $depAmount, 'book_value' => $newBookValue]);
        }

        $status = $input['status'] ?? 'active';
        $d->prepare("UPDATE fixed_assets SET status = ?, updated_at = ? WHERE id = ?")->execute([$status, $now, $id]);
        ok(['id' => $id, 'status' => $status]);
    }
}

// === VEHICLES (Sprint 12) ===
if ($endpoint === 'vehicles') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT * FROM vehicles WHERE id = ?");
            $stmt->execute([$id]);
            ok($stmt->fetch());
        }
        ok($d->query("SELECT * FROM vehicles ORDER BY id DESC LIMIT 100")->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO vehicles (plate_no, vehicle_type, brand, model, capacity_kg, fuel_type, acquisition_date, status, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$input['plate_no'], $input['vehicle_type'] ?? 'truck', $input['brand'] ?? null, $input['model'] ?? null, $input['capacity_kg'] ?? null, $input['fuel_type'] ?? 'diesel', $input['acquisition_date'] ?? date('Y-m-d'), 'active', $input['notes'] ?? null, $now, $now]);
        created(['id' => $d->lastInsertId()]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $d->prepare("UPDATE vehicles SET plate_no=?, vehicle_type=?, brand=?, model=?, capacity_kg=?, fuel_type=?, status=?, notes=?, updated_at=? WHERE id=?")->execute([$input['plate_no'], $input['vehicle_type'] ?? 'truck', $input['brand'] ?? null, $input['model'] ?? null, $input['capacity_kg'] ?? null, $input['fuel_type'] ?? 'diesel', $input['status'] ?? 'active', $input['notes'] ?? null, $now, $id]);
        ok(['id' => $id]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $d->prepare("UPDATE vehicles SET status='inactive' WHERE id=?")->execute([$id]);
        ok(['id' => $id]);
    }
}

// === VEHICLE MAINTENANCE (Sprint 12) ===
if ($endpoint === 'vehicle-maintenance') {
    if ($method === 'GET') {
        $vehicleId = $_GET['vehicle_id'] ?? null;
        if ($vehicleId) {
            $stmt = $d->prepare("SELECT vm.*, v.plate_no FROM vehicle_maintenance vm LEFT JOIN vehicles v ON vm.vehicle_id = v.id WHERE vm.vehicle_id = ? ORDER BY vm.maintenance_date DESC");
            $stmt->execute([$vehicleId]);
            ok($stmt->fetchAll());
        }
        ok($d->query("SELECT vm.*, v.plate_no FROM vehicle_maintenance vm LEFT JOIN vehicles v ON vm.vehicle_id = v.id ORDER BY vm.id DESC LIMIT 100")->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO vehicle_maintenance (vehicle_id, maintenance_date, maintenance_type, cost, odometer_km, description, next_maintenance_date, created_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$input['vehicle_id'], $input['maintenance_date'] ?? date('Y-m-d'), $input['maintenance_type'] ?? 'service', $input['cost'] ?? 0, $input['odometer_km'] ?? null, $input['description'] ?? null, $input['next_maintenance_date'] ?? null, $_SESSION['user']['id'] ?? null, $now, $now]);
        created(['id' => $d->lastInsertId()]);
    }
}

// === DELIVERY ROUTES (Sprint 12) ===
if ($endpoint === 'delivery-routes') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT dr.*, v.plate_no FROM delivery_routes dr LEFT JOIN vehicles v ON dr.vehicle_id = v.id WHERE dr.id = ?");
            $stmt->execute([$id]);
            $route = $stmt->fetch();
            if (!$route) fail('Route not found', 404);
            $stops = $d->prepare("SELECT rs.*, dl.delivery_no FROM route_stops rs LEFT JOIN deliveries dl ON rs.delivery_id = dl.id WHERE rs.route_id = ? ORDER BY rs.stop_order");
            $stops->execute([$id]);
            $route['stops'] = $stops->fetchAll();
            ok($route);
        }
        ok($d->query("SELECT dr.*, v.plate_no FROM delivery_routes dr LEFT JOIN vehicles v ON dr.vehicle_id = v.id ORDER BY dr.id DESC LIMIT 100")->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $routeNo = 'RT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $stmt = $d->prepare("INSERT INTO delivery_routes (route_no, route_date, vehicle_id, driver_name, status, total_distance_km, estimated_time_minutes, notes, created_by, created_at, updated_at) VALUES (?,?,?,?,?,'planned',?,?,?,?,?)");
        $stmt->execute([$routeNo, $input['route_date'] ?? date('Y-m-d'), $input['vehicle_id'] ?? null, $input['driver_name'] ?? null, $input['total_distance_km'] ?? null, $input['estimated_time_minutes'] ?? null, $input['notes'] ?? null, $_SESSION['user']['id'] ?? null, $now, $now]);
        $routeId = $d->lastInsertId();
        foreach ($input['stops'] ?? [] as $i => $stop) {
            $d->prepare("INSERT INTO route_stops (route_id, delivery_id, stop_order, customer_name, address, phone, status, created_at) VALUES (?,?,?,?,?,'pending',?)")->execute([$routeId, $stop['delivery_id'] ?? null, $i + 1, $stop['customer_name'] ?? null, $stop['address'] ?? null, $stop['phone'] ?? null, $now]);
        }
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
            $d->prepare("UPDATE delivery_routes SET status = ?, updated_at = ? WHERE id = ?")->execute([$status, $now, $id]);
            ok(['id' => $id, 'status' => $status]);
        }
    }
}

// === WHATSAPP TEMPLATES (Sprint 12) ===
if ($endpoint === 'whatsapp-templates') {
    if ($method === 'GET') {
        ok($d->query("SELECT * FROM whatsapp_templates WHERE is_active = 1 ORDER BY id")->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO whatsapp_templates (template_name, template_type, message_body, variables, is_active, created_at, updated_at) VALUES (?,?,?,?,1,?,?)");
        $stmt->execute([$input['template_name'], $input['template_type'] ?? 'notification', $input['message_body'], $input['variables'] ?? null, $now, $now]);
        created(['id' => $d->lastInsertId()]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $d->prepare("UPDATE whatsapp_templates SET message_body = ?, variables = ?, updated_at = ? WHERE id = ?")->execute([$input['message_body'], $input['variables'] ?? null, $now, $id]);
        ok(['id' => $id]);
    }
}

// === WHATSAPP MESSAGES (Sprint 12) ===
if ($endpoint === 'whatsapp-messages') {
    if ($method === 'GET') {
        ok($d->query("SELECT * FROM whatsapp_messages ORDER BY id DESC LIMIT 100")->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $phone = $input['phone_number'] ?? '';
        $msg = $input['message_body'] ?? '';
        $templateName = $input['template_name'] ?? null;
        if (!$phone || !$msg) fail('Phone and message required');

        // Log the message (in production, this would call WhatsApp API)
        $stmt = $d->prepare("INSERT INTO whatsapp_messages (phone_number, message_body, template_name, reference_type, reference_id, status, sent_at, created_by, created_at) VALUES (?,?,?,?,?,?,'sent',?,?,?)");
        $stmt->execute([$phone, $msg, $templateName, $input['reference_type'] ?? null, $input['reference_id'] ?? null, $_SESSION['user']['id'] ?? null, $now, $now]);
        created(['id' => $d->lastInsertId(), 'status' => 'sent']);
    }
}

// === E-FAKTUR (Sprint 12) ===
if ($endpoint === 'e-faktur') {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $d->prepare("SELECT * FROM e_faktur WHERE id = ?");
            $stmt->execute([$id]);
            ok($stmt->fetch());
        }
        $type = $_GET['type'] ?? '';
        if ($type) {
            $stmt = $d->prepare("SELECT * FROM e_faktur WHERE faktur_type = ? ORDER BY transaction_date DESC LIMIT 100");
            $stmt->execute([$type]);
            ok($stmt->fetchAll());
        }
        $export = $_GET['export'] ?? '';
        if ($export === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="efaktur_' . date('Ymd') . '.csv"');
            $rows = $d->query("SELECT * FROM e_faktur ORDER BY transaction_date DESC")->fetchAll();
            echo "FK;Jenis;FG Pengganti;Masa;Tahun;No Seri Faktur;Tanggal Faktur;NPWP;Nama;Alamat;DPP;PPN;Tarif PPN;PPnBM;Keterangan\n";
            foreach ($rows as $r) {
                echo "FK;" . ($r['faktur_type'] === 'keluaran' ? '0' : '1') . ";0;" . date('n', strtotime($r['transaction_date'])) . ";" . date('Y', strtotime($r['transaction_date'])) . ";" . $r['faktur_no'] . ";" . $r['transaction_date'] . ";" . $r['counterparty_npwp'] . ";" . str_replace(';', ',', $r['counterparty_name']) . ";;" . $r['dpp'] . ";" . $r['ppn'] . ";11;0;" . str_replace(';', ',', $r['description'] ?? '') . "\n";
            }
            exit;
        }
        ok($d->query("SELECT * FROM e_faktur ORDER BY transaction_date DESC LIMIT 100")->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $fakturNo = date('Y') . sprintf('%03d', date('n')) . '-' . str_pad(rand(1, 999999999), 9, '0', STR_PAD_LEFT);
        $dpp = (float)($input['dpp'] ?? 0);
        $ppn = $dpp * 0.11;
        $stmt = $d->prepare("INSERT INTO e_faktur (faktur_no, faktur_type, transaction_date, counterparty_name, counterparty_npwp, dpp, ppn, description, reference_type, reference_id, export_status, created_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,'pending',?,?,?)");
        $stmt->execute([$fakturNo, $input['faktur_type'] ?? 'keluaran', $input['transaction_date'] ?? date('Y-m-d'), $input['counterparty_name'] ?? '', $input['counterparty_npwp'] ?? '', $dpp, $ppn, $input['description'] ?? null, $input['reference_type'] ?? null, $input['reference_id'] ?? null, $_SESSION['user']['id'] ?? null, $now, $now]);
        created(['id' => $d->lastInsertId(), 'faktur_no' => $fakturNo]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $d->prepare("UPDATE e_faktur SET export_status = 'exported', updated_at = ? WHERE id = ?")->execute([$now, $id]);
        ok(['id' => $id, 'status' => 'exported']);
    }
}

// === LANDED COST DISTRIBUTION (Gap 1) ===
if ($endpoint === 'landed-cost') {
    if ($method === 'GET') {
        $poId = $_GET['po_id'] ?? null;
        if ($poId) {
            $stmt = $d->prepare("SELECT lcd.*, p.name as product_name, p.code as product_code FROM landed_cost_distributions lcd JOIN products p ON lcd.product_id = p.id WHERE lcd.purchase_order_id = ?");
            $stmt->execute([$poId]);
            ok($stmt->fetchAll());
        }
        ok($d->query("SELECT lcd.*, p.name as product_name, p.code as product_code, po.po_number FROM landed_cost_distributions lcd JOIN products p ON lcd.product_id = p.id JOIN purchase_orders po ON lcd.purchase_order_id = po.id ORDER BY lcd.id DESC LIMIT 100")->fetchAll());
    }
    if ($method === 'POST') {
        $poId = $input['po_id'] ?? null;
        if (!$poId) fail('PO ID required');
        
        $po = $d->prepare("SELECT * FROM purchase_orders WHERE id = ?");
        $po->execute([$poId]);
        $poData = $po->fetch();
        if (!$poData) fail('PO not found');
        
        $freight = (float)($poData['freight_cost'] ?? 0);
        $insurance = (float)($poData['insurance_cost'] ?? 0);
        $handling = (float)($poData['handling_cost'] ?? 0);
        $totalLanded = $freight + $insurance + $handling;
        
        if ($totalLanded <= 0) fail('No landed cost to distribute (freight/insurance/handling all 0)');
        
        // Get PO items with their subtotal
        $items = $d->prepare("SELECT pi.*, p.name as product_name FROM purchase_items pi JOIN products p ON pi.product_id = p.id WHERE pi.po_id = ?");
        $items->execute([$poId]);
        $items = $items->fetchAll();
        
        if (empty($items)) fail('No PO items found');
        
        // Calculate total subtotal for proportional distribution
        $totalSubtotal = array_sum(array_map(fn($i) => (float)$i['subtotal'], $items));
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
            
            $d->prepare("INSERT INTO landed_cost_distributions (purchase_order_id, product_id, freight_allocated, insurance_allocated, handling_allocated, total_landed_cost, quantity, landed_unit_cost, distribution_method, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)")
                ->execute([$poId, $item['product_id'], $freightAlloc, $insuranceAlloc, $handlingAlloc, $totalItemLanded, $qty, $landedUnitCost, 'by_value', $now]);
            
            // Update product landed_cost
            $d->prepare("UPDATE products SET landed_cost = ? WHERE id = ?")->execute([$fullLandedCost, $item['product_id']]);
            
            // Update product_batches if exists
            $d->prepare("UPDATE product_batches SET landed_unit_cost = ? WHERE purchase_order_id = ? AND product_id = ?")
                ->execute([$fullLandedCost, $poId, $item['product_id']]);
        }
        
        // Update PO landed_total
        $d->prepare("UPDATE purchase_orders SET landed_total = subtotal + ? WHERE id = ?")->execute([$totalLanded, $poId]);
        
        $result = $d->prepare("SELECT lcd.*, p.name as product_name FROM landed_cost_distributions lcd JOIN products p ON lcd.product_id = p.id WHERE lcd.purchase_order_id = ?");
        $result->execute([$poId]);
        created(['distributions' => $result->fetchAll(), 'total_landed_cost' => $totalLanded]);
    }
}

// === PARTIAL DELIVERIES (Gap 2) ===
if ($endpoint === 'partial-deliveries') {
    if ($method === 'GET') {
        $saleId = $_GET['sale_id'] ?? null;
        if ($saleId) {
            $stmt = $d->prepare("SELECT pd.*, p.name as product_name, p.code as product_code FROM partial_deliveries pd JOIN products p ON pd.product_id = p.id WHERE pd.sale_id = ? ORDER BY pd.delivery_date DESC");
            $stmt->execute([$saleId]);
            ok($stmt->fetchAll());
        }
        ok($d->query("SELECT pd.*, p.name as product_name, p.code as product_code, s.invoice_no FROM partial_deliveries pd JOIN products p ON pd.product_id = p.id JOIN sales s ON pd.sale_id = s.id ORDER BY pd.id DESC LIMIT 100")->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $saleId = $input['sale_id'] ?? null;
        if (!$saleId) fail('Sale ID required');
        
        $deliveryDate = $input['delivery_date'] ?? date('Y-m-d');
        $deliveryId = $input['delivery_id'] ?? null;
        $notes = $input['notes'] ?? null;
        
        foreach ($input['items'] ?? [] as $item) {
            $saleItemId = $item['sale_item_id'] ?? null;
            $deliveredQty = (float)($item['delivered_qty'] ?? 0);
            if (!$saleItemId || $deliveredQty <= 0) continue;
            
            $si = $d->prepare("SELECT * FROM sale_items WHERE id = ?");
            $si->execute([$saleItemId]);
            $siData = $si->fetch();
            if (!$siData) continue;
            
            $orderedQty = (float)$siData['quantity'];
            $alreadyDelivered = (float)($siData['remaining_qty'] !== null ? $orderedQty - (float)$siData['remaining_qty'] : 0);
            $remaining = $orderedQty - $alreadyDelivered - $deliveredQty;
            
            if ($remaining < 0) $remaining = 0;
            
            $status = $remaining == 0 ? 'completed' : 'partial';
            
            $d->prepare("INSERT INTO partial_deliveries (sale_id, delivery_id, sale_item_id, product_id, ordered_qty, delivered_qty, remaining_qty, delivery_date, status, notes, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$saleId, $deliveryId, $saleItemId, $siData['product_id'], $orderedQty, $deliveredQty, $remaining, $deliveryDate, $status, $notes, $now]);
            
            $d->prepare("UPDATE sale_items SET remaining_qty = ? WHERE id = ?")->execute([$remaining, $saleItemId]);
        }
        
        created(['status' => 'ok']);
    }
}

// === PRODUCT BATCHES (Gap 3) ===
if ($endpoint === 'product-batches') {
    if ($method === 'GET') {
        $productId = $_GET['product_id'] ?? null;
        if ($productId) {
            $stmt = $d->prepare("SELECT pb.*, p.name as product_name, p.code as product_code, s.name as supplier_name FROM product_batches pb JOIN products p ON pb.product_id = p.id LEFT JOIN suppliers s ON pb.supplier_id = s.id WHERE pb.product_id = ? AND pb.quantity_remaining > 0 ORDER BY pb.received_date ASC");
            $stmt->execute([$productId]);
            ok($stmt->fetchAll());
        }
        ok($d->query("SELECT pb.*, p.name as product_name, p.code as product_code, s.name as supplier_name FROM product_batches pb JOIN products p ON pb.product_id = p.id LEFT JOIN suppliers s ON pb.supplier_id = s.id ORDER BY pb.id DESC LIMIT 100")->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $stmt = $d->prepare("INSERT INTO product_batches (product_id, batch_no, lot_no, received_date, expiry_date, quantity_received, quantity_remaining, unit_cost, landed_unit_cost, supplier_id, purchase_order_id, status, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'active',?,?,?)");
        $stmt->execute([
            $input['product_id'], $input['batch_no'] ?? null, $input['lot_no'] ?? null,
            $input['received_date'] ?? date('Y-m-d'), $input['expiry_date'] ?? null,
            $input['quantity_received'], $input['quantity_received'],
            $input['unit_cost'] ?? 0, $input['landed_unit_cost'] ?? null,
            $input['supplier_id'] ?? null, $input['purchase_order_id'] ?? null,
            $input['notes'] ?? null, $now, $now
        ]);
        created(['id' => $d->lastInsertId()]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $qty = $input['quantity_remaining'] ?? null;
        if ($qty !== null) {
            $d->prepare("UPDATE product_batches SET quantity_remaining = ?, updated_at = ? WHERE id = ?")->execute([$qty, $now, $id]);
        }
        $status = $input['status'] ?? null;
        if ($status) {
            $d->prepare("UPDATE product_batches SET status = ?, updated_at = ? WHERE id = ?")->execute([$status, $now, $id]);
        }
        ok(['id' => $id]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $d->prepare("UPDATE product_batches SET status = 'inactive' WHERE id = ?")->execute([$id]);
        ok(['id' => $id]);
    }
}

// === STOCK VALUATION FIFO (Gap 3) ===
if ($endpoint === 'stock-valuation-fifo') {
    if ($method === 'GET') {
        $productId = $_GET['product_id'] ?? null;
        if ($productId) {
            $stmt = $d->prepare("SELECT * FROM product_batches WHERE product_id = ? AND quantity_remaining > 0 ORDER BY received_date ASC, id ASC");
            $stmt->execute([$productId]);
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
        $products = $d->query("SELECT id, code, name FROM products WHERE is_active = 1 ORDER BY name")->fetchAll();
        $results = [];
        $grandTotal = 0;
        foreach ($products as $p) {
            $stmt = $d->prepare("SELECT * FROM product_batches WHERE product_id = ? AND quantity_remaining > 0 ORDER BY received_date ASC, id ASC");
            $stmt->execute([$p['id']]);
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
        $operatingIn = (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='in' AND account_type='cash' AND transaction_date BETWEEN '$startDate' AND '$endDate'")->fetchColumn();
        $operatingOut = (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='out' AND account_type='cash' AND transaction_date BETWEEN '$startDate' AND '$endDate'")->fetchColumn();
        
        // Sales cash received
        $salesCash = (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_date BETWEEN '$startDate' AND '$endDate'")->fetchColumn();
        
        // Purchase cash paid
        $purchaseCash = (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM purchase_payments WHERE payment_date BETWEEN '$startDate' AND '$endDate'")->fetchColumn();
        
        // Investing: fixed asset purchases
        $assetPurchases = (float)$d->query("SELECT COALESCE(SUM(acquisition_cost),0) FROM fixed_assets WHERE acquisition_date BETWEEN '$startDate' AND '$endDate'")->fetchColumn();
        
        // Financing: loan payments (from cash_transactions with category=loan)
        $financingIn = (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='in' AND category LIKE '%loan%' AND transaction_date BETWEEN '$startDate' AND '$endDate'")->fetchColumn();
        $financingOut = (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='out' AND category LIKE '%loan%' AND transaction_date BETWEEN '$startDate' AND '$endDate'")->fetchColumn();
        
        $operatingNet = $operatingIn + $salesCash - $operatingOut - $purchaseCash;
        $investingNet = -$assetPurchases;
        $financingNet = $financingIn - $financingOut;
        $netChange = $operatingNet + $investingNet + $financingNet;
        
        // Beginning cash balance
        $beginningCash = (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='in' AND transaction_date < '$startDate'")->fetchColumn() - (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='out' AND transaction_date < '$startDate'")->fetchColumn();
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
        ok($d->query("SELECT * FROM period_closings ORDER BY period_year DESC, period_month DESC")->fetchAll());
    }
    if ($method === 'POST') {
        $now = date('Y-m-d H:i:s');
        $year = (int)($input['year'] ?? date('Y'));
        $month = (int)($input['month'] ?? date('n'));
        
        $existing = $d->prepare("SELECT * FROM period_closings WHERE period_year = ? AND period_month = ?");
        $existing->execute([$year, $month]);
        if ($existing->fetch()) fail("Period $year-$month already exists");
        
        $d->prepare("INSERT INTO period_closings (period_year, period_month, status, closed_by, closed_at, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$year, $month, 'closed', $_SESSION['user']['id'] ?? null, $now, $input['notes'] ?? null, $now, $now]);
        created(['id' => $d->lastInsertId(), 'period' => "$year-$month"]);
    }
    if ($method === 'PUT') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $now = date('Y-m-d H:i:s');
        $status = $input['status'] ?? 'open';
        $d->prepare("UPDATE period_closings SET status = ?, notes = ?, updated_at = ? WHERE id = ?")->execute([$status, $input['notes'] ?? null, $now, $id]);
        ok(['id' => $id, 'status' => $status]);
    }
    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) fail('ID required');
        $d->prepare("UPDATE period_closings SET status = 'open', updated_at = ? WHERE id = ?")->execute([date('Y-m-d H:i:s'), $id]);
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

fail('Endpoint not found: ' . $endpoint, 404);
