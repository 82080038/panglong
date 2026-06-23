<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Product;
use App\Models\Customer;
use Carbon\Carbon;

class ReportService
{
    /**
     * Get daily sales report
     */
    public function getDailySales(string $date): array
    {
        $sales = Sale::where('sale_date', $date)
                     ->where('status', '!=', 'voided')
                     ->with('items.product')
                     ->get();

        $totalSales = $sales->count();
        $totalRevenue = $sales->sum('total');
        $totalCash = $sales->where('payment_method', 'cash')->sum('total');
        $totalCredit = $sales->where('payment_method', 'credit')->sum('total');
        $totalDiscount = $sales->sum('discount');

        $items = [];
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                $productId = $item->product_id;
                if (!isset($items[$productId])) {
                    $items[$productId] = [
                        'product_id' => $productId,
                        'product_name' => $item->product->name,
                        'quantity_sold' => 0,
                        'revenue' => 0,
                    ];
                }
                $items[$productId]['quantity_sold'] += $item->quantity;
                $items[$productId]['revenue'] += $item->subtotal;
            }
        }

        return [
            'date' => $date,
            'total_sales' => $totalSales,
            'total_revenue' => $totalRevenue,
            'total_cash' => $totalCash,
            'total_credit' => $totalCredit,
            'total_discount' => $totalDiscount,
            'items' => array_values($items),
        ];
    }

    /**
     * Get monthly sales report
     */
    public function getMonthlySales(int $year, int $month): array
    {
        $sales = Sale::whereYear('sale_date', $year)
                     ->whereMonth('sale_date', $month)
                     ->where('status', '!=', 'voided')
                     ->get();

        $totalSales = $sales->count();
        $totalRevenue = $sales->sum('total');
        $totalCash = $sales->where('payment_method', 'cash')->sum('total');
        $totalCredit = $sales->where('payment_method', 'credit')->sum('total');

        $dailyBreakdown = [];
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day)->format('Y-m-d');
            $dailySales = $sales->where('sale_date', $date);
            
            $dailyBreakdown[$date] = [
                'sales_count' => $dailySales->count(),
                'revenue' => $dailySales->sum('total'),
            ];
        }

        return [
            'year' => $year,
            'month' => $month,
            'total_sales' => $totalSales,
            'total_revenue' => $totalRevenue,
            'total_cash' => $totalCash,
            'total_credit' => $totalCredit,
            'daily_breakdown' => $dailyBreakdown,
        ];
    }

    /**
     * Get low stock report
     */
    public function getLowStockReport(): array
    {
        $products = Product::with('category')->where('is_active', true)->get();
        
        $lowStockProducts = [];

        foreach ($products as $product) {
            $currentStock = StockMovement::where('product_id', $product->id)->sum('quantity');
            
            if ($currentStock < $product->min_stock && $product->min_stock > 0) {
                $lowStockProducts[] = [
                    'product_id' => $product->id,
                    'product_code' => $product->code,
                    'product_name' => $product->name,
                    'category' => $product->category->name ?? null,
                    'current_stock' => $currentStock,
                    'min_stock' => $product->min_stock,
                    'shortage' => $product->min_stock - $currentStock,
                    'unit' => $product->baseUnit->unit_name ?? 'pcs',
                ];
            }
        }

        return $lowStockProducts;
    }

    /**
     * Get stock report
     */
    public function getStockReport(): array
    {
        $products = Product::with('category', 'baseUnit')->where('is_active', true)->get();
        
        $stockData = [];

        foreach ($products as $product) {
            $currentStock = StockMovement::where('product_id', $product->id)->sum('quantity');
            
            $status = 'normal';
            if ($currentStock < $product->min_stock && $product->min_stock > 0) {
                $status = 'low_stock';
            } elseif ($currentStock > $product->max_stock && $product->max_stock > 0) {
                $status = 'overstock';
            }

            $stockData[] = [
                'product_id' => $product->id,
                'product_code' => $product->code,
                'product_name' => $product->name,
                'category' => $product->category->name ?? null,
                'current_stock' => $currentStock,
                'min_stock' => $product->min_stock,
                'max_stock' => $product->max_stock,
                'base_unit' => $product->baseUnit->unit_name ?? 'pcs',
                'status' => $status,
            ];
        }

        return $stockData;
    }

    /**
     * Get aging report for accounts payable
     */
    public function getPayableAging(): array
    {
        $aps = \App\Models\AccountPayable::with('supplier')->get();
        
        $aging = [
            '0_30_days' => 0,
            '31_60_days' => 0,
            '61_90_days' => 0,
            'over_90_days' => 0,
            'total_outstanding' => 0,
            'details' => [],
        ];

        foreach ($aps as $ap) {
            if ($ap->balance > 0) {
                $daysOverdue = now()->diffInDays($ap->due_date, false);
                $aging['total_outstanding'] += $ap->balance;

                if ($daysOverdue <= 30) {
                    $aging['0_30_days'] += $ap->balance;
                } elseif ($daysOverdue <= 60) {
                    $aging['31_60_days'] += $ap->balance;
                } elseif ($daysOverdue <= 90) {
                    $aging['61_90_days'] += $ap->balance;
                } else {
                    $aging['over_90_days'] += $ap->balance;
                }

                $aging['details'][] = [
                    'supplier_id' => $ap->supplier_id,
                    'supplier_name' => $ap->supplier->name,
                    'outstanding' => $ap->balance,
                    'days_overdue' => $daysOverdue,
                ];
            }
        }

        return $aging;
    }

    /**
     * Sales by product report
     */
    public function getSalesByProduct(string $dateFrom, string $dateTo): array
    {
        $sales = Sale::whereBetween('sale_date', [$dateFrom, $dateTo])
                     ->where('status', '!=', 'voided')
                     ->with('items')
                     ->get();

        $productData = [];
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                $pid = $item->product_id;
                if (!isset($productData[$pid])) {
                    $productData[$pid] = [
                        'product_id' => $pid,
                        'product_name' => $item->product->name ?? 'N/A',
                        'quantity_sold' => 0,
                        'revenue' => 0,
                        'profit' => 0,
                    ];
                }
                $productData[$pid]['quantity_sold'] += $item->quantity;
                $productData[$pid]['revenue'] += $item->subtotal;
                $buyPrice = $item->product->buy_price ?? 0;
                $productData[$pid]['profit'] += $item->subtotal - ($item->quantity * $buyPrice);
            }
        }

        return array_values($productData);
    }

    /**
     * Sales by customer report
     */
    public function getSalesByCustomer(string $dateFrom, string $dateTo): array
    {
        $sales = Sale::whereBetween('sale_date', [$dateFrom, $dateTo])
                     ->where('status', '!=', 'voided')
                     ->with('customer')
                     ->get();

        $customerData = [];
        foreach ($sales as $sale) {
            $cid = $sale->customer_id ?? 0;
            $name = $sale->customer_name_snapshot ?? ($sale->customer->name ?? 'Walk-in');
            if (!isset($customerData[$cid])) {
                $customerData[$cid] = [
                    'customer_id' => $cid,
                    'customer_name' => $name,
                    'total_sales' => 0,
                    'total_revenue' => 0,
                    'total_paid' => 0,
                    'total_unpaid' => 0,
                ];
            }
            $customerData[$cid]['total_sales']++;
            $customerData[$cid]['total_revenue'] += $sale->total;
            if ($sale->payment_status === 'paid') {
                $customerData[$cid]['total_paid'] += $sale->total;
            } else {
                $customerData[$cid]['total_unpaid'] += $sale->total;
            }
        }

        return array_values($customerData);
    }

    /**
     * Profit/Loss report
     */
    public function getProfitLoss(string $dateFrom, string $dateTo): array
    {
        $sales = Sale::whereBetween('sale_date', [$dateFrom, $dateTo])
                     ->where('status', '!=', 'voided')
                     ->with('items.product')
                     ->get();

        $revenue = $sales->sum('subtotal');
        $discount = $sales->sum('discount');
        $tax = $sales->sum('tax');
        $cogs = 0;

        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                $cogs += $item->quantity * ($item->product->buy_price ?? 0);
            }
        }

        $grossProfit = $revenue - $cogs;
        $netProfit = $grossProfit - $discount;

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'discount' => $discount,
            'tax' => $tax,
            'net_profit' => $netProfit,
            'total_sales' => $sales->count(),
        ];
    }

    /**
     * Stock movement report
     */
    public function getStockMovementReport(string $dateFrom, string $dateTo): array
    {
        $movements = \App\Models\StockMovement::with(['product', 'product.baseUnit'])
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->orderBy('created_at', 'desc')
            ->get();

        return $movements->map(function ($m) {
            return [
                'date' => $m->created_at->format('Y-m-d H:i'),
                'product_name' => $m->product->name ?? 'N/A',
                'product_code' => $m->product->code ?? '',
                'quantity' => $m->quantity,
                'movement_type' => $m->movement_type,
                'reference_type' => $m->reference_type,
                'notes' => $m->notes,
            ];
        })->toArray();
    }

    /**
     * Dead stock report (no movement in X days)
     */
    public function getDeadStockReport(int $days = 90): array
    {
        $cutoffDate = now()->subDays($days);
        
        $products = Product::where('is_active', true)->get();
        $deadStock = [];

        foreach ($products as $product) {
            $currentStock = StockMovement::where('product_id', $product->id)->sum('quantity');
            if ($currentStock <= 0) continue;

            $lastMovement = StockMovement::where('product_id', $product->id)
                ->where('created_at', '>=', $cutoffDate)
                ->count();

            if ($lastMovement === 0) {
                $deadStock[] = [
                    'product_id' => $product->id,
                    'product_code' => $product->code,
                    'product_name' => $product->name,
                    'current_stock' => $currentStock,
                    'stock_value' => $currentStock * $product->buy_price,
                    'days_inactive' => $days,
                ];
            }
        }

        return $deadStock;
    }

    /**
     * Stock valuation report (average cost method)
     */
    public function getStockValuation(): array
    {
        $products = Product::where('is_active', true)->get();
        $valuation = [];
        $totalValue = 0;

        foreach ($products as $product) {
            $currentStock = StockMovement::where('product_id', $product->id)->sum('quantity');
            if ($currentStock <= 0) continue;

            $avgCost = (float) $product->buy_price;
            $stockValue = $currentStock * $avgCost;
            $totalValue += $stockValue;

            $valuation[] = [
                'product_id' => $product->id,
                'product_code' => $product->code,
                'product_name' => $product->name,
                'current_stock' => $currentStock,
                'avg_cost' => $avgCost,
                'stock_value' => $stockValue,
                'sell_price' => (float) $product->sell_price,
                'potential_revenue' => $currentStock * (float) $product->sell_price,
            ];
        }

        return [
            'items' => $valuation,
            'total_stock_value' => $totalValue,
            'total_products' => count($valuation),
        ];
    }

    /**
     * Advanced custom report builder
     */
    public function getCustomReport(string $dateFrom, string $dateTo, string $groupBy, string $metric): array
    {
        $query = Sale::whereBetween('sale_date', [$dateFrom, $dateTo])->where('status', '!=', 'voided');

        switch ($groupBy) {
            case 'day':
                $query->selectRaw('sale_date as label, COUNT(*) as transaction_count, SUM(subtotal) as subtotal, SUM(discount) as discount, SUM(tax) as tax, SUM(total) as total');
                $query->groupBy('sale_date')->orderBy('sale_date');
                break;
            case 'month':
                $query->selectRaw("DATE_FORMAT(sale_date, '%Y-%m') as label, COUNT(*) as transaction_count, SUM(subtotal) as subtotal, SUM(discount) as discount, SUM(tax) as tax, SUM(total) as total");
                $query->groupByRaw("DATE_FORMAT(sale_date, '%Y-%m')")->orderBy('label');
                break;
            case 'customer':
                $query->with('customer')->selectRaw('customer_id, COUNT(*) as transaction_count, SUM(subtotal) as subtotal, SUM(discount) as discount, SUM(tax) as tax, SUM(total) as total');
                $query->groupBy('customer_id')->orderByDesc('total');
                break;
            case 'payment_method':
                $query->selectRaw('payment_method as label, COUNT(*) as transaction_count, SUM(subtotal) as subtotal, SUM(discount) as discount, SUM(tax) as tax, SUM(total) as total');
                $query->groupBy('payment_method')->orderByDesc('total');
                break;
            case 'product':
                $query->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                    ->selectRaw('products.name as label, SUM(sale_items.quantity) as quantity_sold, SUM(sale_items.subtotal) as revenue, COUNT(DISTINCT sales.id) as transaction_count')
                    ->groupBy('products.id', 'products.name')->orderByDesc('revenue');
                break;
            default:
                $query->selectRaw('sale_date as label, COUNT(*) as transaction_count, SUM(total) as total');
                $query->groupBy('sale_date')->orderBy('sale_date');
        }

        $results = $query->get()->toArray();

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'group_by' => $groupBy,
            'metric' => $metric,
            'data' => $results,
            'total_records' => count($results),
        ];
    }
}
