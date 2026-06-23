<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $sub = \App\Models\Category::where('level', 2)->pluck('id', 'name');

        $products = [
            // === SEMEN & BETON ===
            ['code' => 'SMT-GRK-40', 'name' => 'Semen Gresik Portland 40kg', 'buy_price' => 58000, 'sell_price' => 65000, 'min_stock' => 50, 'max_stock' => 1000, 'brand' => 'Semen Gresik', 'location' => 'A-01', 'cat' => 'Semen Portland', 'units' => [['sak', 1, true, 65000], ['ton', 25, false, 1625000]]],
            ['code' => 'SMT-TRD-40', 'name' => 'Semen Tiga Roda Portland 40kg', 'buy_price' => 57000, 'sell_price' => 64000, 'min_stock' => 50, 'max_stock' => 1000, 'brand' => 'Tiga Roda', 'location' => 'A-02', 'cat' => 'Semen Portland', 'units' => [['sak', 1, true, 64000], ['ton', 25, false, 1600000]]],
            ['code' => 'SMT-HLC-40', 'name' => 'Semen Holcim Portland 40kg', 'buy_price' => 56000, 'sell_price' => 63000, 'min_stock' => 50, 'max_stock' => 800, 'brand' => 'Holcim', 'location' => 'A-03', 'cat' => 'Semen Portland', 'units' => [['sak', 1, true, 63000], ['ton', 25, false, 1575000]]],
            ['code' => 'SMT-PTH-40', 'name' => 'Semen Putih Gresik 40kg', 'buy_price' => 95000, 'sell_price' => 110000, 'min_stock' => 20, 'max_stock' => 300, 'brand' => 'Semen Gresik', 'location' => 'A-04', 'cat' => 'Semen Putih', 'units' => [['sak', 1, true, 110000], ['ton', 25, false, 2750000]]],
            ['code' => 'MRT-WBR-25', 'name' => 'Mortar Weber TileFix 25kg', 'buy_price' => 85000, 'sell_price' => 98000, 'min_stock' => 10, 'max_stock' => 200, 'brand' => 'Weber', 'location' => 'A-05', 'cat' => 'Mortar & Insta Cement', 'units' => [['sak', 1, true, 98000]]],
            ['code' => 'MRT-SKA-25', 'name' => 'SikaGrout 215 Powder 25kg', 'buy_price' => 320000, 'sell_price' => 370000, 'min_stock' => 5, 'max_stock' => 50, 'brand' => 'Sika', 'location' => 'A-06', 'cat' => 'Mortar & Insta Cement', 'units' => [['sak', 1, true, 370000]]],
            ['code' => 'HBL-600-100', 'name' => 'Hebel Block 600x200x100mm', 'buy_price' => 22000, 'sell_price' => 28000, 'min_stock' => 100, 'max_stock' => 2000, 'brand' => 'Hebel', 'location' => 'A-07', 'cat' => 'Hebel & Bata Ringan', 'units' => [['pcs', 1, true, 28000], ['m3', 83, false, 2324000]]],

            // === BESI & BAJA ===
            ['code' => 'BSI-KS-D10', 'name' => 'Besi Beton KS D10mm 12m', 'buy_price' => 49000, 'sell_price' => 56000, 'min_stock' => 50, 'max_stock' => 500, 'brand' => 'KS', 'location' => 'B-01', 'cat' => 'Besi Beton', 'units' => [['batang', 1, true, 56000], ['kg', 7.4, false, 7568], ['ton', 7400, false, 56000000]]],
            ['code' => 'BSI-KS-D12', 'name' => 'Besi Beton KS D12mm 12m', 'buy_price' => 71000, 'sell_price' => 81000, 'min_stock' => 50, 'max_stock' => 500, 'brand' => 'KS', 'location' => 'B-02', 'cat' => 'Besi Beton', 'units' => [['batang', 1, true, 81000], ['kg', 10.66, false, 7598], ['ton', 10660, false, 81000000]]],
            ['code' => 'BSI-SNI-D13', 'name' => 'Besi Beton SNI D13mm 12m', 'buy_price' => 68000, 'sell_price' => 77000, 'min_stock' => 50, 'max_stock' => 500, 'brand' => 'SNI', 'location' => 'B-03', 'cat' => 'Besi Beton', 'units' => [['batang', 1, true, 77000], ['kg', 12.5, false, 6160], ['ton', 12500, false, 77000000]]],
            ['code' => 'KWT-BND-2', 'name' => 'Kawat Bendrat BWG 2mm 15kg', 'buy_price' => 135000, 'sell_price' => 155000, 'min_stock' => 20, 'max_stock' => 200, 'brand' => 'Bendrat', 'location' => 'B-04', 'cat' => 'Kawat & Wiremesh', 'units' => [['roll', 1, true, 155000], ['kg', 15, false, 10333]]],
            ['code' => 'WMH-M4-612', 'name' => 'Wiremesh M4 6x12m', 'buy_price' => 580000, 'sell_price' => 660000, 'min_stock' => 10, 'max_stock' => 100, 'brand' => 'SNI', 'location' => 'B-05', 'cat' => 'Kawat & Wiremesh', 'units' => [['lembar', 1, true, 660000], ['m2', 72, false, 9167]]],
            ['code' => 'SPD-04-109', 'name' => 'Spandek 0.4mm 1090mm', 'buy_price' => 125000, 'sell_price' => 145000, 'min_stock' => 30, 'max_stock' => 300, 'brand' => 'SNI', 'location' => 'B-06', 'cat' => 'Spandek & Genteng Metal', 'units' => [['lembar', 1, true, 145000], ['m', 3, false, 48333]]],

            // === CAT & FINISHING ===
            ['code' => 'CAT-DLX-25', 'name' => 'Cat Tembok Dulux Vitex 25kg', 'buy_price' => 780000, 'sell_price' => 890000, 'min_stock' => 10, 'max_stock' => 100, 'brand' => 'Dulux', 'location' => 'C-01', 'cat' => 'Cat Tembok', 'units' => [['galon', 1, true, 890000], ['kg', 25, false, 35600]]],
            ['code' => 'CAT-AVN-25', 'name' => 'Cat Tembok Avian 25kg', 'buy_price' => 680000, 'sell_price' => 780000, 'min_stock' => 10, 'max_stock' => 100, 'brand' => 'Avian', 'location' => 'C-02', 'cat' => 'Cat Tembok', 'units' => [['galon', 1, true, 780000], ['kg', 25, false, 31200]]],
            ['code' => 'CAT-NPP-25', 'name' => 'Cat Tembok Nippon 25kg', 'buy_price' => 750000, 'sell_price' => 860000, 'min_stock' => 10, 'max_stock' => 100, 'brand' => 'Nippon Paint', 'location' => 'C-03', 'cat' => 'Cat Tembok', 'units' => [['galon', 1, true, 860000], ['kg', 25, false, 34400]]],
            ['code' => 'CAT-KY-NP', 'name' => 'Cat Kayu Nippon 2.5kg', 'buy_price' => 145000, 'sell_price' => 168000, 'min_stock' => 10, 'max_stock' => 100, 'brand' => 'Nippon Paint', 'location' => 'C-04', 'cat' => 'Cat Kayu & Besi', 'units' => [['kaleng', 1, true, 168000], ['kg', 2.5, false, 67200]]],
            ['code' => 'THN-A-5L', 'name' => 'Thinner A 5 Liter', 'buy_price' => 65000, 'sell_price' => 78000, 'min_stock' => 15, 'max_stock' => 150, 'brand' => 'Generic', 'location' => 'C-05', 'cat' => 'Thinner & Pelarut', 'units' => [['galon', 1, true, 78000], ['liter', 5, false, 15600]]],
            ['code' => 'PLM-DLX-5', 'name' => 'Plamir Dulux 5kg', 'buy_price' => 78000, 'sell_price' => 92000, 'min_stock' => 10, 'max_stock' => 100, 'brand' => 'Dulux', 'location' => 'C-06', 'cat' => 'Waterproofing & Plamir', 'units' => [['kaleng', 1, true, 92000], ['kg', 5, false, 18400]]],
            ['code' => 'WP-SKA-4', 'name' => 'Waterproofing Sika 4kg', 'buy_price' => 185000, 'sell_price' => 215000, 'min_stock' => 5, 'max_stock' => 50, 'brand' => 'Sika', 'location' => 'C-07', 'cat' => 'Waterproofing & Plamir', 'units' => [['kaleng', 1, true, 215000], ['kg', 4, false, 53750]]],

            // === KERAMIK & GRANIT ===
            ['code' => 'KRM-RMN-3030', 'name' => 'Keramik Roman 30x30cm', 'buy_price' => 32000, 'sell_price' => 38000, 'min_stock' => 30, 'max_stock' => 300, 'brand' => 'Roman', 'location' => 'D-01', 'cat' => 'Keramik Lantai', 'units' => [['dus', 1, true, 38000], ['m2', 1.08, false, 35185], ['pcs', 12, false, 3167]]],
            ['code' => 'KRM-RMN-4040', 'name' => 'Keramik Roman 40x40cm', 'buy_price' => 48000, 'sell_price' => 56000, 'min_stock' => 30, 'max_stock' => 300, 'brand' => 'Roman', 'location' => 'D-02', 'cat' => 'Keramik Lantai', 'units' => [['dus', 1, true, 56000], ['m2', 1.6, false, 35000], ['pcs', 10, false, 5600]]],
            ['code' => 'GRN-6060-POL', 'name' => 'Granit 60x60cm Polished', 'buy_price' => 165000, 'sell_price' => 195000, 'min_stock' => 20, 'max_stock' => 200, 'brand' => 'Roman', 'location' => 'D-03', 'cat' => 'Granit & Homogeneous', 'units' => [['dus', 1, true, 195000], ['m2', 1.44, false, 135417], ['pcs', 4, false, 48750]]],

            // === KACA ===
            ['code' => 'KCA-5-183244', 'name' => 'Kaca Bening 5mm 183x244cm', 'buy_price' => 850000, 'sell_price' => 980000, 'min_stock' => 10, 'max_stock' => 80, 'brand' => 'Asahimas', 'location' => 'E-01', 'cat' => 'Kaca Bening', 'units' => [['lembar', 1, true, 980000], ['m2', 4.46, false, 219731]]],
            ['code' => 'KCA-8-183244', 'name' => 'Kaca Bening 8mm 183x244cm', 'buy_price' => 1350000, 'sell_price' => 1550000, 'min_stock' => 5, 'max_stock' => 50, 'brand' => 'Asahimas', 'location' => 'E-02', 'cat' => 'Kaca Bening', 'units' => [['lembar', 1, true, 1550000], ['m2', 4.46, false, 347534]]],

            // === KAYU & PLYWOOD ===
            ['code' => 'PLY-MRN-9', 'name' => 'Plywood Meranti 9mm 122x244cm', 'buy_price' => 195000, 'sell_price' => 230000, 'min_stock' => 20, 'max_stock' => 200, 'brand' => 'Meranti', 'location' => 'F-01', 'cat' => 'Plywood', 'units' => [['lembar', 1, true, 230000], ['m2', 2.98, false, 77181]]],
            ['code' => 'MDF-18-122244', 'name' => 'MDF Board 18mm 122x244cm', 'buy_price' => 285000, 'sell_price' => 330000, 'min_stock' => 15, 'max_stock' => 150, 'brand' => 'Sunshine', 'location' => 'F-02', 'cat' => 'MDF & Blockboard', 'units' => [['lembar', 1, true, 330000], ['m2', 2.98, false, 110738]]],
            ['code' => 'KYU-KMP-46', 'name' => 'Kayu Kamper 4x6cm 4m', 'buy_price' => 85000, 'sell_price' => 100000, 'min_stock' => 30, 'max_stock' => 300, 'brand' => 'Kamper', 'location' => 'F-03', 'cat' => 'Kayu Solid', 'units' => [['batang', 1, true, 100000], ['m3', 0.0096, false, 10416667]]],

            // === ATAP ===
            ['code' => 'GNT-BTN-KMR', 'name' => 'Genteng Beton Kanmuri', 'buy_price' => 4500, 'sell_price' => 5500, 'min_stock' => 500, 'max_stock' => 10000, 'brand' => 'Kanmuri', 'location' => 'G-01', 'cat' => 'Genteng', 'units' => [['pcs', 1, true, 5500], ['m2', 10, false, 55000]]],
            ['code' => 'SPD-MR-109', 'name' => 'Spandek Metal Roof 0.4mm 1090mm', 'buy_price' => 125000, 'sell_price' => 145000, 'min_stock' => 30, 'max_stock' => 300, 'brand' => 'SNI', 'location' => 'G-02', 'cat' => 'Spandek & Metal Roof', 'units' => [['lembar', 1, true, 145000], ['m', 3, false, 48333]]],
            ['code' => 'TLG-PVC-6', 'name' => 'Talang Air PVC 6 inch 4m', 'buy_price' => 95000, 'sell_price' => 112000, 'min_stock' => 20, 'max_stock' => 200, 'brand' => 'Vinilon', 'location' => 'G-03', 'cat' => 'Talang & Aksesoris Atap', 'units' => [['batang', 1, true, 112000], ['m', 4, false, 28000]]],

            // === SANITARY & PLUMBING ===
            ['code' => 'CLS-TTO-621', 'name' => 'Closet TOTO CW621J', 'buy_price' => 1850000, 'sell_price' => 2150000, 'min_stock' => 5, 'max_stock' => 50, 'brand' => 'TOTO', 'location' => 'H-01', 'cat' => 'Closet & Urinoir', 'units' => [['set', 1, true, 2150000]]],
            ['code' => 'PVC-RUC-4', 'name' => 'Pipa PVC Ruciruca 4 inch 4m', 'buy_price' => 78000, 'sell_price' => 92000, 'min_stock' => 30, 'max_stock' => 300, 'brand' => 'Ruciruca', 'location' => 'H-02', 'cat' => 'Pipa PVC & Fitting', 'units' => [['batang', 1, true, 92000], ['m', 4, false, 23000]]],
            ['code' => 'PVC-VNL-3', 'name' => 'Pipa PVC Vinilon 3 inch 4m', 'buy_price' => 52000, 'sell_price' => 62000, 'min_stock' => 30, 'max_stock' => 300, 'brand' => 'Vinilon', 'location' => 'H-03', 'cat' => 'Pipa PVC & Fitting', 'units' => [['batang', 1, true, 62000], ['m', 4, false, 15500]]],
            ['code' => 'KRN-TTO-12', 'name' => 'Kran Air TOTO 1/2 inch', 'buy_price' => 85000, 'sell_price' => 102000, 'min_stock' => 15, 'max_stock' => 150, 'brand' => 'TOTO', 'location' => 'H-04', 'cat' => 'Kran & Valve', 'units' => [['pcs', 1, true, 102000]]],
            ['code' => 'WST-TTO-LSN', 'name' => 'Washtafel TOTO Lavatory', 'buy_price' => 650000, 'sell_price' => 780000, 'min_stock' => 5, 'max_stock' => 50, 'brand' => 'TOTO', 'location' => 'H-05', 'cat' => 'Washtafel & Lavabo', 'units' => [['set', 1, true, 780000]]],

            // === PERALATAN ===
            ['code' => 'MTR-FST-5', 'name' => 'Meteran Fiber 5m', 'buy_price' => 28000, 'sell_price' => 35000, 'min_stock' => 20, 'max_stock' => 200, 'brand' => 'Fastway', 'location' => 'I-01', 'cat' => 'Perkakas', 'units' => [['pcs', 1, true, 35000]]],
            ['code' => 'BOR-MKT-13', 'name' => 'Bor Makita HP1630 13mm', 'buy_price' => 580000, 'sell_price' => 680000, 'min_stock' => 5, 'max_stock' => 50, 'brand' => 'Makita', 'location' => 'I-02', 'cat' => 'Perkakas', 'units' => [['set', 1, true, 680000]]],
            ['code' => 'HMT-SFT-YL', 'name' => 'Helm Safety SNI Yellow', 'buy_price' => 32000, 'sell_price' => 42000, 'min_stock' => 20, 'max_stock' => 200, 'brand' => 'Safetoe', 'location' => 'I-03', 'cat' => 'Safety Equipment', 'units' => [['pcs', 1, true, 42000]]],
        ];

        foreach ($products as $p) {
            $product = Product::create([
                'code' => $p['code'],
                'name' => $p['name'],
                'buy_price' => $p['buy_price'],
                'sell_price' => $p['sell_price'],
                'min_stock' => $p['min_stock'],
                'max_stock' => $p['max_stock'],
                'category_id' => $sub[$p['cat']] ?? null,
                'brand' => $p['brand'],
                'location' => $p['location'],
                'is_active' => true,
            ]);

            foreach ($p['units'] as $u) {
                ProductUnit::create([
                    'product_id' => $product->id,
                    'unit_name' => $u[0],
                    'conversion_factor' => $u[1],
                    'is_base_unit' => $u[2],
                    'price_per_unit' => $u[3],
                ]);
            }
        }
    }
}
