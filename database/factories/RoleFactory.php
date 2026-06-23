<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->jobTitle(),
            'slug' => $this->faker->unique()->slug(2),
            'description' => $this->faker->sentence(),
        ];
    }
}
