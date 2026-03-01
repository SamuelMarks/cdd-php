<?php

declare(strict_types=1);

declare (strict_types=1);
namespace Cdd\Parameters;

/**
 * Parses a parameter definition into an OpenAPI Parameter Object.
 *
 * @param string $name The parameter name
 * @param string $type The parameter type
 * @param string $in The location of the parameter
 * @param bool $required Whether the parameter is required
 * @return array The OpenAPI Parameter Object
 */
function parse(string $name, string $type, string $in = 'query', bool $required = true): array
{
    $param = ['name' => $name, 'in' => $in, 'required' => $required, 'schema' => []];
    $typeMap = ['int' => 'integer', 'float' => 'number', 'bool' => 'boolean', 'string' => 'string', 'array' => 'array', 'object' => 'object'];
    if (isset($typeMap[$type])) {
        $param['schema']['type'] = $typeMap[$type];
    } else {
        $param['schema']['$ref'] = "#/components/schemas/{$type}";
    }
    if ($in === 'path') {
        $param['required'] = true;
    }
    return $param;
}
/**
 * Validates a Parameter Object or Reference Object.
 */
function validateParameterOrReferenceObject(mixed $parameter): void
{
    if (!is_array($parameter)) {
        throw new \RuntimeException('Parameter must be an object');
    }
    if (isset($parameter['$ref'])) {
        \Cdd\Openapi\validateReferenceObject($parameter);
        return;
    }
    if (!isset($parameter['name']) || !is_string($parameter['name'])) {
        throw new \RuntimeException('Parameter must contain a "name" string');
    }
    if (!isset($parameter['in']) || !is_string($parameter['in'])) {
        throw new \RuntimeException('Parameter must contain an "in" string');
    }
    $validIn = ['query', 'querystring', 'header', 'path', 'cookie'];
    if (!in_array($parameter['in'], $validIn, true)) {
        throw new \RuntimeException('Parameter "in" must be one of: query, querystring, header, path, cookie');
    }
    if (isset($parameter['description']) && !is_string($parameter['description'])) {
        throw new \RuntimeException('Parameter "description" must be a string');
    }
    if (isset($parameter['required']) && !is_bool($parameter['required'])) {
        throw new \RuntimeException('Parameter "required" must be a boolean');
    }
    if ($parameter['in'] === 'path' && (!isset($parameter['required']) || $parameter['required'] !== true)) {
        throw new \RuntimeException('Parameter with in: path MUST have required: true');
    }
    if (isset($parameter['deprecated']) && !is_bool($parameter['deprecated'])) {
        throw new \RuntimeException('Parameter "deprecated" must be a boolean');
    }
    if (isset($parameter['allowEmptyValue']) && $parameter['in'] !== 'query') {
        throw new \RuntimeException('Parameter "allowEmptyValue" is only allowed for in: query');
    }
    if (isset($parameter['allowEmptyValue']) && !is_bool($parameter['allowEmptyValue'])) {
        throw new \RuntimeException('Parameter "allowEmptyValue" must be a boolean');
    }
    if (isset($parameter['example']) && isset($parameter['examples'])) {
        throw new \RuntimeException('Parameter cannot contain both "example" and "examples"');
    }
    $hasSchema = isset($parameter['schema']);
    $hasContent = isset($parameter['content']);
    if ($hasSchema && $hasContent) {
        throw new \RuntimeException('Parameter cannot contain both "schema" and "content"');
    }
    if (!$hasSchema && !$hasContent) {
        throw new \RuntimeException('Parameter must contain either "schema" or "content"');
    }
    if ($hasSchema) {
        if ($parameter['in'] === 'querystring') {
            if (isset($parameter['style']) || isset($parameter['explode']) || isset($parameter['allowReserved']) || isset($parameter['schema'])) {
                throw new \RuntimeException('Fields schema, style, explode, allowReserved MUST NOT be used with in: querystring');
            }
        }
        if (isset($parameter['style']) && !is_string($parameter['style'])) {
            throw new \RuntimeException('Parameter "style" must be a string');
        }
        if (isset($parameter['explode']) && !is_bool($parameter['explode'])) {
            throw new \RuntimeException('Parameter "explode" must be a boolean');
        }
        if (isset($parameter['allowReserved']) && !is_bool($parameter['allowReserved'])) {
            throw new \RuntimeException('Parameter "allowReserved" must be a boolean');
        }
        if (!is_array($parameter['schema'])) {
            throw new \RuntimeException('Parameter "schema" must be an object');
        }
    }
    if ($hasContent) {
        if (!is_array($parameter['content'])) {
            throw new \RuntimeException('Parameter "content" must be a map');
        }
        if (count($parameter['content']) !== 1) {
            throw new \RuntimeException('Parameter "content" map MUST only contain one entry');
        }
        foreach ($parameter['content'] as $mediaType => $mediaTypeObj) {
            \Cdd\Encoding\validateMediaTypeOrReferenceObject($mediaTypeObj);
        }
    }
}
