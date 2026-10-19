<?php

/**
 * Parses and validates OpenAPI Response, Header, and Link Objects.
 *
 * @package Cdd\Responses
 */

declare(strict_types=1);

namespace Cdd\Responses;

/**
 * Parses response definitions (or an emitted `@return` docblock string) to an OpenAPI Responses Object.
 *
 * @param string $statusCodeOrDocblock HTTP status code (`200`, `201`, etc.) or emitted `@return` docblock string.
 * @param string|null $type Return type (`string`, `int`, `float`, `bool`, `array`, `object`, `mixed`, or class name).
 * @param string $description Response description.
 * @return array<string, mixed> The OpenAPI Responses Object.
 */
function parse(string $statusCodeOrDocblock = '200', ?string $type = 'string', string $description = 'Success'): array
{
    if (str_contains($statusCodeOrDocblock, '@return')) {
        $responses = [];
        if (preg_match_all('/@return\s+([^\s*]+)(?:\s+(\d{3}|[1-5]XX|default))?(?:\s+([^\n]+))?/', $statusCodeOrDocblock, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $idx => $m) {
                $retType = trim($m[1]);
                $code = isset($m[2]) && $m[2] !== '' ? trim($m[2]) : ($idx === 0 ? '200' : (string) (200 + $idx));
                $desc = isset($m[3]) && trim($m[3]) !== '' ? trim($m[3]) : $description;
                $single = parse($code, $retType === 'mixed' ? null : $retType, $desc);
                $responses[$code] = $single[$code];
            }
        }
        return $responses;
    }

    $statusCode = $statusCodeOrDocblock;
    $responses = [
        $statusCode => [
            'description' => $description,
        ],
    ];

    if ($type !== null && $type !== 'void') {
        $responses[$statusCode]['content'] = [
            'application/json' => [
                'schema' => [],
            ],
        ];
        $mappedScalar = \Cdd\Schemas\php_type_to_openapi($type);
        if ($mappedScalar !== null) {
            $responses[$statusCode]['content']['application/json']['schema']['type'] = $mappedScalar;
        } else {
            $responses[$statusCode]['content']['application/json']['schema']['$ref'] = "#/components/schemas/{$type}";
        }
    }

    return $responses;
}

/**
 * Validates a Response Object or Reference Object.
 *
 * @param mixed $response The Response or Reference Object to validate.
 * @return void
 * @throws \RuntimeException If validation fails.
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
    if (isset($response['headers'])) {
        if (!is_array($response['headers'])) {
            throw new \RuntimeException('Response "headers" must be a map');
        }
        foreach ($response['headers'] as $headerName => $headerObj) {
            if (strtolower((string) $headerName) === 'content-type') {
                continue;
            }
            validateHeaderOrReferenceObject($headerObj);
        }
    }
    if (isset($response['content'])) {
        if (!is_array($response['content'])) {
            throw new \RuntimeException('Response "content" must be a map');
        }
        foreach ($response['content'] as $mediaTypeObj) {
            \Cdd\Encoding\validateMediaTypeOrReferenceObject($mediaTypeObj);
        }
    }
    if (isset($response['links'])) {
        if (!is_array($response['links'])) {
            throw new \RuntimeException('Response "links" must be a map');
        }
        foreach ($response['links'] as $linkObj) {
            validateLinkOrReferenceObject($linkObj);
        }
    }
}

/**
 * Validates a Header Object or Reference Object.
 *
 * @param mixed $header The Header or Reference Object to validate.
 * @return void
 * @throws \RuntimeException If validation fails.
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
    if (isset($header['schema']) && isset($header['content'])) {
        throw new \RuntimeException('Header cannot contain both "schema" and "content"');
    }
    if (!isset($header['schema']) && !isset($header['content'])) {
        throw new \RuntimeException('Header must contain either "schema" or "content"');
    }
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
    if (isset($header['schema'])) {
        if (!is_array($header['schema']) && !is_bool($header['schema'])) {
            throw new \RuntimeException('Header "schema" must be an object');
        }
        \Cdd\Schemas\validateSchemaOrReferenceObject($header['schema']);
    }
    if (isset($header['content'])) {
        if (!is_array($header['content'])) {
            throw new \RuntimeException('Header "content" must be a map');
        }
        if (count($header['content']) !== 1) {
            throw new \RuntimeException('Header "content" map MUST only contain one entry');
        }
        foreach ($header['content'] as $mediaTypeObj) {
            \Cdd\Encoding\validateMediaTypeOrReferenceObject($mediaTypeObj);
        }
    }
}

/**
 * Validates a Link Object or Reference Object.
 *
 * @param mixed $link The Link or Reference Object to validate.
 * @return void
 * @throws \RuntimeException If validation fails.
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
    if (isset($link['operationRef']) && isset($link['operationId'])) {
        throw new \RuntimeException('Link cannot contain both "operationRef" and "operationId"');
    }
    if (isset($link['operationRef']) && !is_string($link['operationRef'])) {
        throw new \RuntimeException('Link "operationRef" must be a string');
    }
    if (isset($link['operationId']) && !is_string($link['operationId'])) {
        throw new \RuntimeException('Link "operationId" must be a string');
    }
    if (isset($link['parameters']) && !is_array($link['parameters'])) {
        throw new \RuntimeException('Link "parameters" must be a map');
    }
    if (isset($link['description']) && !is_string($link['description'])) {
        throw new \RuntimeException('Link "description" must be a string');
    }
    if (isset($link['server'])) {
        \Cdd\Servers\validateServerObject($link['server']);
    }
}
