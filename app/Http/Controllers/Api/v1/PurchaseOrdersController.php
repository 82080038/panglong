<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\AccountPayable;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrdersController extends Controller
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['supplier', 'items.product']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $pos = $query->orderBy('po_date', 'desc')->paginate($request->per_page ?? 15);

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
        $po = PurchaseOrder::with(['supplier', 'items.product', 'items.product.baseUnit', 'payments', 'accountsPayable'])->findOrFail($id);

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
            'payment_method' => 'nullable|in:cash,credit,transfer',
            'notes' => 'nullable|string',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $poNumber = 'PO' . date('Ymd') . str_pad(PurchaseOrder::count() + 1, 4, '0', STR_PAD_LEFT);
                $subtotal = collect($validated['items'])->sum(function ($item) {
                    return $item['quantity'] * $item['unit_price'];
                });
                $discount = $validated['discount'] ?? 0;
                $total = $subtotal - $discount;

                $paymentMethod = $validated['payment_method'] ?? 'credit';

                $po = PurchaseOrder::create([
                    'po_number' => $poNumber,
                    'supplier_id' => $validated['supplier_id'],
                    'po_date' => $validated['po_date'],
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'tax' => 0,
                    'total' => $total,
                    'payment_status' => $paymentMethod === 'cash' ? 'paid' : 'unpaid',
                    'status' => 'ordered',
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                foreach ($validated['items'] as $item) {
                    PurchaseItem::create([
                        'po_id' => $po->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'received_quantity' => 0,
                        'unit_id' => $item['unit_id'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $item['quantity'] * $item['unit_price'],
                    ]);
                }

                // Auto-create AP if credit
                if ($paymentMethod !== 'cash') {
                    $supplier = \App\Models\Supplier::find($validated['supplier_id']);
                    $terms = $supplier->payment_terms ?? 30;
                    AccountPayable::create([
                        'supplier_id' => $validated['supplier_id'],
                        'po_id' => $po->id,
                        'amount' => $total,
                        'balance' => $total,
                        'due_date' => date('Y-m-d', strtotime($validated['po_date'] . " +{$terms} days")),
                        'status' => 'pending',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Purchase order created successfully',
                    'data' => $po,
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Receive items (supports partial receive)
     */
    public function receive(Request $request, $id)
    {
        $validated = $request->validate([
            'items' => 'nullable|array',
            'items.*.purchase_item_id' => 'required_with:items|exists:purchase_items,id',
            'items.*.received_quantity' => 'required_with:items|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            return DB::transaction(function () use ($validated, $id) {
                $po = PurchaseOrder::with('items')->findOrFail($id);

                if ($po->status === 'received') {
                    return response()->json([
                        'success' => false,
                        'message' => 'PO already fully received',
                    ], 400);
                }

                $userId = auth()->id();

                // If no items specified, receive all
                if (empty($validated['items'])) {
                    foreach ($po->items as $item) {
                        $remaining = $item->quantity - $item->received_quantity;
                        if ($remaining > 0) {
                            StockMovement::create([
                                'product_id' => $item->product_id,
                                'quantity' => $remaining,
                                'unit_id' => $item->unit_id,
                                'movement_type' => 'purchase',
                                'reference_id' => $po->id,
                                'reference_type' => 'purchase_order',
                                'notes' => "PO received: {$po->po_number}",
                                'created_by' => $userId,
                            ]);
                            $item->update(['received_quantity' => $item->quantity]);
                        }
                    }
                } else {
                    foreach ($validated['items'] as $recvItem) {
                        $pi = PurchaseItem::findOrFail($recvItem['purchase_item_id']);
                        if ($pi->po_id != $po->id) continue;

                        $recvQty = (float) $recvItem['received_quantity'];
                        $newTotal = $pi->received_quantity + $recvQty;
                        if ($newTotal > $pi->quantity) {
                            $recvQty = $pi->quantity - $pi->received_quantity;
                            $newTotal = $pi->quantity;
                        }

                        if ($recvQty > 0) {
                            StockMovement::create([
                                'product_id' => $pi->product_id,
                                'quantity' => $recvQty,
                                'unit_id' => $pi->unit_id,
                                'movement_type' => 'purchase',
                                'reference_id' => $po->id,
                                'reference_type' => 'purchase_order',
                                'notes' => "PO partial receive: {$po->po_number}",
                                'created_by' => $userId,
                            ]);
                            $pi->update(['received_quantity' => $newTotal]);
                        }
                    }
                }

                // Check if all items fully received
                $allReceived = $po->items->every(fn($item) => $item->received_quantity >= $item->quantity);
                $po->update(['status' => $allReceived ? 'received' : 'partially_received']);

                return response()->json([
                    'success' => true,
                    'message' => $allReceived ? 'PO fully received' : 'PO partially received',
                    'data' => $po->fresh('items'),
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to receive: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Record payment for purchase order
     */
    public function payment(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,transfer,check',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        try {
            $payment = $this->paymentService->recordPurchasePayment($id, $validated, auth()->id());

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

    public function destroy($id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status === 'received' || $po->status === 'partially_received') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete received PO',
            ], 400);
        }

        $po->delete();

        return response()->json([
            'success' => true,
            'message' => 'Purchase order deleted successfully',
        ]);
    }
}
