<?php

namespace Tests\Feature\Services;

use App\Services\StockService;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\StockMovement;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    private StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stockService = app(StockService::class);
    }

    public function test_get_current_stock_returns_zero_for_new_product(): void
    {
        $product = Product::factory()->create();

        $stock = $this->stockService->getCurrentStock($product->id);

        $this->assertEquals(0, $stock);
    }

    public function test_get_current_stock_calculates_from_movements(): void
    {
        $product = Product::factory()->create();
        $unit = ProductUnit::factory()->create(['product_id' => $product->id]);

        StockMovement::create([
            'product_id' => $product->id,
            'quantity' => 100,
            'unit_id' => $unit->id,
            'movement_type' => 'purchase',
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'quantity' => -30,
            'unit_id' => $unit->id,
            'movement_type' => 'sale',
        ]);

        $stock = $this->stockService->getCurrentStock($product->id);

        $this->assertEquals(70, $stock);
    }

    public function test_is_low_stock_returns_true_when_below_min(): void
    {
        $product = Product::factory()->create(['min_stock' => 50]);
        $unit = ProductUnit::factory()->create(['product_id' => $product->id]);

        StockMovement::create([
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_id' => $unit->id,
            'movement_type' => 'purchase',
        ]);

        $this->assertTrue($this->stockService->isLowStock($product->id));
    }

    public function test_is_low_stock_returns_false_when_above_min(): void
    {
        $product = Product::factory()->create(['min_stock' => 50]);
        $unit = ProductUnit::factory()->create(['product_id' => $product->id]);

        StockMovement::create([
            'product_id' => $product->id,
            'quantity' => 100,
            'unit_id' => $unit->id,
            'movement_type' => 'purchase',
        ]);

        $this->assertFalse($this->stockService->isLowStock($product->id));
    }

    public function test_is_overstock_returns_true_when_above_max(): void
    {
        $product = Product::factory()->create(['max_stock' => 100]);
        $unit = ProductUnit::factory()->create(['product_id' => $product->id]);

        StockMovement::create([
            'product_id' => $product->id,
            'quantity' => 150,
            'unit_id' => $unit->id,
            'movement_type' => 'purchase',
        ]);

        $this->assertTrue($this->stockService->isOverstock($product->id));
    }

    public function test_convert_to_base_unit(): void
    {
        $product = Product::factory()->create();
        ProductUnit::factory()->create([
            'product_id' => $product->id,
            'unit_name' => 'box',
            'conversion_factor' => 12,
            'is_base_unit' => false,
        ]);

        $result = $this->stockService->convertToBaseUnit($product->id, 5, 'box');

        $this->assertEquals(60, $result);
    }

    public function test_create_adjustment_sets_status_pending(): void
    {
        $product = Product::factory()->create();
        $user = \App\Models\User::factory()->create();

        $adjustment = $this->stockService->createAdjustment([
            'product_id' => $product->id,
            'quantity' => 10,
            'adjustment_type' => 'correction',
            'reason' => 'Test correction',
        ], $user->id);

        $this->assertEquals('pending', $adjustment->status);
        $this->assertEquals($product->id, $adjustment->product_id);
    }
}
