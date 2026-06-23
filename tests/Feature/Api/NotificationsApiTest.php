<?php
namespace Tests\Feature\Api;

use Tests\TestCase;

class NotificationsApiTest extends TestCase
{
    public function test_notifications_require_permission(): void
    {
        $response = $this->actingAsUser('kasir')->postJson('/api/v1/notifications/ar-due-reminders');
        $response->assertStatus(403);
    }

    public function test_can_send_ar_due_reminders(): void
    {
        $response = $this->actingAsUser('owner')->postJson('/api/v1/notifications/ar-due-reminders');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_can_send_ap_due_reminders(): void
    {
        $response = $this->actingAsUser('owner')->postJson('/api/v1/notifications/ap-due-reminders');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }
}
