<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Barcode;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class ProductService
{
    /**
     * Create product with units and barcodes
     */
    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'alias' => $data['alias'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'brand' => $data['brand'] ?? null,
                'min_stock' => $data['min_stock'] ?? 0,
                'max_stock' => $data['max_stock'] ?? 0,
                'location' => $data['location'] ?? null,
                'buy_price' => $data['buy_price'] ?? 0,
                'sell_price' => $data['sell_price'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Create units
            if (isset($data['units'])) {
                foreach ($data['units'] as $unitData) {
                    $unit = ProductUnit::create([
                        'product_id' => $product->id,
                        'unit_name' => $unitData['unit_name'],
                        'conversion_factor' => $unitData['conversion_factor'],
                        'is_base_unit' => $unitData['is_base_unit'] ?? false,
                        'price_per_unit' => $unitData['price_per_unit'] ?? 0,
                    ]);

                    if ($unitData['is_base_unit']) {
                        // base_unit_id removed - is_base_unit flag on product_units is used instead
                    }
                }
            }

            // Create barcodes
            if (isset($data['barcodes'])) {
                foreach ($data['barcodes'] as $barcodeData) {
                    Barcode::create([
                        'product_id' => $product->id,
                        'unit_id' => $barcodeData['unit_id'] ?? null,
                        'barcode' => $barcodeData['barcode'],
                        'is_primary' => $barcodeData['is_primary'] ?? false,
                    ]);
                }
            }

            return $product;
        });
    }

    /**
     * Update product
     */
    public function updateProduct(int $productId, array $data): Product
    {
        $product = Product::findOrFail($productId);
        
        $product->update([
            'name' => $data['name'] ?? $product->name,
            'alias' => $data['alias'] ?? $product->alias,
            'category_id' => $data['category_id'] ?? $product->category_id,
            'brand' => $data['brand'] ?? $product->brand,
            'min_stock' => $data['min_stock'] ?? $product->min_stock,
            'max_stock' => $data['max_stock'] ?? $product->max_stock,
            'location' => $data['location'] ?? $product->location,
            'buy_price' => $data['buy_price'] ?? $product->buy_price,
            'sell_price' => $data['sell_price'] ?? $product->sell_price,
            'is_active' => $data['is_active'] ?? $product->is_active,
        ]);

        return $product;
    }

    /**
     * Search products by keyword
     */
    public function searchProducts(string $query, int $limit = 10)
    {
        return Product::where('name', 'like', "%{$query}%")
                     ->orWhere('code', 'like', "%{$query}%")
                     ->orWhere('brand', 'like', "%{$query}%")
                     ->orWhereJsonContains('alias', $query)
                     ->limit($limit)
                     ->get();
    }

    /**
     * Get low stock products
     */
    public function getLowStockProducts()
    {
        $stockService = app(StockService::class);
        return Product::where('is_active', true)
                     ->get()
                     ->filter(function ($product) use ($stockService) {
                         return $stockService->getCurrentStock($product->id) < $product->min_stock;
                     });
    }
}
