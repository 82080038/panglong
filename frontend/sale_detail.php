<?php
require_once __DIR__ . '/config.php';

$id = $_GET['id'] ?? 0;
$resp = apiCall('/sales/' . $id);
$sale = $resp['body']['data'] ?? null;

if (!$sale) {
    echo "<p>Sale not found</p>";
    exit;
}
?>
<table class="table table-sm">
    <tr><td>Invoice</td><td><?php echo htmlspecialchars($sale['invoice_no'] ?? $id); ?></td></tr>
    <tr><td>Date</td><td><?php echo htmlspecialchars($sale['sale_date'] ?? ''); ?></td></tr>
    <tr><td>Customer</td><td><?php echo htmlspecialchars($sale['customer']['name'] ?? 'Walk-in'); ?></td></tr>
    <tr><td>Payment Method</td><td><?php echo htmlspecialchars($sale['payment_method'] ?? ''); ?></td></tr>
    <tr><td>Payment Status</td><td><span class="badge bg-<?php echo ($sale['payment_status'] ?? '') === 'paid' ? 'success' : 'warning'; ?>"><?php echo htmlspecialchars($sale['payment_status'] ?? 'unpaid'); ?></span></td></tr>
    <tr><td>Status</td><td><span class="badge bg-<?php echo ($sale['status'] ?? '') === 'completed' ? 'success' : 'warning'; ?>"><?php echo htmlspecialchars($sale['status'] ?? 'completed'); ?></span></td></tr>
</table>
<h6>Items</h6>
<table class="table table-sm table-bordered">
    <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Discount</th><th>Subtotal</th></tr></thead>
    <tbody>
        <?php if (isset($sale['items'])): ?>
            <?php foreach ($sale['items'] as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['product']['name'] ?? 'Item'); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>Rp <?php echo number_format($item['unit_price'], 0, ',', '.'); ?></td>
                    <td>Rp <?php echo number_format($item['discount'] ?? 0, 0, ',', '.'); ?></td>
                    <td>Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr><td colspan="4" class="text-end fw-bold">Subtotal</td><td>Rp <?php echo number_format($sale['subtotal'] ?? 0, 0, ',', '.'); ?></td></tr>
        <tr><td colspan="4" class="text-end">Discount</td><td>Rp <?php echo number_format($sale['discount'] ?? 0, 0, ',', '.'); ?></td></tr>
        <tr><td colspan="4" class="text-end">Tax</td><td>Rp <?php echo number_format($sale['tax'] ?? 0, 0, ',', '.'); ?></td></tr>
        <tr><td colspan="4" class="text-end fw-bold fs-5">Total</td><td class="fw-bold fs-5">Rp <?php echo number_format($sale['total'] ?? 0, 0, ',', '.'); ?></td></tr>
    </tfoot>
</table>
<?php if (isset($sale['payments']) && count($sale['payments']) > 0): ?>
<h6>Payments</h6>
<table class="table table-sm">
    <thead><tr><th>Date</th><th>Amount</th><th>Method</th></tr></thead>
    <tbody>
        <?php foreach ($sale['payments'] as $pay): ?>
            <tr><td><?php echo htmlspecialchars($pay['payment_date']); ?></td><td>Rp <?php echo number_format($pay['amount'], 0, ',', '.'); ?></td><td><?php echo htmlspecialchars($pay['payment_method']); ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
