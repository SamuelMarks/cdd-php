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
        if (isset($data['definitions'])) {
            $data['components'] = ['schemas' => $data['definitions']];
            unset($data['definitions']);
        }
        if (isset($data['paths'])) {
            foreach ($data['paths'] as $path => &$pathItem) {
                foreach ($pathItem as $method => &$operation) {
                    if (in_array(strtolower($method), ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace'])) {
                        if (isset($operation['parameters'])) {
                            $formData = [];
                            foreach ($operation['parameters'] as $i => &$param) {
                                if (isset($param['in']) && $param['in'] === 'body') {
                                    $operation['requestBody'] = [
                                        'content' => [
                                            'application/json' => [
                                                'schema' => $param['schema'] ?? []
                                            ]
                                        ],
                                        'required' => $param['required'] ?? false
                                    ];
                                    unset($operation['parameters'][$i]);
                                } else if (isset($param['in']) && $param['in'] === 'formData') {
                                    $schema = ['type' => $param['type'] ?? 'string'];
                                    if (isset($param['format'])) $schema['format'] = $param['format'];
                                    $formData[$param['name']] = $schema;
                                    unset($operation['parameters'][$i]);
                                } else if (!isset($param['schema']) && isset($param['type'])) {
                                    $param['schema'] = ['type' => $param['type']];
                                    if (isset($param['format'])) $param['schema']['format'] = $param['format'];
                                    if (isset($param['items'])) $param['schema']['items'] = $param['items'];
                                    unset($param['type'], $param['format'], $param['items']);
                                }
                            }
                            $operation['parameters'] = array_values($operation['parameters']);
                            if (!empty($formData)) {
                                $operation['requestBody'] = [
                                    'content' => [
                                        'application/x-www-form-urlencoded' => [
                                            'schema' => [
                                                'type' => 'object',
                                                'properties' => $formData
                                            ]
                                        ]
                                    ]
                                ];
                            }
                        }
                        if (isset($operation['responses'])) {
                            foreach ($operation['responses'] as $code => &$response) {
                                if (isset($response['schema'])) {
                                    $response['content'] = [
                                        'application/json' => [
                                            'schema' => $response['schema']
                                        ]
                                    ];
                                    unset($response['schema']);
                                }
                                if (isset($response['headers'])) {
                                    foreach ($response['headers'] as $headerName => &$header) {
                                        if (isset($header['type']) && !isset($header['schema'])) {
                                            $header['schema'] = ['type' => $header['type']];
                                            if (isset($header['format'])) $header['schema']['format'] = $header['format'];
                                            unset($header['type'], $header['format']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        // Also update $ref to point to components/schemas instead of definitions
        $json = json_encode($data);
        $json = str_replace('#/definitions/', '#/components/schemas/', $json);
        $json = str_replace('#\/definitions\/', '#\/components\/schemas\/', $json);
        $data = json_decode($json, true);
    }

    // openapi (string) - REQUIRED
    if (!isset($data['openapi'])) {
        throw new \RuntimeException('Missing REQUIRED field "openapi" in OpenAPI Object');
    }
    if (!is_string($data['openapi']) || (!str_starts_with($data['openapi'], '3.0.') && !str_starts_with($data['openapi'], '3.1.') && $data['openapi'] !== '3.2.0')) {
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
