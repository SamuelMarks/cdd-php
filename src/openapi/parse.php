<?php

/**
 * Parses and validates OpenAPI 3.0.x, 3.1.x, 3.2.0, and Swagger 2.0 JSON documents.
 *
 * @package Cdd\Openapi
 */

declare(strict_types=1);

namespace Cdd\Openapi;

/**
 * Extracts Swagger 2.0 inline parameter/header schema validation keywords into an OpenAPI 3.x `schema` sub-object.
 *
 * @param array<string, mixed> $item The Swagger 2.0 parameter or header array.
 * @return array<string, mixed> The updated item with a nested `schema` array.
 */
function upgrade_swagger2_inline_schema(array $item): array
{
    $schemaKeys = [
        'format',
        'items',
        'default',
        'maximum',
        'exclusiveMaximum',
        'minimum',
        'exclusiveMinimum',
        'maxLength',
        'minLength',
        'pattern',
        'maxItems',
        'minItems',
        'uniqueItems',
        'enum',
        'multipleOf',
    ];
    $item['schema'] = ['type' => $item['type']];
    unset($item['type']);
    foreach ($schemaKeys as $key) {
        if (isset($item[$key])) {
            $item['schema'][$key] = $item[$key];
            unset($item[$key]);
        }
    }
    return $item;
}

/**
 * Upgrades a parsed Swagger 2.0 array structure into an OpenAPI 3.x structure.
 *
 * @param array<string, mixed> $data The parsed Swagger 2.0 array.
 * @return array<string, mixed> The upgraded OpenAPI 3.x array.
 */
