<?php

/**
 * Emits Eloquent Model classes from OpenAPI Schema Objects.
 *
 * @package Cdd\Schemas
 */

declare(strict_types=1);

namespace Cdd\Schemas;

/**
 * Emits a PHP class definition from an OpenAPI Schema Object.
 *
 * @param string $className The class name to emit.
 * @param array<string, mixed> $schema The OpenAPI Schema Object.
 * @return string The generated PHP class source code.
 */
function emit(string $className, array $schema): string
{
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
        $out .= "/**
";
        foreach ($docTags as $tag) {
            $out .= " * $tag
";
        }
        $out .= " */
";
    }

    $out .= "class $className extends \Illuminate\Database\Eloquent\Model {
";
    $out .= "    protected \$fillable = [
";
    if (isset($schema['properties']) && is_array($schema['properties'])) {
        foreach ($schema['properties'] as $propName => $propSchema) {
            $out .= "        '$propName',
";
        }
    }
    $out .= "    ];
";

    $out .= "    protected \$casts = [
";
    if (isset($schema['properties']) && is_array($schema['properties'])) {
        $typeMap = [
            'integer' => 'integer',
            'number' => 'float',
            'boolean' => 'boolean',
            'array' => 'array',
            'object' => 'object',
        ];
        foreach ($schema['properties'] as $propName => $propSchema) {
            if (isset($propSchema['type']) && isset($typeMap[$propSchema['type']])) {
                $out .= "        '$propName' => '{$typeMap[$propSchema['type']]}',
";
            }
        }
    }
    $out .= "    ];
";

    $out .= "}
";
    return $out;
}
