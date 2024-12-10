<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{

    public function definition(): array
    {
        $tool = fake()->tool();

        return [
            'name' => $tool['name'],
            'category' => $tool['category'],
            'company' => $tool['company'],
            'price' => fake()->randomFloat(2, 100, 1000),
            'rental_price' => fake()->randomFloat(2, 10, 50),
        ];
    }
}
