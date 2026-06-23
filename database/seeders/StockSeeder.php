<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();
        $adminId = \App\Models\User::where('username', 'admin')->first()->id ?? 1;

        $stockLevels = [
            'SMT-GRK-40' => 200,
            'SMT-TRD-40' => 150,
            'SMT-HLC-40' => 100,
            'SMT-PTH-40' => 30,
            'MRT-WBR-25' => 25,
            'MRT-SKA-25' => 8,
            'HBL-600-100' => 300,
            'BSI-KS-D10' => 120,
            'BSI-KS-D12' => 80,
            'BSI-SNI-D13' => 60,
            'KWT-BND-2' => 40,
            'WMH-M4-612' => 15,
            'SPD-04-109' => 50,
            'CAT-DLX-25' => 20,
            'CAT-AVN-25' => 25,
            'CAT-NPP-25' => 18,
            'CAT-KY-NP' => 30,
            'THN-A-5L' => 35,
            'PLM-DLX-5' => 22,
            'WP-SKA-4' => 10,
            'KRM-RMN-3030' => 80,
            'KRM-RMN-4040' => 60,
            'GRN-6060-POL' => 30,
            'KCA-5-183244' => 15,
            'KCA-8-183244' => 8,
            'PLY-MRN-9' => 40,
            'MDF-18-122244' => 25,
            'KYU-KMP-46' => 50,
            'GNT-BTN-KMR' => 1500,
            'SPD-MR-109' => 45,
            'TLG-PVC-6' => 35,
            'CLS-TTO-621' => 10,
            'PVC-RUC-4' => 60,
            'PVC-VNL-3' => 55,
            'KRN-TTO-12' => 28,
            'WST-TTO-LSN' => 8,
            'MTR-FST-5' => 40,
            'BOR-MKT-13' => 8,
            'HMT-SFT-YL' => 35,
        ];

        foreach ($products as $product) {
            $qty = $stockLevels[$product->code] ?? 0;
            if ($qty <= 0) {
                continue;
            }

            $baseUnit = $product->units()->where('is_base_unit', true)->first();
            if (!$baseUnit) {
                continue;
            }

            StockMovement::create([
                'product_id' => $product->id,
                'quantity' => $qty,
                'unit_id' => $baseUnit->id,
                'movement_type' => 'purchase',
                'reference_id' => null,
                'reference_type' => 'initial_stock',
                'notes' => 'Stok awal dari supplier',
                'created_by' => $adminId,
            ]);
        }
    }
}
