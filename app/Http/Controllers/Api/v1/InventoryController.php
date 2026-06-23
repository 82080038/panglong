<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\StockService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    private StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        $query = \App\Models\Product::with(['category', 'baseUnit']);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
        }

        $products = $query->where('is_active', true)->orderBy('name')->paginate($request->per_page ?? 15);

        $stockData = [];
        foreach ($products->items() as $product) {
            $currentStock = $this->stockService->getCurrentStock($product->id);
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
                'base_unit' => $product->baseUnit->unit_name ?? 'pcs',
                'min_stock' => $product->min_stock,
                'max_stock' => $product->max_stock,
                'status' => $status,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $stockData,
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    public function show($productId)
    {
        $product = \App\Models\Product::with(['category', 'baseUnit'])->findOrFail($productId);
        $currentStock = $this->stockService->getCurrentStock($productId);

        $movements = \App\Models\StockMovement::where('product_id', $productId)
            ->with(['creator'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'product' => $product,
                'current_stock' => $currentStock,
                'movements' => $movements->items(),
            ],
        ]);
    }

    public function adjustment(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric',
            'adjustment_type' => 'required|in:physical_count,damage,loss,theft,correction',
            'reason' => 'required|string',
        ]);

        try {
            $adjustment = $this->stockService->createAdjustment($validated, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment created successfully',
                'data' => $adjustment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create adjustment. Please check your input.',
            ], 500);
        }
    }

    public function approveAdjustment($id)
    {
        try {
            $this->stockService->approveAdjustment($id, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment approved',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve adjustment.',
            ], 500);
        }
    }

    public function opname(Request $request)
    {
        $validated = $request->validate([
            'opname_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.physical_qty' => 'required|numeric',
        ]);

        try {
            $opname = $this->stockService->createOpname($validated, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Stock opname created successfully',
                'data' => $opname,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create stock opname. Please check your input.',
            ], 500);
        }
    }

    public function approveOpname($id)
    {
        try {
            $this->stockService->approveOpname($id, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Stock opname approved and adjustments created',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve stock opname.',
            ], 500);
        }
    }
}
