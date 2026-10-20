<?php

/**
 * Emits PHP parameter definitions from OpenAPI Parameter Objects.
 *
 * @package Cdd\Parameters
 */

declare(strict_types=1);

namespace Cdd\Parameters;

/**
 * Sanitizes an arbitrary OpenAPI parameter or template name into a valid PHP variable identifier.
 *
 * @param string $rawName The raw OpenAPI parameter or path variable name.
 * @return string Valid PHP variable identifier (without leading `$`).
 */
function sanitize_var_name(string $rawName): string
{
    $cleaned = (string) preg_replace('/[^a-zA-Z0-9_]+/', '_', $rawName);
    $cleaned = trim($cleaned, '_');
    if ($cleaned === '' || preg_match('/^[0-9]/', $cleaned) === 1) {
        $cleaned = 'param_' . $cleaned;
    }
    return $cleaned;
}

/**
 * Emits a PHP parameter definition from an OpenAPI Parameter Object.
 *
 * @param array<string, mixed> $parameter The OpenAPI Parameter Object.
 * @return string The generated parameter definition string.
 */
function emit(array $parameter): string
{
    $rawName = (string) ($parameter['name'] ?? 'param');
    $name = sanitize_var_name($rawName);
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
        if ($required || $type === 'mixed') {
            $typeStr = $type . ' ';
        } else {
            $typeStr = "?$type" . ' ';
        }
    }

    return "{$typeStr}\${$name}";
}
