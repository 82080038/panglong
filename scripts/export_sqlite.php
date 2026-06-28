<?php
/**
 * Export SQLite database untuk distribusi ke komputer lain.
 * 
 * Usage:
 *   /opt/lampp/bin/php database/export_sqlite.php
 * 
 * Output:
 *   database/database.sqlite (fresh, dengan semua data)
 *   database/database_export.sql (SQL dump untuk import manual)
 * 
 * Script ini:
 * 1. Membaca database SQLite aktif
 * 2. Export semua schema + data ke SQL dump
 * 3. Bisa di-import di komputer lain dengan: sqlite3 database/database.sqlite < database/database_export.sql
 */

$dbPath = __DIR__ . '/database.sqlite';
$exportPath = __DIR__ . '/database_export.sql';

if (!file_exists($dbPath)) {
    echo "ERROR: database.sqlite tidak ditemukan di $dbPath\n";
    echo "Pastikan aplikasi sudah pernah dijalankan.\n";
    exit(1);
}

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get all tables
$tables = $db->query("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$output = "-- Panglong ERP SQLite Export\n";
$output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$output .= "-- Tables: " . count($tables) . "\n";
$output .= "-- Import: sqlite3 database/database.sqlite < database/database_export.sql\n\n";
$output .= "PRAGMA foreign_keys=OFF;\n\n";

foreach ($tables as $table) {
    $tableName = $table['name'];
    echo "Exporting: $tableName\n";
    
    // Schema
    $output .= "-- Table: $tableName\n";
    $output .= "DROP TABLE IF EXISTS \"$tableName\";\n";
    $output .= $table['sql'] . ";\n\n";
    
    // Data
    $rows = $db->query("SELECT * FROM \"$tableName\"")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        $output .= "-- (no data)\n\n";
        continue;
    }
    
    $columns = array_keys($rows[0]);
    $colList = '"' . implode('", "', $columns) . '"';
    
    foreach ($rows as $row) {
        $values = [];
        foreach ($row as $val) {
            if ($val === null) {
                $values[] = 'NULL';
            } else {
                $escaped = str_replace("'", "''", $val);
                $values[] = "'" . $escaped . "'";
            }
        }
        $output .= "INSERT INTO \"$tableName\" ($colList) VALUES (" . implode(', ', $values) . ");\n";
    }
    $output .= "\n";
}

// Get indexes
$indexes = $db->query("SELECT sql FROM sqlite_master WHERE type='index' AND sql IS NOT NULL ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
if (!empty($indexes)) {
    $output .= "-- Indexes\n";
    foreach ($indexes as $idx) {
        $output .= $idx . ";\n";
    }
    $output .= "\n";
}

$output .= "PRAGMA foreign_keys=ON;\n";

file_put_contents($exportPath, $output);

$fileSize = filesize($exportPath);
echo "\n=== Export Complete ===\n";
echo "File: $exportPath\n";
echo "Size: " . number_format($fileSize) . " bytes (" . round($fileSize / 1024, 1) . " KB)\n";
echo "Tables: " . count($tables) . "\n";
echo "\nUntuk import di komputer lain:\n";
echo "  1. Copy file database_export.sql ke komputer tujuan\n";
echo "  2. Jalankan: sqlite3 database/database.sqlite < database/database_export.sql\n";
echo "  Atau gunakan: /opt/lampp/bin/php database/import_sqlite.php\n";
