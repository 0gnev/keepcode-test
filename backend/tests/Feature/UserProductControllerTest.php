<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\UserProduct;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;

    #[Test]
    public function user_can_purchase_a_product_successfully()
    {
        // Authenticate the user for this test
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson(route('products.purchase', ['product' => $this->product->id]));

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Product purchased successfully',
                'data' => [
                    'product_id' => $this->product->id,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'product_id',
                    'unique_code',
                ],
            ]);

        // Assert that 'unique_code' is a valid UUID
        $this->assertTrue(Str::isUuid($response->json('data.unique_code')));

        // Assert that the user's balance is deducted
        $this->assertEquals('300.00', $this->user->fresh()->balance);

        // Assert that the UserProduct record exists
        $this->assertDatabaseHas('user_products', [
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'ownership_type' => 'purchase',
        ]);
    }

    #[Test]
    public function user_cannot_purchase_a_product_with_insufficient_balance()
    {
        // Authenticate the user for this test
        Sanctum::actingAs($this->user, ['*']);

        // Reduce user balance below product price
        $this->user->balance = 100.00;
        $this->user->save();

        $response = $this->postJson(route('products.purchase', ['product' => $this->product->id]));

        $response->assertStatus(402)
            ->assertJson([
                'error' => 'Insufficient balance',
            ]);

        // Assert that the balance remains unchanged
        $this->assertEquals('100.00', $this->user->fresh()->balance);

        // Assert that no UserProduct record was created
        $this->assertDatabaseMissing('user_products', [
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'ownership_type' => 'purchase',
        ]);
    }

    #[Test]
    public function user_cannot_purchase_a_product_already_owned()
    {
        // Authenticate the user for this test
        Sanctum::actingAs($this->user, ['*']);

        // First purchase
        UserProduct::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'ownership_type' => 'purchase',
        ]);

        // Attempt to purchase again
        $response = $this->postJson(route('products.purchase', ['product' => $this->product->id]));

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Product already owned',
            ]);

        // Assert that balance remains unchanged
        $this->assertEquals('500.00', $this->user->fresh()->balance);
    }

    #[Test]
    public function user_can_rent_a_product_successfully()
    {
        // Authenticate the user for this test
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson(route('products.rent', ['product' => $this->product->id]), [
            'duration' => 8, // 8 hours
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Product rented successfully',
                'data' => [
                    'product_id' => $this->product->id,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'product_id',
                    'rent_expires_at',
                    'unique_code',
                ],
            ]);

        // Assert that 'unique_code' is a valid UUID
        $this->assertTrue(Str::isUuid($response->json('data.unique_code')));

        // Assert that the user's balance is deducted
        $this->assertEquals('450.00', $this->user->fresh()->balance);

        // Assert that the UserProduct record exists
        $this->assertDatabaseHas('user_products', [
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'ownership_type' => 'rent',
        ]);

        // Assert that 'rent_expires_at' is correctly set
        $expectedExpiration = now()->addHours(8)->toIso8601String(); // 2024-12-10T09:00:00+00:00
        $this->assertEquals(
            $expectedExpiration,
            $this->user->userProducts()->latest()->first()->rent_expires_at->toIso8601String()
        );
    }

    #[Test]
    public function user_cannot_rent_a_product_already_owned()
    {
        // Authenticate the user for this test
        Sanctum::actingAs($this->user, ['*']);

        // Purchase the product
        UserProduct::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'ownership_type' => 'purchase',
        ]);

        // Attempt to rent the owned product
        $response = $this->postJson(route('products.rent', ['product' => $this->product->id]), [
            'duration' => 8,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Cannot rent a product you already own',
            ]);

        // Assert that balance remains unchanged
        $this->assertEquals('500.00', $this->user->fresh()->balance);
    }

    #[Test]
    public function user_cannot_rent_a_product_with_insufficient_balance()
    {
        // Authenticate the user for this test
        Sanctum::actingAs($this->user, ['*']);

        // Reduce user balance below rental price
        $this->user->balance = 30.00;
        $this->user->save();

        $response = $this->postJson(route('products.rent', ['product' => $this->product->id]), [
            'duration' => 8,
        ]);

        $response->assertStatus(402)
            ->assertJson([
                'error' => 'Insufficient balance',
            ]);

        // Assert that balance remains unchanged
        $this->assertEquals('30.00', $this->user->fresh()->balance);

        // Assert that no UserProduct record was created
        $this->assertDatabaseMissing('user_products', [
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'ownership_type' => 'rent',
        ]);
    }

    #[Test]
    public function user_cannot_rent_a_product_with_active_rental()
    {
        // Authenticate the user for this test
        Sanctum::actingAs($this->user, ['*']);

        // Existing active rental
        UserProduct::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'ownership_type' => 'rent',
            'rent_expires_at' => now()->addHours(4),
        ]);

        // Attempt to rent again
        $response = $this->postJson(route('products.rent', ['product' => $this->product->id]), [
            'duration' => 8,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'You already have an active rental for this product',
            ]);

        // Assert that balance remains unchanged
        $this->assertEquals('500.00', $this->user->fresh()->balance);
    }

    #[Test]
    public function user_can_renew_a_rental_successfully()
    {
        // Authenticate the user for this test
        Sanctum::actingAs($this->user, ['*']);

        // Existing rental
        $userProduct = UserProduct::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'ownership_type' => 'rent',
            'rent_expires_at' => now()->addHours(8),
        ]);

        $response = $this->postJson(route('user-products.renew', ['userProduct' => $userProduct->id]), [
            'duration' => 8, // Renew for additional 8 hours, total 16
        ]);

        $newExpiration = now()->addHours(16)->toIso8601String(); // 2024-12-10T17:00:00+00:00

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Rental renewed successfully',
                'data' => [
                    'new_expiration' => $newExpiration,
                    'user_balance' => '450.00',
                ],
            ]);

        // Assert that the user's balance is deducted
        $this->assertEquals('450.00', $this->user->fresh()->balance);

        // Assert that the rental expiration is updated correctly
        $this->assertEquals(
            $newExpiration,
            $userProduct->fresh()->rent_expires_at->toIso8601String()
        );
    }

    #[Test]
    public function user_cannot_renew_a_purchase()
    {
        // Authenticate the user for this test
        Sanctum::actingAs($this->user, ['*']);

        // Purchase the product
        $userProduct = UserProduct::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'ownership_type' => 'purchase',
        ]);

        $response = $this->postJson(route('user-products.renew', ['userProduct' => $userProduct->id]), [
            'duration' => 8,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);
    }

    #[Test]
    public function user_cannot_renew_a_rental_beyond_24_hours_total()
    {
        // Authenticate the user for this test
        Sanctum::actingAs($this->user, ['*']);

        // Existing rental with 20 hours left
        $userProduct = UserProduct::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'ownership_type' => 'rent',
            'rent_expires_at' => now()->addHours(20), // 2024-12-10T21:00:00+00:00
        ]);

        // Attempt to renew for additional 8 hours (total 28 > 24)
        $response = $this->postJson(route('user-products.renew', ['userProduct' => $userProduct->id]), [
            'duration' => 8,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Cannot exceed 24 hours total rental time',
            ]);

        // Assert that balance remains unchanged
        $this->assertEquals('500.00', $this->user->fresh()->balance);

        // Assert that the rental expiration is unchanged
        $this->assertEquals(
            now()->addHours(20)->toIso8601String(),
            $userProduct->fresh()->rent_expires_at->toIso8601String()
        );
    }

    #[Test]
    public function user_cannot_renew_a_rental_with_insufficient_balance()
    {
        // Authenticate the user for this test
        Sanctum::actingAs($this->user, ['*']);

        // Existing rental
        $userProduct = UserProduct::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'ownership_type' => 'rent',
            'rent_expires_at' => now()->addHours(8),
        ]);

        // Reduce user balance below rental price
        $this->user->balance = 30.00;
        $this->user->save();

        $response = $this->postJson(route('user-products.renew', ['userProduct' => $userProduct->id]), [
            'duration' => 4,
        ]);

        $response->assertStatus(402)
            ->assertJson([
                'error' => 'Insufficient balance',
            ]);

        // Assert that balance remains unchanged
        $this->assertEquals('30.00', $this->user->fresh()->balance);

        // Assert that the rental expiration is unchanged
        $this->assertEquals(
            now()->addHours(8)->toIso8601String(),
            $userProduct->fresh()->rent_expires_at->toIso8601String()
        );
    }

    #[Test]
    public function user_can_check_status_of_their_product()
    {
        // Authenticate the user for this test
        Sanctum::actingAs($this->user, ['*']);

        // Existing rental without unique_code
        $userProduct = UserProduct::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'ownership_type' => 'rent',
            'rent_expires_at' => now()->addHours(8),
            'unique_code' => null,
        ]);

        $response = $this->getJson(route('user-products.status', ['userProduct' => $userProduct->id]));

        $userProduct->refresh();

        $newUniqueCode = $userProduct->unique_code;
        $newRentExpiresAt = $userProduct->rent_expires_at->toIso8601String();

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $userProduct->id,
                    'product_id' => $this->product->id,
                    'ownership_type' => 'rent',
                    'unique_code' => $newUniqueCode,
                    'rental_active' => true,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'product_id',
                    'ownership_type',
                    'unique_code',
                    'rent_expires_at',
                    'rental_active',
                ],
            ]);

        // Assert that 'rent_expires_at' matches up to seconds
        $expectedRentExpiresAt = now()->addHours(8)->toIso8601String();
        $actualRentExpiresAt = $userProduct->fresh()->rent_expires_at->toIso8601String();

        $this->assertEquals($expectedRentExpiresAt, $actualRentExpiresAt);

        // Assert that unique_code was generated and is a valid UUID
        $this->assertNotNull($userProduct->unique_code);
        $this->assertTrue(Str::isUuid($userProduct->unique_code));

        // Assert that rent_expires_at is correctly set
        $this->assertEquals(
            $expectedRentExpiresAt,
            $userProduct->fresh()->rent_expires_at->toIso8601String()
        );
    }

    #[Test]
    public function user_cannot_check_status_of_another_users_product()
    {
        // Authenticate the user for this test
        Sanctum::actingAs($this->user, ['*']);

        // Create another user
        $otherUser = User::factory()->create();

        // Create a UserProduct for the other user
        $userProduct = UserProduct::factory()->create([
            'user_id' => $otherUser->id,
            'product_id' => $this->product->id,
            'ownership_type' => 'rent',
            'rent_expires_at' => now()->addHours(8),
        ]);

        $response = $this->getJson(route('user-products.status', ['userProduct' => $userProduct->id]));

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);
    }

    #[Test]
    public function user_can_view_their_purchase_history()
    {
        // Authenticate the user for this test
        Sanctum::actingAs($this->user, ['*']);

        // Create purchases and rentals
        UserProduct::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'ownership_type' => 'purchase',
        ]);

        $anotherProduct = Product::factory()->create([
            'price' => 150.00,
            'rental_price' => 30.00,
        ]);

        UserProduct::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $anotherProduct->id,
            'ownership_type' => 'rent',
            'rent_expires_at' => now()->addHours(8),
        ]);

        $response = $this->getJson(route('user.purchaseHistory'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Assert that only purchases are included
        $this->assertCount(1, $response->json('data'));

        $this->assertEquals($this->product->id, $response->json('data')[0]['product_id']);
        $this->assertEquals('purchase', $response->json('data')[0]['ownership_type']);
    }

    #[Test]
    public function user_cannot_view_purchase_history_if_not_authenticated()
    {
        // Do not authenticate any user

        $response = $this->getJson(route('user.purchaseHistory'));

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    #[Test]
    public function user_can_extend_rental_without_exceeding_24_hours()
    {
        // Authenticate the user for this test
        Sanctum::actingAs($this->user, ['*']);

        // Existing rental with 16 hours left
        $userProduct = UserProduct::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'ownership_type' => 'rent',
            'rent_expires_at' => now()->addHours(16),
        ]);

        $response = $this->postJson(route('user-products.renew', ['userProduct' => $userProduct->id]), [
            'duration' => 8,
        ]);

        $newExpiration = now()->addHours(24)->toIso8601String(); // 2024-12-11T01:00:00+00:00

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Rental renewed successfully',
                'data' => [
                    'new_expiration' => $newExpiration,
                    'user_balance' => '450.00',
                ],
            ]);

        // Assert that the user's balance is deducted
        $this->assertEquals('450.00', $this->user->fresh()->balance);

        // Assert that the rental expiration is updated correctly
        $this->assertEquals(
            $newExpiration,
            $userProduct->fresh()->rent_expires_at->toIso8601String()
        );
    }

    #[Test]
    public function user_cannot_extend_rental_beyond_24_hours()
    {
        // Authenticate the user for this test
        Sanctum::actingAs($this->user, ['*']);

        // Existing rental with 20 hours left
        $userProduct = UserProduct::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'ownership_type' => 'rent',
            'rent_expires_at' => now()->addHours(20),
        ]);

        // Attempt to renew for additional 8 hours (total 28 > 24)
        $response = $this->postJson(route('user-products.renew', ['userProduct' => $userProduct->id]), [
            'duration' => 8,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Cannot exceed 24 hours total rental time',
            ]);

        // Assert that balance remains unchanged
        $this->assertEquals('500.00', $this->user->fresh()->balance);

        // Assert that the rental expiration is unchanged
        $this->assertEquals(
            now()->addHours(20)->toIso8601String(),
            $userProduct->fresh()->rent_expires_at->toIso8601String()
        );
    }

    #[Test]
    public function unique_code_is_generated_if_not_present_on_status_check()
    {
        // Authenticate the user for this test
        Sanctum::actingAs($this->user, ['*']);

        // Existing purchase without unique_code
        $userProduct = UserProduct::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'ownership_type' => 'purchase',
            'unique_code' => null,
        ]);

        $response = $this->getJson(route('user-products.status', ['userProduct' => $userProduct->id]));

        $userProduct->refresh();

        $newUniqueCode = $userProduct->unique_code;
        $newRentExpiresAt = $userProduct->rent_expires_at->toIso8601String();

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $userProduct->id,
                    'product_id' => $this->product->id,
                    'ownership_type' => 'purchase',
                    'unique_code' => $newUniqueCode,
                    'rental_active' => false,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'product_id',
                    'ownership_type',
                    'unique_code',
                    'rent_expires_at',
                    'rental_active',
                ],
            ]);

        // Assert that unique_code was generated and is a valid UUID
        $this->assertNotNull($userProduct->unique_code);
        $this->assertTrue(Str::isUuid($userProduct->unique_code));
    }

    #[Test]
    public function unique_code_is_not_regenerated_if_already_present()
    {
        // Authenticate the user for this test
        Sanctum::actingAs($this->user, ['*']);

        // Existing purchase with unique_code
        $userProduct = UserProduct::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'ownership_type' => 'purchase',
            'unique_code' => Str::uuid(),
        ]);

        $originalUniqueCode = $userProduct->unique_code;

        $response = $this->getJson(route('user-products.status', ['userProduct' => $userProduct->id]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'unique_code' => $originalUniqueCode,
                ],
            ]);

        // Assert that unique_code remains unchanged
        $this->assertEquals($originalUniqueCode, $userProduct->fresh()->unique_code);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Set a fixed current time
        Carbon::setTestNow(Carbon::parse('2024-12-10 01:00:00'));

        // Create a user with a balance
        $this->user = User::factory()->create([
            'balance' => 500.00,
        ]);

        // Create a product
        $this->product = Product::factory()->create([
            'price' => 200.00,
            'rental_price' => 50.00,
        ]);

        // Ensure the product has a valid ID
        $this->assertNotNull($this->product->id, 'Product ID should not be null');

        // Remove default authentication
        // Sanctum::actingAs($this->user, ['*']); // Remove or comment out this line
    }
}
