<?php
/**
 * Import SQLite database dari export SQL dump.
 * 
 * Usage:
 *   /opt/lampp/bin/php database/import_sqlite.php
 * 
 * Script ini:
 * 1. Membaca database_export.sql
 * 2. Membuat database.sqlite baru (overwrite jika ada)
 * 3. Import semua schema + data
 */

$dbPath = __DIR__ . '/database.sqlite';
$exportPath = __DIR__ . '/database_export.sql';

if (!file_exists($exportPath)) {
    echo "ERROR: database_export.sql tidak ditemukan di $exportPath\n";
    echo "Jalankan export_sqlite.php di komputer yang punya database.\n";
    exit(1);
}

// Backup existing database if present
if (file_exists($dbPath)) {
    $backupPath = $dbPath . '.backup.' . date('Ymd_His');
    copy($dbPath, $backupPath);
    echo "Backup: $backupPath\n";
}

// Remove existing database
unlink($dbPath);

// Create new database and import
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = file_get_contents($exportPath);

// Execute SQL statements
$statements = explode(";\n", $sql);
$count = 0;
$errors = 0;

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt) || strpos($stmt, '--') === 0) continue;
    try {
        $db->exec($stmt);
        $count++;
    } catch (Exception $e) {
        // Skip PRAGMA errors and comment-only lines
        if (strpos($stmt, 'PRAGMA') !== 0) {
            $errors++;
            echo "ERROR: " . $e->getMessage() . "\n";
            echo "SQL: " . substr($stmt, 0, 100) . "...\n\n";
        }
    }
}

echo "\n=== Import Complete ===\n";
echo "Database: $dbPath\n";
echo "Statements executed: $count\n";
echo "Errors: $errors\n";

// Verify
$tableCount = $db->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchColumn();
echo "Tables: $tableCount\n";
echo "Size: " . number_format(filesize($dbPath)) . " bytes\n";
