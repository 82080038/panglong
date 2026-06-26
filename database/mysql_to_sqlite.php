<?php
/**
 * Export MySQL database (phpMyAdmin) ke SQLite untuk aplikasi Panglong ERP.
 * 
 * Usage:
 *   /opt/lampp/bin/php database/mysql_to_sqlite.php
 * 
 * Script ini:
 * 1. Koneksi ke MySQL database "panglong" (dari phpMyAdmin)
 * 2. Baca semua tabel + data dari MySQL
 * 3. Konversi ke format SQLite
 * 4. Tambahkan tabel Sprint 12 yang belum ada di MySQL (vehicles, routes, whatsapp, e_faktur)
 * 5. Output: database/database.sqlite (fresh)
 * 
 * Prasyarat:
 *   - MySQL panglong database ada (via phpMyAdmin/XAMPP)
 *   - PHP dengan pdo_mysql dan pdo_sqlite
 */

$mysqlHost = '127.0.0.1';
$mysqlPort = 3306;
$mysqlDb   = 'panglong';
$mysqlUser = 'root';
$mysqlPass = 'root';

$sqlitePath = __DIR__ . '/database.sqlite';

echo "=== MySQL to SQLite Converter ===\n";
echo "Source: MySQL://$mysqlUser@$mysqlHost:$mysqlPort/$mysqlDb\n";
echo "Target: $sqlitePath\n\n";

// Connect to MySQL
try {
    $mysql = new PDO("mysql:host=$mysqlHost;port=$mysqlPort;dbname=$mysqlDb;charset=utf8mb4", $mysqlUser, $mysqlPass);
    $mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to MySQL.\n";
} catch (Exception $e) {
    echo "ERROR: Cannot connect to MySQL: " . $e->getMessage() . "\n";
    echo "Pastikan MySQL berjalan dan kredensial benar.\n";
    exit(1);
}

// Backup existing SQLite
if (file_exists($sqlitePath)) {
    $backup = $sqlitePath . '.backup.' . date('Ymd_His');
    copy($sqlitePath, $backup);
    echo "Backup existing SQLite: $backup\n";
}

// Remove and create fresh SQLite
if (file_exists($sqlitePath)) unlink($sqlitePath);
$sqlite = new PDO('sqlite:' . $sqlitePath);
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->exec("PRAGMA foreign_keys = OFF");

// Get all MySQL tables
$tables = $mysql->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "MySQL tables: " . count($tables) . "\n\n";

// Sprint 12 tables that might not exist in MySQL
$sprint12Tables = [
    'vehicles' => "CREATE TABLE vehicles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        plate_no TEXT UNIQUE,
        vehicle_type TEXT,
        brand TEXT,
        model TEXT,
        capacity_kg REAL,
        fuel_type TEXT,
        acquisition_date TEXT,
        status TEXT DEFAULT 'active',
        notes TEXT,
        created_at TEXT,
        updated_at TEXT
    )",
    'vehicle_maintenance' => "CREATE TABLE vehicle_maintenance (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        vehicle_id INTEGER NOT NULL,
        maintenance_date TEXT NOT NULL,
        maintenance_type TEXT,
        cost REAL,
        odometer_km INTEGER,
        description TEXT,
        next_maintenance_date TEXT,
        created_by INTEGER,
        created_at TEXT,
        updated_at TEXT
    )",
    'delivery_routes' => "CREATE TABLE delivery_routes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        route_no TEXT UNIQUE,
        route_date TEXT NOT NULL,
        vehicle_id INTEGER,
        driver_name TEXT,
        status TEXT DEFAULT 'planned',
        total_distance_km REAL,
        estimated_time_minutes INTEGER,
        notes TEXT,
        created_by INTEGER,
        created_at TEXT,
        updated_at TEXT
    )",
    'route_stops' => "CREATE TABLE route_stops (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        route_id INTEGER NOT NULL,
        delivery_id INTEGER,
        stop_order INTEGER NOT NULL,
        customer_name TEXT,
        address TEXT,
        phone TEXT,
        status TEXT DEFAULT 'pending',
        arrived_at TEXT,
        departed_at TEXT,
        notes TEXT,
        created_at TEXT
    )",
    'whatsapp_templates' => "CREATE TABLE whatsapp_templates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        template_name TEXT UNIQUE,
        template_type TEXT,
        message_body TEXT,
        variables TEXT,
        is_active INTEGER DEFAULT 1,
        created_at TEXT,
        updated_at TEXT
    )",
    'whatsapp_messages' => "CREATE TABLE whatsapp_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        phone_number TEXT NOT NULL,
        message_body TEXT,
        template_name TEXT,
        reference_type TEXT,
        reference_id INTEGER,
        status TEXT DEFAULT 'pending',
        sent_at TEXT,
        error_message TEXT,
        created_by INTEGER,
        created_at TEXT
    )",
    'e_faktur' => "CREATE TABLE e_faktur (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        faktur_no TEXT UNIQUE,
        faktur_type TEXT,
        transaction_date TEXT NOT NULL,
        counterparty_name TEXT,
        counterparty_npwp TEXT,
        dpp REAL,
        ppn REAL,
        description TEXT,
        reference_type TEXT,
        reference_id INTEGER,
        export_status TEXT DEFAULT 'pending',
        created_by INTEGER,
        created_at TEXT,
        updated_at TEXT
    )",
];

