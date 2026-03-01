<?php

declare(strict_types=1);

namespace Cdd\RequestBodies;

/**
 * Emits a PHP parameter from an OpenAPI RequestBody Object.
 *
 * @param array $requestBody The OpenAPI RequestBody Object
 * @param string $name The parameter name
 * @return string The generated parameter definition
 */
function emit(array $requestBody, string $name = 'body'): string {
    $schema = $requestBody['content']['application/json']['schema'] ?? [];
    
    $type = '';
    if (isset($schema['type'])) {
        $type = $schema['type'];
    } elseif (isset($schema['$ref'])) {
        $parts = explode('/', $schema['$ref']);
        $type = end($parts);
    }
    
    $required = $requestBody['required'] ?? false;
    
    $typeStr = ($type !== '') ? ($required ? $type : "?$type") . ' ' : '';
    
    return "{$typeStr}\${$name}";
}
