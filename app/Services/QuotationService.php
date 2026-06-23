<?php

namespace App\Services;

use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Sale;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Exception;

class QuotationService
{
    private PricingService $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    public function createQuotation(array $data, int $userId): Quotation
    {
        return DB::transaction(function () use ($data, $userId) {
            $customerId = $data['customer_id'] ?? null;
            $customerName = 'Walk-in Customer';

            if ($customerId) {
                $customer = Customer::find($customerId);
                if (!$customer) {
                    throw new Exception('Customer not found');
                }
                $customerName = $customer->name;
            }

            $quoteNo = 'QT' . date('Ymd') . str_pad(Quotation::count() + 1, 4, '0', STR_PAD_LEFT);

            $subtotal = collect($data['items'])->sum(function ($item) {
                return ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
            });

            $discount = $data['discount'] ?? 0;
            $taxRate = $this->pricingService->getTaxRate();
            $taxable = $subtotal - $discount;
            $tax = $taxable * $taxRate;
            $total = $taxable + $tax;

            $quotation = Quotation::create([
                'quote_no' => $quoteNo,
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'quote_date' => $data['quote_date'],
                'valid_until' => $data['valid_until'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($data['items'] as $item) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'bonus_qty' => $item['bonus_qty'] ?? 0,
                    'unit_id' => $item['unit_id'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'subtotal' => ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0),
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $quotation;
        });
    }

    public function updateStatus(int $quotationId, string $status): Quotation
    {
        $quotation = Quotation::findOrFail($quotationId);
        $quotation->update(['status' => $status]);
        return $quotation;
    }

    public function convertToSalesOrder(int $quotationId, int $userId): SalesOrder
    {
        return DB::transaction(function () use ($quotationId, $userId) {
            $quotation = Quotation::with('items')->findOrFail($quotationId);

            if ($quotation->status === 'converted') {
                throw new Exception('Quotation already converted');
            }

            $soNumber = 'SO' . date('Ymd') . str_pad(SalesOrder::count() + 1, 4, '0', STR_PAD_LEFT);

            $salesOrder = SalesOrder::create([
                'so_number' => $soNumber,
                'customer_id' => $quotation->customer_id,
                'customer_name' => $quotation->customer_name,
                'order_date' => date('Y-m-d'),
                'expected_delivery_date' => null,
                'subtotal' => $quotation->subtotal,
                'discount' => $quotation->discount,
                'tax' => $quotation->tax,
                'total' => $quotation->total,
                'payment_method' => 'cash',
                'status' => 'confirmed',
                'notes' => $quotation->notes,
                'delivery_address' => $quotation->delivery_address,
                'quotation_id' => $quotation->id,
                'created_by' => $userId,
            ]);

            foreach ($quotation->items as $item) {
                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'bonus_qty' => $item->bonus_qty,
                    'unit_id' => $item->unit_id,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount,
                    'subtotal' => $item->subtotal,
                    'notes' => $item->notes,
                ]);
            }

            $quotation->update(['status' => 'converted']);

            return $salesOrder;
        });
    }

    public function convertToInvoice(int $soId, int $userId): Sale
    {
        return DB::transaction(function () use ($soId, $userId) {
            $so = SalesOrder::with('items')->findOrFail($soId);

            if ($so->status === 'invoiced') {
                throw new Exception('Sales order already invoiced');
            }

            $saleService = app(SaleService::class);

            $saleData = [
                'customer_id' => $so->customer_id,
                'sale_date' => date('Y-m-d'),
                'items' => $so->items->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'unit_id' => $item->unit_id,
                        'unit_price' => $item->unit_price,
                        'discount' => $item->discount,
                    ];
                })->toArray(),
                'payment_method' => $so->payment_method,
                'notes' => $so->notes,
                'delivery_address' => $so->delivery_address,
            ];

            $sale = $saleService->createSale($saleData, $userId);

            $so->update([
                'status' => 'invoiced',
                'sale_id' => $sale->id,
            ]);

            return $sale;
        });
    }
}
