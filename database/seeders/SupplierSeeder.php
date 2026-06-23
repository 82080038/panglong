<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'PT Semen Gresik Distributor',
                'address' => 'Jl. Industri No. 1, Gresik, Jawa Timur',
                'phone' => '031-3951234',
                'email' => 'sales@semen-gresik-dist.co.id',
                'payment_terms' => 30,
                'credit_limit' => 500000000,
                'is_active' => true,
            ],
            [
                'name' => 'PT Krakatau Steel Distributor',
                'address' => 'Jl. Industri Baja No. 7, Cilegon, Banten',
                'phone' => '0254-3721234',
                'email' => 'sales@krakatau-steel.co.id',
                'payment_terms' => 45,
                'credit_limit' => 1000000000,
                'is_active' => true,
            ],
            [
                'name' => 'PT Avian Brands Indonesia',
                'address' => 'Jl. Cat Industri No. 22, Tangerang, Banten',
                'phone' => '021-5551234',
                'email' => 'distributor@avianbrands.co.id',
                'payment_terms' => 30,
                'credit_limit' => 300000000,
                'is_active' => true,
            ],
            [
                'name' => 'PT Roman Ceramic Group',
                'address' => 'Jl. Keramik Raya No. 15, Surabaya, Jawa Timur',
                'phone' => '031-7481234',
                'email' => 'distributor@romanceramic.co.id',
                'payment_terms' => 30,
                'credit_limit' => 250000000,
                'is_active' => true,
            ],
            [
                'name' => 'PT Asahimas Flat Glass',
                'address' => 'Jl. Kaca Industri No. 3, Cikampek, Jawa Barat',
                'phone' => '021-8951234',
                'email' => 'sales@asahimas.co.id',
                'payment_terms' => 30,
                'credit_limit' => 200000000,
                'is_active' => true,
            ],
            [
                'name' => 'PT Sumalindo Lestari Jaya',
                'address' => 'Jl. Kayu Industri No. 9, Banjarmasin, Kalimantan Selatan',
                'phone' => '0511-3361234',
                'email' => 'sales@sumalindo.co.id',
                'payment_terms' => 30,
                'credit_limit' => 150000000,
                'is_active' => true,
            ],
            [
                'name' => 'PT Kanmuri Roof Indonesia',
                'address' => 'Jl. Genteng Industri No. 12, Mojokerto, Jawa Timur',
                'phone' => '0321-3211234',
                'email' => 'sales@kanmuri.co.id',
                'payment_terms' => 30,
                'credit_limit' => 100000000,
                'is_active' => true,
            ],
            [
                'name' => 'PT TOTO Indonesia',
                'address' => 'Jl. Sanitary Industri No. 5, Bekasi, Jawa Barat',
                'phone' => '021-8851234',
                'email' => 'distributor@toto.co.id',
                'payment_terms' => 30,
                'credit_limit' => 200000000,
                'is_active' => true,
            ],
            [
                'name' => 'PT Vinilon Pipe Distributor',
                'address' => 'Jl. Pipa Industri No. 8, Cikarang, Jawa Barat',
                'phone' => '021-8981234',
                'email' => 'sales@vinilon-dist.co.id',
                'payment_terms' => 30,
                'credit_limit' => 150000000,
                'is_active' => true,
            ],
            [
                'name' => 'PT Makita Power Tools Indonesia',
                'address' => 'Jl. Perkakas Industri No. 20, Jakarta Utara',
                'phone' => '021-6661234',
                'email' => 'distributor@makita.co.id',
                'payment_terms' => 30,
                'credit_limit' => 80000000,
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
