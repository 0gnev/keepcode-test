<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "LoginRequest",
    required: ["email", "password"],
    properties: [
        new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com"),
        new OA\Property(property: "password", type: "string", example: "password123")
    ],
    type: "object"
)]
#[OA\Schema(
    schema: "RegisterRequest",
    required: ["name", "email", "password", "password_confirmation"],
    properties: [
        new OA\Property(property: "name", type: "string", example: "Jane Smith"),
        new OA\Property(property: "email", type: "string", format: "email", example: "janesmith@example.com"),
        new OA\Property(property: "password", type: "string", format: "password", example: "securepassword"),
        new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "securepassword")
    ],
    type: "object"
)]
#[OA\Schema(
    schema: "ErrorResponse",
    properties: [
        new OA\Property(property: "error", type: "string", example: "Unauthorized")
    ],
    type: "object"
)]
#[OA\Schema(
    schema: "Product",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Sample Product"),
        new OA\Property(property: "price", type: "number", format: "float", example: 19.99),
        new OA\Property(property: "category", type: "string", example: "Electronics"),
        new OA\Property(property: "company", type: "string", example: "Brand Name")
    ],
    type: "object"
)]
#[OA\Schema(
    schema: "UserProduct",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1001),
        new OA\Property(property: "user_id", type: "integer", example: 1),
        new OA\Property(property: "product_id", type: "integer", example: 101),
        new OA\Property(property: "ownership_type", type: "string", example: "rent"),
        new OA\Property(property: "rent_started_at", type: "string", format: "date-time", example: "2024-12-10T15:00:00.000Z"),
        new OA\Property(property: "rent_expires_at", type: "string", format: "date-time", example: "2024-12-10T19:00:00.000Z"),
        new OA\Property(property: "unique_code", type: "string", format: "uuid", example: "123e4567-e89b-12d3-a456-426614174000"),
        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2024-12-10T14:50:00.000Z"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2024-12-10T14:50:00.000Z")
    ],
    type: "object"
)]
#[OA\Schema(
    schema: "RentRequest",
    required: ["duration"],
    properties: [
        new OA\Property(property: "duration", type: "integer", enum: [4, 8, 12, 24], example: 8)
    ],
    type: "object"
)]
#[OA\Schema(
    schema: "RenewRequest",
    required: ["duration"],
    properties: [
        new OA\Property(property: "duration", type: "integer", enum: [4, 8, 12, 24], example: 4)
    ],
    type: "object"
)]
class schemas
{
    // This class holds all the schema definitions as attributes.
}
