<?php
require_once __DIR__ . '/config.php';
requirePermission('manage_sales');

$id = $_GET['id'] ?? 0;
$d = db();
$user = currentUser();
$tenantId = $user['tenant_id'] ?? null;
$branchId = $user['branch_id'] ?? null;
$isSuperAdmin = $user['role_slug'] === 'super_admin';

$stmt = $d->prepare("SELECT s.*, c.name as customer_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.id = ?" . ($isSuperAdmin ? "" : " AND s.tenant_id = ? AND s.branch_id = ?"));
$stmt->execute($isSuperAdmin ? [$id] : [$id, $tenantId, $branchId]);
$sale = $stmt->fetch();

if (!$sale) {
    echo "<p>Sale not found</p>";
    exit;
}

$sale['customer'] = ['name' => $sale['customer_name'] ?? 'Walk-in'];

$items = $d->prepare("SELECT si.*, p.name as product_name FROM sale_items si LEFT JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?" . ($isSuperAdmin ? "" : " AND si.tenant_id = ?"));
$items->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
$sale['items'] = $items->fetchAll();

$pays = $d->prepare("SELECT * FROM sale_payments WHERE sale_id = ?" . ($isSuperAdmin ? "" : " AND tenant_id = ?"));
$pays->execute($isSuperAdmin ? [$id] : [$id, $tenantId]);
$sale['payments'] = $pays->fetchAll();
?>
<div class="table-responsive"><table class="table table-sm">
    <tr><td>Invoice</td><td><?php echo htmlspecialchars($sale['invoice_no'] ?? $id); ?></td></tr>
    <tr><td>Date</td><td><?php echo htmlspecialchars($sale['sale_date'] ?? ''); ?></td></tr>
    <tr><td>Customer</td><td><?php echo htmlspecialchars($sale['customer']['name'] ?? 'Walk-in'); ?></td></tr>
    <tr><td>Payment Method</td><td><?php echo htmlspecialchars($sale['payment_method'] ?? ''); ?></td></tr>
    <tr><td>Payment Status</td><td><span class="badge bg-<?php echo ($sale['payment_status'] ?? '') === 'paid' ? 'success' : 'warning'; ?>"><?php echo htmlspecialchars($sale['payment_status'] ?? 'unpaid'); ?></span></td></tr>
    <tr><td>Status</td><td><span class="badge bg-<?php echo ($sale['status'] ?? '') === 'completed' ? 'success' : 'warning'; ?>"><?php echo htmlspecialchars($sale['status'] ?? 'completed'); ?></span></td></tr>
</table></div>
<h6>Items</h6>
<div class="table-responsive"><table class="table table-sm table-bordered">
    <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Discount</th><th>Subtotal</th></tr></thead>
    <tbody>
        <?php if (isset($sale['items'])): ?>
            <?php foreach ($sale['items'] as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['product_name'] ?? 'Item'); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><?php echo rupiah($item['unit_price']) ?></td>
                    <td><?php echo rupiah($item['discount'] ?? 0) ?></td>
                    <td><?php echo rupiah($item['subtotal']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr><td colspan="4" class="text-end fw-bold">Subtotal</td><td><?php echo rupiah($sale['subtotal'] ?? 0) ?></td></tr>
        <tr><td colspan="4" class="text-end">Discount</td><td><?php echo rupiah($sale['discount'] ?? 0) ?></td></tr>
        <tr><td colspan="4" class="text-end">Tax</td><td><?php echo rupiah($sale['tax'] ?? 0) ?></td></tr>
        <tr><td colspan="4" class="text-end fw-bold fs-5">Total</td><td class="fw-bold fs-5"><?php echo rupiah($sale['total'] ?? 0) ?></td></tr>
    </tfoot>
</table></div>
<?php if (isset($sale['payments']) && count($sale['payments']) > 0): ?>
<h6>Payments</h6>
<div class="table-responsive"><table class="table table-sm">
    <thead><tr><th>Date</th><th>Amount</th><th>Method</th></tr></thead>
    <tbody>
        <?php foreach ($sale['payments'] as $pay): ?>
            <tr><td><?php echo htmlspecialchars($pay['payment_date']); ?></td><td><?php echo rupiah($pay['amount']) ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table></div>
<?php endif; ?>
