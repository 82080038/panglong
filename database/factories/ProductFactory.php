<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'PRD' . $this->faker->unique()->numerify('#####'),
            'name' => $this->faker->words(3, true),
            'alias' => null,
            'category_id' => Category::factory(),
            'brand' => $this->faker->company(),
            'min_stock' => $this->faker->numberBetween(10, 50),
            'max_stock' => $this->faker->numberBetween(100, 500),
            'location' => $this->faker->optional()->bothify('Rack-##'),
            'buy_price' => $this->faker->numberBetween(10000, 100000),
            'sell_price' => $this->faker->numberBetween(15000, 150000),
            'is_active' => true,
        ];
    }
}
