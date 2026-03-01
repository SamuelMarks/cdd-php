<?php

declare(strict_types=1);

namespace Cdd\RequestBodies;

/**
 * Parses request body information to OpenAPI RequestBody Object.
 *
 * @param string $type The expected type or schema reference
 * @param string $description Optional description
 * @return array The OpenAPI RequestBody Object
 */
function parse(string $type, string $description = ''): array {
    $requestBody = [
        'required' => true,
        'content' => [
            'application/json' => [
                'schema' => [
                    '$ref' => "#/components/schemas/$type",
                ],
            ],
        ],
    ];
    
    if ($description !== '') {
        $requestBody['description'] = $description;
    }
    
    return $requestBody;
}

/**
 * Validates a Request Body Object or Reference Object.
 */
function validateRequestBodyOrReferenceObject(mixed $requestBody): void {
    if (!is_array($requestBody)) {
        throw new \RuntimeException('Request Body must be an object');
    }
    if (isset($requestBody['$ref'])) {
        \Cdd\Openapi\validateReferenceObject($requestBody);
        return;
    }
    if (isset($requestBody['description']) && !is_string($requestBody['description'])) {
        throw new \RuntimeException('Request Body "description" must be a string');
    }
    if (!isset($requestBody['content']) || !is_array($requestBody['content'])) {
        throw new \RuntimeException('Request Body must contain a "content" map');
    }
    foreach ($requestBody['content'] as $mediaType => $mediaTypeObj) {
        \Cdd\Encoding\validateMediaTypeOrReferenceObject($mediaTypeObj);
    }
    if (isset($requestBody['required']) && !is_bool($requestBody['required'])) {
        throw new \RuntimeException('Request Body "required" must be a boolean');
    }
}
