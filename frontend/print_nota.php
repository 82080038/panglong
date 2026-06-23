<?php
require_once __DIR__ . '/config.php';

$id = $_GET['id'] ?? 0;
$resp = apiCall('/sales/' . $id);
$sale = $resp['body']['data'] ?? null;

if (!$sale) {
    echo "Sale not found";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota - <?php echo htmlspecialchars($sale['invoice_no'] ?? $id); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 12px; width: 80mm; padding: 10px; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h2 { font-size: 18px; }
        .header p { font-size: 10px; }
        .info { margin-bottom: 10px; }
        .info-row { display: flex; justify-content: space-between; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { padding: 2px 0; text-align: left; font-size: 11px; }
        th { border-bottom: 1px dashed #000; }
        .total { border-top: 1px dashed #000; padding-top: 5px; margin-top: 5px; }
        .total-row { display: flex; justify-content: space-between; font-weight: bold; font-size: 13px; }
        .footer { text-align: center; margin-top: 15px; font-size: 10px; }
        @media print { body { width: 80mm; } }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h2>PANGLONG ERP</h2>
        <p>Jl. Contoh No. 123, Kota<br>Telp: 021-1234567</p>
    </div>
    <div class="info">
        <div class="info-row"><span>No:</span><span><?php echo htmlspecialchars($sale['invoice_no'] ?? $id); ?></span></div>
        <div class="info-row"><span>Date:</span><span><?php echo htmlspecialchars($sale['sale_date'] ?? ''); ?></span></div>
        <div class="info-row"><span>Customer:</span><span><?php echo htmlspecialchars($sale['customer']['name'] ?? 'Walk-in'); ?></span></div>
        <div class="info-row"><span>Payment:</span><span><?php echo htmlspecialchars($sale['payment_method'] ?? 'cash'); ?></span></div>
    </div>
    <table>
        <thead>
            <tr><th>Item</th><th>Qty</th><th>Price</th><th>Sub</th></tr>
        </thead>
        <tbody>
            <?php if (isset($sale['items'])): ?>
                <?php foreach ($sale['items'] as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product']['name'] ?? 'Item'); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo number_format($item['unit_price'], 0, ',', '.'); ?></td>
                        <td><?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="total">
        <div class="total-row"><span>TOTAL</span><span>Rp <?php echo number_format($sale['total'] ?? 0, 0, ',', '.'); ?></span></div>
    </div>
    <div class="footer">
        <p>Terima kasih atas kunjungan Anda</p>
        <p>&copy; <?php echo date('Y'); ?> Panglong ERP</p>
    </div>
</body>
</html>
