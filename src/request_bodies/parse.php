<?php

/**
 * Parses and validates OpenAPI RequestBody Objects.
 *
 * @package Cdd\RequestBodies
 */

declare(strict_types=1);

namespace Cdd\RequestBodies;

/**
 * Parses request body information (or an emitted PHP parameter signature like `?User $body` or `User $user`) to an OpenAPI RequestBody Object.
 *
 * @param string $typeOrSignature PHP type hint (`string`, `int`, `array`, or class name) or parameter signature like `?User $body`.
 * @param string|null $description Optional description.
 * @param bool $required Whether the request body is required.
 * @param string $mediaType Media type key (defaults to `application/json`).
 * @return array<string, mixed> The OpenAPI RequestBody Object.
 */
function parse(
    string $typeOrSignature,
    ?string $description = null,
    bool $required = true,
    string $mediaType = 'application/json'
): array {
    $type = trim($typeOrSignature);
    if (str_contains($type, '$')) {
        if (preg_match('/^(\??[a-zA-Z0-9_]+)?\s*\$[a-zA-Z0-9_]+$/', $type, $m)) {
            $rawType = trim($m[1] ?? '');
            if ($rawType === '') {
                $type = 'object';
                $required = false;
            } elseif (str_starts_with($rawType, '?')) {
                $type = substr($rawType, 1);
                $required = false;
            } else {
                $type = $rawType;
                $required = true;
            }
        }
    } elseif (str_starts_with($type, '?')) {
        $type = substr($type, 1);
        $required = false;
    }

    $rb = [
        'required' => $required,
        'content' => [
            $mediaType => [
                'schema' => [],
            ],
        ],
    ];

    if ($description !== null) {
        $rb['description'] = $description;
    }

    $mappedScalar = \Cdd\Schemas\php_type_to_openapi($type);
    if ($mappedScalar !== null) {
        $rb['content'][$mediaType]['schema']['type'] = $mappedScalar;
    } else {
        $rb['content'][$mediaType]['schema']['$ref'] = "#/components/schemas/{$type}";
    }

    return $rb;
}

/**
 * Validates a Request Body Object or Reference Object.
 *
 * @param mixed $requestBody The Request Body or Reference Object to validate.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateRequestBodyOrReferenceObject(mixed $requestBody): void
{
    if (!is_array($requestBody)) {
        throw \Cdd\Exceptions\CddException::validation('Request Body must be an object');
    }
    if (isset($requestBody['$ref'])) {
        \Cdd\Openapi\validateReferenceObject($requestBody);
        return;
    }
    if (isset($requestBody['description']) && !is_string($requestBody['description'])) {
        throw \Cdd\Exceptions\CddException::validation('Request Body "description" must be a string');
    }
    if (!isset($requestBody['content']) || !is_array($requestBody['content'])) {
        throw \Cdd\Exceptions\CddException::validation('Request Body must contain a "content" map');
    }
    foreach ($requestBody['content'] as $mediaTypeObj) {
        \Cdd\Encoding\validateMediaTypeOrReferenceObject($mediaTypeObj);
    }
    if (isset($requestBody['required']) && !is_bool($requestBody['required'])) {
        throw \Cdd\Exceptions\CddException::validation('Request Body "required" must be a boolean');
    }
}
