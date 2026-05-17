<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PurchaseOrdersController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\PurchaseOrder::with(['supplier']);

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $pos = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $pos->items(),
            'meta' => [
                'current_page' => $pos->currentPage(),
                'per_page' => $pos->perPage(),
                'total' => $pos->total(),
                'last_page' => $pos->lastPage(),
            ],
        ]);
    }

    public function show($id)
    {
        $po = \App\Models\PurchaseOrder::with(['supplier', 'items.product', 'payments', 'creator'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $po,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'po_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_id' => 'required|exists:product_units,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $poNumber = 'PO' . date('Ymd') . str_pad(\App\Models\PurchaseOrder::count() + 1, 4, '0', STR_PAD_LEFT);
            $subtotal = collect($validated['items'])->sum(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            });
            $total = $subtotal - ($validated['discount'] ?? 0);

            $po = \App\Models\PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_id' => $validated['supplier_id'],
                'po_date' => $validated['po_date'],
                'subtotal' => $subtotal,
                'discount' => $validated['discount'] ?? 0,
                'tax' => 0,
                'total' => $total,
                'payment_status' => 'unpaid',
                'status' => 'ordered',
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                \App\Models\PurchaseItem::create([
                    'po_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Purchase order created successfully',
                'data' => $po,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase order: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function receive(Request $request, $id)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        try {
            $po = \App\Models\PurchaseOrder::findOrFail($id);
            
            if ($po->status === 'received') {
                return response()->json([
                    'success' => false,
                    'message' => 'Purchase order already received',
                ], 400);
            }

            foreach ($po->items as $item) {
                \App\Models\StockMovement::create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_id' => $item->unit_id,
                    'movement_type' => 'purchase',
                    'reference_id' => $po->id,
                    'reference_type' => 'purchase',
                    'notes' => "PO {$po->po_number}",
                    'created_by' => auth()->id(),
                ]);
            }

            $po->update([
                'status' => 'received',
                'notes' => ($po->notes ?? '') . " [Received: {$validated['notes']}]",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Purchase order received successfully',
                'data' => $po,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to receive purchase order: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $po = \App\Models\PurchaseOrder::findOrFail($id);
        $po->delete();

        return response()->json([
            'success' => true,
            'message' => 'Purchase order deleted successfully',
        ]);
    }
}
