<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\ReorderSuggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReorderController extends Controller
{
    public function suggestions()
    {
        $products = Product::where('is_active', true)->get();
        $suggestions = [];

        foreach ($products as $product) {
            $currentStock = StockMovement::where('product_id', $product->id)->sum('quantity');
            if ($currentStock <= 0) continue;

            // Calculate avg daily usage over last 30 days
            $thirtyDaysAgo = now()->subDays(30);
            $totalSold = StockMovement::where('product_id', $product->id)
                ->where('movement_type', 'sale')
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->sum(DB::raw('ABS(quantity)'));

            $avgDailyUsage = $totalSold / 30;
            $daysOfSupply = $avgDailyUsage > 0 ? (int)($currentStock / $avgDailyUsage) : 999;

            // Suggest reorder if days of supply < 14 or stock below min
            $needsReorder = $daysOfSupply < 14 || $currentStock <= ($product->min_stock ?? 0);

            if ($needsReorder) {
                $suggestedQty = max($product->max_stock ?? 100, $avgDailyUsage * 30);
                $priority = 'low';
                if ($currentStock <= 0) $priority = 'critical';
                elseif ($daysOfSupply < 3) $priority = 'critical';
                elseif ($daysOfSupply < 7) $priority = 'high';
                elseif ($daysOfSupply < 14) $priority = 'medium';

                $reason = "Stock: {$currentStock}, Avg daily usage: " . number_format($avgDailyUsage, 2) . ", Days of supply: {$daysOfSupply}";

                $suggestions[] = [
                    'product_id' => $product->id,
                    'product_code' => $product->code,
                    'product_name' => $product->name,
                    'current_stock' => $currentStock,
                    'avg_daily_usage' => round($avgDailyUsage, 2),
                    'days_of_supply' => $daysOfSupply,
                    'suggested_order_qty' => ceil($suggestedQty),
                    'priority' => $priority,
                    'reason' => $reason,
                ];
            }
        }

        // Sort by priority
        $priorityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        usort($suggestions, fn($a, $b) => $priorityOrder[$a['priority']] <=> $priorityOrder[$b['priority']]);

        return response()->json(['success' => true, 'data' => $suggestions, 'total' => count($suggestions)]);
    }
}
