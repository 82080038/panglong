<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto">
<h2 style="color:#dc3545">{{ $company_name }}</h2>
<hr>
<h3>Payment Due Reminder</h3>
<p>Dear {{ $customer_name }},</p>
<p>Ini adalah pengingat bahwa pembayaran Anda telah jatuh tempo:</p>
<p>Amount: Rp {{ number_format($amount, 0) }}<br>Due Date: {{ $due_date }}<br>Days Overdue: {{ $days_overdue }}</p>
<p>Mohon segera lakukan pembayaran. Terima kasih.</p>
</body></html>
