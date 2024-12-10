<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProduct;
use Illuminate\Database\Seeder;

class UserProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        User::all()->each(function ($user) {
            UserProduct::factory()
                ->count(rand(1, 5))
                ->create([
                    'user_id' => $user->id,
                ]);
        });
    }
}
