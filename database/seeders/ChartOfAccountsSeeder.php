<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChartOfAccount;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // Assets
            ['code' => '1000', 'name' => 'Cash', 'type' => 'asset', 'subtype' => 'current_asset'],
            ['code' => '1010', 'name' => 'Cash on Hand', 'type' => 'asset', 'subtype' => 'current_asset', 'parent_code' => '1000'],
            ['code' => '1020', 'name' => 'Bank Transfer', 'type' => 'asset', 'subtype' => 'current_asset', 'parent_code' => '1000'],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => 'asset', 'subtype' => 'current_asset'],
            ['code' => '1200', 'name' => 'Inventory', 'type' => 'asset', 'subtype' => 'current_asset'],
            ['code' => '1500', 'name' => 'Fixed Assets', 'type' => 'asset', 'subtype' => 'fixed_asset'],
            // Liabilities
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'subtype' => 'current_liability'],
            ['code' => '2100', 'name' => 'VAT Payable (PPN)', 'type' => 'liability', 'subtype' => 'current_liability'],
            ['code' => '2200', 'name' => 'Short-term Loans', 'type' => 'liability', 'subtype' => 'current_liability'],
            // Equity
            ['code' => '3000', 'name' => 'Owner Capital', 'type' => 'equity', 'subtype' => 'capital'],
            ['code' => '3100', 'name' => 'Retained Earnings', 'type' => 'equity', 'subtype' => 'retained_earnings'],
            // Revenue
            ['code' => '4000', 'name' => 'Sales Revenue', 'type' => 'revenue', 'subtype' => 'sales_revenue'],
            ['code' => '4100', 'name' => 'Other Revenue', 'type' => 'revenue', 'subtype' => 'other_revenue'],
            // Expenses
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'subtype' => 'cogs'],
            ['code' => '6000', 'name' => 'Operating Expenses', 'type' => 'expense', 'subtype' => 'operating_expense'],
            ['code' => '6100', 'name' => 'Salary Expense', 'type' => 'expense', 'subtype' => 'operating_expense', 'parent_code' => '6000'],
            ['code' => '6200', 'name' => 'Rent Expense', 'type' => 'expense', 'subtype' => 'operating_expense', 'parent_code' => '6000'],
            ['code' => '6300', 'name' => 'Utility Expense', 'type' => 'expense', 'subtype' => 'operating_expense', 'parent_code' => '6000'],
        ];

        $codeToId = [];
        foreach ($accounts as $acc) {
            $parentId = null;
            if (isset($acc['parent_code'])) {
                $parentId = $codeToId[$acc['parent_code']] ?? null;
            }
            $record = ChartOfAccount::create([
                'code' => $acc['code'],
                'name' => $acc['name'],
                'type' => $acc['type'],
                'subtype' => $acc['subtype'] ?? null,
                'parent_id' => $parentId,
                'is_active' => true,
            ]);
            $codeToId[$acc['code']] = $record->id;
        }
    }
}
