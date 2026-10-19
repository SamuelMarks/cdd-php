<?php

/**
 * Parses and validates OpenAPI Operation and Callback Objects.
 *
 * @package Cdd\Operations
 */

declare(strict_types=1);

namespace Cdd\Operations;

/**
 * Parses an Operation Object structure from PHP method source code or explicit arguments.
 *
 * @param string $operationIdOrCode Unique operation identifier or PHP method source code snippet.
 * @param array<int, mixed> $parameters List of parameter arrays.
 * @param array<string|int, mixed> $responses Map of responses.
 * @param array<string, mixed>|null $requestBody Optional request body map.
 * @param string $summary Optional summary.
 * @return array<string, mixed> The OpenAPI Operation Object.
 */
function parse(string $operationIdOrCode, array $parameters = [], array $responses = [], ?array $requestBody = null, string $summary = ''): array
{
    $trimmed = trim($operationIdOrCode);
    if (str_contains($trimmed, 'function ') || str_starts_with($trimmed, '<?php') || str_starts_with($trimmed, '/**')) {
        $phpCode = str_starts_with($trimmed, '<?php')
            ? $trimmed
            : "<?php\nclass TempController {\n{$trimmed}\n}\n";
        $parsedOps = \Cdd\Controllers\parse($phpCode);
        if (!empty($parsedOps)) {
            $firstOpId = (string) array_key_first($parsedOps);
            $op = $parsedOps[$firstOpId];
            $op['operationId'] = $firstOpId;
            if (!isset($op['responses'])) {
                $op['responses'] = ['200' => ['description' => 'Success']];
            }
            return $op;
        }
    }

    $operation = [
        'operationId' => $operationIdOrCode,
        'responses' => empty($responses) ? ['200' => ['description' => 'Success']] : $responses,
    ];
    if (!empty($parameters)) {
        $operation['parameters'] = $parameters;
    }
    if ($requestBody !== null) {
        $operation['requestBody'] = $requestBody;
    }
    if ($summary !== '') {
        $operation['summary'] = $summary;
    }
    return $operation;
}

/**
 * Validates an Operation Object.
 *
 * @param mixed $operation The Operation Object to validate.
 * @param array<int, mixed> $pathItemParams Parameters inherited from the parent Path Item.
 * @param array<string, bool>|null $seenOperationIds Optional map of seen operationIds for uniqueness validation.
 * @return void
 * @throws \RuntimeException If validation fails.
 */
