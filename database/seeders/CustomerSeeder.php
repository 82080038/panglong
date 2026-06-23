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
                'name' => 'PT Wijaya Karya Konstruksi',
                'email' => 'procurement@wijayakarya.co.id',
                'phone' => '021-5701234',
                'address' => 'Jl. Konstruksi Raya No. 1, Jakarta Selatan',
                'group_id' => $customerGroups->where('name', 'Kontraktor')->first()->id ?? 3,
                'credit_limit' => 100000000,
                'payment_terms' => 45,
                'credit_score' => 'A',
                'is_active' => true,
            ],
            [
                'name' => 'PT Pembangunan Perumahan Nusantara',
                'email' => 'purchasing@ppn.co.id',
                'phone' => '021-5552345',
                'address' => 'Jl. Perumahan No. 88, Jakarta Barat',
                'group_id' => $customerGroups->where('name', 'Proyek')->first()->id ?? 4,
                'credit_limit' => 200000000,
                'payment_terms' => 60,
                'credit_score' => 'A',
                'is_active' => true,
            ],
            [
                'name' => 'CV Bangun Mandiri Sejahtera',
                'email' => 'cvbangunmandiri@gmail.com',
                'phone' => '0812-3456-7890',
                'address' => 'Jl. Bahan Bangunan No. 15, Bekasi',
                'group_id' => $customerGroups->where('name', 'Langganan')->first()->id ?? 5,
                'credit_limit' => 50000000,
                'payment_terms' => 30,
                'credit_score' => 'A',
                'is_active' => true,
            ],
            [
                'name' => 'Toko Bangunan Sumber Rejeki',
                'email' => 'sumberrejeki.bangunan@gmail.com',
                'phone' => '0813-1111-2222',
                'address' => 'Jl. Raya Bekasi KM 25, Bekasi',
                'group_id' => $customerGroups->where('name', 'Tukang')->first()->id ?? 2,
                'credit_limit' => 25000000,
                'payment_terms' => 30,
                'credit_score' => 'B',
                'is_active' => true,
            ],
            [
                'name' => 'Toko Bangunan Jaya Abadi',
                'email' => 'jayaabadi.bangunan@gmail.com',
                'phone' => '0813-3333-4444',
                'address' => 'Jl. Raya Bogor KM 30, Depok',
                'group_id' => $customerGroups->where('name', 'Tukang')->first()->id ?? 2,
                'credit_limit' => 20000000,
                'payment_terms' => 30,
                'credit_score' => 'B',
                'is_active' => true,
            ],
            [
                'name' => 'Pak Suhardi (Mandor)',
                'email' => null,
                'phone' => '0857-1234-5678',
                'address' => 'Jl. Swadaya No. 7, Cibitung',
                'group_id' => $customerGroups->where('name', 'Tukang')->first()->id ?? 2,
                'credit_limit' => 5000000,
                'payment_terms' => 15,
                'credit_score' => 'C',
                'is_active' => true,
            ],
            [
                'name' => 'Pak Joko Santoso (Tukang)',
                'email' => null,
                'phone' => '0858-9876-5432',
                'address' => 'Jl. Gotong Royong No. 12, Cikarang',
                'group_id' => $customerGroups->where('name', 'Retail')->first()->id ?? 1,
                'credit_limit' => 2000000,
                'payment_terms' => 7,
                'credit_score' => 'C',
                'is_active' => true,
            ],
            [
                'name' => 'PT Graha Property Development',
                'email' => 'procurement@grahaproperty.co.id',
                'phone' => '021-7778888',
                'address' => 'Jl. Property Raya No. 100, Tangerang Selatan',
                'group_id' => $customerGroups->where('name', 'Proyek')->first()->id ?? 4,
                'credit_limit' => 150000000,
                'payment_terms' => 60,
                'credit_score' => 'A',
                'is_active' => true,
            ],
            [
                'name' => 'UD Sentosa Bangun Jaya',
                'email' => 'sentosabangunjaya@yahoo.com',
                'phone' => '021-8889999',
                'address' => 'Jl. Industri No. 45, Cileungsi',
                'group_id' => $customerGroups->where('name', 'Langganan')->first()->id ?? 5,
                'credit_limit' => 40000000,
                'payment_terms' => 30,
                'credit_score' => 'B',
                'is_active' => true,
            ],
            [
                'name' => 'Ibu Wati (Renovasi Rumah)',
                'email' => null,
                'phone' => '0812-7777-8888',
                'address' => 'Jl. Melati No. 3, Cibitung',
                'group_id' => $customerGroups->where('name', 'Retail')->first()->id ?? 1,
                'credit_limit' => 1000000,
                'payment_terms' => 0,
                'credit_score' => 'C',
                'is_active' => true,
            ],
        ];

        foreach ($customers as $customer) {
            \App\Models\Customer::create($customer);
        }
    }
}
