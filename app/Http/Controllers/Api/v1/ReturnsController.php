<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\ReturnService;
use App\Models\SalesReturn;
use App\Models\PurchaseReturn;
use Illuminate\Http\Request;

class ReturnsController extends Controller
{
    private ReturnService $returnService;

    public function __construct(ReturnService $returnService)
    {
        $this->returnService = $returnService;
    }

    public function salesIndex(Request $request)
    {
        $query = SalesReturn::with(['sale', 'customer', 'items.product']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('date_from')) {
            $query->where('return_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('return_date', '<=', $request->date_to);
        }
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('return_no', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $returns = $query->orderBy('return_date', 'desc')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $returns->items(),
            'meta' => [
                'current_page' => $returns->currentPage(),
                'per_page' => $returns->perPage(),
                'total' => $returns->total(),
                'last_page' => $returns->lastPage(),
            ],
        ]);
    }

    public function salesShow($id)
    {
        $return = SalesReturn::with(['sale', 'customer', 'items.product', 'items.saleItem', 'creator'])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $return]);
    }

    public function salesStore(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'return_date' => 'required|date',
            'refund_method' => 'nullable|in:cash,credit,transfer',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.reason' => 'nullable|string',
        ]);

        try {
            $return = $this->returnService->createSalesReturn(
                $validated['sale_id'],
                $validated,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Sales return created successfully',
                'data' => $return->load(['sale', 'customer', 'items.product']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function purchaseIndex(Request $request)
    {
        $query = PurchaseReturn::with(['purchaseOrder', 'supplier', 'items.product']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('date_from')) {
            $query->where('return_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('return_date', '<=', $request->date_to);
        }
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('return_no', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $returns = $query->orderBy('return_date', 'desc')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $returns->items(),
            'meta' => [
                'current_page' => $returns->currentPage(),
                'per_page' => $returns->perPage(),
                'total' => $returns->total(),
                'last_page' => $returns->lastPage(),
            ],
        ]);
    }

    public function purchaseShow($id)
    {
        $return = PurchaseReturn::with(['purchaseOrder', 'supplier', 'items.product', 'items.purchaseItem', 'creator'])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $return]);
    }

    public function purchaseStore(Request $request)
    {
        $validated = $request->validate([
            'po_id' => 'required|exists:purchase_orders,id',
            'return_date' => 'required|date',
            'refund_method' => 'nullable|in:cash,credit,transfer',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.purchase_item_id' => 'required|exists:purchase_items,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.reason' => 'nullable|string',
        ]);

        try {
            $return = $this->returnService->createPurchaseReturn(
                $validated['po_id'],
                $validated,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Purchase return created successfully',
                'data' => $return->load(['purchaseOrder', 'supplier', 'items.product']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
