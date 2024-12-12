<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Authentication",
    description: "API Endpoints of Authentication"
)]
class RegistrationController extends Controller
{
    #[OA\Post(
        path: "/register",
        summary: "Register a new user",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/RegisterRequest")
        ),
        tags: ["Authentication"],
        responses: [
            new OA\Response(
                response: 201,
                description: "Registration successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Registration successful"),
                        new OA\Property(property: "token", type: "string", example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
                        new OA\Property(property: "expires_at", type: "string", format: "date-time", example: "2024-12-11T14:48:00.000Z")
                    ],
                    type: "object"
                )
            ),
            new OA\Response(
                response: 400,
                description: "Validation errors",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'balance' => 0.00,
        ]);

        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'token' => $token,
            'expires_at' => now()->addHours(24),
        ], 201);
    }
}
