<?php
namespace Tests\Feature\Api;

use Tests\TestCase;

class MarketplaceApiTest extends TestCase
{
    public function test_can_list_marketplace_integrations(): void
    {
        $response = $this->actingAsUser('owner')->getJson('/api/v1/marketplace');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_can_connect_marketplace(): void
    {
        $response = $this->actingAsUser('owner')->postJson('/api/v1/marketplace/connect', [
            'platform' => 'tokopedia',
            'shop_id' => 'shop123',
            'shop_name' => 'Test Shop',
        ]);
        $response->assertStatus(201)->assertJsonPath('success', true);
    }

    public function test_marketplace_requires_permission(): void
    {
        $response = $this->actingAsUser('kasir')->getJson('/api/v1/marketplace');
        $response->assertStatus(403);
    }
}
