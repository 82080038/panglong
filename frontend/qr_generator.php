<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requirePermission('view_dashboard');

header('Content-Type: image/png');

$data = $_GET['data'] ?? '';
$size = (int)($_GET['size'] ?? 200);

if (empty($data)) {
    http_response_code(400);
    echo 'QR code data required';
    exit;
}

// Use Google Chart API as QR code generator (no Composer dependency needed)
$url = 'https://chart.googleapis.com/chart?cht=qr&chs=' . $size . 'x' . $size . '&chl=' . urlencode($data) . '&choe=UTF-8';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($result && $httpCode === 200) {
    echo $result;
} else {
    // Fallback: generate a simple placeholder image using GD
    $img = imagecreate($size, $size);
    $bg = imagecolorallocate($img, 255, 255, 255);
    $fg = imagecolorallocate($img, 0, 0, 0);
    imagestring($img, 2, 5, 5, 'QR Error', $fg);
    imagestring($img, 1, 5, 25, substr($data, 0, 30), $fg);
    imagepng($img);
    imagedestroy($img);
}
