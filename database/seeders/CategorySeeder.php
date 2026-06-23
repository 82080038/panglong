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
        $parents = [
            'Semen & Beton',
            'Besi & Baja',
            'Cat & Finishing',
            'Keramik & Granit',
            'Kaca',
            'Kayu & Plywood',
            'Atap',
            'Sanitary & Plumbing',
            'Peralatan',
        ];

        $parentIds = [];
        foreach ($parents as $name) {
            $cat = Category::create([
                'name' => $name,
                'parent_id' => null,
                'level' => 1,
                'is_active' => true,
            ]);
            $parentIds[$name] = $cat->id;
        }

        $children = [
            'Semen & Beton' => ['Semen Portland', 'Semen Putih', 'Mortar & Insta Cement', 'Hebel & Bata Ringan'],
            'Besi & Baja' => ['Besi Beton', 'Baja Ringan & Kanal', 'Pipa Besi', 'Kawat & Wiremesh', 'Spandek & Genteng Metal'],
            'Cat & Finishing' => ['Cat Tembok', 'Cat Kayu & Besi', 'Thinner & Pelarut', 'Waterproofing & Plamir'],
            'Keramik & Granit' => ['Keramik Lantai', 'Keramik Dinding', 'Granit & Homogeneous', 'Marmer & Natural Stone'],
            'Kaca' => ['Kaca Bening', 'Kaca Tempered', 'Cermin'],
            'Kayu & Plywood' => ['Kayu Solid', 'Plywood', 'MDF & Blockboard'],
            'Atap' => ['Genteng', 'Spandek & Metal Roof', 'Talang & Aksesoris Atap'],
            'Sanitary & Plumbing' => ['Closet & Urinoir', 'Washtafel & Lavabo', 'Kran & Valve', 'Pipa PVC & Fitting'],
            'Peralatan' => ['Perkakas', 'Safety Equipment'],
        ];

        foreach ($children as $parentName => $subs) {
            foreach ($subs as $subName) {
                Category::create([
                    'name' => $subName,
                    'parent_id' => $parentIds[$parentName],
                    'level' => 2,
                    'is_active' => true,
                ]);
            }
        }
    }
}
