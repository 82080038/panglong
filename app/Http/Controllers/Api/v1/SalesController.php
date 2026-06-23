<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\SaleService;
use App\Services\PricingService;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    private SaleService $saleService;
    private PricingService $pricingService;

    public function __construct(SaleService $saleService, PricingService $pricingService)
    {
        $this->saleService = $saleService;
        $this->pricingService = $pricingService;
    }

    public function index(Request $request)
    {
        $query = \App\Models\Sale::with(['customer', 'items.product', 'payments']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('date_from')) {
            $query->where('sale_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('sale_date', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhere('customer_name_snapshot', 'like', "%{$search}%");
            });
        }

        $sales = $query->orderBy('sale_date', 'desc')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $sales->items(),
            'meta' => [
                'current_page' => $sales->currentPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
                'last_page' => $sales->lastPage(),
            ],
        ]);
    }

    public function show($id)
    {
        $sale = \App\Models\Sale::with(['customer', 'items.product', 'items.product.baseUnit', 'payments', 'deliveries'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $sale,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'sale_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_id' => 'required|exists:product_units,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,credit,transfer',
            'notes' => 'nullable|string',
            'delivery_address' => 'nullable|string',
        ]);

        try {
            $sale = $this->saleService->createSale($validated, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Sale created successfully',
                'data' => $sale->load(['customer', 'items.product']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function update(Request $request, $id)
    {
        $sale = \App\Models\Sale::findOrFail($id);

        if ($sale->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update completed sale',
            ], 400);
        }

        $validated = $request->validate([
            'customer_id' => 'sometimes|nullable|exists:customers,id',
            'items' => 'sometimes|array',
            'discount' => 'sometimes|numeric|min:0',
            'payment_method' => 'sometimes|in:cash,credit,transfer',
            'notes' => 'sometimes|string',
        ]);

        $sale->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Sale updated successfully',
            'data' => $sale,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        try {
            $this->saleService->voidSale($id, $validated['reason'], auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Sale voided successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function payment(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,transfer,check',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        try {
            $payment = $this->saleService->recordSalePayment($id, $validated, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'data' => $payment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get price for a product+unit+customer combination
     */
    public function getPrice(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'unit_id' => 'required|exists:product_units,id',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $price = $this->pricingService->getUnitPrice(
            $validated['product_id'],
            $validated['unit_id'],
            $validated['customer_id'] ?? null
        );

        $margin = $this->pricingService->checkMargin($validated['product_id'], $price);

        return response()->json([
            'success' => true,
            'data' => [
                'unit_price' => $price,
                'margin' => $margin,
            ],
        ]);
    }
}
