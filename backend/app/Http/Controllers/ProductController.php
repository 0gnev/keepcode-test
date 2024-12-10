<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::all();

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
        ], 200);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ], 200);
    }
}
