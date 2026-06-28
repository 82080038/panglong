<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requirePermission('view_dashboard');

require_once __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

header('Content-Type: image/png');

$data = $_GET['data'] ?? '';
$size = $_GET['size'] ?? 200;

if (empty($data)) {
    http_response_code(400);
    echo 'QR code data required';
    exit;
}

$qrCode = QrCode::create($data)
    ->setSize($size)
    ->setMargin(10);

$writer = new PngWriter();
$result = $writer->write($qrCode);

echo $result->getString();
