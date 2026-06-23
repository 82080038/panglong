<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto">
<h2 style="color:#0d6efd">{{ $company_name }}</h2>
<hr>
<h3>Invoice {{ $invoice_no }}</h3>
<p>Dear {{ $customer_name }},</p>
<p>Berikut adalah invoice Anda:</p>
<table border="1" cellpadding="8" style="border-collapse:collapse;width:100%">
<tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
@foreach ($items as $item)
<tr><td>{{ $item->product->name ?? 'N/A' }}</td><td>{{ $item->quantity }}</td><td>Rp {{ number_format($item->unit_price, 0) }}</td><td>Rp {{ number_format($item->subtotal, 0) }}</td></tr>
@endforeach
</table>
<p><strong>Total: Rp {{ number_format($total, 0) }}</strong></p>
<p>Date: {{ $sale_date }}</p>
<p>Terima kasih atas pembelian Anda.</p>
</body></html>
