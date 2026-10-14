<?php

/**
 * Emits PHP parameter definitions from OpenAPI Parameter Objects.
 *
 * @package Cdd\Parameters
 */

declare(strict_types=1);

namespace Cdd\Parameters;

/**
 * Emits a PHP parameter definition from an OpenAPI Parameter Object.
 *
 * @param array<string, mixed> $parameter The OpenAPI Parameter Object.
 * @return string The generated parameter definition string.
 */
function emit(array $parameter): string
{
    $name = (string) ($parameter['name'] ?? 'param');
    $schema = $parameter['schema'] ?? [];

    $type = '';
    if (isset($schema['type'])) {
        $type = \Cdd\Schemas\openapi_type_to_php((string) $schema['type'], 'mixed');
    } elseif (isset($schema['$ref'])) {
        $parts = explode('/', (string) $schema['$ref']);
        $type = (string) end($parts);
    }

    $required = (bool) ($parameter['required'] ?? false);
    if (isset($parameter['in']) && $parameter['in'] === 'path') {
        $required = true;
    }

    $typeStr = '';
    if ($type !== '') {
        if ($required) {
            $typeStr = $type . ' ';
        } else {
            $typeStr = "?$type" . ' ';
        }
    }

    return "{$typeStr}\${$name}";
}
