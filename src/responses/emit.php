<?php

/**
 * Emits PHP docblock `@return` annotations from OpenAPI Responses Objects.
 *
 * @package Cdd\Responses
 */

declare(strict_types=1);

namespace Cdd\Responses;

/**
 * Emits a PHP docstring `@return` from an OpenAPI Responses Object.
 *
 * @param array<string, mixed> $responses The OpenAPI Responses Object.
 * @param bool $includeMetadata Whether to include status code and description after the PHP return type.
 * @return string The emitted `@return` docblock lines.
 */
function emit(array $responses, bool $includeMetadata = false): string
{
    $out = '';
    foreach ($responses as $statusCode => $response) {
        $type = 'mixed';
        $schema = $response['content']['application/json']['schema'] ?? null;
        if (isset($schema['type'])) {
            $type = \Cdd\Schemas\openapi_type_to_php((string) $schema['type'], 'mixed');
        } elseif (isset($schema['$ref'])) {
            $parts = explode('/', (string) $schema['$ref']);
            $type = (string) end($parts);
        }

        if ($includeMetadata) {
            $desc = isset($response['description']) && is_string($response['description']) ? ' ' . $response['description'] : '';
            $out .= " * @return {$type} {$statusCode}{$desc}
";
        } else {
            $out .= " * @return $type
";
        }
    }

    return $out;
}
