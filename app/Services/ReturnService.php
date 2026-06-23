<?php

namespace App\Services;

use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Sale;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\AccountReceivable;
use App\Models\AccountPayable;
use Illuminate\Support\Facades\DB;
use Exception;

class ReturnService
{
    private AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function createSalesReturn(int $saleId, array $data, int $userId): SalesReturn
    {
        return DB::transaction(function () use ($saleId, $data, $userId) {
            $sale = Sale::with('items')->findOrFail($saleId);

            if ($sale->status === 'voided') {
                throw new Exception('Cannot return a voided sale');
            }

            $returnNo = 'SR' . date('Ymd') . str_pad(SalesReturn::count() + 1, 4, '0', STR_PAD_LEFT);

            $totalRefund = 0;
            $returnItems = [];

            foreach ($data['items'] as $item) {
                $saleItem = $sale->items()->find($item['sale_item_id']);
                if (!$saleItem) {
                    throw new Exception("Sale item #{$item['sale_item_id']} not found in this sale");
                }

                $returnQty = $item['quantity'];
                if ($returnQty > $saleItem->quantity) {
                    throw new Exception("Return quantity exceeds sold quantity for this item");
                }

                $refundAmount = $returnQty * $saleItem->unit_price;
                $totalRefund += $refundAmount;

                $returnItems[] = [
                    'sale_item_id' => $item['sale_item_id'],
                    'product_id' => $saleItem->product_id,
                    'quantity' => $returnQty,
                    'unit_id' => $saleItem->unit_id,
                    'unit_price' => $saleItem->unit_price,
                    'refund_amount' => $refundAmount,
                    'reason' => $item['reason'] ?? null,
                ];
            }

            $return = SalesReturn::create([
                'return_no' => $returnNo,
                'sale_id' => $saleId,
                'customer_id' => $sale->customer_id,
                'return_date' => $data['return_date'],
                'total_refund' => $totalRefund,
                'refund_method' => $data['refund_method'] ?? 'cash',
                'status' => 'completed',
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($returnItems as $item) {
                $item['sales_return_id'] = $return->id;
                SalesReturnItem::create($item);

                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                    'movement_type' => 'return_sale',
                    'reference_id' => $return->id,
                    'reference_type' => 'sales_return',
                    'notes' => "Retur penjualan {$returnNo}",
                    'created_by' => $userId,
                ]);
            }

            if ($sale->payment_method === 'credit') {
                $ar = AccountReceivable::where('sale_id', $saleId)->first();
                if ($ar) {
                    $ar->balance = max(0, $ar->balance - $totalRefund);
                    $ar->save();
                }
            }

            $this->accountingService->postSalesReturnJournal($return, $userId);

            return $return;
        });
    }

    public function createPurchaseReturn(int $poId, array $data, int $userId): PurchaseReturn
    {
        return DB::transaction(function () use ($poId, $data, $userId) {
            $po = PurchaseOrder::with('items')->findOrFail($poId);

            if ($po->status === 'cancelled') {
                throw new Exception('Cannot return a cancelled PO');
            }

            $returnNo = 'PR' . date('Ymd') . str_pad(PurchaseReturn::count() + 1, 4, '0', STR_PAD_LEFT);

            $totalRefund = 0;
            $returnItems = [];

            foreach ($data['items'] as $item) {
                $poItem = $po->items()->find($item['purchase_item_id']);
                if (!$poItem) {
                    throw new Exception("Purchase item #{$item['purchase_item_id']} not found in this PO");
                }

                $returnQty = $item['quantity'];
                if ($returnQty > $poItem->received_quantity) {
                    throw new Exception("Return quantity exceeds received quantity");
                }

                $refundAmount = $returnQty * $poItem->unit_price;
                $totalRefund += $refundAmount;

                $returnItems[] = [
                    'purchase_item_id' => $item['purchase_item_id'],
                    'product_id' => $poItem->product_id,
                    'quantity' => $returnQty,
                    'unit_id' => $poItem->unit_id,
                    'unit_price' => $poItem->unit_price,
                    'refund_amount' => $refundAmount,
                    'reason' => $item['reason'] ?? null,
                ];
            }

            $return = PurchaseReturn::create([
                'return_no' => $returnNo,
                'po_id' => $poId,
                'supplier_id' => $po->supplier_id,
                'return_date' => $data['return_date'],
                'total_refund' => $totalRefund,
                'refund_method' => $data['refund_method'] ?? 'credit',
                'status' => 'completed',
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($returnItems as $item) {
                $item['purchase_return_id'] = $return->id;
                PurchaseReturnItem::create($item);

                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'quantity' => -$item['quantity'],
                    'unit_id' => $item['unit_id'],
                    'movement_type' => 'return_purchase',
                    'reference_id' => $return->id,
                    'reference_type' => 'purchase_return',
                    'notes' => "Retur pembelian {$returnNo}",
                    'created_by' => $userId,
                ]);
            }

            $ap = AccountPayable::where('po_id', $poId)->first();
            if ($ap) {
                $ap->balance = max(0, $ap->balance - $totalRefund);
                $ap->save();
            }

            $this->accountingService->postPurchaseReturnJournal($return, $userId);

            return $return;
        });
    }
}
