<?php
require_once __DIR__ . '/config.php';

$d = db();

echo "Creating reference tables...\n";

// 1. Payment Methods
$d->exec("CREATE TABLE IF NOT EXISTS payment_methods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Insert default payment methods
$existingPaymentMethods = $d->query("SELECT COUNT(*) as count FROM payment_methods")->fetchColumn();
if ($existingPaymentMethods == 0) {
    $d->exec("INSERT INTO payment_methods (code, name) VALUES 
        ('cash', 'Cash'),
        ('transfer', 'Transfer Bank'),
        ('credit', 'Credit/Tempo'),
        ('check', 'Cek/Giro'),
        ('e-wallet', 'E-Wallet'),
        ('credit_card', 'Kartu Kredit')
    ");
    echo "Payment methods inserted.\n";
}

// 2. Unit Measurements
$d->exec("CREATE TABLE IF NOT EXISTS unit_measurements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Insert default unit measurements
$existingUnits = $d->query("SELECT COUNT(*) as count FROM unit_measurements")->fetchColumn();
if ($existingUnits == 0) {
    $d->exec("INSERT INTO unit_measurements (code, name) VALUES 
        ('pcs', 'Pieces/Pcs'),
        ('kg', 'Kilogram'),
        ('liter', 'Liter'),
        ('meter', 'Meter'),
        ('box', 'Box'),
        ('pack', 'Pack'),
        ('set', 'Set'),
        ('unit', 'Unit')
    ");
    echo "Unit measurements inserted.\n";
}

// 3. Tax Rates
$d->exec("CREATE TABLE IF NOT EXISTS tax_rates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    rate DECIMAL(5,4) NOT NULL,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Insert default tax rates
$existingTaxRates = $d->query("SELECT COUNT(*) as count FROM tax_rates")->fetchColumn();
if ($existingTaxRates == 0) {
    $d->exec("INSERT INTO tax_rates (code, name, rate) VALUES 
        ('ppn_11', 'PPN 11%', 0.11),
        ('ppn_10', 'PPN 10%', 0.10),
        ('non_ppn', 'Non-PPN', 0.00)
    ");
    echo "Tax rates inserted.\n";
}

// 4. Delivery Methods
$d->exec("CREATE TABLE IF NOT EXISTS delivery_methods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Insert default delivery methods
$existingDeliveryMethods = $d->query("SELECT COUNT(*) as count FROM delivery_methods")->fetchColumn();
if ($existingDeliveryMethods == 0) {
    $d->exec("INSERT INTO delivery_methods (code, name) VALUES 
        ('pickup', 'Ambil Sendiri'),
        ('kurir_internal', 'Kurir Internal'),
        ('jne', 'JNE'),
        ('jnt', 'J&T'),
        ('gojek', 'Gojek/Grab'),
        ('sicepat', 'SiCepat')
    ");
    echo "Delivery methods inserted.\n";
}

// 5. Status Codes
$d->exec("CREATE TABLE IF NOT EXISTS status_codes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    module VARCHAR(50) NOT NULL,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(module, code)
)");

// Insert default status codes
$existingStatusCodes = $d->query("SELECT COUNT(*) as count FROM status_codes")->fetchColumn();
if ($existingStatusCodes == 0) {
    $d->exec("INSERT INTO status_codes (module, code, name) VALUES 
        ('sales', 'draft', 'Draft'),
        ('sales', 'pending', 'Pending'),
        ('sales', 'approved', 'Approved'),
        ('sales', 'completed', 'Completed'),
        ('sales', 'voided', 'Voided'),
        ('purchase_orders', 'draft', 'Draft'),
        ('purchase_orders', 'pending', 'Pending'),
        ('purchase_orders', 'approved', 'Approved'),
        ('purchase_orders', 'received', 'Received'),
        ('purchase_orders', 'voided', 'Voided'),
        ('deliveries', 'pending', 'Pending'),
        ('deliveries', 'in_transit', 'In Transit'),
        ('deliveries', 'delivered', 'Delivered'),
        ('deliveries', 'cancelled', 'Cancelled'),
        ('quotations', 'draft', 'Draft'),
        ('quotations', 'sent', 'Sent'),
        ('quotations', 'accepted', 'Accepted'),
        ('quotations', 'rejected', 'Rejected'),
        ('quotations', 'expired', 'Expired'),
        ('sales_orders', 'draft', 'Draft'),
        ('sales_orders', 'open', 'Open'),
        ('sales_orders', 'in_progress', 'In Progress'),
        ('sales_orders', 'fulfilled', 'Fulfilled'),
        ('sales_orders', 'cancelled', 'Cancelled')
    ");
    echo "Status codes inserted.\n";
}

echo "All reference tables created successfully!\n";
