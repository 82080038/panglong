<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use Tests\TestCase;

class ProductsApiTest extends TestCase
{
    public function test_products_search_route_resolves_to_search_handler(): void
    {
        // Regression: GET /products/search was shadowed by /products/{id}.
        Product::factory()->create(['name' => 'Plywood Sheet 9mm']);

        $response = $this->actingAsUser()->getJson('/api/v1/products/search?q=Plywood');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data']);
    }
}