function upgrade_swagger2_to_openapi3(array $data): array
{
    $data['openapi'] = '3.0.0';

    if (isset($data['host']) || isset($data['basePath']) || isset($data['schemes'])) {
        $host = $data['host'] ?? 'localhost';
        $basePath = $data['basePath'] ?? '/';
        $schemes = $data['schemes'] ?? ['http'];
        $servers = [];
        foreach ($schemes as $scheme) {
            $servers[] = ['url' => $scheme . '://' . $host . $basePath];
        }
        $data['servers'] = $servers;
        unset($data['host'], $data['basePath'], $data['schemes']);
    }

    if (!isset($data['components'])) {
        $data['components'] = [];
    }
    if (isset($data['definitions'])) {
        $data['components']['schemas'] = $data['definitions'];
        unset($data['definitions']);
    }
    if (isset($data['parameters'])) {
        $data['components']['parameters'] = $data['parameters'];
        unset($data['parameters']);
    }
    if (isset($data['responses'])) {
        $data['components']['responses'] = $data['responses'];
        unset($data['responses']);
    }
    if (isset($data['securityDefinitions']) && is_array($data['securityDefinitions'])) {
        foreach ($data['securityDefinitions'] as &$scheme) {
            if ($scheme['type'] === 'basic') {
                $scheme['type'] = 'http';
                $scheme['scheme'] = 'basic';
            }
            if ($scheme['type'] === 'oauth2') {
                $flowType = $scheme['flow'] ?? 'implicit';
                if ($flowType === 'application') {
                    $flowType = 'clientCredentials';
                }
                if ($flowType === 'accessCode') {
                    $flowType = 'authorizationCode';
                }
                $flow = [];
                if (isset($scheme['authorizationUrl'])) {
                    $flow['authorizationUrl'] = $scheme['authorizationUrl'];
                }
                if (isset($scheme['tokenUrl'])) {
                    $flow['tokenUrl'] = $scheme['tokenUrl'];
                }
                if (isset($scheme['scopes'])) {
                    $flow['scopes'] = $scheme['scopes'];
                }
                $scheme['flows'] = [$flowType => $flow];
                unset($scheme['flow'], $scheme['authorizationUrl'], $scheme['tokenUrl'], $scheme['scopes']);
            }
        }
        $data['components']['securitySchemes'] = $data['securityDefinitions'];
        unset($data['securityDefinitions']);
    }

    $globalConsumes = $data['consumes'] ?? ['application/json'];
    $globalProduces = $data['produces'] ?? ['application/json'];
    unset($data['consumes'], $data['produces']);

    $validMethods = ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace'];

    if (isset($data['paths']) && is_array($data['paths'])) {
        foreach ($data['paths'] as &$pathItem) {
            foreach ($pathItem as $method => &$operation) {
                if (in_array(strtolower((string) $method), $validMethods, true)) {
                    $opConsumes = $operation['consumes'] ?? $globalConsumes;
                    $opProduces = $operation['produces'] ?? $globalProduces;
                    unset($operation['consumes'], $operation['produces']);

                    if (isset($operation['parameters']) && is_array($operation['parameters'])) {
                        $formData = [];
                        foreach ($operation['parameters'] as $i => &$param) {
                            if (isset($param['in']) && $param['in'] === 'body') {
                                $schema = $param['schema'] ?? [];
                                $content = [];
                                foreach ($opConsumes as $mime) {
                                    $content[$mime] = ['schema' => $schema];
                                }
                                $operation['requestBody'] = [
                                    'content' => empty($content) ? ['application/json' => ['schema' => $schema]] : $content,
                                    'required' => $param['required'] ?? false,
                                ];
                                if (isset($param['description'])) {
                                    $operation['requestBody']['description'] = $param['description'];
                                }
                                unset($operation['parameters'][$i]);
                            } elseif (isset($param['in']) && $param['in'] === 'formData') {
                                $schema = ['type' => $param['type'] ?? 'string'];
                                if (isset($param['format'])) {
                                    $schema['format'] = $param['format'];
                                }
                                $formData[$param['name']] = $schema;
                                unset($operation['parameters'][$i]);
                            } elseif (!isset($param['schema']) && isset($param['type'])) {
                                $param = upgrade_swagger2_inline_schema($param);
                            }
                        }
                        $operation['parameters'] = array_values($operation['parameters']);
                        if (!empty($formData)) {
                            $content = [];
                            foreach ($opConsumes as $mime) {
                                if (in_array($mime, ['application/x-www-form-urlencoded', 'multipart/form-data'], true)) {
                                    $content[$mime] = [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => $formData,
                                        ],
                                    ];
                                }
                            }
                            if (empty($content)) {
                                $content['application/x-www-form-urlencoded'] = [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => $formData,
                                    ],
                                ];
                            }
                            $operation['requestBody'] = [
                                'content' => $content,
                            ];
                        }
                    }
                    if (isset($operation['responses']) && is_array($operation['responses'])) {
                        foreach ($operation['responses'] as &$response) {
                            if (isset($response['schema'])) {
                                $content = [];
                                foreach ($opProduces as $mime) {
                                    $content[$mime] = ['schema' => $response['schema']];
                                }
                                $response['content'] = empty($content) ? ['application/json' => ['schema' => $response['schema']]] : $content;
                                unset($response['schema']);
                            }
                            if (isset($response['headers']) && is_array($response['headers'])) {
                                foreach ($response['headers'] as &$header) {
                                    if (isset($header['type']) && !isset($header['schema'])) {
                                        $header = upgrade_swagger2_inline_schema($header);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    $data = rewrite_ref_prefixes($data, [
        '#/definitions/' => '#/components/schemas/',
        '#/parameters/' => '#/components/parameters/',
        '#/responses/' => '#/components/responses/',
        '#/securityDefinitions/' => '#/components/securitySchemes/',
    ]);

    if (empty($data['components'])) {
        unset($data['components']);
    }

    return $data;
}

/**
 * Parses an OpenAPI JSON string into a PHP array structure.
 *
 * @param string $json The raw OpenAPI or Swagger JSON string.
 * @return array<string, mixed> The validated OpenAPI 3.2.0 array structure.
 * @throws \RuntimeException If JSON decoding or OpenAPI validation fails.
 */
function parse(string $json): array
{
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \RuntimeException('Invalid JSON provided: ' . json_last_error_msg());
    }
    if (!is_array($data)) {
        throw new \RuntimeException('OpenAPI document must be a JSON object');
    }
    if (isset($data['swagger']) && !isset($data['openapi'])) {
        $data = upgrade_swagger2_to_openapi3($data);
    }

    if (!isset($data['openapi'])) {
        throw new \RuntimeException('Missing REQUIRED field "openapi" in OpenAPI Object');
    }
    if (!is_string($data['openapi']) || (!str_starts_with($data['openapi'], '3.0.') && !str_starts_with($data['openapi'], '3.1.') && $data['openapi'] !== '3.2.0')) {
        throw new \RuntimeException('Spec must be OpenAPI 3.0.x, 3.1.x, or 3.2.0');
    }
    $data['openapi'] = '3.2.0';

    if (isset($data['$self']) && !is_string($data['$self'])) {
        throw new \RuntimeException('Field "$self" must be a string (URI reference)');
    }
    if (!isset($data['info'])) {
        throw new \RuntimeException('Missing REQUIRED field "info" in OpenAPI Object');
    }
    \Cdd\Info\validateInfoObject($data['info']);

    if (isset($data['jsonSchemaDialect']) && !is_string($data['jsonSchemaDialect'])) {
        throw new \RuntimeException('Field "jsonSchemaDialect" must be a string (URI)');
    }
    if (isset($data['servers'])) {
        if (!is_array($data['servers'])) {
            throw new \RuntimeException('Field "servers" must be an array of Server Objects');
        }
        foreach ($data['servers'] as $server) {
            \Cdd\Servers\validateServerObject($server);
        }
    }

    $hasPaths = isset($data['paths']) && is_array($data['paths']);
    $hasComponents = isset($data['components']) && is_array($data['components']);
    $hasWebhooks = false;
    if (isset($data['webhooks'])) {
        if (!is_array($data['webhooks'])) {
            throw new \RuntimeException('Field "webhooks" must be a map');
        }
        $hasWebhooks = true;
        foreach ($data['webhooks'] as $pathItem) {
            \Cdd\Paths\validatePathItemObject($pathItem);
        }
    }
    if (isset($data['paths'])) {
        \Cdd\Paths\validatePathsObject($data['paths']);
    }
    if (isset($data['components'])) {
        \Cdd\Components\validateComponentsObject($data['components']);
    }
    if (!$hasPaths && !$hasComponents && !$hasWebhooks) {
        throw new \RuntimeException('Spec must contain paths, components, or webhooks');
    }

    if (isset($data['security'])) {
        if (!is_array($data['security'])) {
            throw new \RuntimeException('Field "security" must be an array of Security Requirement Objects');
        }
        foreach ($data['security'] as $secReq) {
            \Cdd\Security\validateSecurityRequirementObject($secReq);
        }
    }
    if (isset($data['tags'])) {
        if (!is_array($data['tags'])) {
            throw new \RuntimeException('Field "tags" must be an array of Tag Objects');
        }
        foreach ($data['tags'] as $tag) {
            \Cdd\Info\validateTagObject($tag);
        }
    }
    if (isset($data['externalDocs'])) {
        \Cdd\Info\validateExternalDocsObject($data['externalDocs']);
    }
    return $data;
}

/**
 * Validates a Reference Object.
 *
 * @param array<string, mixed> $ref The Reference Object to validate.
 * @return void
 * @throws \RuntimeException If validation fails.
 */
function validateReferenceObject(array $ref): void
{
    if (!is_string($ref['$ref'])) {
        throw new \RuntimeException('Reference "$ref" must be a string');
    }
    if (isset($ref['summary']) && !is_string($ref['summary'])) {
        throw new \RuntimeException('Reference "summary" must be a string');
    }
    if (isset($ref['description']) && !is_string($ref['description'])) {
        throw new \RuntimeException('Reference "description" must be a string');
    }
}
