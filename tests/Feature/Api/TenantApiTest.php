<?php
namespace Tests\Feature\Api;

use Tests\TestCase;

class TenantApiTest extends TestCase
{
    public function test_can_list_subscription_plans(): void
    {
        $this->seed(\Database\Seeders\SubscriptionPlansSeeder::class);
        $response = $this->actingAsUser()->getJson('/api/v1/subscription-plans');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_can_list_tenants(): void
    {
        $this->seed(\Database\Seeders\SubscriptionPlansSeeder::class);
        $response = $this->actingAsUser('owner')->getJson('/api/v1/tenants');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_can_create_tenant(): void
    {
        $this->seed(\Database\Seeders\SubscriptionPlansSeeder::class);
        $response = $this->actingAsUser('owner')->postJson('/api/v1/tenants', [
            'name' => 'Test Tenant Co',
            'subdomain' => 'testco' . uniqid(),
            'company_name' => 'Test Co Ltd',
            'plan_code' => 'STARTER',
        ]);
        $response->assertStatus(201)->assertJsonPath('success', true);
    }

    public function test_tenant_requires_permission(): void
    {
        $response = $this->actingAsUser('kasir')->getJson('/api/v1/tenants');
        $response->assertStatus(403);
    }
}
