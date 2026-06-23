<?php
namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\StockMovement;
use Tests\TestCase;

class AIApiTest extends TestCase
{
    public function test_can_generate_demand_forecast(): void
    {
        $product = Product::factory()->create();
        $unit = ProductUnit::factory()->create(['product_id' => $product->id]);

        $response = $this->actingAsUser('owner')->postJson('/api/v1/ai/demand-forecast', [
            'product_id' => $product->id,
            'horizon_days' => 30,
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);
    }

    public function test_can_get_batch_forecasts(): void
    {
        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->actingAsUser('owner')->getJson('/api/v1/ai/demand-forecast/batch');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_can_generate_price_optimization(): void
    {
        $product = Product::factory()->create([
            'buy_price' => 50000,
            'sell_price' => 75000,
        ]);

        $response = $this->actingAsUser('owner')->postJson('/api/v1/ai/price-optimization', [
            'product_id' => $product->id,
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);
    }

    public function test_can_get_batch_price_optimization(): void
    {
        Product::factory()->create(['is_active' => true, 'buy_price' => 10000, 'sell_price' => 15000]);

        $response = $this->actingAsUser('owner')->getJson('/api/v1/ai/price-optimization/batch');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_ai_requires_permission(): void
    {
        $product = Product::factory()->create();
        $response = $this->actingAsUser('kasir')->postJson('/api/v1/ai/demand-forecast', [
            'product_id' => $product->id,
        ]);
        $response->assertStatus(403);
    }
}
