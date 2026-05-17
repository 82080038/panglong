<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use App\Models\AccountReceivable;
use App\Models\AccountPayable;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Record sale payment
     */
    public function recordSalePayment(int $saleId, array $data, int $userId): SalePayment
    {
        return DB::transaction(function () use ($saleId, $data, $userId) {
            $sale = Sale::findOrFail($saleId);
            
            $payment = SalePayment::create([
                'sale_id' => $saleId,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'payment_date' => $data['payment_date'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            // Update accounts receivable
            $ar = AccountReceivable::where('sale_id', $saleId)->first();
            if ($ar) {
                $totalPaid = SalePayment::where('sale_id', $saleId)->sum('amount');
                $ar->update([
                    'balance' => $ar->amount - $totalPaid,
                    'status' => ($ar->amount - $totalPaid) <= 0 ? 'paid' : 'partial',
                ]);
            }

            // Update sale payment status
            $totalPaid = SalePayment::where('sale_id', $saleId)->sum('amount');
            $sale->update([
                'payment_status' => $totalPaid >= $sale->total ? 'paid' : 'partial',
            ]);

            return $payment;
        });
    }

    /**
     * Record purchase payment
     */
    public function recordPurchasePayment(int $poId, array $data, int $userId): PurchasePayment
    {
        return DB::transaction(function () use ($poId, $data, $userId) {
            $po = PurchaseOrder::findOrFail($poId);
            
            $payment = PurchasePayment::create([
                'po_id' => $poId,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'payment_date' => $data['payment_date'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            // Update accounts payable
            $ap = AccountPayable::where('po_id', $poId)->first();
            if ($ap) {
                $totalPaid = PurchasePayment::where('po_id', $poId)->sum('amount');
                $ap->update([
                    'balance' => $ap->amount - $totalPaid,
                    'status' => ($ap->amount - $totalPaid) <= 0 ? 'paid' : 'partial',
                ]);
            }

            // Update purchase order payment status
            $totalPaid = PurchasePayment::where('po_id', $poId)->sum('amount');
            $po->update([
                'payment_status' => $totalPaid >= $po->total ? 'paid' : 'partial',
            ]);

            return $payment;
        });
    }

    /**
     * Get aging report for accounts receivable
     */
    public function getReceivableAging(): array
    {
        $ars = AccountReceivable::with('customer')->get();
        
        $aging = [
            '0_30_days' => 0,
            '31_60_days' => 0,
            '61_90_days' => 0,
            'over_90_days' => 0,
            'total_outstanding' => 0,
            'details' => [],
        ];

        foreach ($ars as $ar) {
            if ($ar->balance > 0) {
                $daysOverdue = now()->diffInDays($ar->due_date, false);
                $aging['total_outstanding'] += $ar->balance;

                if ($daysOverdue <= 30) {
                    $aging['0_30_days'] += $ar->balance;
                } elseif ($daysOverdue <= 60) {
                    $aging['31_60_days'] += $ar->balance;
                } elseif ($daysOverdue <= 90) {
                    $aging['61_90_days'] += $ar->balance;
                } else {
                    $aging['over_90_days'] += $ar->balance;
                }

                $aging['details'][] = [
                    'customer_id' => $ar->customer_id,
                    'customer_name' => $ar->customer->name,
                    'outstanding' => $ar->balance,
                    'days_overdue' => $daysOverdue,
                ];
            }
        }

        return $aging;
    }
}
