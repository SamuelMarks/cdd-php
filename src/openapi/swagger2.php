<?php

/**
 * Converts between Swagger 2.0 and OpenAPI 3.2.0 specification structures.
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
 * Downgrades an OpenAPI 3.x structure into a Swagger 2.0 specification array.
 *
 * @param array<string, mixed> $openapi The OpenAPI 3.x specification array.
 * @return array<string, mixed> The converted Swagger 2.0 specification array.
 */
function downgradeOpenApi3ToSwagger2(array $openapi): array
{
    $swagger = ['swagger' => '2.0'];
    if (isset($openapi['info'])) {
        $swagger['info'] = $openapi['info'];
    }
    if (isset($openapi['servers']) && count($openapi['servers']) > 0) {
        $url = parse_url((string) $openapi['servers'][0]['url']);
        if (isset($url['host'])) {
            $swagger['host'] = $url['host'] . (isset($url['port']) ? ':' . $url['port'] : '');
        }
        if (isset($url['path'])) {
            $swagger['basePath'] = $url['path'];
        }
        if (isset($url['scheme'])) {
            $swagger['schemes'] = [$url['scheme']];
        }
    }
    $swagger['consumes'] = ['application/json'];
    $swagger['produces'] = ['application/json'];
    if (isset($openapi['paths'])) {
        $swagger['paths'] = (array) $openapi['paths'];
        foreach ($swagger['paths'] as $path => &$pathItem) {
            foreach ($pathItem as $method => &$op) {
                if (in_array(strtolower((string) $method), ['get', 'put', 'post', 'delete', 'options', 'head', 'patch'], true)) {
                    if (isset($op['requestBody'])) {
                        $content = $op['requestBody']['content'] ?? [];
                        foreach ($content as $mime => $media) {
                            if ($mime === 'application/x-www-form-urlencoded' || $mime === 'multipart/form-data') {
                                $props = $media['schema']['properties'] ?? [];
                                if (!isset($op['parameters'])) {
                                    $op['parameters'] = [];
                                }
                                foreach ($props as $name => $propSchema) {
                                    $p = $propSchema;
                                    $p['name'] = $name;
                                    $p['in'] = 'formData';
                                    $op['parameters'][] = $p;
                                }
                            } else {
                                $p = [
                                    'in' => 'body',
                                    'name' => 'body',
                                    'required' => $op['requestBody']['required'] ?? false,
                                    'schema' => $media['schema'] ?? [],
                                ];
                                if (isset($op['requestBody']['description'])) {
                                    $p['description'] = $op['requestBody']['description'];
                                }
                                if (!isset($op['parameters'])) {
                                    $op['parameters'] = [];
                                }
                                $op['parameters'][] = $p;
                            }
                            break;
                        }
                        unset($op['requestBody']);
                    }
                    if (isset($op['responses'])) {
                        foreach ($op['responses'] as $code => &$resp) {
                            if (isset($resp['content'])) {
                                foreach ($resp['content'] as $mime => $media) {
                                    $resp['schema'] = $media['schema'] ?? [];
                                    break;
                                }
                                unset($resp['content']);
                            }
                        }
                    }
                }
            }
        }
    }
    if (isset($openapi['components']['schemas'])) {
        $swagger['definitions'] = $openapi['components']['schemas'];
    }
    if (isset($openapi['components']['parameters'])) {
        $swagger['parameters'] = $openapi['components']['parameters'];
    }
    if (isset($openapi['components']['responses'])) {
        $swagger['responses'] = $openapi['components']['responses'];
    }
    if (isset($openapi['components']['securitySchemes'])) {
        $swagger['securityDefinitions'] = $openapi['components']['securitySchemes'];
        foreach ($swagger['securityDefinitions'] as $name => &$scheme) {
            if ($scheme['type'] === 'http' && isset($scheme['scheme']) && $scheme['scheme'] === 'basic') {
                $scheme['type'] = 'basic';
                unset($scheme['scheme']);
            }
            if (isset($scheme['flows'])) {
                $scheme['type'] = 'oauth2';
                foreach ($scheme['flows'] as $flowType => $flow) {
                    $ft = $flowType;
                    if ($ft === 'clientCredentials') {
                        $ft = 'application';
                    }
                    if ($ft === 'authorizationCode') {
                        $ft = 'accessCode';
                    }
                    $scheme['flow'] = $ft;
                    if (isset($flow['authorizationUrl'])) {
                        $scheme['authorizationUrl'] = $flow['authorizationUrl'];
                    }
                    if (isset($flow['tokenUrl'])) {
                        $scheme['tokenUrl'] = $flow['tokenUrl'];
                    }
                    if (isset($flow['scopes'])) {
                        $scheme['scopes'] = $flow['scopes'];
                    }
                    break;
                }
                unset($scheme['flows']);
            }
        }
    }
    if (isset($openapi['security'])) {
        $swagger['security'] = $openapi['security'];
    }
    if (isset($openapi['tags'])) {
        $swagger['tags'] = $openapi['tags'];
    }
    if (isset($openapi['externalDocs'])) {
        $swagger['externalDocs'] = $openapi['externalDocs'];
    }

    return rewrite_ref_prefixes($swagger, [
        '#/components/schemas/' => '#/definitions/',
        '#/components/parameters/' => '#/parameters/',
        '#/components/responses/' => '#/responses/',
        '#/components/securitySchemes/' => '#/securityDefinitions/',
    ]);
}
