<?php
namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BankService
{
    public function verifyPayment(string $transactionId, float $expectedAmount): array
    {
        // Bank integration placeholder
        // In production, integrate with bank API (BCA, Mandiri, BNI, etc.)
        // or payment gateway (Midtrans, Xendit, DOKU)

        $provider = AppSetting::get('bank_provider', 'manual');

        if ($provider === 'manual') {
            return [
                'success' => false,
                'message' => 'Manual verification mode - no automatic bank integration configured',
                'provider' => 'manual',
            ];
        }

        // Example: Midtrans integration
        // $serverKey = AppSetting::get('midtrans_server_key');
        // $response = Http::withBasicAuth($serverKey, '')
        //     ->get("https://api.sandbox.midtrans.com/v2/{$transactionId}/status");
        // $data = $response->json();
        // return ['success' => $data['transaction_status'] === 'settlement', ...];

        Log::info("Bank verification for {$transactionId}, expected: {$expectedAmount}");
        return ['success' => false, 'message' => 'Bank integration not configured'];
    }

    public function getBankStatements(string $fromDate, string $toDate): array
    {
        // Placeholder for bank statement fetching
        // In production, integrate with bank API or statement parser
        return [
            'transactions' => [],
            'message' => 'Bank statement integration not configured. Use manual reconciliation.',
        ];
    }

    public function reconcilePayment(string $transactionId, float $amount, string $type): array
    {
        $verification = $this->verifyPayment($transactionId, $amount);

        if ($verification['success']) {
            return [
                'success' => true,
                'message' => "Payment verified for {$type} #{$transactionId}",
                'amount' => $amount,
            ];
        }

        return [
            'success' => false,
            'message' => $verification['message'] ?? 'Verification failed',
        ];
    }
}
