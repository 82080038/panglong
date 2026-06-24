<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductUnit;
use Tests\TestCase;

class SalesApiTest extends TestCase
{
    public function test_can_list_sales(): void
    {
        $response = $this->actingAsUser()->getJson('/api/v1/sales');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
            ]);
    }

    public function test_can_create_sale(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        $unit = ProductUnit::factory()->create(['product_id' => $product->id]);

        // Give product initial stock
        \App\Models\StockMovement::create([
            'product_id' => $product->id,
            'quantity' => 100,
            'unit_id' => $unit->id,
            'movement_type' => 'purchase',
            'reference_type' => 'test',
            'notes' => 'Test initial stock',
        ]);

        $saleData = [
            'customer_id' => $customer->id,
            'sale_date' => '2025-06-23',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'unit_id' => $unit->id,
                    'unit_price' => 10000,
                ],
            ],
            'payment_method' => 'cash',
        ];

        $response = $this->actingAsUser('owner')->postJson('/api/v1/sales', $saleData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Sale created successfully',
            ]);

        $this->assertDatabaseHas('sales', [
            'customer_id' => $customer->id,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
        ]);
    }

    public function test_create_sale_validation_fails_without_items(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAsUser('owner')->postJson('/api/v1/sales', [
            'customer_id' => $customer->id,
            'sale_date' => '2025-06-23',
            'items' => [],
            'payment_method' => 'cash',
        ]);

        $response->assertUnprocessable();
    }

    public function test_can_show_sale_detail(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        $unit = ProductUnit::factory()->create(['product_id' => $product->id]);

        // Give product initial stock
        \App\Models\StockMovement::create([
            'product_id' => $product->id,
            'quantity' => 100,
            'unit_id' => $unit->id,
            'movement_type' => 'purchase',
            'reference_type' => 'test',
            'notes' => 'Test initial stock',
        ]);

        $createResponse = $this->actingAsUser('owner')->postJson('/api/v1/sales', [
            'customer_id' => $customer->id,
            'sale_date' => '2025-06-23',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_id' => $unit->id,
                    'unit_price' => 5000,
                ],
            ],
            'payment_method' => 'cash',
        ]);

        $saleId = $createResponse->json('data.id');

        $response = $this->actingAsUser()->getJson("/api/v1/sales/{$saleId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_can_filter_sales_by_status(): void
    {
        $response = $this->actingAsUser()->getJson('/api/v1/sales?status=completed');

        $response->assertStatus(200);
    }

    public function test_sales_price_route_resolves_to_get_price_handler(): void
    {
        // Regression: GET /sales/price was shadowed by /sales/{id}.
        $product = Product::factory()->create();
        $unit = ProductUnit::factory()->create(['product_id' => $product->id]);

        $response = $this->actingAsUser()->getJson(
            "/api/v1/sales/price?product_id={$product->id}&unit_id={$unit->id}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data' => ['unit_price', 'margin']]);
    }

    public function test_create_sale_rejects_oversell(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        $unit = ProductUnit::factory()->create(['product_id' => $product->id]);

        \App\Models\StockMovement::create([
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_id' => $unit->id,
            'movement_type' => 'purchase',
            'reference_type' => 'test',
            'notes' => 'Test initial stock',
        ]);

        $response = $this->actingAsUser('owner')->postJson('/api/v1/sales', [
            'customer_id' => $customer->id,
            'sale_date' => '2025-06-23',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 50,
                    'unit_id' => $unit->id,
                    'unit_price' => 10000,
                ],
            ],
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false);

        $this->assertDatabaseMissing('sales', ['customer_id' => $customer->id]);
    }
}
