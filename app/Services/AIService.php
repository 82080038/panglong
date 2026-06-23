<?php
namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\SaleItem;
use App\Models\DemandForecast;
use App\Models\PriceOptimization;
use Illuminate\Support\Facades\DB;

class AIService
{
    public function generateDemandForecast(int $productId, int $horizonDays = 30): DemandForecast
    {
        $product = Product::findOrFail($productId);

        // Get last 90 days of sales data
        $salesData = SaleItem::where('product_id', $productId)
            ->whereHas('sale', function ($q) {
                $q->where('status', '!=', 'voided')
                  ->where('sale_date', '>=', now()->subDays(90));
            })
            ->selectRaw('DATE(sales.sale_date) as date, SUM(sale_items.quantity) as qty')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Simple moving average + trend analysis
        $dailyAverages = [];
        $totalQty = 0;
        $dataPoints = $salesData->count();

        if ($dataPoints > 0) {
            $totalQty = $salesData->sum('qty');
            $avgDaily = $totalQty / max($dataPoints, 1);

            // Calculate trend (linear regression slope)
            $sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0;
            $n = 0;
            foreach ($salesData as $i => $data) {
                $x = $i;
                $y = $data->qty;
                $sumX += $x; $sumY += $y;
                $sumXY += ($x * $y); $sumX2 += ($x * $x);
                $n++;
            }
            $slope = $n > 1 ? (($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX)) : 0;

            // Project forward with trend
            $predictedDemand = $avgDaily * $horizonDays * (1 + $slope * 0.1);
            $predictedDemand = max($predictedDemand, 0);

            // Confidence interval (based on variance)
            $variance = 0;
            if ($n > 1) {
                $mean = $sumY / $n;
                foreach ($salesData as $data) {
                    $variance += pow($data->qty - $mean, 2);
                }
                $variance = sqrt($variance / $n);
            }
            $confidenceLower = max($predictedDemand - ($variance * $horizonDays * 0.5), 0);
            $confidenceUpper = $predictedDemand + ($variance * $horizonDays * 0.5);
            $confidenceScore = $dataPoints >= 30 ? 0.85 : ($dataPoints >= 14 ? 0.65 : ($dataPoints >= 7 ? 0.45 : 0.25));

            $factors = [
                'avg_daily_sales' => round($avgDaily, 2),
                'trend_slope' => round($slope, 4),
                'data_points' => $dataPoints,
                'total_qty_90d' => $totalQty,
                'variance' => round($variance, 2),
                'seasonality' => $this->detectSeasonality($salesData),
            ];
        } else {
            $predictedDemand = 0;
            $confidenceLower = 0;
            $confidenceUpper = 0;
            $confidenceScore = 0.1;
            $factors = ['message' => 'Insufficient data for forecast'];
        }

        return DemandForecast::create([
            'product_id' => $productId,
            'tenant_id' => session('tenant_id'),
            'forecast_date' => now()->toDateString(),
            'horizon_days' => $horizonDays,
            'predicted_demand' => round($predictedDemand, 2),
            'confidence_lower' => round($confidenceLower, 2),
            'confidence_upper' => round($confidenceUpper, 2),
            'confidence_score' => $confidenceScore,
            'method' => 'moving_average_with_trend',
            'factors' => $factors,
        ]);
    }

    private function detectSeasonality($salesData): ?array
    {
        if ($salesData->count() < 14) return null;

        $dayOfWeek = [];
        foreach ($salesData as $data) {
            $dow = date('N', strtotime($data->date));
            $dayOfWeek[$dow] = ($dayOfWeek[$dow] ?? 0) + $data->qty;
        }

        $max = max($dayOfWeek);
        $min = min($dayOfWeek);
        if ($max > 0 && ($max - $min) / $max > 0.3) {
            $peakDay = array_search($max, $dayOfWeek);
            $dayNames = [1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'];
            return ['detected' => true, 'peak_day' => $dayNames[$peakDay] ?? 'N/A'];
        }

        return ['detected' => false];
    }

    public function generatePriceOptimization(int $productId): PriceOptimization
    {
        $product = Product::findOrFail($productId);
        $currentPrice = (float)$product->sell_price;
        $buyPrice = (float)$product->buy_price;

        if ($buyPrice <= 0) {
            return PriceOptimization::create([
                'product_id' => $productId,
                'tenant_id' => session('tenant_id'),
                'current_price' => $currentPrice,
                'suggested_price' => $currentPrice,
                'current_margin' => 0,
                'suggested_margin' => 0,
                'estimated_demand_change' => 0,
                'estimated_revenue_change' => 0,
                'reasoning' => 'No buy price set, cannot optimize',
                'generated_date' => now()->toDateString(),
            ]);
        }

        $currentMargin = (($currentPrice - $buyPrice) / $currentPrice) * 100;

        // Get sales velocity (last 30 days)
        $recentSales = SaleItem::where('product_id', $productId)
            ->whereHas('sale', function ($q) {
                $q->where('status', '!=', 'voided')
                  ->where('sale_date', '>=', now()->subDays(30));
            })->sum('quantity');

        $currentStock = StockMovement::where('product_id', $productId)->sum('quantity');

        // Price elasticity heuristic
        $suggestedPrice = $currentPrice;
        $reasoning = [];

        if ($currentMargin < 10) {
            $suggestedPrice = $buyPrice * 1.2;
            $reasoning[] = "Margin too low ({$currentMargin}%), suggest 20% markup";
        } elseif ($currentMargin > 50 && $recentSales > 0 && $currentStock > $recentSales * 2) {
            $suggestedPrice = $currentPrice * 0.92;
            $reasoning[] = "High margin ({$currentMargin}%) with high stock, suggest 8% price drop to boost sales";
        } elseif ($recentSales == 0 && $currentStock > 0) {
            $suggestedPrice = $currentPrice * 0.9;
            $reasoning[] = "No sales in 30 days with stock available, suggest 10% discount to clear stock";
        } elseif ($recentSales > 0 && $currentStock < $recentSales / 2) {
            $suggestedPrice = $currentPrice * 1.05;
            $reasoning[] = "High demand with low stock, suggest 5% price increase";
        } else {
            $reasoning[] = "Price is optimal at current margin of {$currentMargin}%";
        }

        $suggestedMargin = (($suggestedPrice - $buyPrice) / $suggestedPrice) * 100;
        $priceChangePct = ($suggestedPrice - $currentPrice) / $currentPrice * 100;
        $estimatedDemandChange = -$priceChangePct * 1.5; // elasticity = -1.5
        $estimatedRevenueChange = ($suggestedPrice * (1 + $estimatedDemandChange / 100) - $currentPrice) * $recentSales;

        return PriceOptimization::create([
            'product_id' => $productId,
            'tenant_id' => session('tenant_id'),
            'current_price' => $currentPrice,
            'suggested_price' => round($suggestedPrice, 2),
            'current_margin' => round($currentMargin, 2),
            'suggested_margin' => round($suggestedMargin, 2),
            'estimated_demand_change' => round($estimatedDemandChange, 2),
            'estimated_revenue_change' => round($estimatedRevenueChange, 2),
            'reasoning' => implode('; ', $reasoning),
            'generated_date' => now()->toDateString(),
        ]);
    }
}
