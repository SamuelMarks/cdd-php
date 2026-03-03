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
    
    $out .= "class $className extends \\Illuminate\\Database\\Eloquent\\Model {\n";
    $out .= "    protected \$fillable = [\n";
    if (isset($schema['properties']) && is_array($schema['properties'])) {
        foreach ($schema['properties'] as $propName => $propSchema) {
            $out .= "        '$propName',\n";
        }
    }
    $out .= "    ];\n";
    
    $out .= "    protected \$casts = [\n";
    if (isset($schema['properties']) && is_array($schema['properties'])) {
        foreach ($schema['properties'] as $propName => $propSchema) {
            if (isset($propSchema['type'])) {
                $typeMap = [
                    'integer' => 'integer',
                    'number' => 'float',
                    'boolean' => 'boolean',
                    'array' => 'array',
                    'object' => 'object'
                ];
                if (isset($typeMap[$propSchema['type']])) {
                    $out .= "        '$propName' => '{$typeMap[$propSchema['type']]}',\n";
                }
            }
        }
    }
    $out .= "    ];\n";
    
    $out .= "}\n";
    return $out;
}
