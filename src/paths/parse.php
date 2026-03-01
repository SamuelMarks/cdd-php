<?php

declare(strict_types=1);

declare (strict_types=1);
namespace Cdd\Paths;

/**
 * Parses an array of Path Item Objects into an OpenAPI Paths Object.
 *
 * @param array $pathItems Key-value map of path strings to Path Item Arrays
 * @return array The OpenAPI Paths Object
 */
function parse(array $pathItems): array
{
    $paths = [];
    foreach ($pathItems as $path => $item) {
        $paths[$path] = $item;
    }
    return $paths;
}
/**
 * Validates a Paths Object.
 */
function validatePathsObject(mixed $paths): void
{
    global $globalOperationIds;
    $globalOperationIds = [];
    if (!is_array($paths)) {
        throw new \RuntimeException('Field "paths" must be an object (array in PHP)');
    }
    foreach ($paths as $path => $pathItem) {
        if (!is_string($path) || !str_starts_with($path, '/')) {
            if (!str_starts_with($path, 'x-')) {
                throw new \RuntimeException('Paths object keys must start with a forward slash (/)');
            }
        }
        if (str_starts_with($path, '/')) {
            if (preg_match_all('/\{([^}]+)\}/', $path, $matches)) {
                $vars = $matches[1];
                if (count($vars) !== count(array_unique($vars))) {
                    throw new \RuntimeException("Path template expressions MUST NOT appear more than once in a single path: {$path}");
                }
            }
            \Cdd\Paths\validatePathItemObject($pathItem);
        }
    }
}
/**
 * Validates a Path Item Object.
 */
function validatePathItemObject(mixed $pathItem): void
{
    if (!is_array($pathItem)) {
        throw new \RuntimeException('Path Item must be an object');
    }
    if (isset($pathItem['$ref']) && !is_string($pathItem['$ref'])) {
        throw new \RuntimeException('Path Item "$ref" must be a string');
    }
    if (isset($pathItem['summary']) && !is_string($pathItem['summary'])) {
        throw new \RuntimeException('Path Item "summary" must be a string');
    }
    if (isset($pathItem['description']) && !is_string($pathItem['description'])) {
        throw new \RuntimeException('Path Item "description" must be a string');
    }
    $methods = ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace', 'query'];
    $pathItemParams = isset($pathItem['parameters']) && is_array($pathItem['parameters']) ? $pathItem['parameters'] : [];
    foreach ($methods as $method) {
        if (isset($pathItem[$method])) {
            \Cdd\Operations\validateOperationObject($pathItem[$method], $pathItemParams);
        }
    }
    if (isset($pathItem['additionalOperations'])) {
        if (!is_array($pathItem['additionalOperations'])) {
            throw new \RuntimeException('Path Item "additionalOperations" must be a map');
        }
        foreach ($pathItem['additionalOperations'] as $opKey => $opVal) {
            if (in_array(strtolower($opKey), $methods, true)) {
                throw new \RuntimeException('Path Item "additionalOperations" MUST NOT contain any entry for fixed methods (e.g., ' . $opKey . ')');
            }
            \Cdd\Operations\validateOperationObject($opVal, $pathItemParams);
        }
    }
    if (isset($pathItem['servers'])) {
        if (!is_array($pathItem['servers'])) {
            throw new \RuntimeException('Path Item "servers" must be an array');
        }
        foreach ($pathItem['servers'] as $server) {
            \Cdd\Servers\validateServerObject($server);
        }
    }
    if (isset($pathItem['parameters'])) {
        if (!is_array($pathItem['parameters'])) {
            throw new \RuntimeException('Path Item "parameters" must be an array');
        }
        $paramSet = [];
        foreach ($pathItem['parameters'] as $parameter) {
            \Cdd\Parameters\validateParameterOrReferenceObject($parameter);
            if (!isset($parameter['$ref']) && isset($parameter['name']) && isset($parameter['in'])) {
                $key = $parameter['name'] . ':' . $parameter['in'];
                if (isset($paramSet[$key])) {
                    throw new \RuntimeException('Path Item parameters list MUST NOT include duplicated parameters');
                }
                $paramSet[$key] = true;
            }
        }
    }
}
