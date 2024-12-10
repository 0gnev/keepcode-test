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

class UserProductController extends Controller
{
    private UserProductService $userProductService;

    public function __construct(UserProductService $userProductService)
    {
        $this->userProductService = $userProductService;
    }

    public function purchase(PurchaseRequest $request, Product $product): JsonResponse
    {
        $result = $this->userProductService->purchaseProduct($product, $request->user());

        return $this->formatResponse($result, 'Product purchased successfully');
    }

    public function rent(RentRequest $request, Product $product): JsonResponse
    {
        $result = $this->userProductService->rentProduct(
            $product,
            $request->input('duration'),
            $request->user()
        );

        return $this->formatResponse($result, 'Product rented successfully');
    }

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
