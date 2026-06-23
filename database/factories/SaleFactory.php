<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = $this->faker->numberBetween(100000, 10000000);
        $discount = $this->faker->numberBetween(0, 500000);
        $taxable = $subtotal - $discount;
        $tax = $taxable * 0.11;
        $total = $taxable + $tax;

        return [
            'invoice_no' => 'INV' . date('Ymd') . $this->faker->unique()->numerify('####'),
            'customer_id' => Customer::factory(),
            'sale_date' => $this->faker->date(),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
            'payment_method' => $this->faker->randomElement(['cash', 'credit', 'transfer']),
            'payment_status' => $this->faker->randomElement(['paid', 'partial', 'unpaid']),
            'status' => 'completed',
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }
}
