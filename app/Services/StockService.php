<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockAdjustment;
use App\Models\StockOpname;
use App\Models\OpnameItem;
use Illuminate\Support\Facades\DB;
use Exception;

class StockService
{
    /**
     * Get current stock for product
     */
    public function getCurrentStock(int $productId): float
    {
        return StockMovement::where('product_id', $productId)->sum('quantity');
    }

    /**
     * Convert unit to base unit
     */
    public function convertToBaseUnit(int $productId, float $quantity, string $unitName): float
    {
        $product = Product::with('units')->findOrFail($productId);
        $unit = $product->units()->where('unit_name', $unitName)->firstOrFail();
        
        return $quantity * $unit->conversion_factor;
    }

    /**
     * Check if product has sufficient stock
     */
    public function validateStockAvailability(int $productId, float $quantity, int $unitId): bool
    {
        $currentStock = $this->getCurrentStock($productId);
        $unit = ProductUnit::findOrFail($unitId);
        $requiredQuantity = $quantity * $unit->conversion_factor;
        
        return $currentStock >= $requiredQuantity;
    }

    /**
     * Check if product is low stock
     */
    public function isLowStock(int $productId): bool
    {
        $product = Product::findOrFail($productId);
        $currentStock = $this->getCurrentStock($productId);
        
        return $currentStock < $product->min_stock;
    }

    /**
     * Check if product is overstock
     */
    public function isOverstock(int $productId): bool
    {
        $product = Product::findOrFail($productId);
        $currentStock = $this->getCurrentStock($productId);
        
        return $currentStock > $product->max_stock;
    }

    /**
     * Create stock adjustment
     */
    public function createAdjustment(array $data, int $userId): StockAdjustment
    {
        return StockAdjustment::create([
            'product_id' => $data['product_id'],
            'quantity' => $data['quantity'],
            'adjustment_type' => $data['adjustment_type'],
            'reason' => $data['reason'],
            'created_by' => $userId,
        ]);
    }

    /**
     * Approve stock adjustment
     */
    public function approveAdjustment(int $adjustmentId, int $approverId): bool
    {
        return DB::transaction(function () use ($adjustmentId, $approverId) {
            $adjustment = StockAdjustment::findOrFail($adjustmentId);
            
            if ($adjustment->approved_at) {
                throw new Exception('Adjustment already approved');
            }

            // Create stock movement
            StockMovement::create([
                'product_id' => $adjustment->product_id,
                'quantity' => $adjustment->quantity,
                'unit_id' => Product::findOrFail($adjustment->product_id)->base_unit_id,
                'movement_type' => 'adjustment',
                'reference_id' => $adjustment->id,
                'reference_type' => 'adjustment',
                'notes' => $adjustment->reason,
                'created_by' => $adjustment->created_by,
            ]);

            // Update adjustment status
            $adjustment->update([
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * Create stock opname
     */
    public function createOpname(array $data, int $userId): StockOpname
    {
        return DB::transaction(function () use ($data, $userId) {
            $opname = StockOpname::create([
                'opname_date' => $data['opname_date'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $systemQty = $this->getCurrentStock($item['product_id']);
                $difference = $item['physical_qty'] - $systemQty;

                OpnameItem::create([
                    'opname_id' => $opname->id,
                    'product_id' => $item['product_id'],
                    'system_qty' => $systemQty,
                    'physical_qty' => $item['physical_qty'],
                    'difference' => $difference,
                ]);
            }

            return $opname;
        });
    }

    /**
     * Approve stock opname and create adjustments
     */
    public function approveOpname(int $opnameId, int $approverId): bool
    {
        return DB::transaction(function () use ($opnameId, $approverId) {
            $opname = StockOpname::with('items')->findOrFail($opnameId);
            
            if ($opname->approved_at) {
                throw new Exception('Opname already approved');
            }

            $adjustmentsCreated = 0;

            foreach ($opname->items as $item) {
                if ($item->difference != 0) {
                    StockMovement::create([
                        'product_id' => $item->product_id,
                        'quantity' => $item->difference,
                        'unit_id' => Product::findOrFail($item->product_id)->base_unit_id,
                        'movement_type' => 'opname',
                        'reference_id' => $opname->id,
                        'reference_type' => 'opname',
                        'notes' => "Stock opname adjustment",
                        'created_by' => $opname->created_by,
                    ]);

                    $adjustmentsCreated++;
                }
            }

            $opname->update([
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            return true;
        });
    }
}
