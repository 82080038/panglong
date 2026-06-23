<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request)
    {
        $query = \App\Models\Product::with(['category', 'baseUnit']);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%')
                  ->orWhere('brand', 'like', '%' . $request->search . '%');
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('low_stock')) {
            $products = $query->get()->filter(function ($product) {
                return $product->current_stock < $product->min_stock;
            });

            return response()->json([
                'success' => true,
                'data' => $products->values(),
                'meta' => [
                    'total' => $products->count(),
                ],
            ]);
        }

        $products = $query->orderBy('name')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    public function show($id)
    {
        $product = \App\Models\Product::with(['category', 'units', 'barcodes', 'baseUnit'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:products',
            'name' => 'required|string|max:255',
            'alias' => 'nullable|array',
            'category_id' => 'nullable|exists:categories,id',
            'brand' => 'nullable|string|max:100',
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:50',
            'buy_price' => 'nullable|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
            'units' => 'required|array|min:1',
            'units.*.unit_name' => 'required|string',
            'units.*.conversion_factor' => 'required|numeric|min:0',
            'units.*.is_base_unit' => 'boolean',
            'units.*.price_per_unit' => 'nullable|numeric|min:0',
            'barcodes' => 'nullable|array',
            'barcodes.*.barcode' => 'required|string',
            'barcodes.*.unit_id' => 'nullable|exists:product_units,id',
            'barcodes.*.is_primary' => 'boolean',
        ]);

        try {
            $product = $this->productService->createProduct($validated);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'alias' => 'sometimes|array',
            'category_id' => 'sometimes|exists:categories,id',
            'brand' => 'sometimes|string|max:100',
            'min_stock' => 'sometimes|numeric|min:0',
            'max_stock' => 'sometimes|numeric|min:0',
            'location' => 'sometimes|string|max:50',
            'buy_price' => 'sometimes|numeric|min:0',
            'sell_price' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $product = $this->productService->updateProduct($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $product = \App\Models\Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->input('q');
        $limit = $request->input('limit', 10);

        $products = $this->productService->searchProducts($query, $limit);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }
}
