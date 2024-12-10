<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public function run()
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'balance' => 500.00,
        ]);
    }
}
