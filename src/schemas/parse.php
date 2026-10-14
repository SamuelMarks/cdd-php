<?php

/**
 * Parses PHP class AST nodes into OpenAPI Schema Objects and validates schemas.
 *
 * @package Cdd\Schemas
 */

declare(strict_types=1);

namespace Cdd\Schemas;

use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\UnionType;

/**
 * Maps a PHP scalar/builtin type name to an OpenAPI schema type name.
 *
 * @param string $phpType The PHP type identifier.
 * @return string|null The OpenAPI type ('integer', 'number', 'boolean', 'string', 'array', 'object') or null if non-scalar/class.
 */
function php_type_to_openapi(string $phpType): ?string
{
    $typeMap = [
        'int' => 'integer',
        'integer' => 'integer',
        'float' => 'number',
        'double' => 'number',
        'number' => 'number',
        'bool' => 'boolean',
        'boolean' => 'boolean',
        'string' => 'string',
        'array' => 'array',
        'object' => 'object',
    ];
    return $typeMap[strtolower($phpType)] ?? null;
}

/**
 * Maps an OpenAPI schema type name to its idiomatic PHP type hint.
 *
 * @param string $openApiType The OpenAPI type identifier.
 * @param string $default Fallback PHP type when unmapped.
 * @return string The mapped PHP type hint.
 */
function openapi_type_to_php(string $openApiType, string $default = 'mixed'): string
{
    $typeMap = [
        'integer' => 'int',
        'number' => 'float',
        'boolean' => 'bool',
        'string' => 'string',
        'array' => 'array',
        'object' => 'object',
    ];
    return $typeMap[strtolower($openApiType)] ?? $default;
}

/**
 * Parses a PHP class node into an OpenAPI Schema Object.
 *
 * @param Class_ $classNode The PHP class AST node.
 * @return array<string, mixed> The generated OpenAPI schema.
 */
function parse(Class_ $classNode): array
{
    $schema = ['type' => 'object', 'properties' => []];
    $docComment = $classNode->getDocComment();
    if ($docComment !== null) {
        $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
        if (!empty($parsedDoc['description'])) {
            $schema['description'] = trim($parsedDoc['description']);
        }
        if (isset($parsedDoc['tags']['xml'])) {
            foreach ($parsedDoc['tags']['xml'] as $xmlTag) {
                $parts = explode(' ', trim((string) $xmlTag), 2);
                if (count($parts) === 2 && $parts[0] === 'nodeType') {
                    if (!isset($schema['xml'])) {
                        $schema['xml'] = [];
                    }
                    $schema['xml']['nodeType'] = trim($parts[1]);
                }
            }
        }
        if (isset($parsedDoc['tags']['discriminator'])) {
            foreach ($parsedDoc['tags']['discriminator'] as $discTag) {
                $parts = explode(' ', trim((string) $discTag), 2);
                if (count($parts) === 2 && $parts[0] === 'defaultMapping') {
                    if (!isset($schema['discriminator'])) {
                        $schema['discriminator'] = ['propertyName' => 'type'];
                    }
                    $schema['discriminator']['defaultMapping'] = trim($parts[1]);
                } elseif (count($parts) === 2 && $parts[0] === 'propertyName') {
                    if (!isset($schema['discriminator'])) {
                        $schema['discriminator'] = [];
                    }
                    $schema['discriminator']['propertyName'] = trim($parts[1]);
                } elseif (count($parts) === 2 && $parts[0] === 'mapping') {
                    if (!isset($schema['discriminator'])) {
                        $schema['discriminator'] = ['propertyName' => 'type'];
                    }
                    if (!isset($schema['discriminator']['mapping'])) {
                        $schema['discriminator']['mapping'] = [];
                    }
                    $mapParts = explode(' ', trim($parts[1]), 2);
                    if (count($mapParts) === 2) {
                        $schema['discriminator']['mapping'][$mapParts[0]] = trim($mapParts[1]);
                    }
                }
            }
        }
    }
    $required = [];

    foreach ($classNode->getProperties() as $prop) {
        if (!$prop->isPublic()) {
            continue;
        }
        $typeNode = $prop->type;
        $nullable = false;
        $typeName = 'mixed';
        if ($typeNode !== null) {
            if ($typeNode instanceof NullableType) {
                $nullable = true;
                $inner = $typeNode->type;
                if ($inner instanceof Identifier || $inner instanceof Name) {
                    $typeName = $inner->toString();
                }
            } elseif ($typeNode instanceof UnionType) {
                foreach ($typeNode->types as $subType) {
                    if ($subType instanceof Identifier && strtolower($subType->toString()) === 'null') {
                        $nullable = true;
                    } elseif (($subType instanceof Identifier || $subType instanceof Name) && $typeName === 'mixed') {
                        $typeName = $subType->toString();
                    }
                }
            } elseif ($typeNode instanceof Identifier || $typeNode instanceof Name) {
                $typeName = $typeNode->toString();
            }
        }
        $mappedScalar = php_type_to_openapi($typeName);
        foreach ($prop->props as $p) {
            $propName = $p->name->toString();
            $propSchema = [];
            if (strtolower($typeName) === 'mixed') {
                $propSchema['type'] = 'string';
            } elseif ($mappedScalar !== null) {
                $propSchema['type'] = $mappedScalar;
            } else {
                $propSchema['$ref'] = "#/components/schemas/{$typeName}";
            }
            if ($nullable) {
                $propSchema['nullable'] = true;
            }
            if (!$nullable && strtolower($typeName) !== 'mixed') {
                $required[] = $propName;
            }
            $schema['properties'][$propName] = $propSchema;
        }
    }
    if (!empty($required)) {
        $schema['required'] = array_values(array_unique($required));
    }
    return $schema;
}

