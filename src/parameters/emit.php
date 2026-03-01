<?php

declare(strict_types=1);

namespace Cdd\Parameters;

/**
 * Emits a PHP parameter definition from an OpenAPI Parameter Object.
 *
 * @param array $parameter The OpenAPI Parameter Object
 * @return string The generated parameter definition string
 */
function emit(array $parameter): string {
    $name = $parameter['name'] ?? 'param';
    $schema = $parameter['schema'] ?? [];
    
    $type = '';
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
        $parts = explode('/', $schema['$ref']);
        $type = end($parts);
    }
    
    $required = $parameter['required'] ?? false;
    if (isset($parameter['in']) && $parameter['in'] === 'path') {
        $required = true;
    }
    
    $typeStr = ($type !== '') ? ($required ? $type : "?$type") . ' ' : '';
    
    return "{$typeStr}\${$name}";
}
