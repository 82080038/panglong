<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->companyEmail(),
            'group_id' => CustomerGroup::factory(),
            'credit_limit' => $this->faker->numberBetween(1000000, 50000000),
            'payment_terms' => $this->faker->numberBetween(7, 60),
            'credit_score' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
            'is_active' => true,
        ];
    }
}
