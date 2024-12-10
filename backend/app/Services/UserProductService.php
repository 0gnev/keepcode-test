<?php

namespace App\Services;

use App\Models\Product;
use App\Models\UserProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserProductService
{
    public function purchaseProduct(Product $product, $user): array
    {
        if (!$product) {
            return $this->errorResponse('Invalid product', 400);
        }

        if ($this->alreadyOwnsProduct($user, $product)) {
            return $this->errorResponse('Product already owned', 400);
        }

        DB::beginTransaction();

        try {
            if ($user->balance < $product->price) {
                return $this->errorResponse('Insufficient balance', 402);
            }

            $user->balance -= $product->price;
            $user->save();

            $userProduct = UserProduct::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'ownership_type' => 'purchase',
                'unique_code' => Str::uuid(),
            ]);

            DB::commit();

            return $this->successResponse([
                'product_id' => $userProduct->product_id,
                'unique_code' => $userProduct->unique_code,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase failed', ['exception' => $e]);
            return $this->errorResponse('Purchase failed', 500);
        }
    }

    private function errorResponse(string $message, int $status): array
    {
        return ['status' => $status, 'error' => $message];
    }

    private function alreadyOwnsProduct($user, $product): bool
    {
        return UserProduct::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('ownership_type', 'purchase')
            ->exists();
    }

    private function successResponse($data, int $status): array
    {
        return ['status' => $status, 'data' => $data];
    }

    public function rentProduct(Product $product, int $duration, $user): array
    {
        if ($this->alreadyOwnsProduct($user, $product)) {
            return $this->errorResponse('Cannot rent a product you already own', 400);
        }

        if ($this->hasActiveRental($user, $product)) {
            return $this->errorResponse('You already have an active rental for this product', 400);
        }

        DB::beginTransaction();

        try {
            if ($user->balance < $product->rental_price) {
                return $this->errorResponse('Insufficient balance', 402);
            }

            $user->balance -= $product->rental_price;
            $user->save();

            $rentExpiresAt = now()->addHours($duration);

            $userProduct = UserProduct::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'ownership_type' => 'rent',
                'rent_expires_at' => $rentExpiresAt,
                'unique_code' => Str::uuid(),
            ]);

            DB::commit();

            return $this->successResponse([
                'product_id' => $product->id,
                'rent_expires_at' => $rentExpiresAt,
                'unique_code' => $userProduct->unique_code,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Rental failed', ['exception' => $e]);
            return $this->errorResponse('Rental failed', 500);
        }
    }

    private function hasActiveRental($user, $product): bool
    {
        return UserProduct::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('ownership_type', 'rent')
            ->where('rent_expires_at', '>', now())
            ->exists();
    }

    public function renewRental(UserProduct $userProduct, int $duration, $user): array
    {
        if ($userProduct->ownership_type !== 'rent') {
            return $this->errorResponse('Cannot renew a purchase', 400);
        }

        $newExpiration = $userProduct->rent_expires_at->copy()->addHours($duration);
        $maxExpiration = now()->copy()->addHours(24);

        if ($newExpiration->gt($maxExpiration)) {
            return $this->errorResponse('Cannot exceed 24 hours total rental time', 400);
        }

        DB::beginTransaction();

        try {
            if ($user->balance < $userProduct->product->rental_price) {
                return $this->errorResponse('Insufficient balance', 402);
            }

            $user->balance -= $userProduct->product->rental_price;
            $user->save();

            $userProduct->rent_expires_at = $newExpiration;
            $userProduct->save();

            DB::commit();

            return $this->successResponse([
                'new_expiration' => $userProduct->rent_expires_at->toIso8601String(),
                'user_balance' => number_format($user->balance, 2, '.', ''),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Rental renewal failed', ['exception' => $e]);
            return $this->errorResponse('Rental renewal failed', 500);
        }
    }

    public function checkStatus(UserProduct $userProduct, $user): array
    {
        if (!$userProduct->unique_code) {
            $userProduct->unique_code = Str::uuid();
            $userProduct->save();
        }

        $isActiveRental = $userProduct->ownership_type === 'rent' &&
            $userProduct->rent_expires_at &&
            $userProduct->rent_expires_at->isFuture();

        return $this->successResponse([
            'id' => $userProduct->id,
            'product_id' => $userProduct->product_id,
            'ownership_type' => $userProduct->ownership_type,
            'unique_code' => $userProduct->unique_code,
            'rent_expires_at' => $userProduct->rent_expires_at,
            'rental_active' => $isActiveRental,
        ], 200);
    }

    public function getPurchaseHistory($user): array
    {
        $purchases = $user->userProducts()
            ->with('product')
            ->where('ownership_type', 'purchase')
            ->get();

        return $this->successResponse($purchases, 200);
    }
}
