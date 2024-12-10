<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\UserProduct;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $userProduct = null;

        if ($user) {
            $userProduct = UserProduct::where('user_id', $user->id)
                ->where('product_id', $this->id)
                ->first();
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => number_format($this->price, 2, '.', ''),
            'category' => $this->category,
            'company' => $this->company,
            'rental_price' => number_format($this->rental_price, 2, '.', ''),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'ownership_info' => $userProduct ? [
                'ownership_type' => $userProduct->ownership_type,
                'unique_code' => $userProduct->unique_code,
                'rent_started_at' => $userProduct->rent_started_at ?: null,
                'rent_expires_at' => $userProduct->rent_expires_at ?: null,
                'rental_active' => $userProduct->ownership_type === 'rent' && $userProduct->rent_expires_at && $userProduct->rent_expires_at->isFuture(),
            ] : null,
        ];
    }
}
