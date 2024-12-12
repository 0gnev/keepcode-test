<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    info: new OA\Info(
        version: "1.0.0",
        description: "API service for renting or selling goods. Users can purchase or rent products, renew rentals, and check product status.",
        title: "Goods Rental and Sale API",
        contact: new OA\Contact(
            email: "support@example.com"
        )
    ),
    servers: [
        new OA\Server(
            url: "http://localhost/api",
            description: "Local Development Server"
        )
    ]
)]
class swagger
{
    // This class serves as a container for the OpenAPI attributes.
}
