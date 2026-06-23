<?php
namespace Tests\Feature\Api;

use Tests\TestCase;

class BankApiTest extends TestCase
{
    public function test_can_verify_payment(): void
    {
        $response = $this->actingAsUser('owner')->postJson('/api/v1/bank/verify-payment', [
            'transaction_id' => 'TXN-TEST-001',
            'amount' => 100000,
            'type' => 'sale',
        ]);
        // In manual mode, verification returns 422 (not configured)
        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_can_get_statements(): void
    {
        $response = $this->actingAsUser('owner')->getJson('/api/v1/bank/statements');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_bank_requires_permission(): void
    {
        $response = $this->actingAsUser('kasir')->getJson('/api/v1/bank/statements');
        $response->assertStatus(403);
    }
}
