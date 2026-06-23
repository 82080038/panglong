<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\AIService;
use App\Models\DemandForecast;
use App\Models\PriceOptimization;
use App\Models\Product;
use Illuminate\Http\Request;

class AIController extends Controller
{
    public function __construct(private AIService $aiService)
    {
    }

    public function demandForecast(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'horizon_days' => 'nullable|integer|min:1|max:365',
        ]);

        $horizon = $validated['horizon_days'] ?? 30;
        $forecast = $this->aiService->generateDemandForecast($validated['product_id'], $horizon);

        return response()->json(['success' => true, 'data' => $forecast], 201);
    }

    public function batchForecasts(Request $request)
    {
        $products = Product::where('is_active', true)->get();
        $forecasts = [];

        foreach ($products as $product) {
            $forecast = $this->aiService->generateDemandForecast($product->id, 30);
            $forecasts[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'predicted_demand' => $forecast->predicted_demand,
                'confidence' => $forecast->confidence_score,
                'method' => $forecast->method,
            ];
        }

        usort($forecasts, fn($a, $b) => $b['predicted_demand'] <=> $a['predicted_demand']);

        return response()->json(['success' => true, 'data' => $forecasts, 'total' => count($forecasts)]);
    }

    public function priceOptimization(Request $request)
    {
        $validated = $request->validate(['product_id' => 'required|exists:products,id']);
        $opt = $this->aiService->generatePriceOptimization($validated['product_id']);

        return response()->json(['success' => true, 'data' => $opt], 201);
    }

    public function batchPriceOptimization()
    {
        $products = Product::where('is_active', true)->where('buy_price', '>', 0)->get();
        $results = [];

        foreach ($products as $product) {
            $opt = $this->aiService->generatePriceOptimization($product->id);
            if (abs($opt->suggested_price - $opt->current_price) > 0.01) {
                $results[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'current_price' => $opt->current_price,
                    'suggested_price' => $opt->suggested_price,
                    'current_margin' => $opt->current_margin,
                    'suggested_margin' => $opt->suggested_margin,
                    'reasoning' => $opt->reasoning,
                ];
            }
        }

        return response()->json(['success' => true, 'data' => $results, 'total' => count($results)]);
    }

    public function forecastHistory($productId)
    {
        $forecasts = DemandForecast::where('product_id', $productId)->orderBy('created_at', 'desc')->limit(10)->get();
        return response()->json(['success' => true, 'data' => $forecasts]);
    }
}
