<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Bahan Bangunan', 'parent_id' => null, 'level' => 1],
            ['name' => 'Semen', 'parent_id' => null, 'level' => 1],
            ['name' => 'Besi', 'parent_id' => null, 'level' => 1],
            ['name' => 'Cat', 'parent_id' => null, 'level' => 1],
            ['name' => 'Keramik', 'parent_id' => null, 'level' => 1],
            ['name' => 'Kaca', 'parent_id' => null, 'level' => 1],
            ['name' => 'Kayu', 'parent_id' => null, 'level' => 1],
            ['name' => 'Atap', 'parent_id' => null, 'level' => 1],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
