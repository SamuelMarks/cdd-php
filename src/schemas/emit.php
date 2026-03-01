<?php

declare(strict_types=1);

namespace Cdd\Schemas;

/**
 * Emits a PHP class definition from an OpenAPI Schema Object.
 */
function emit(string $className, array $schema): string {
    $out = '';
    
    $docTags = [];
    if (isset($schema['description'])) {
        $docTags[] = $schema['description'];
    }
    if (isset($schema['xml']['nodeType'])) {
        $docTags[] = "@xml nodeType {$schema['xml']['nodeType']}";
    }
    if (isset($schema['discriminator'])) {
        if (isset($schema['discriminator']['propertyName'])) {
            $docTags[] = "@discriminator propertyName {$schema['discriminator']['propertyName']}";
        }
        if (isset($schema['discriminator']['defaultMapping'])) {
            $docTags[] = "@discriminator defaultMapping {$schema['discriminator']['defaultMapping']}";
        }
        if (isset($schema['discriminator']['mapping']) && is_array($schema['discriminator']['mapping'])) {
            foreach ($schema['discriminator']['mapping'] as $key => $val) {
                $docTags[] = "@discriminator mapping {$key} {$val}";
            }
        }
    }
    
    if (!empty($docTags)) {
        $out .= "/**\n";
        foreach ($docTags as $tag) {
            $out .= " * $tag\n";
        }
        $out .= " */\n";
    }
    
    $out .= "class $className {\n";
    
    if (isset($schema['properties']) && is_array($schema['properties'])) {
        foreach ($schema['properties'] as $propName => $propSchema) {
            $type = '';
            if (isset($propSchema['type'])) {
                $typeMap = [
                    'integer' => 'int',
                    'number' => 'float',
                    'boolean' => 'bool',
                    'string' => 'string',
                    'array' => 'array',
                    'object' => 'object'
                ];
                $type = $typeMap[$propSchema['type']] ?? 'mixed';
            } elseif (isset($propSchema['$ref'])) {
                $parts = explode('/', $propSchema['$ref']);
                $type = end($parts);
            }
            
            $nullable = !empty($propSchema['nullable']) || (!empty($schema['required']) && !in_array($propName, $schema['required']));
            
            $typeStr = '';
            if ($type !== '') {
                $typeStr = ($nullable ? '?' : '') . $type . ' ';
            }
            
            $out .= "    public $typeStr\$$propName;\n";
        }
    }
    
    $out .= "}\n";
    return $out;
}
