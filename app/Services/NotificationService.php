<?php
namespace App\Services;

use App\Models\Sale;
use App\Models\Customer;
use App\Models\AccountReceivable;
use App\Models\AccountPayable;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function sendInvoiceEmail(Sale $sale): void
    {
        $customer = $sale->customer;
        if (!$customer || empty($customer->email)) {
            Log::info("Invoice email skipped: no customer email for sale {$sale->invoice_no}");
            return;
        }

        $data = [
            'invoice_no' => $sale->invoice_no,
            'sale_date' => $sale->sale_date,
            'total' => $sale->total,
            'customer_name' => $customer->name,
            'items' => $sale->items,
            'company_name' => AppSetting::get('company_name', 'Panglong ERP'),
        ];

        Mail::send('emails.invoice', $data, function ($msg) use ($customer, $sale) {
            $msg->to($customer->email, $customer->name)
                ->subject('Invoice ' . $sale->invoice_no);
        });

        Log::info("Invoice email sent to {$customer->email} for sale {$sale->invoice_no}");
    }

    public function sendPaymentReceiptEmail(Sale $sale, float $amount): void
    {
        $customer = $sale->customer;
        if (!$customer || empty($customer->email)) return;

        $data = [
            'invoice_no' => $sale->invoice_no,
            'amount' => $amount,
            'payment_method' => $sale->payment_method,
            'customer_name' => $customer->name,
            'company_name' => AppSetting::get('company_name', 'Panglong ERP'),
        ];

        Mail::send('emails.payment_receipt', $data, function ($msg) use ($customer, $sale) {
            $msg->to($customer->email, $customer->name)
                ->subject('Payment Receipt - ' . $sale->invoice_no);
        });
    }

    public function sendARDueReminder(AccountReceivable $ar): void
    {
        $customer = $ar->customer;
        if (!$customer || empty($customer->email)) return;

        $data = [
            'customer_name' => $customer->name,
            'amount' => $ar->balance,
            'due_date' => $ar->due_date,
            'days_overdue' => now()->diffInDays($ar->due_date, false),
            'company_name' => AppSetting::get('company_name', 'Panglong ERP'),
        ];

        Mail::send('emails.ar_due_reminder', $data, function ($msg) use ($customer) {
            $msg->to($customer->email, $customer->name)
                ->subject('Payment Due Reminder');
        });
    }

    public function sendAPDueReminder(AccountPayable $ap): void
    {
        $supplier = $ap->supplier;
        if (!$supplier || empty($supplier->email)) return;

        $data = [
            'supplier_name' => $supplier->name,
            'amount' => $ap->balance,
            'due_date' => $ap->due_date,
            'company_name' => AppSetting::get('company_name', 'Panglong ERP'),
        ];

        Mail::send('emails.ap_due_reminder', $data, function ($msg) use ($supplier) {
            $msg->to($supplier->email, $supplier->name)
                ->subject('Payment Due Reminder');
        });
    }

    public function sendSMS(string $phone, string $message): array
    {
        // SMS gateway integration placeholder
        // In production, integrate with providers like Twilio, Nexmo, or Indonesian providers (e.g., Zenziva, SMSHub)
        $gateway = AppSetting::get('sms_gateway', 'log');

        if ($gateway === 'log') {
            Log::info("SMS to {$phone}: {$message}");
            return ['success' => true, 'message' => 'SMS logged (dev mode)'];
        }

        // Production: call actual SMS gateway API
        // Example for Zenziva:
        // $userkey = AppSetting::get('sms_userkey');
        // $passkey = AppSetting::get('sms_passkey');
        // Http::post("https://reguler.zenziva.net/apps/smsapi.php", [
        //     'userkey' => $userkey, 'passkey' => $passkey, 'nohp' => $phone, 'pesan' => $message
        // ]);

        Log::info("SMS sent to {$phone}: {$message}");
        return ['success' => true, 'message' => 'SMS sent'];
    }

    public function sendARDueSMS(AccountReceivable $ar): array
    {
        $customer = $ar->customer;
        if (!$customer || empty($customer->phone)) return ['success' => false, 'message' => 'No phone'];

        $msg = "Panglong ERP: Pembayaran Rp " . number_format($ar->balance, 0) . " jatuh tempo " . $ar->due_date . ". Mohon segera lakukan pembayaran. Terima kasih.";
        return $this->sendSMS($customer->phone, $msg);
    }
}