function validateOperationObject(mixed $operation, array $pathItemParams = [], ?array &$seenOperationIds = null): void
{
    if (!is_array($operation)) {
        throw new \RuntimeException('Operation must be an object');
    }
    $hasQuery = false;
    $hasQueryString = false;
    $allParams = array_merge($pathItemParams, isset($operation['parameters']) && is_array($operation['parameters']) ? $operation['parameters'] : []);
    foreach ($allParams as $parameter) {
        if (!is_array($parameter) || isset($parameter['$ref'])) {
            continue;
        }
        if (isset($parameter['in'])) {
            if ($parameter['in'] === 'query') {
                $hasQuery = true;
            }
            if ($parameter['in'] === 'querystring') {
                if ($hasQueryString) {
                    throw new \RuntimeException('querystring parameter MUST NOT appear more than once');
                }
                $hasQueryString = true;
            }
        }
    }
    if ($hasQuery && $hasQueryString) {
        throw new \RuntimeException('query and querystring parameters are mutually exclusive');
    }
    if (isset($operation['tags'])) {
        if (!is_array($operation['tags'])) {
            throw new \RuntimeException('Operation "tags" must be an array of strings');
        }
        foreach ($operation['tags'] as $tag) {
            if (!is_string($tag)) {
                throw new \RuntimeException('Operation "tags" items must be strings');
            }
        }
    }
    if (isset($operation['summary']) && !is_string($operation['summary'])) {
        throw new \RuntimeException('Operation "summary" must be a string');
    }
    if (isset($operation['description']) && !is_string($operation['description'])) {
        throw new \RuntimeException('Operation "description" must be a string');
    }
    if (isset($operation['externalDocs'])) {
        \Cdd\Info\validateExternalDocsObject($operation['externalDocs']);
    }
    if (isset($operation['operationId'])) {
        if (!is_string($operation['operationId'])) {
            throw new \RuntimeException('Operation "operationId" must be a string');
        }
        if ($seenOperationIds !== null) {
            if (isset($seenOperationIds[$operation['operationId']])) {
                throw new \RuntimeException('Operation "operationId" MUST be unique among all operations described in the API');
            }
            $seenOperationIds[$operation['operationId']] = true;
        }
    }
    if (isset($operation['parameters'])) {
        if (!is_array($operation['parameters'])) {
            throw new \RuntimeException('Operation "parameters" must be an array');
        }
        $paramSet = [];
        foreach ($operation['parameters'] as $parameter) {
            \Cdd\Parameters\validateParameterOrReferenceObject($parameter);
            if (!isset($parameter['$ref']) && isset($parameter['name']) && isset($parameter['in'])) {
                $key = $parameter['name'] . ':' . $parameter['in'];
                if (isset($paramSet[$key])) {
                    throw new \RuntimeException('Operation parameters list MUST NOT include duplicated parameters');
                }
                $paramSet[$key] = true;
            }
        }
    }
    if (isset($operation['requestBody'])) {
        \Cdd\RequestBodies\validateRequestBodyOrReferenceObject($operation['requestBody']);
    }
    if (!isset($operation['responses'])) {
        throw new \RuntimeException('Operation must contain a "responses" object');
    }
    if (!is_array($operation['responses'])) {
        throw new \RuntimeException('Operation "responses" must be a Responses Object map');
    }
    if (empty($operation['responses'])) {
        throw new \RuntimeException('Responses Object MUST contain at least one response code');
    }
    foreach ($operation['responses'] as $key => $val) {
        $keyStr = (string) $key;
        if ($keyStr !== 'default' && !preg_match('/^[1-5](?:[0-9]{2}|XX)$/', $keyStr)) {
            if (!str_starts_with($keyStr, 'x-')) {
                throw new \RuntimeException('Responses keys must be HTTP status codes, ranges like 2XX, or "default"');
            }
        }
        if (!str_starts_with($keyStr, 'x-')) {
            \Cdd\Responses\validateResponseOrReferenceObject($val);
        }
    }
    if (isset($operation['callbacks'])) {
        if (!is_array($operation['callbacks'])) {
            throw new \RuntimeException('Operation "callbacks" must be a map');
        }
        foreach ($operation['callbacks'] as $callback) {
            \Cdd\Operations\validateCallbackOrReferenceObject($callback, $seenOperationIds);
        }
    }
    if (isset($operation['deprecated']) && !is_bool($operation['deprecated'])) {
        throw new \RuntimeException('Operation "deprecated" must be a boolean');
    }
    if (isset($operation['security'])) {
        if (!is_array($operation['security'])) {
            throw new \RuntimeException('Operation "security" must be an array');
        }
        foreach ($operation['security'] as $secReq) {
            \Cdd\Security\validateSecurityRequirementObject($secReq);
        }
    }
    if (isset($operation['servers'])) {
        if (!is_array($operation['servers'])) {
            throw new \RuntimeException('Operation "servers" must be an array');
        }
        foreach ($operation['servers'] as $server) {
            \Cdd\Servers\validateServerObject($server);
        }
    }
}

/**
 * Validates a Callback Object or Reference Object.
 *
 * @param mixed $callback The Callback or Reference Object to validate.
 * @param array<string, bool>|null $seenOperationIds Optional map of seen operationIds.
 * @return void
 * @throws \RuntimeException If validation fails.
 */
function validateCallbackOrReferenceObject(mixed $callback, ?array &$seenOperationIds = null): void
{
    if (!is_array($callback)) {
        throw new \RuntimeException('Callback must be an object');
    }
    if (isset($callback['$ref'])) {
        \Cdd\Openapi\validateReferenceObject($callback);
        return;
    }
    foreach ($callback as $expression => $pathItem) {
        $exprStr = (string) $expression;
        if (str_starts_with($exprStr, 'x-')) {
            continue;
        }
        \Cdd\Paths\validatePathItemObject($pathItem, $seenOperationIds);
    }
}
