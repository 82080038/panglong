<?php
namespace Tests\Feature\Api;

use Tests\TestCase;

class AccountingApiTest extends TestCase
{
    public function test_can_list_chart_of_accounts(): void
    {
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
        $response = $this->actingAsUser('owner')->getJson('/api/v1/accounting/chart-of-accounts');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_can_list_journal_entries(): void
    {
        $response = $this->actingAsUser('owner')->getJson('/api/v1/accounting/journal-entries');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_can_get_trial_balance(): void
    {
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
        $response = $this->actingAsUser('owner')->getJson('/api/v1/accounting/trial-balance');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_can_get_balance_sheet(): void
    {
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
        $response = $this->actingAsUser('owner')->getJson('/api/v1/accounting/balance-sheet');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_can_get_income_statement(): void
    {
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
        $response = $this->actingAsUser('owner')->getJson('/api/v1/accounting/income-statement');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_can_get_general_ledger(): void
    {
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
        $response = $this->actingAsUser('owner')->getJson('/api/v1/accounting/general-ledger');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_accounting_requires_permission(): void
    {
        $response = $this->actingAsUser('kasir')->getJson('/api/v1/accounting/chart-of-accounts');
        $response->assertStatus(403);
    }
}
