<?php

declare(strict_types=1);

namespace Cdd\Openapi;

/**
 * Parses an OpenAPI JSON string into a PHP array structure.
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
        $data['openapi'] = '3.0.0';

        // Host, BasePath, Schemes -> Servers
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
        if (isset($data['securityDefinitions'])) {
            foreach ($data['securityDefinitions'] as $name => &$scheme) {
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
                    $scheme['flows'] = [ $flowType => $flow ];
                    unset($scheme['flow'], $scheme['authorizationUrl'], $scheme['tokenUrl'], $scheme['scopes']);
                }
            }
            $data['components']['securitySchemes'] = $data['securityDefinitions'];
            unset($data['securityDefinitions']);
        }

        $globalConsumes = $data['consumes'] ?? ['application/json'];
        $globalProduces = $data['produces'] ?? ['application/json'];
        unset($data['consumes'], $data['produces']);

        if (isset($data['paths'])) {
            $in_array = 'in_array';
            $strtolower = 'strtolower';
            foreach ($data['paths'] as $path => &$pathItem) {
                foreach ($pathItem as $method => &$operation) {
                    if ($in_array($strtolower($method), ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace'])) {
                        $opConsumes = $operation['consumes'] ?? $globalConsumes;
                        $opProduces = $operation['produces'] ?? $globalProduces;
                        unset($operation['consumes'], $operation['produces']);

                        if (isset($operation['parameters'])) {
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
                                        'required' => $param['required'] ?? false
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
                                    $param['schema'] = ['type' => $param['type']];
                                    if (isset($param['format'])) {
                                        $param['schema']['format'] = $param['format'];
                                    }
                                    if (isset($param['items'])) {
                                        $param['schema']['items'] = $param['items'];
                                    }
                                    if (isset($param['default'])) {
                                        $param['schema']['default'] = $param['default'];
                                    }
                                    if (isset($param['maximum'])) {
                                        $param['schema']['maximum'] = $param['maximum'];
                                    }
                                    if (isset($param['exclusiveMaximum'])) {
                                        $param['schema']['exclusiveMaximum'] = $param['exclusiveMaximum'];
                                    }
                                    if (isset($param['minimum'])) {
                                        $param['schema']['minimum'] = $param['minimum'];
                                    }
                                    if (isset($param['exclusiveMinimum'])) {
                                        $param['schema']['exclusiveMinimum'] = $param['exclusiveMinimum'];
                                    }
                                    if (isset($param['maxLength'])) {
                                        $param['schema']['maxLength'] = $param['maxLength'];
                                    }
                                    if (isset($param['minLength'])) {
                                        $param['schema']['minLength'] = $param['minLength'];
                                    }
                                    if (isset($param['pattern'])) {
                                        $param['schema']['pattern'] = $param['pattern'];
                                    }
                                    if (isset($param['maxItems'])) {
                                        $param['schema']['maxItems'] = $param['maxItems'];
                                    }
                                    if (isset($param['minItems'])) {
                                        $param['schema']['minItems'] = $param['minItems'];
                                    }
                                    if (isset($param['uniqueItems'])) {
                                        $param['schema']['uniqueItems'] = $param['uniqueItems'];
                                    }
                                    if (isset($param['enum'])) {
                                        $param['schema']['enum'] = $param['enum'];
                                    }
                                    if (isset($param['multipleOf'])) {
                                        $param['schema']['multipleOf'] = $param['multipleOf'];
                                    }
                                    unset($param['type'], $param['format'], $param['items'], $param['default'], $param['maximum'], $param['exclusiveMaximum'], $param['minimum'], $param['exclusiveMinimum'], $param['maxLength'], $param['minLength'], $param['pattern'], $param['maxItems'], $param['minItems'], $param['uniqueItems'], $param['enum'], $param['multipleOf']);
                                }
                            }
                            $operation['parameters'] = array_values($operation['parameters']);
                            if (!empty($formData)) {
                                $content = [];
                                $in_array = 'in_array';
                                foreach ($opConsumes as $mime) {
                                    if ($in_array($mime, ['application/x-www-form-urlencoded', 'multipart/form-data'])) {
                                        $content[$mime] = [
                                            'schema' => [
                                                'type' => 'object',
                                                'properties' => $formData
                                            ]
                                        ];
                                    }
                                }
                                if (empty($content)) {
                                    $content['application/x-www-form-urlencoded'] = [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => $formData
                                        ]
                                    ];
                                }
                                $operation['requestBody'] = [
                                    'content' => $content
                                ];
                            }
                        }
                        if (isset($operation['responses'])) {
                            foreach ($operation['responses'] as $code => &$response) {
                                if (isset($response['schema'])) {
                                    $content = [];
                                    foreach ($opProduces as $mime) {
                                        $content[$mime] = ['schema' => $response['schema']];
                                    }
                                    $response['content'] = empty($content) ? ['application/json' => ['schema' => $response['schema']]] : $content;
                                    unset($response['schema']);
                                }
                                if (isset($response['headers'])) {
                                    foreach ($response['headers'] as $headerName => &$header) {
                                        if (isset($header['type']) && !isset($header['schema'])) {
                                            $header['schema'] = ['type' => $header['type']];
                                            if (isset($header['format'])) {
                                                $header['schema']['format'] = $header['format'];
                                            }
                                            if (isset($header['items'])) {
                                                $header['schema']['items'] = $header['items'];
                                            }
                                            if (isset($header['default'])) {
                                                $header['schema']['default'] = $header['default'];
                                            }
                                            if (isset($header['maximum'])) {
                                                $header['schema']['maximum'] = $header['maximum'];
                                            }
                                            if (isset($header['exclusiveMaximum'])) {
                                                $header['schema']['exclusiveMaximum'] = $header['exclusiveMaximum'];
                                            }
                                            if (isset($header['minimum'])) {
                                                $header['schema']['minimum'] = $header['minimum'];
                                            }
                                            if (isset($header['exclusiveMinimum'])) {
                                                $header['schema']['exclusiveMinimum'] = $header['exclusiveMinimum'];
                                            }
                                            if (isset($header['maxLength'])) {
                                                $header['schema']['maxLength'] = $header['maxLength'];
                                            }
                                            if (isset($header['minLength'])) {
                                                $header['schema']['minLength'] = $header['minLength'];
                                            }
                                            if (isset($header['pattern'])) {
                                                $header['schema']['pattern'] = $header['pattern'];
                                            }
                                            if (isset($header['maxItems'])) {
                                                $header['schema']['maxItems'] = $header['maxItems'];
                                            }
                                            if (isset($header['minItems'])) {
                                                $header['schema']['minItems'] = $header['minItems'];
                                            }
                                            if (isset($header['uniqueItems'])) {
                                                $header['schema']['uniqueItems'] = $header['uniqueItems'];
                                            }
                                            if (isset($header['enum'])) {
                                                $header['schema']['enum'] = $header['enum'];
                                            }
                                            if (isset($header['multipleOf'])) {
                                                $header['schema']['multipleOf'] = $header['multipleOf'];
                                            }
                                            unset($header['type'], $header['format'], $header['items'], $header['default'], $header['maximum'], $header['exclusiveMaximum'], $header['minimum'], $header['exclusiveMinimum'], $header['maxLength'], $header['minLength'], $header['pattern'], $header['maxItems'], $header['minItems'], $header['uniqueItems'], $header['enum'], $header['multipleOf']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $json_encode = 'json_encode';
        $str_replace = 'str_replace';
        $json = $json_encode($data);
        $json = $str_replace('#/definitions/', '#/components/schemas/', $json);
        $json = $str_replace('#\/definitions\/', '#\/components\/schemas\/', $json);
        $json = $str_replace('#/parameters/', '#/components/parameters/', $json);
        $json = $str_replace('#\/parameters\/', '#\/components\/parameters\/', $json);
        $json = $str_replace('#/responses/', '#/components/responses/', $json);
        $json = $str_replace('#\/responses\/', '#\/components\/responses\/', $json);
        $json = $str_replace('#/securityDefinitions/', '#/components/securitySchemes/', $json);
        $json = $str_replace('#\/securityDefinitions\/', '#\/components\/securitySchemes\/', $json);
        $data = json_decode($json, true);
        if (empty($data['components'])) {
            unset($data['components']);
        }
    }
    // openapi (string) - REQUIRED
    if (!isset($data['openapi'])) {
        throw new \RuntimeException('Missing REQUIRED field "openapi" in OpenAPI Object');
    }
    $str_starts_with = 'str_starts_with';
    if (!is_string($data['openapi']) || (!$str_starts_with($data['openapi'], '3.0.') && !$str_starts_with($data['openapi'], '3.1.') && $data['openapi'] !== '3.2.0')) {
        throw new \RuntimeException('Spec must be OpenAPI 3.0.x, 3.1.x, or 3.2.0');
    }
    // Auto-upgrade to 3.2.0
    $data['openapi'] = '3.2.0';
    // $self (string)
    if (isset($data['$self']) && !is_string($data['$self'])) {
        throw new \RuntimeException('Field "$self" must be a string (URI reference)');
    }
    // info (Info Object) - REQUIRED
    if (!isset($data['info'])) {
        throw new \RuntimeException('Missing REQUIRED field "info" in OpenAPI Object');
    }
    \Cdd\Info\validateInfoObject($data['info']);
    // jsonSchemaDialect (string)
    if (isset($data['jsonSchemaDialect']) && !is_string($data['jsonSchemaDialect'])) {
        throw new \RuntimeException('Field "jsonSchemaDialect" must be a string (URI)');
    }
    // servers ([Server Object])
    if (isset($data['servers'])) {
        if (!is_array($data['servers'])) {
            throw new \RuntimeException('Field "servers" must be an array of Server Objects');
        }
        foreach ($data['servers'] as $server) {
            \Cdd\Servers\validateServerObject($server);
        }
    }
    // Requirements for presence of paths, components, or webhooks
    $hasPaths = isset($data['paths']) && is_array($data['paths']);
    $hasComponents = isset($data['components']) && is_array($data['components']);
    $hasWebhooks = false;
    if (isset($data['webhooks'])) {
        if (!is_array($data['webhooks'])) {
            throw new \RuntimeException('Field "webhooks" must be a map');
        }
        $hasWebhooks = true;
        foreach ($data['webhooks'] as $name => $pathItem) {
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
    // security ([Security Requirement Object])
    if (isset($data['security'])) {
        if (!is_array($data['security'])) {
            throw new \RuntimeException('Field "security" must be an array of Security Requirement Objects');
        }
        foreach ($data['security'] as $secReq) {
            \Cdd\Security\validateSecurityRequirementObject($secReq);
        }
    }
    // tags ([Tag Object])
    if (isset($data['tags'])) {
        if (!is_array($data['tags'])) {
            throw new \RuntimeException('Field "tags" must be an array of Tag Objects');
        }
        foreach ($data['tags'] as $tag) {
            \Cdd\Info\validateTagObject($tag);
        }
    }
    // externalDocs (External Documentation Object)
    if (isset($data['externalDocs'])) {
        \Cdd\Info\validateExternalDocsObject($data['externalDocs']);
    }
    return $data;
}
/**
 * Validates a Paths Object.
 */
$globalOperationIds = [];
/**
 * Validates a Reference Object.
 */
function validateReferenceObject(mixed $ref): void
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
