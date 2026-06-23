<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\StockTransfer;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehousesController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => Warehouse::where('is_active', true)->get()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:warehouses,code',
            'name' => 'required|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);
        $warehouse = Warehouse::create($validated + ['is_active' => true]);
        return response()->json(['success' => true, 'data' => $warehouse], 201);
    }

    public function stockByWarehouse($warehouseId)
    {
        $products = \App\Models\Product::where('is_active', true)->get();
        $stockData = [];
        foreach ($products as $product) {
            $stock = StockMovement::where('product_id', $product->id)
                ->where('warehouse_id', $warehouseId)
                ->sum('quantity');
            if ($stock != 0) {
                $stockData[] = [
                    'product_id' => $product->id,
                    'product_code' => $product->code,
                    'product_name' => $product->name,
                    'stock' => $stock,
                ];
            }
        }
        return response()->json(['success' => true, 'data' => $stockData]);
    }

    public function createTransfer(Request $request)
    {
        $validated = $request->validate([
            'transfer_date' => 'required|date',
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $transferNo = 'TRF-' . date('YmdHis');
            $transfer = StockTransfer::create([
                'transfer_no' => $transferNo,
                'transfer_date' => $validated['transfer_date'],
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'status' => 'in_transit',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['items'] as $item) {
                $transfer->items()->create($item);
                // Out from source warehouse
                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $validated['from_warehouse_id'],
                    'quantity' => -$item['quantity'],
                    'movement_type' => 'transfer_out',
                    'reference_id' => $transfer->id,
                    'reference_type' => 'stock_transfer',
                    'notes' => 'Transfer out: ' . $transferNo,
                    'created_by' => $request->user()->id,
                ]);
                // In to destination warehouse
                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $validated['to_warehouse_id'],
                    'quantity' => $item['quantity'],
                    'movement_type' => 'transfer_in',
                    'reference_id' => $transfer->id,
                    'reference_type' => 'stock_transfer',
                    'notes' => 'Transfer in: ' . $transferNo,
                    'created_by' => $request->user()->id,
                ]);
            }

            $transfer->update(['status' => 'received']);
            return response()->json(['success' => true, 'data' => $transfer->load('items.product')], 201);
        });
    }

    public function transfers()
    {
        $transfers = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'items.product'])->orderBy('created_at', 'desc')->paginate(20);
        return response()->json(['success' => true, 'data' => $transfers]);
    }
}
