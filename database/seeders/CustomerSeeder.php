<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customerGroups = \App\Models\CustomerGroup::all();
        
        $customers = [
            [
                'name' => 'PT Maju Jaya',
                'email' => 'info@majujaya.com',
                'phone' => '081234567890',
                'address' => 'Jl. Sudirman No. 123, Jakarta',
                'group_id' => $customerGroups->first()->id ?? 1,
                'credit_limit' => 50000000,
                'payment_terms' => 30,
                'credit_score' => 'A',
                'is_active' => true,
            ],
            [
                'name' => 'CV Berkah Abadi',
                'email' => 'sales@berkahabadi.com',
                'phone' => '081234567891',
                'address' => 'Jl. Gatot Subroto No. 45, Jakarta',
                'group_id' => $customerGroups->skip(1)->first()->id ?? 2,
                'credit_limit' => 30000000,
                'payment_terms' => 30,
                'credit_score' => 'A',
                'is_active' => true,
            ],
            [
                'name' => 'UD Sejahtera',
                'email' => 'udsejahtera@gmail.com',
                'phone' => '081234567892',
                'address' => 'Jl. Thamrin No. 67, Jakarta',
                'group_id' => $customerGroups->skip(2)->first()->id ?? 3,
                'credit_limit' => 20000000,
                'payment_terms' => 30,
                'credit_score' => 'B',
                'is_active' => true,
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@yahoo.com',
                'phone' => '081234567893',
                'address' => 'Jl. Kebagusan No. 12, Jakarta',
                'group_id' => $customerGroups->skip(3)->first()->id ?? 4,
                'credit_limit' => 10000000,
                'payment_terms' => 30,
                'credit_score' => 'B',
                'is_active' => true,
            ],
            [
                'name' => 'Siti Aminah',
                'email' => 'siti.aminah@gmail.com',
                'phone' => '081234567894',
                'address' => 'Jl. Pancoran No. 34, Jakarta',
                'group_id' => $customerGroups->skip(4)->first()->id ?? 5,
                'credit_limit' => 15000000,
                'payment_terms' => 30,
                'credit_score' => 'A',
                'is_active' => true,
            ],
        ];

        foreach ($customers as $customer) {
            \App\Models\Customer::create($customer);
        }
    }
}
