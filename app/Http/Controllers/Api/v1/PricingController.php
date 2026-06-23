<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\CustomerProductPrice;
use App\Models\ProductTierPrice;
use App\Models\SupplierPriceHistory;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function customerPrices(Request $request)
    {
        $query = CustomerProductPrice::with(['customer', 'product', 'unit']);

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $prices = $query->orderBy('id', 'desc')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $prices->items(),
            'meta' => [
                'current_page' => $prices->currentPage(),
                'per_page' => $prices->perPage(),
                'total' => $prices->total(),
                'last_page' => $prices->lastPage(),
            ],
        ]);
    }

    public function storeCustomerPrice(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'unit_id' => 'required|exists:product_units,id',
            'custom_price' => 'required|numeric|min:0',
            'min_qty' => 'nullable|numeric|min:0.001',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $price = CustomerProductPrice::updateOrCreate(
            [
                'customer_id' => $validated['customer_id'],
                'product_id' => $validated['product_id'],
                'unit_id' => $validated['unit_id'],
            ],
            [
                'custom_price' => $validated['custom_price'],
                'min_qty' => $validated['min_qty'] ?? 1,
                'is_active' => $validated['is_active'] ?? true,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Customer price saved successfully',
            'data' => $price->load(['customer', 'product', 'unit']),
        ], 201);
    }

    public function destroyCustomerPrice($id)
    {
        $price = CustomerProductPrice::findOrFail($id);
        $price->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer price deleted',
        ]);
    }

    public function tierPrices(Request $request)
    {
        $query = ProductTierPrice::with(['product', 'unit']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $prices = $query->orderBy('min_qty', 'asc')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $prices->items(),
            'meta' => [
                'current_page' => $prices->currentPage(),
                'per_page' => $prices->perPage(),
                'total' => $prices->total(),
                'last_page' => $prices->lastPage(),
            ],
        ]);
    }

    public function storeTierPrice(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'unit_id' => 'required|exists:product_units,id',
            'min_qty' => 'required|numeric|min:0.001',
            'max_qty' => 'nullable|numeric|min:0.001',
            'unit_price' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $price = ProductTierPrice::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tier price created successfully',
            'data' => $price->load(['product', 'unit']),
        ], 201);
    }

    public function destroyTierPrice($id)
    {
        $price = ProductTierPrice::findOrFail($id);
        $price->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tier price deleted',
        ]);
    }

    public function supplierPriceHistory(Request $request)
    {
        $query = SupplierPriceHistory::with(['supplier', 'product', 'unit']);

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $history = $query->orderBy('effective_date', 'desc')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $history->items(),
            'meta' => [
                'current_page' => $history->currentPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
                'last_page' => $history->lastPage(),
            ],
        ]);
    }
}
