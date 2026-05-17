<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CustomerGroup;

class CustomerGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            ['name' => 'Retail', 'discount_pct' => 0, 'credit_limit' => 1000000],
            ['name' => 'Tukang', 'discount_pct' => 5, 'credit_limit' => 5000000],
            ['name' => 'Kontraktor', 'discount_pct' => 10, 'credit_limit' => 20000000],
            ['name' => 'Proyek', 'discount_pct' => 15, 'credit_limit' => 50000000],
            ['name' => 'Langganan', 'discount_pct' => 8, 'credit_limit' => 10000000],
        ];

        foreach ($groups as $group) {
            CustomerGroup::create($group);
        }
    }
}
