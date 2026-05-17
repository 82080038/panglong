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
}
