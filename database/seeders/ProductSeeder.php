<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = \App\Models\Category::all();
        
        $products = [
            [
                'code' => 'PRD-001',
                'name' => 'Indomie Goreng',
                'buy_price' => 3000,
                'sell_price' => 3500,
                'min_stock' => 10,
                'max_stock' => 200,
                'category_id' => $categories->first()->id ?? 1,
                'brand' => 'Indomie',
                'is_active' => true,
            ],
            [
                'code' => 'PRD-002',
                'name' => 'Aqua 600ml',
                'buy_price' => 3500,
                'sell_price' => 4000,
                'min_stock' => 50,
                'max_stock' => 500,
                'category_id' => $categories->skip(1)->first()->id ?? 2,
                'brand' => 'Aqua',
                'is_active' => true,
            ],
            [
                'code' => 'PRD-003',
                'name' => 'Roti Tawar',
                'buy_price' => 12000,
                'sell_price' => 15000,
                'min_stock' => 10,
                'max_stock' => 100,
                'category_id' => $categories->skip(2)->first()->id ?? 3,
                'brand' => 'Sari Roti',
                'is_active' => true,
            ],
            [
                'code' => 'PRD-004',
                'name' => 'Susu UHT 1L',
                'buy_price' => 15000,
                'sell_price' => 18000,
                'min_stock' => 20,
                'max_stock' => 200,
                'category_id' => $categories->skip(3)->first()->id ?? 4,
                'brand' => 'Ultra',
                'is_active' => true,
            ],
            [
                'code' => 'PRD-005',
                'name' => 'Kopi Sachet',
                'buy_price' => 1500,
                'sell_price' => 2000,
                'min_stock' => 50,
                'max_stock' => 500,
                'category_id' => $categories->skip(4)->first()->id ?? 5,
                'brand' => 'Nescafe',
                'is_active' => true,
            ],
            [
                'code' => 'PRD-006',
                'name' => 'Teh Botol',
                'buy_price' => 4000,
                'sell_price' => 5000,
                'min_stock' => 30,
                'max_stock' => 300,
                'category_id' => $categories->skip(5)->first()->id ?? 6,
                'brand' => 'Teh Pucuk',
                'is_active' => true,
            ],
            [
                'code' => 'PRD-007',
                'name' => 'Biskuit Kaleng',
                'buy_price' => 10000,
                'sell_price' => 12000,
                'min_stock' => 10,
                'max_stock' => 100,
                'category_id' => $categories->skip(6)->first()->id ?? 7,
                'brand' => 'Oreo',
                'is_active' => true,
            ],
            [
                'code' => 'PRD-008',
                'name' => 'Minyak Goreng 1L',
                'buy_price' => 20000,
                'sell_price' => 25000,
                'min_stock' => 5,
                'max_stock' => 50,
                'category_id' => $categories->skip(7)->first()->id ?? 8,
                'brand' => 'Bimoli',
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            \App\Models\Product::create($product);
        }
    }
}
