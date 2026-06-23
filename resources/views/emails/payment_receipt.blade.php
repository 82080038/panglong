<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto">
<h2 style="color:#198754">{{ $company_name }}</h2>
<hr>
<h3>Payment Receipt</h3>
<p>Dear {{ $customer_name }},</p>
<p>Kami telah menerima pembayaran Anda:</p>
<p>Invoice: {{ $invoice_no }}<br>Amount: Rp {{ number_format($amount, 0) }}<br>Method: {{ ucfirst($payment_method) }}</p>
<p>Terima kasih.</p>
</body></html>
