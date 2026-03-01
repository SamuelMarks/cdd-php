<?php

declare(strict_types=1);

declare (strict_types=1);
namespace Cdd\Responses;

/**
 * Parses response definitions to OpenAPI Responses Object.
 *
 * @param string $statusCode The HTTP status code
 * @param string $type The response schema type
 * @param string $description Optional description
 * @return array The OpenAPI Responses Object
 */
function parse(string $statusCode = '200', string $type = 'string', string $description = 'Successful response'): array
{
    $response = ['description' => $description, 'content' => ['application/json' => ['schema' => []]]];
    $typeMap = ['int' => 'integer', 'float' => 'number', 'bool' => 'boolean', 'string' => 'string', 'array' => 'array', 'object' => 'object'];
    if (isset($typeMap[$type])) {
        $response['content']['application/json']['schema']['type'] = $typeMap[$type];
    } else {
        $response['content']['application/json']['schema']['$ref'] = "#/components/schemas/{$type}";
    }
    return [$statusCode => $response];
}
/**
 * Validates a Response Object or Reference Object.
 */
function validateResponseOrReferenceObject(mixed $response): void
{
    if (!is_array($response)) {
        throw new \RuntimeException('Response must be an object');
    }
    if (isset($response['$ref'])) {
        \Cdd\Openapi\validateReferenceObject($response);
        return;
    }
    if (!isset($response['description']) || !is_string($response['description'])) {
        throw new \RuntimeException('Response must contain a "description" string');
    }
    if (isset($response['content'])) {
        if (!is_array($response['content'])) {
            throw new \RuntimeException('Response "content" must be a map');
        }
        foreach ($response['content'] as $mt => $mtObj) {
            \Cdd\Encoding\validateMediaTypeOrReferenceObject($mtObj);
        }
    }
    if (isset($response['headers'])) {
        if (!is_array($response['headers'])) {
            throw new \RuntimeException('Response "headers" must be a map');
        }
        foreach ($response['headers'] as $hName => $headerObj) {
            if (strtolower($hName) === 'content-type') {
                continue;
                // "If a response header is defined with the name "Content-Type", it SHALL be ignored."
            }
            \Cdd\Responses\validateHeaderOrReferenceObject($headerObj);
        }
    }
    if (isset($response['links'])) {
        if (!is_array($response['links'])) {
            throw new \RuntimeException('Response "links" must be a map');
        }
        foreach ($response['links'] as $lName => $linkObj) {
            \Cdd\Responses\validateLinkOrReferenceObject($linkObj);
        }
    }
}
/**
 * Validates a Header Object or Reference Object.
 */
function validateHeaderOrReferenceObject(mixed $header): void
{
    if (!is_array($header)) {
        throw new \RuntimeException('Header must be an object');
    }
    if (isset($header['$ref'])) {
        \Cdd\Openapi\validateReferenceObject($header);
        return;
    }
    if (isset($header['name'])) {
        throw new \RuntimeException('Header "name" MUST NOT be specified');
    }
    if (isset($header['in'])) {
        throw new \RuntimeException('Header "in" MUST NOT be specified');
    }
    if (isset($header['allowEmptyValue'])) {
        throw new \RuntimeException('Header "allowEmptyValue" MUST NOT be used');
    }
    if (isset($header['description']) && !is_string($header['description'])) {
        throw new \RuntimeException('Header "description" must be a string');
    }
    if (isset($header['required']) && !is_bool($header['required'])) {
        throw new \RuntimeException('Header "required" must be a boolean');
    }
    if (isset($header['deprecated']) && !is_bool($header['deprecated'])) {
        throw new \RuntimeException('Header "deprecated" must be a boolean');
    }
    if (isset($header['example']) && isset($header['examples'])) {
        throw new \RuntimeException('Header cannot contain both "example" and "examples"');
    }
    $hasSchema = isset($header['schema']);
    $hasContent = isset($header['content']);
    if ($hasSchema && $hasContent) {
        throw new \RuntimeException('Header cannot contain both "schema" and "content"');
    }
    if (!$hasSchema && !$hasContent) {
        throw new \RuntimeException('Header must contain either "schema" or "content"');
    }
    if ($hasSchema) {
        if (isset($header['style'])) {
            if (!is_string($header['style'])) {
                throw new \RuntimeException('Header "style" must be a string');
            }
            if ($header['style'] !== 'simple') {
                throw new \RuntimeException('Header "style", if used, MUST be limited to "simple"');
            }
        }
        if (isset($header['explode']) && !is_bool($header['explode'])) {
            throw new \RuntimeException('Header "explode" must be a boolean');
        }
        if (!is_array($header['schema'])) {
            throw new \RuntimeException('Header "schema" must be an object');
        }
    }
    if ($hasContent) {
        if (!is_array($header['content'])) {
            throw new \RuntimeException('Header "content" must be a map');
        }
        if (count($header['content']) !== 1) {
            throw new \RuntimeException('Header "content" map MUST only contain one entry');
        }
        foreach ($header['content'] as $mediaType => $mediaTypeObj) {
            \Cdd\Encoding\validateMediaTypeOrReferenceObject($mediaTypeObj);
        }
    }
}
/**
 * Validates a Link Object or Reference Object.
 */
function validateLinkOrReferenceObject(mixed $link): void
{
    if (!is_array($link)) {
        throw new \RuntimeException('Link must be an object');
    }
    if (isset($link['$ref'])) {
        \Cdd\Openapi\validateReferenceObject($link);
        return;
    }
    if (isset($link['operationRef']) && !is_string($link['operationRef'])) {
        throw new \RuntimeException('Link "operationRef" must be a string');
    }
    if (isset($link['operationId']) && !is_string($link['operationId'])) {
        throw new \RuntimeException('Link "operationId" must be a string');
    }
    if (isset($link['operationRef']) && isset($link['operationId'])) {
        throw new \RuntimeException('Link cannot contain both "operationRef" and "operationId"');
    }
    if (isset($link['parameters']) && !is_array($link['parameters'])) {
        throw new \RuntimeException('Link "parameters" must be a map');
    }
    if (isset($link['description']) && !is_string($link['description'])) {
        throw new \RuntimeException('Link "description" must be a string');
    }
    if (array_key_exists('requestBody', $link)) {
        // requestBody can be any type
    }
    if (isset($link['server'])) {
        \Cdd\Servers\validateServerObject($link['server']);
    }
}
