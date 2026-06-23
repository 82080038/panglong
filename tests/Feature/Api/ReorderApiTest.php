<?php
namespace Tests\Feature\Api;

use Tests\TestCase;

class ReorderApiTest extends TestCase
{
    public function test_can_get_reorder_suggestions(): void
    {
        $response = $this->actingAsUser('owner')->getJson('/api/v1/reorder/suggestions');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_reorder_requires_permission(): void
    {
        $response = $this->actingAsUser('kasir')->getJson('/api/v1/reorder/suggestions');
        $response->assertStatus(403);
    }
}
