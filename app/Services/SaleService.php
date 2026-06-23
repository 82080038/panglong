<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\StockMovement;
use App\Models\Customer;
use App\Models\AccountReceivable;
use App\Models\AppSetting;
use Illuminate\Support\Facades\DB;
use Exception;

class SaleService
{
    private PricingService $pricingService;
    private AccountingService $accountingService;

    public function __construct(PricingService $pricingService, AccountingService $accountingService)
    {
        $this->pricingService = $pricingService;
        $this->accountingService = $accountingService;
    }

    /**
     * Calculate subtotal from items
     */
    public function calculateSubtotal(array $items): float
    {
        return collect($items)->sum(function ($item) {
            return ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
        });
    }

    /**
     * Calculate total with configurable tax
     */
    public function calculateTotal(float $subtotal, float $discount, ?float $taxRate = null): float
    {
        if ($taxRate === null) {
            $taxRate = $this->pricingService->getTaxRate();
        }
        $taxable = $subtotal - $discount;
        return $taxable + ($taxable * $taxRate);
    }

    /**
     * Generate invoice number
     */
    public function generateInvoiceNumber(string $date): string
    {
        $prefix = date('Ymd', strtotime($date));
        $lastInvoice = Sale::where('invoice_no', 'like', "INV{$prefix}%")
                          ->orderBy('id', 'desc')
                          ->first();
        
        $sequence = $lastInvoice ? (int)substr($lastInvoice->invoice_no, -4) + 1 : 1;
        
        return "INV{$prefix}" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create sale with stock movements, credit limit check, negative stock prevention
     */
    public function createSale(array $data, int $userId): Sale
    {
        return DB::transaction(function () use ($data, $userId) {
            $customerId = $data['customer_id'] ?? null;
            $customerName = 'Walk-in Customer';
            $customer = null;

            if ($customerId) {
                $customer = Customer::with('group')->find($customerId);
                if (!$customer) {
                    throw new Exception('Customer not found');
                }
                $customerName = $customer->name;
            }

            // Calculate subtotal
            $subtotal = $this->calculateSubtotal($data['items']);
            $discount = $data['discount'] ?? 0;
            $taxRate = $this->pricingService->getTaxRate();
            $total = $this->calculateTotal($subtotal, $discount, $taxRate);
            $taxAmount = ($subtotal - $discount) * $taxRate;

            // Credit limit check for credit sales
            if ($data['payment_method'] === 'credit' && $customer) {
                $creditCheck = $this->pricingService->checkCreditLimit($customer->id, $total);
                if (!$creditCheck['allowed']) {
                    throw new Exception($creditCheck['message']);
                }
            }

            // Negative stock prevention - check all items
            $stockService = app(StockService::class);
            foreach ($data['items'] as $item) {
                $currentStock = $stockService->getCurrentStock($item['product_id']);
                if ($currentStock < $item['quantity']) {
                    $product = \App\Models\Product::find($item['product_id']);
                    $productName = $product ? $product->name : "Product #{$item['product_id']}";
                    throw new Exception("Insufficient stock for {$productName}. Available: {$currentStock}, Requested: {$item['quantity']}");
                }
            }

            $invoiceNo = $this->generateInvoiceNumber($data['sale_date']);

            $sale = Sale::create([
                'invoice_no' => $invoiceNo,
                'customer_id' => $customerId,
                'customer_name_snapshot' => $customerName,
                'sale_date' => $data['sale_date'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $taxAmount,
                'total' => $total + ($data['delivery_cost'] ?? 0),
                'delivery_cost' => $data['delivery_cost'] ?? 0,
                'payment_method' => $data['payment_method'],
                'payment_status' => $data['payment_method'] === 'cash' ? 'paid' : 'unpaid',
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'created_by' => $userId,
            ]);

            // Create sale items and stock movements
            foreach ($data['items'] as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'bonus_qty' => $item['bonus_qty'] ?? 0,
                    'unit_id' => $item['unit_id'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'subtotal' => ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0),
                ]);

                // Create stock movement (negative = out, including bonus)
                $totalOut = $item['quantity'] + ($item['bonus_qty'] ?? 0);
                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'quantity' => -$totalOut,
                    'unit_id' => $item['unit_id'],
                    'movement_type' => 'sale',
                    'reference_id' => $sale->id,
                    'reference_type' => 'sale',
                    'notes' => "Sale {$invoiceNo}" . (isset($item['bonus_qty']) && $item['bonus_qty'] > 0 ? " (incl. bonus {$item['bonus_qty']})" : ''),
                    'created_by' => $userId,
                ]);
            }

            // Create accounts receivable if credit
            if ($data['payment_method'] === 'credit' && $customer) {
                $dueDate = date('Y-m-d', strtotime($data['sale_date'] . " +{$customer->payment_terms} days"));

                AccountReceivable::create([
                    'customer_id' => $customer->id,
                    'sale_id' => $sale->id,
                    'amount' => $total,
                    'balance' => $total,
                    'due_date' => $dueDate,
                    'status' => 'pending',
                ]);
            }

            // Post journal entry
            $this->accountingService->postSaleJournal($sale->fresh('items.product'));

            return $sale;
        });
    }

    /**
     * Void sale
     */
    public function voidSale(int $saleId, string $reason, int $userId): bool
    {
        return DB::transaction(function () use ($saleId, $reason, $userId) {
            $sale = Sale::findOrFail($saleId);
            
            if ($sale->status === 'voided') {
                throw new Exception('Sale already voided');
            }

            // Reverse stock movements
            foreach ($sale->items as $item) {
                StockMovement::create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_id' => $item->unit_id,
                    'movement_type' => 'return_sale',
                    'reference_id' => $sale->id,
                    'reference_type' => 'sale',
                    'notes' => "Void sale: {$reason}",
                    'created_by' => $userId,
                ]);
            }

            // Update sale status
            $sale->update([
                'status' => 'voided',
                'notes' => ($sale->notes ?? '') . " [VOIDED: {$reason}]",
            ]);

            // Update AR if exists
            $ar = AccountReceivable::where('sale_id', $saleId)->first();
            if ($ar) {
                $ar->update(['status' => 'voided', 'balance' => 0]);
            }

            // Void journal entry (reversal)
            $this->accountingService->voidSaleJournal($sale, $reason, $userId);

            return true;
        });
    }

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

            $totalPaid = $sale->payments()->sum('amount');

            if ($totalPaid >= $sale->total) {
                $sale->update(['payment_status' => 'paid']);
            } elseif ($totalPaid > 0) {
                $sale->update(['payment_status' => 'partial']);
            }

            if ($sale->payment_method === 'credit') {
                $receivable = AccountReceivable::where('sale_id', $saleId)->first();
                if ($receivable) {
                    $newBalance = $receivable->balance - $data['amount'];
                    $receivable->update([
                        'balance' => max(0, $newBalance),
                        'status' => $newBalance <= 0 ? 'paid' : 'partial',
                    ]);
                }
            }

            // Post payment journal
            $this->accountingService->postPaymentJournal('sale', $saleId, $data['amount'], $data['payment_method'], $userId);

            return $payment;
        });
    }
}