/**
 * Validates a Schema Object or Reference Object.
 *
 * @param mixed $schema The Schema or Reference Object to validate.
 * @return void
 * @throws \RuntimeException If validation fails.
 */
function validateSchemaOrReferenceObject(mixed $schema): void
{
    if (!is_array($schema) && !is_bool($schema)) {
        throw new \RuntimeException('Schema must be an object or boolean');
    }
    if (is_bool($schema)) {
        return;
    }
    if (isset($schema['$ref'])) {
        \Cdd\Openapi\validateReferenceObject($schema);
        return;
    }
    if (isset($schema['type'])) {
        if (!is_string($schema['type']) && !is_array($schema['type'])) {
            throw new \RuntimeException('Schema "type" must be a string or array of strings');
        }
    }
    if (isset($schema['properties'])) {
        if (!is_array($schema['properties'])) {
            throw new \RuntimeException('Schema "properties" must be a map');
        }
        foreach ($schema['properties'] as $prop) {
            \Cdd\Schemas\validateSchemaOrReferenceObject($prop);
        }
    }
    if (isset($schema['items'])) {
        \Cdd\Schemas\validateSchemaOrReferenceObject($schema['items']);
    }
    if (isset($schema['allOf'])) {
        if (!is_array($schema['allOf'])) {
            throw new \RuntimeException('Schema "allOf" must be an array');
        }
        foreach ($schema['allOf'] as $s) {
            \Cdd\Schemas\validateSchemaOrReferenceObject($s);
        }
    }
    if (isset($schema['anyOf'])) {
        if (!is_array($schema['anyOf'])) {
            throw new \RuntimeException('Schema "anyOf" must be an array');
        }
        foreach ($schema['anyOf'] as $s) {
            \Cdd\Schemas\validateSchemaOrReferenceObject($s);
        }
    }
    if (isset($schema['oneOf'])) {
        if (!is_array($schema['oneOf'])) {
            throw new \RuntimeException('Schema "oneOf" must be an array');
        }
        foreach ($schema['oneOf'] as $s) {
            \Cdd\Schemas\validateSchemaOrReferenceObject($s);
        }
    }
    if (isset($schema['not'])) {
        \Cdd\Schemas\validateSchemaOrReferenceObject($schema['not']);
    }
    if (isset($schema['discriminator'])) {
        \Cdd\Schemas\validateDiscriminatorObject($schema['discriminator']);
    }
    if (isset($schema['xml'])) {
        \Cdd\Schemas\validateXMLObject($schema['xml']);
    }
    if (isset($schema['externalDocs'])) {
        \Cdd\Info\validateExternalDocsObject($schema['externalDocs']);
    }
}

/**
 * Validates a Discriminator Object.
 *
 * @param mixed $discriminator The Discriminator Object to validate.
 * @return void
 * @throws \RuntimeException If validation fails.
 */
function validateDiscriminatorObject(mixed $discriminator): void
{
    if (!is_array($discriminator)) {
        throw new \RuntimeException('Discriminator must be an object');
    }
    if (!isset($discriminator['propertyName']) || !is_string($discriminator['propertyName'])) {
        throw new \RuntimeException('Discriminator must contain a "propertyName" string');
    }
    if (isset($discriminator['mapping']) && !is_array($discriminator['mapping'])) {
        throw new \RuntimeException('Discriminator "mapping" must be a map');
    }
    if (isset($discriminator['defaultMapping']) && !is_string($discriminator['defaultMapping'])) {
        throw new \RuntimeException('Discriminator "defaultMapping" must be a string');
    }
}

/**
 * Validates an XML Object.
 *
 * @param mixed $xml The XML Object to validate.
 * @return void
 * @throws \RuntimeException If validation fails.
 */
function validateXMLObject(mixed $xml): void
{
    if (!is_array($xml)) {
        throw new \RuntimeException('XML must be an object');
    }
    if (isset($xml['name']) && !is_string($xml['name'])) {
        throw new \RuntimeException('XML "name" must be a string');
    }
    if (isset($xml['namespace']) && !is_string($xml['namespace'])) {
        throw new \RuntimeException('XML "namespace" must be a string');
    }
    if (isset($xml['prefix']) && !is_string($xml['prefix'])) {
        throw new \RuntimeException('XML "prefix" must be a string');
    }
    if (isset($xml['attribute']) && !is_bool($xml['attribute'])) {
        throw new \RuntimeException('XML "attribute" must be a boolean');
    }
    if (isset($xml['wrapped']) && !is_bool($xml['wrapped'])) {
        throw new \RuntimeException('XML "wrapped" must be a boolean');
    }
    if (isset($xml['nodeType'])) {
        if (!is_string($xml['nodeType'])) {
            throw new \RuntimeException('XML "nodeType" must be a string');
        }
        $validNodes = ['element', 'attribute', 'text', 'cdata', 'none'];
        if (!in_array($xml['nodeType'], $validNodes, true)) {
            throw new \RuntimeException('XML "nodeType" must be one of: element, attribute, text, cdata, none');
        }
        if (isset($xml['attribute'])) {
            throw new \RuntimeException('XML "attribute" MUST NOT be present if "nodeType" is present');
        }
        if (isset($xml['wrapped'])) {
            throw new \RuntimeException('XML "wrapped" MUST NOT be present if "nodeType" is present');
        }
    }
}
