<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        Warehouse::create([
            'code' => 'WH-MAIN',
            'name' => 'Gudang Utama',
            'address' => 'Jl. Raya Panglong No. 1',
            'phone' => '021-1234567',
            'is_active' => true,
        ]);
    }
}
