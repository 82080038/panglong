<?php
namespace Tests\Feature\Api;

use Tests\TestCase;

class IoTApiTest extends TestCase
{
    public function test_can_list_sensors(): void
    {
        $response = $this->actingAsUser('owner')->getJson('/api/v1/iot/sensors');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_can_register_sensor(): void
    {
        $response = $this->actingAsUser('owner')->postJson('/api/v1/iot/sensors', [
            'sensor_id' => 'TEMP-TEST-' . uniqid(),
            'name' => 'Test Temperature Sensor',
            'type' => 'temperature',
            'location' => 'Warehouse A',
        ]);
        $response->assertStatus(201)->assertJsonPath('success', true);
    }

    public function test_can_get_alerts(): void
    {
        $response = $this->actingAsUser('owner')->getJson('/api/v1/iot/alerts');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }
}
