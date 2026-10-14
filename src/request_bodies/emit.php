<?php

/**
 * Emits PHP parameters from OpenAPI RequestBody Objects.
 *
 * @package Cdd\RequestBodies
 */

declare(strict_types=1);

namespace Cdd\RequestBodies;

/**
 * Emits a PHP parameter from an OpenAPI RequestBody Object.
 *
 * @param array<string, mixed> $requestBody The OpenAPI RequestBody Object.
 * @param string $name The parameter name.
 * @return string The generated parameter definition.
 */
function emit(array $requestBody, string $name = 'body'): string
{
    $schema = $requestBody['content']['application/json']['schema'] ?? [];

    $type = '';
    if (isset($schema['type'])) {
        $type = \Cdd\Schemas\openapi_type_to_php((string) $schema['type'], (string) $schema['type']);
    } elseif (isset($schema['$ref'])) {
        $parts = explode('/', (string) $schema['$ref']);
        $type = (string) end($parts);
    }

    $required = (bool) ($requestBody['required'] ?? false);

    $typeStr = ($type !== '') ? ($required ? $type : "?$type") . ' ' : '';

    return "{$typeStr}\${$name}";
}
