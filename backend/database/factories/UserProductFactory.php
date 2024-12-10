<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use App\Models\UserProduct;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserProductFactory extends Factory
{
    protected $model = UserProduct::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'ownership_type' => 'rent',
            'rent_expires_at' => $this->faker->dateTimeBetween('now', '+24 hours'),
            'unique_code' => Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
