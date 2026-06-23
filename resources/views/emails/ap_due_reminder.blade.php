<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto">
<h2>{{ $company_name }}</h2>
<hr>
<h3>Payment Due Reminder</h3>
<p>Dear {{ $supplier_name }},</p>
<p>Ini adalah pengingat jadwal pembayaran:</p>
<p>Amount: Rp {{ number_format($amount, 0) }}<br>Due Date: {{ $due_date }}</p>
<p>Terima kasih.</p>
</body></html>
