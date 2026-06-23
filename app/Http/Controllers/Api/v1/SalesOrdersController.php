<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\QuotationService;
use App\Models\SalesOrder;
use Illuminate\Http\Request;

class SalesOrdersController extends Controller
{
    private QuotationService $quotationService;

    public function __construct(QuotationService $quotationService)
    {
        $this->quotationService = $quotationService;
    }

    public function index(Request $request)
    {
        $query = SalesOrder::with(['customer', 'items.product', 'quotation']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->has('date_from')) {
            $query->where('order_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('order_date', '<=', $request->date_to);
        }
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('so_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $orders = $query->orderBy('order_date', 'desc')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    public function show($id)
    {
        $so = SalesOrder::with(['customer', 'items.product', 'items.product.units', 'quotation', 'sale', 'creator'])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $so]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,confirmed,processing,delivered,invoiced,cancelled',
        ]);

        $so = SalesOrder::findOrFail($id);
        $so->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Sales order status updated',
            'data' => $so,
        ]);
    }

    public function convertToInvoice($id)
    {
        try {
            $sale = $this->quotationService->convertToInvoice($id, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Sales order converted to invoice',
                'data' => $sale->load(['customer', 'items.product']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
