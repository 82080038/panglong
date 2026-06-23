<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\Sale;
use Illuminate\Http\Request;

class DeliveriesController extends Controller
{
    public function index(Request $request)
    {
        $query = Delivery::with(['sale', 'items.product']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('date_from')) {
            $query->where('delivery_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('delivery_date', '<=', $request->date_to);
        }
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('delivery_no', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('driver_name', 'like', "%{$search}%");
            });
        }

        $deliveries = $query->orderBy('delivery_date', 'desc')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $deliveries->items(),
            'meta' => [
                'current_page' => $deliveries->currentPage(),
                'per_page' => $deliveries->perPage(),
                'total' => $deliveries->total(),
                'last_page' => $deliveries->lastPage(),
            ],
        ]);
    }

    public function show($id)
    {
        $delivery = Delivery::with(['sale', 'items.product', 'items.saleItem'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $delivery,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'nullable|exists:sales,id',
            'customer_name' => 'required|string|max:255',
            'delivery_address' => 'nullable|string',
            'origin_address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'delivery_date' => 'required|date',
            'delivery_time' => 'nullable|string',
            'driver_name' => 'nullable|string|max:100',
            'vehicle_plate' => 'nullable|string|max:20',
            'delivery_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|numeric|min:0.001',
            'items.*.unit_id' => 'nullable|exists:product_units,id',
            'items.*.sale_item_id' => 'nullable|exists:sale_items,id',
        ]);

        $deliveryNo = 'DO' . date('Ymd') . str_pad(Delivery::count() + 1, 4, '0', STR_PAD_LEFT);

        $delivery = Delivery::create([
            'delivery_no' => $deliveryNo,
            'sale_id' => $validated['sale_id'] ?? null,
            'customer_name' => $validated['customer_name'],
            'delivery_address' => $validated['delivery_address'] ?? null,
            'origin_address' => $validated['origin_address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'delivery_date' => $validated['delivery_date'],
            'delivery_time' => $validated['delivery_time'] ?? null,
            'driver_name' => $validated['driver_name'] ?? null,
            'vehicle_plate' => $validated['vehicle_plate'] ?? null,
            'delivery_cost' => $validated['delivery_cost'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        // Auto-fill items from sale if sale_id provided and no items
        if (!empty($validated['sale_id']) && empty($validated['items'])) {
            $sale = Sale::with('items')->find($validated['sale_id']);
            if ($sale) {
                foreach ($sale->items as $item) {
                    DeliveryItem::create([
                        'delivery_id' => $delivery->id,
                        'sale_item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'unit_id' => $item->unit_id,
                    ]);
                }
            }
        } elseif (!empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                DeliveryItem::create([
                    'delivery_id' => $delivery->id,
                    'sale_item_id' => $item['sale_item_id'] ?? null,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'] ?? null,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Delivery created successfully',
            'data' => $delivery->load('items'),
        ], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,loaded,in_transit,delivered,failed',
            'delivery_proof' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $delivery = Delivery::findOrFail($id);
        $updateData = ['status' => $validated['status']];

        if ($validated['status'] === 'delivered') {
            $updateData['delivered_at'] = now();
        }
        if (!empty($validated['delivery_proof'])) {
            $updateData['delivery_proof'] = $validated['delivery_proof'];
        }
        if (!empty($validated['notes'])) {
            $updateData['notes'] = $validated['notes'];
        }

        $delivery->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Delivery status updated',
            'data' => $delivery,
        ]);
    }

    public function destroy($id)
    {
        $delivery = Delivery::findOrFail($id);
        $delivery->delete();

        return response()->json([
            'success' => true,
            'message' => 'Delivery deleted successfully',
        ]);
    }
}
