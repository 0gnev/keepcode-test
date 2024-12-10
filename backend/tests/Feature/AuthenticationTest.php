<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\UserProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_valid_login_returns_token_and_status_200()
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'token', 'expires_at'])
            ->assertJson(['message' => 'Login successful']);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    #[Test]
    public function test_invalid_login_credentials_return_401()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    #[Test]
    public function test_logged_in_user_can_access_protected_routes()
    {
        $user = User::factory()->create();

        Product::factory()->count(3)->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'price',
                        'category',
                        'company',
                        'rental_price',
                        'created_at',
                        'updated_at',
                        'ownership_info'
                    ],
                ],
            ]);
    }

    #[Test]
    public function test_non_authenticated_request_returns_401()
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    #[Test]
    public function test_tokens_are_invalidated_after_logout()
    {
        $user = User::factory()->create();

        // Create two tokens
        $token1 = $user->createToken('Token 1')->plainTextToken;
        $token2 = $user->createToken('Token 2')->plainTextToken;

        // Authenticate using Token 1
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token1",
        ])->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully',
            ]);

        // Assert that all tokens are deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    #[Test]
    public function test_token_cannot_access_other_users_resources()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $product = Product::factory()->create();

        $userProduct = UserProduct::factory()->create([
            'user_id' => $user1->id,
            'product_id' => $product->id,
            'ownership_type' => 'rent',
            'rent_expires_at' => now()->addHours(4),
        ]);

        Sanctum::actingAs($user2, ['*']);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'ownership_info' => null, // Ownership info should be null for other users
                ],
            ]);
    }
}
