<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Customer;
use App\Models\AccountReceivable;
use Illuminate\Support\Facades\DB;
use Exception;

class SaleService
{
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
     * Calculate total with tax
     */
    public function calculateTotal(float $subtotal, float $discount, float $taxRate = 0.11): float
    {
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
     * Create sale with stock movements
     */
    public function createSale(array $data, int $userId): Sale
    {
        return DB::transaction(function () use ($data, $userId) {
            $invoiceNo = $this->generateInvoiceNumber($data['sale_date']);
            $subtotal = $this->calculateSubtotal($data['items']);
            $total = $this->calculateTotal($subtotal, $data['discount'] ?? 0, 0.11);

            $sale = Sale::create([
                'invoice_no' => $invoiceNo,
                'customer_id' => $data['customer_id'],
                'sale_date' => $data['sale_date'],
                'subtotal' => $subtotal,
                'discount' => $data['discount'] ?? 0,
                'tax' => $total - $subtotal - ($data['discount'] ?? 0),
                'total' => $total,
                'payment_method' => $data['payment_method'],
                'payment_status' => $data['payment_method'] === 'cash' ? 'paid' : 'unpaid',
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            // Create sale items and stock movements
            foreach ($data['items'] as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'subtotal' => ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0),
                ]);

                // Create stock movement
                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'quantity' => -$item['quantity'],
                    'unit_id' => $item['unit_id'],
                    'movement_type' => 'sale',
                    'reference_id' => $sale->id,
                    'reference_type' => 'sale',
                    'notes' => "Sale {$invoiceNo}",
                    'created_by' => $userId,
                ]);
            }

            // Create accounts receivable if credit
            if ($data['payment_method'] === 'credit') {
                $customer = Customer::find($data['customer_id']);
                $dueDate = date('Y-m-d', strtotime($data['sale_date'] . " +{$customer->payment_terms} days"));

                AccountReceivable::create([
                    'customer_id' => $data['customer_id'],
                    'sale_id' => $sale->id,
                    'amount' => $total,
                    'balance' => $total,
                    'due_date' => $dueDate,
                    'status' => 'pending',
                ]);
            }

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

            return true;
        });
    }
}
