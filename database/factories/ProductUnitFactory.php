<?php

namespace Database\Factories;

use App\Models\ProductUnit;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductUnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'unit_name' => $this->faker->randomElement(['pcs', 'box', 'lusin', 'batang', 'sak', 'kg']),
            'conversion_factor' => 1,
            'is_base_unit' => true,
            'price_per_unit' => $this->faker->numberBetween(10000, 100000),
        ];
    }
}
