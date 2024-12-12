<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Products",
    description: "API Endpoints for Products"
)]
class ProductController extends Controller
{
    #[OA\Get(
        path: "/products",
        summary: "Get list of all products",
        security: [["BearerAuth" => []]],
        tags: ["Products"],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of products",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/Product")
                        )
                    ],
                    type: "object"
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function index(): JsonResponse
    {
        $products = Product::all();

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
        ], 200);
    }

    #[OA\Get(
        path: "/products/{productId}",
        summary: "Get details of a specific product",
        security: [["BearerAuth" => []]],
        tags: ["Products"],
        parameters: [
            new OA\Parameter(
                name: "productId",
                description: "ID of the product to retrieve",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 101)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Product details",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/Product")
                    ],
                    type: "object"
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(
                response: 404,
                description: "Product not found",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ], 200);
    }
}
