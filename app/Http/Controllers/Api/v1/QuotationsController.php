<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\QuotationService;
use App\Models\Quotation;
use App\Models\SalesOrder;
use Illuminate\Http\Request;

class QuotationsController extends Controller
{
    private QuotationService $quotationService;

    public function __construct(QuotationService $quotationService)
    {
        $this->quotationService = $quotationService;
    }

    public function index(Request $request)
    {
        $query = Quotation::with(['customer', 'items.product']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->has('date_from')) {
            $query->where('quote_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('quote_date', '<=', $request->date_to);
        }
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('quote_no', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $quotations = $query->orderBy('quote_date', 'desc')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $quotations->items(),
            'meta' => [
                'current_page' => $quotations->currentPage(),
                'per_page' => $quotations->perPage(),
                'total' => $quotations->total(),
                'last_page' => $quotations->lastPage(),
            ],
        ]);
    }

    public function show($id)
    {
        $quotation = Quotation::with(['customer', 'items.product', 'items.product.units', 'creator'])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $quotation]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'quote_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:quote_date',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'delivery_address' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.bonus_qty' => 'nullable|numeric|min:0',
            'items.*.unit_id' => 'required|exists:product_units,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);

        try {
            $quotation = $this->quotationService->createQuotation($validated, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Quotation created successfully',
                'data' => $quotation->load(['customer', 'items.product']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,sent,accepted,rejected,expired,converted',
        ]);

        $quotation = $this->quotationService->updateStatus($id, $validated['status']);

        return response()->json([
            'success' => true,
            'message' => 'Quotation status updated',
            'data' => $quotation,
        ]);
    }

    public function convertToSalesOrder($id)
    {
        try {
            $so = $this->quotationService->convertToSalesOrder($id, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Quotation converted to Sales Order',
                'data' => $so->load(['customer', 'items.product']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
