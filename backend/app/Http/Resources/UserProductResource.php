<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product->name,
            'ownership_type' => $this->ownership_type,
            'unique_code' => $this->unique_code,
            'rent_expires_at' => $this->rent_expires_at ? $this->rent_expires_at->format('Y-m-d\TH:i:sP') : null,
            'created_at' => $this->created_at->format('Y-m-d\TH:i:sP'),
            'updated_at' => $this->updated_at->format('Y-m-d\TH:i:sP'),
        ];
    }
}
