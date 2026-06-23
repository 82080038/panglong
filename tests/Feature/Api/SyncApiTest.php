<?php
namespace Tests\Feature\Api;

use Tests\TestCase;

class SyncApiTest extends TestCase
{
    public function test_can_get_sync_status(): void
    {
        $response = $this->actingAsUser()->getJson('/api/v1/sync/status');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_can_pull_changes(): void
    {
        $response = $this->actingAsUser()->getJson('/api/v1/sync/pull?last_sync_at=2025-01-01');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_can_push_empty_changes(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/sync/push', [
            'device_id' => 'test-device-001',
            'changes' => [
                [
                    'entity_type' => 'Product',
                    'entity_id' => null,
                    'action' => 'create',
                    'payload' => ['name' => 'Test Product', 'code' => 'TST001'],
                ],
            ],
        ]);
        $response->assertStatus(200)->assertJsonPath('success', true);
    }
}
