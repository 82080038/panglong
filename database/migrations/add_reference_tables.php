<?php
$db = new PDO('sqlite:database/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Adding Reference Tables ===\n\n";

// 1. adjustment_types table
echo "Creating adjustment_types table...\n";
$db->exec("CREATE TABLE IF NOT EXISTS adjustment_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TEXT,
    updated_at TEXT
)");

// Insert default adjustment types
$defaultAdjustmentTypes = [
    ['code' => 'physical_count', 'name' => 'Physical Count', 'description' => 'Adjustment based on physical inventory count'],
    ['code' => 'damage', 'name' => 'Damage', 'description' => 'Product damage adjustment'],
    ['code' => 'loss', 'name' => 'Loss', 'description' => 'Product loss adjustment'],
    ['code' => 'theft', 'name' => 'Theft', 'description' => 'Product theft adjustment'],
    ['code' => 'correction', 'name' => 'Correction', 'description' => 'Data correction adjustment']
];

foreach ($defaultAdjustmentTypes as $type) {
    $stmt = $db->prepare("INSERT OR IGNORE INTO adjustment_types (code, name, description, is_active, created_at, updated_at) VALUES (?, ?, ?, 1, datetime('now'), datetime('now'))");
    $stmt->execute([$type['code'], $type['name'], $type['description']]);
}
echo "Adjustment types inserted.\n\n";

// 2. e_faktur_types table
echo "Creating e_faktur_types table...\n";
$db->exec("CREATE TABLE IF NOT EXISTS e_faktur_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TEXT,
    updated_at TEXT
)");

// Insert default e-faktur types
$defaultEFakturTypes = [
    ['code' => 'keluaran', 'name' => 'Keluaran (Penjualan)', 'description' => 'Sales e-faktur'],
    ['code' => 'masukan', 'name' => 'Masukan (Pembelian)', 'description' => 'Purchase e-faktur']
];

foreach ($defaultEFakturTypes as $type) {
    $stmt = $db->prepare("INSERT OR IGNORE INTO e_faktur_types (code, name, description, is_active, created_at, updated_at) VALUES (?, ?, ?, 1, datetime('now'), datetime('now'))");
    $stmt->execute([$type['code'], $type['name'], $type['description']]);
}
echo "E-Faktur types inserted.\n\n";

// 3. whatsapp_template_types table
echo "Creating whatsapp_template_types table...\n";
$db->exec("CREATE TABLE IF NOT EXISTS whatsapp_template_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TEXT,
    updated_at TEXT
)");

// Insert default whatsapp template types
$defaultWaTypes = [
    ['code' => 'notification', 'name' => 'Notification', 'description' => 'General notification template'],
    ['code' => 'reminder', 'name' => 'Reminder', 'description' => 'Reminder template'],
    ['code' => 'confirmation', 'name' => 'Confirmation', 'description' => 'Confirmation template'],
    ['code' => 'marketing', 'name' => 'Marketing', 'description' => 'Marketing template']
];

foreach ($defaultWaTypes as $type) {
    $stmt = $db->prepare("INSERT OR IGNORE INTO whatsapp_template_types (code, name, description, is_active, created_at, updated_at) VALUES (?, ?, ?, 1, datetime('now'), datetime('now'))");
    $stmt->execute([$type['code'], $type['name'], $type['description']]);
}
echo "WhatsApp template types inserted.\n\n";

echo "=== Migration Complete ===\n";
