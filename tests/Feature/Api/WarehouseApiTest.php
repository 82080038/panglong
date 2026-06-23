<?php
namespace Tests\Feature\Api;

use Tests\TestCase;

class WarehouseApiTest extends TestCase
{
    public function test_can_list_warehouses(): void
    {
        $this->seed(\Database\Seeders\WarehouseSeeder::class);
        $response = $this->actingAsUser('owner')->getJson('/api/v1/warehouses');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_can_create_warehouse(): void
    {
        $response = $this->actingAsUser('owner')->postJson('/api/v1/warehouses', [
            'code' => 'WH-TEST',
            'name' => 'Test Warehouse',
            'address' => 'Test Address',
            'phone' => '021-999',
        ]);
        $response->assertStatus(201)->assertJsonPath('success', true);
    }

    public function test_can_list_transfers(): void
    {
        $response = $this->actingAsUser('owner')->getJson('/api/v1/warehouses/transfers');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_warehouse_requires_permission(): void
    {
        $response = $this->actingAsUser('kasir')->getJson('/api/v1/warehouses');
        $response->assertStatus(403);
    }
}
