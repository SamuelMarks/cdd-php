<?php

/**
 * Parses and validates OpenAPI Parameter Objects.
 *
 * @package Cdd\Parameters
 */

declare(strict_types=1);

namespace Cdd\Parameters;

/**
 * Parses a parameter definition (or emitted PHP parameter signature like `?int $limit` or `string $id`) into an OpenAPI Parameter Object.
 *
 * @param string $nameOrSignature Parameter name without `$`, or emitted PHP signature like `?int $limit`.
 * @param string|null $type PHP type hint (`string`, `int`, `float`, `bool`, `array`, or class name), or null when parsing a signature.
 * @param string $in Parameter location (`query`, `header`, `path`, `cookie`, `querystring`).
 * @param bool $required Whether the parameter is required.
 * @param string|null $description Optional parameter description.
 * @return array<string, mixed> The OpenAPI Parameter Object.
 */
function parse(
    string $nameOrSignature,
    ?string $type = null,
    string $in = 'query',
    bool $required = true,
    ?string $description = null
): array {
    $name = $nameOrSignature;
    $resolvedType = $type ?? 'string';

    if ($type === null && str_contains($nameOrSignature, '$')) {
        $trimmed = trim($nameOrSignature);
        if (preg_match('/^(\??[a-zA-Z0-9_]+)?\s*\$([a-zA-Z0-9_]+)$/', $trimmed, $m)) {
            $rawType = trim($m[1] ?? '');
            $name = $m[2];
            if ($rawType === '') {
                $resolvedType = 'string';
                $required = $in === 'path';
            } elseif (str_starts_with($rawType, '?')) {
                $resolvedType = substr($rawType, 1);
                $required = false;
            } else {
                $resolvedType = $rawType;
                $required = true;
            }
        }
    }

    if ($in === 'path') {
        $required = true;
    }

    $param = [
        'name' => $name,
        'in' => $in,
        'required' => $required,
        'schema' => [],
    ];

    if ($description !== null && $description !== '') {
        $param['description'] = $description;
    }

    $mappedScalar = \Cdd\Schemas\php_type_to_openapi($resolvedType);
    if ($mappedScalar !== null) {
        $param['schema']['type'] = $mappedScalar;
    } else {
        $param['schema']['$ref'] = "#/components/schemas/{$resolvedType}";
    }

    return $param;
}

/**
 * Validates a Parameter Object or Reference Object.
 *
 * @param mixed $parameter The Parameter or Reference Object to validate.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateParameterOrReferenceObject(mixed $parameter): void
{
    if (!is_array($parameter)) {
        throw \Cdd\Exceptions\CddException::validation('Parameter must be an object');
    }
    if (isset($parameter['$ref'])) {
        \Cdd\Openapi\validateReferenceObject($parameter);
        return;
    }
    if (!isset($parameter['name']) || !is_string($parameter['name'])) {
        throw \Cdd\Exceptions\CddException::validation('Parameter must contain a "name" string');
    }
    if (!isset($parameter['in']) || !is_string($parameter['in'])) {
        throw \Cdd\Exceptions\CddException::validation('Parameter must contain an "in" string');
    }
    $validIn = ['query', 'querystring', 'header', 'path', 'cookie'];
    if (!in_array($parameter['in'], $validIn, true)) {
        throw \Cdd\Exceptions\CddException::validation('Parameter "in" must be one of: query, querystring, header, path, cookie');
    }
    if (isset($parameter['description']) && !is_string($parameter['description'])) {
        throw \Cdd\Exceptions\CddException::validation('Parameter "description" must be a string');
    }
    if (isset($parameter['required']) && !is_bool($parameter['required'])) {
        throw \Cdd\Exceptions\CddException::validation('Parameter "required" must be a boolean');
    }
    if (isset($parameter['deprecated']) && !is_bool($parameter['deprecated'])) {
        throw \Cdd\Exceptions\CddException::validation('Parameter "deprecated" must be a boolean');
    }
    if (isset($parameter['allowEmptyValue'])) {
        if (!is_bool($parameter['allowEmptyValue'])) {
            throw \Cdd\Exceptions\CddException::validation('Parameter "allowEmptyValue" must be a boolean');
        }
        if ($parameter['in'] !== 'query') {
            throw \Cdd\Exceptions\CddException::validation('Parameter "allowEmptyValue" is only allowed for in: query');
        }
    }
    if ($parameter['in'] === 'path') {
        if (!isset($parameter['required']) || $parameter['required'] !== true) {
            throw \Cdd\Exceptions\CddException::validation('Parameter with in: path MUST have required: true');
        }
    }
    if (isset($parameter['example']) && isset($parameter['examples'])) {
        throw \Cdd\Exceptions\CddException::validation('Parameter cannot contain both "example" and "examples"');
    }
    if (isset($parameter['schema']) && isset($parameter['content'])) {
        throw \Cdd\Exceptions\CddException::validation('Parameter cannot contain both "schema" and "content"');
    }
    if (!isset($parameter['schema']) && !isset($parameter['content'])) {
        throw \Cdd\Exceptions\CddException::validation('Parameter must contain either "schema" or "content"');
    }
    if ($parameter['in'] === 'querystring') {
        if (isset($parameter['schema']) || isset($parameter['style']) || isset($parameter['explode']) || isset($parameter['allowReserved'])) {
            throw \Cdd\Exceptions\CddException::validation('Fields schema, style, explode, allowReserved MUST NOT be used with in: querystring');
        }
    }
    if (isset($parameter['style']) && !is_string($parameter['style'])) {
        throw \Cdd\Exceptions\CddException::validation('Parameter "style" must be a string');
    }
    if (isset($parameter['explode']) && !is_bool($parameter['explode'])) {
        throw \Cdd\Exceptions\CddException::validation('Parameter "explode" must be a boolean');
    }
    if (isset($parameter['allowReserved']) && !is_bool($parameter['allowReserved'])) {
        throw \Cdd\Exceptions\CddException::validation('Parameter "allowReserved" must be a boolean');
    }
    if (isset($parameter['schema'])) {
        if (!is_array($parameter['schema']) && !is_bool($parameter['schema'])) {
            throw \Cdd\Exceptions\CddException::validation('Parameter "schema" must be an object');
        }
        \Cdd\Schemas\validateSchemaOrReferenceObject($parameter['schema']);
    }
    if (isset($parameter['content'])) {
        if (!is_array($parameter['content'])) {
            throw \Cdd\Exceptions\CddException::validation('Parameter "content" must be a map');
        }
        if (count($parameter['content']) !== 1) {
            throw \Cdd\Exceptions\CddException::validation('Parameter "content" map MUST only contain one entry');
        }
        foreach ($parameter['content'] as $mediaTypeObj) {
            \Cdd\Encoding\validateMediaTypeOrReferenceObject($mediaTypeObj);
        }
    }
}
