<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\SaleService;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    private SaleService $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    public function index(Request $request)
    {
        $query = \App\Models\Sale::with(['customer', 'items.product']);

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('sale_date', [$request->from_date, $request->to_date]);
        }

        if ($request->has('search')) {
            $query->where('invoice_no', 'like', '%' . $request->search . '%')
                  ->orWhereHas('customer', function ($q) use ($request) {
                      $q->where('name', 'like', '%' . $request->search . '%');
                  });
        }

        $sales = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15);

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
        $sale = \App\Models\Sale::with(['customer', 'items.product', 'payments', 'creator'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $sale,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sale_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_id' => 'required|exists:product_units,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,credit,transfer',
            'notes' => 'nullable|string',
        ]);

        try {
            $sale = $this->saleService->createSale($validated, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Sale created successfully',
                'data' => $sale,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create sale: ' . $e->getMessage(),
            ], 500);
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
            'customer_id' => 'sometimes|exists:customers,id',
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
                'message' => 'Failed to void sale: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function payment(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
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
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment: ' . $e->getMessage(),
            ], 500);
        }
    }
}
