<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Http\Requests\RenewRequest;
use App\Http\Requests\RentRequest;
use App\Http\Resources\UserProductResource;
use App\Models\Product;
use App\Models\UserProduct;
use App\Services\UserProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Purchases",
    description: "API Endpoints for Purchases"
)]
#[OA\Tag(
    name: "Rentals",
    description: "API Endpoints for Rentals"
)]
#[OA\Tag(
    name: "User Activities",
    description: "API Endpoints for User Activities"
)]
class UserProductController extends Controller
{
    private UserProductService $userProductService;

    public function __construct(UserProductService $userProductService)
    {
        $this->userProductService = $userProductService;
    }

    #[OA\Post(
        path: "/products/{productId}/purchase",
        summary: "Purchase a product permanently",
        security: [["BearerAuth" => []]],
        tags: ["Purchases"],
        parameters: [
            new OA\Parameter(
                name: "productId",
                description: "ID of the product to purchase",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 101)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Purchase successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Product purchased successfully"),
                        new OA\Property(property: "data", ref: "#/components/schemas/UserProduct")
                    ],
                    type: "object"
                )
            ),
            new OA\Response(
                response: 400,
                description: "Bad Request (e.g., insufficient balance)",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
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
    public function purchase(PurchaseRequest $request, Product $product): JsonResponse
    {
        $result = $this->userProductService->purchaseProduct($product, $request->user());

        return $this->formatResponse($result, 'Product purchased successfully');
    }

    #[OA\Post(
        path: "/products/{productId}/rent",
        summary: "Rent a product for a specified duration",
        security: [["BearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/RentRequest")
        ),
        tags: ["Rentals"],
        parameters: [
            new OA\Parameter(
                name: "productId",
                description: "ID of the product to rent",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 101)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Rental successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Product rented successfully"),
                        new OA\Property(property: "data", ref: "#/components/schemas/UserProduct")
                    ],
                    type: "object"
                )
            ),
            new OA\Response(
                response: 400,
                description: "Bad Request (e.g., insufficient balance, duration exceeds limit)",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
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
    public function rent(RentRequest $request, Product $product): JsonResponse
    {
        $result = $this->userProductService->rentProduct(
            $product,
            $request->input('duration'),
            $request->user()
        );

        return $this->formatResponse($result, 'Product rented successfully');
    }

    #[OA\Post(
        path: "/products/{productId}/renew",
        summary: "Renew an existing rental",
        security: [["BearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/RenewRequest")
        ),
        tags: ["Rentals"],
        parameters: [
            new OA\Parameter(
                name: "productId",
                description: "ID of the product to renew rental for",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 101)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Rental renewal successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Rental renewed successfully"),
                        new OA\Property(property: "data", ref: "#/components/schemas/UserProduct")
                    ],
                    type: "object"
                )
            ),
            new OA\Response(
                response: 400,
                description: "Bad Request (e.g., duration exceeds total limit)",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden (e.g., no active rental found)",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(
                response: 404,
                description: "Product not found",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function renew(RenewRequest $request, Product $product): JsonResponse
    {
        $userProduct = UserProduct::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->first();

        if (!$userProduct || $userProduct->ownership_type !== 'rent') {
            return response()->json([
                'success' => false,
                'message' => 'No active rental found for this product.',
            ], 404);
        }

        $this->authorize('renew', $userProduct);

        $result = $this->userProductService->renewRental(
            $userProduct,
            $request->input('duration'),
            $request->user()
        );

        return $this->formatResponse($result, 'Rental renewed successfully');
    }


    #[OA\Get(
        path: "/user/purchase-history",
        summary: "Get user's purchase and rental history",
        security: [["BearerAuth" => []]],
        tags: ["User Activities"],
        responses: [
            new OA\Response(
                response: 200,
                description: "User purchase and rental history",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/UserProduct")
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
    public function purchaseHistory(Request $request): JsonResponse
    {
        $result = $this->userProductService->getPurchaseHistory($request->user());

        return response()->json([
            'success' => true,
            'data' => UserProductResource::collection($result['data']),
        ], $result['status']);
    }

    private function formatResponse(array $result, string $successMessage): JsonResponse
    {
        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => $successMessage,
            'data' => $result['data'],
        ], $result['status']);
    }
}
