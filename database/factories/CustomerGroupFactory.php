<?php

namespace Database\Factories;

use App\Models\CustomerGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerGroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Retail', 'Grosir', 'Proyek', 'VIP']),
            'discount_pct' => $this->faker->randomElement([0, 5, 10, 15]),
            'credit_limit' => $this->faker->numberBetween(1000000, 50000000),
            'is_active' => true,
        ];
    }
}
