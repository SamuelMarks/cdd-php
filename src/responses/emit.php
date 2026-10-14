<?php

/**
 * Emits PHP docblock `@return` annotations from OpenAPI Responses Objects.
 *
 * @package Cdd\Responses
 */

declare(strict_types=1);

namespace Cdd\Responses;

/**
 * Emits a PHP docstring @return from an OpenAPI Responses Object.
 *
 * @param array<string|int, mixed> $responses The OpenAPI Responses Object.
 * @return string The generated docstring segment.
 */
function emit(array $responses): string
{
    $out = '';

    foreach ($responses as $response) {
        $schema = $response['content']['application/json']['schema'] ?? [];

        $type = 'mixed';
        if (isset($schema['type'])) {
            $typeMap = [
                'integer' => 'int',
                'number' => 'float',
                'boolean' => 'bool',
                'string' => 'string',
                'array' => 'array',
                'object' => 'object',
            ];
            $type = $typeMap[$schema['type']] ?? 'mixed';
        } elseif (isset($schema['$ref'])) {
            $parts = explode('/', (string) $schema['$ref']);
            $type = (string) end($parts);
        }

        $out .= " * @return $type
";
    }

    return $out;
}