// Convert MySQL types to SQLite types
function mysqlTypeToSqlite($col) {
    $type = strtoupper($col['Type']);
    if (strpos($type, 'INT') !== false) return 'INTEGER';
    if (strpos($type, 'VARCHAR') !== false || strpos($type, 'TEXT') !== false || strpos($type, 'CHAR') !== false) return 'TEXT';
    if (strpos($type, 'DECIMAL') !== false || strpos($type, 'FLOAT') !== false || strpos($type, 'DOUBLE') !== false) return 'REAL';
    if (strpos($type, 'DATE') !== false || strpos($type, 'TIME') !== false) return 'TEXT';
    if (strpos($type, 'BLOB') !== false || strpos($type, 'BINARY') !== false) return 'BLOB';
    return 'TEXT';
}

// Process each MySQL table
$created = 0;
foreach ($tables as $tableName) {
    echo "Converting: $tableName\n";
    
    // Get column info
    $cols = $mysql->query("SHOW COLUMNS FROM `$tableName`")->fetchAll(PDO::FETCH_ASSOC);
    
    // Build CREATE TABLE for SQLite
    $colDefs = [];
    $primaryKeys = [];
    foreach ($cols as $col) {
        $sqliteType = mysqlTypeToSqlite($col);
        $def = '"' . $col['Field'] . '" ' . $sqliteType;
        if ($col['Extra'] === 'auto_increment') {
            $def .= ' PRIMARY KEY AUTOINCREMENT';
            $primaryKeys[] = $col['Field'];
        } elseif ($col['Key'] === 'PRI') {
            $primaryKeys[] = $col['Field'];
        }
        if ($col['Null'] === 'NO' && $col['Key'] !== 'PRI') {
            $def .= ' NOT NULL';
        }
        if ($col['Default'] !== null) {
            $def .= " DEFAULT '" . str_replace("'", "''", $col['Default']) . "'";
        }
        $colDefs[] = $def;
    }
    
    // If no auto_increment PK but has PRI, add PRIMARY KEY clause
    $hasAutoInc = false;
    foreach ($cols as $col) {
        if ($col['Extra'] === 'auto_increment') $hasAutoInc = true;
    }
    if (!$hasAutoInc && count($primaryKeys) > 0) {
        $colDefs[] = 'PRIMARY KEY ("' . implode('", "', $primaryKeys) . '")';
    }
    
    $createSql = "CREATE TABLE \"$tableName\" (" . implode(', ', $colDefs) . ")";
    $sqlite->exec("DROP TABLE IF EXISTS \"$tableName\"");
    $sqlite->exec($createSql);
    $created++;
    
    // Copy data
    $rows = $mysql->query("SELECT * FROM `$tableName`")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) continue;
    
    $colNames = array_keys($rows[0]);
    $placeholders = implode(', ', array_fill(0, count($colNames), '?'));
    $colList = '"' . implode('", "', $colNames) . '"';
    
    $stmt = $sqlite->prepare("INSERT INTO \"$tableName\" ($colList) VALUES ($placeholders)");
    foreach ($rows as $row) {
        $stmt->execute(array_values($row));
    }
    echo "  -> " . count($rows) . " rows\n";
}

// Add Sprint 12 tables if not in MySQL
echo "\nChecking Sprint 12 tables...\n";
foreach ($sprint12Tables as $tableName => $createSql) {
    $exists = $sqlite->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='$tableName'")->fetchColumn();
    if (!$exists) {
        echo "Adding: $tableName\n";
        $sqlite->exec($createSql);
        $created++;
    }
}

// Seed WhatsApp templates if empty
$waCount = $sqlite->query("SELECT COUNT(*) FROM whatsapp_templates")->fetchColumn();
if ($waCount == 0) {
    echo "Seeding WhatsApp templates...\n";
    $now = date('Y-m-d H:i:s');
    $templates = [
        ['invoice_reminder', 'reminder', 'Yth {customer_name}, invoice {invoice_no} senilai Rp {total} jatuh tempo {due_date}. Mohon segera dilunasi. Terima kasih - PT Panglong Bangunan Jaya', 'customer_name,invoice_no,total,due_date'],
        ['delivery_notification', 'notification', 'Yth {customer_name}, pesanan Anda dengan invoice {invoice_no} akan dikirim pada {delivery_date} oleh {driver_name} ({vehicle_plate}). Terima kasih - PT Panglong Bangunan Jaya', 'customer_name,invoice_no,delivery_date,driver_name,vehicle_plate'],
        ['payment_confirmation', 'confirmation', 'Yth {customer_name}, pembayaran Rp {amount} untuk invoice {invoice_no} telah kami terima pada {payment_date}. Saldo piutang: Rp {balance}. Terima kasih - PT Panglong Bangunan Jaya', 'customer_name,amount,invoice_no,payment_date,balance'],
        ['quotation_sent', 'notification', 'Yth {customer_name}, penawaran harga {quote_no} telah kami kirim. Berlaku sampai {valid_until}. Total: Rp {total}. Terima kasih - PT Panglong Bangunan Jaya', 'customer_name,quote_no,valid_until,total'],
    ];
    foreach ($templates as $t) {
        $sqlite->prepare("INSERT INTO whatsapp_templates (template_name, template_type, message_body, variables, is_active, created_at, updated_at) VALUES (?,?,?,?,1,?,?)")->execute([$t[0], $t[1], $t[2], $t[3], $now, $now]);
    }
}

$sqlite->exec("PRAGMA foreign_keys = ON");

// Verify
$finalCount = $sqlite->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchColumn();
$fileSize = filesize($sqlitePath);

echo "\n=== Conversion Complete ===\n";
echo "Database: $sqlitePath\n";
echo "Tables: $finalCount (from MySQL: " . count($tables) . " + Sprint 12 additions)\n";
echo "Size: " . number_format($fileSize) . " bytes (" . round($fileSize / 1024, 1) . " KB)\n";
echo "\nDatabase siap digunakan. Frontend akan otomatis terhubung.\n";
