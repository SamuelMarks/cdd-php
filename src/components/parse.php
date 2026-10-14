<?php

/**
 * Parses and validates OpenAPI Components Objects and extracted component classes.
 *
 * @package Cdd\Components
 */

declare(strict_types=1);

namespace Cdd\Components;

/**
 * Compiles a Components Object from raw PHP definitions.
 *
 * @param array<string, mixed> $schemas The schemas map.
 * @param array<string, mixed> $parameters The parameters map.
 * @param array<string, mixed> $responses The responses map.
 * @return array<string, mixed> The compiled components object.
 */
function parse(array $schemas = [], array $parameters = [], array $responses = []): array
{
    $components = [];
    if (!empty($schemas)) {
        $components['schemas'] = $schemas;
    }
    if (!empty($parameters)) {
        $components['parameters'] = $parameters;
    }
    if (!empty($responses)) {
        $components['responses'] = $responses;
    }
    return $components;
}

/**
 * Parses extracted class nodes from `\Cdd\Classes\parse` into an OpenAPI Components map.
 *
 * @param array<int, array<string, mixed>> $classes Extracted class descriptors.
 * @param array<string, array<string, mixed>> $existingComponents Optional initial components array to merge into.
 * @return array<string, array<string, mixed>> The populated OpenAPI Components map.
 */
function parse_classes(array $classes, array $existingComponents = []): array
{
    $components = $existingComponents;

    foreach ($classes as $c) {
        $node = $c['node'];
        $name = (string) $c['name'];
        $schema = \Cdd\Schemas\parse($node);
        $type = (string) ($c['componentType'] ?? 'schemas');

        if (!isset($components[$type])) {
            $components[$type] = [];
        }

        $docComment = $node->getDocComment();
        $parsedDoc = $docComment !== null
            ? \Cdd\Docstrings\parse($docComment->getText())
            : ['description' => '', 'tags' => []];

        $firstDescLine = $parsedDoc['description'] !== ''
            ? explode("
", $parsedDoc['description'])[0]
            : '';

        if ($type === 'mediaTypes') {
            $mediaType = ['schema' => $schema];
            if (isset($parsedDoc['tags']['itemSchema'])) {
                $mediaType['itemSchema'] = ['$ref' => '#/components/schemas/' . trim((string) $parsedDoc['tags']['itemSchema'][0])];
            }
            if (isset($parsedDoc['tags']['itemEncoding'])) {
                $rawItemEnc = trim((string) $parsedDoc['tags']['itemEncoding'][0]);
                $decodedEnc = json_decode($rawItemEnc, true);
                $mediaType['itemEncoding'] = is_array($decodedEnc)
                    ? \Cdd\Encoding\parse($decodedEnc)
                    : \Cdd\Encoding\parse(['contentType' => $rawItemEnc]);
            }
            $components[$type][$name] = $mediaType;
        } elseif ($type === 'parameters') {
            $paramName = isset($parsedDoc['tags']['name']) ? trim((string) $parsedDoc['tags']['name'][0]) : $name;
            $in = isset($parsedDoc['tags']['in']) ? trim((string) $parsedDoc['tags']['in'][0]) : 'query';
            $required = isset($parsedDoc['tags']['required']);
            $components[$type][$name] = [
                'name' => $paramName,
                'in' => $in,
                'required' => $required,
                'schema' => $schema,
            ];
        } elseif ($type === 'responses') {
            $desc = $firstDescLine !== '' ? $firstDescLine : $name;
            $components[$type][$name] = [
                'description' => $desc,
                'content' => ['application/json' => ['schema' => $schema]],
            ];
        } elseif ($type === 'requestBodies') {
            $components[$type][$name] = [
                'description' => $firstDescLine,
                'content' => ['application/json' => ['schema' => $schema]],
            ];
        } elseif ($type === 'headers') {
            $components[$type][$name] = [
                'description' => $firstDescLine,
                'schema' => $schema,
            ];
        } elseif ($type === 'securitySchemes') {
            $schemeType = isset($parsedDoc['tags']['type']) ? trim((string) $parsedDoc['tags']['type'][0]) : 'http';
            $scheme = isset($parsedDoc['tags']['scheme']) ? trim((string) $parsedDoc['tags']['scheme'][0]) : 'bearer';
            $secScheme = ['type' => $schemeType];

            if ($schemeType === 'http') {
                $secScheme['scheme'] = $scheme;
            } elseif ($schemeType === 'apiKey') {
                $secScheme['in'] = isset($parsedDoc['tags']['in']) ? trim((string) $parsedDoc['tags']['in'][0]) : 'header';
                $secScheme['name'] = isset($parsedDoc['tags']['name']) ? trim((string) $parsedDoc['tags']['name'][0]) : 'X-API-Key';
            } elseif ($schemeType === 'oauth2') {
                $secScheme['flows'] = [];
                if (isset($parsedDoc['tags']['flow'])) {
                    foreach ($parsedDoc['tags']['flow'] as $flowStr) {
                        $parts = explode(' ', (string) $flowStr, 2);
                        if (isset($parts[1])) {
                            $secScheme['flows'][$parts[0]] = json_decode($parts[1], true);
                        }
                    }
                }
            } elseif ($schemeType === 'openIdConnect') {
                if (isset($parsedDoc['tags']['openIdConnectUrl'])) {
                    $secScheme['openIdConnectUrl'] = trim((string) $parsedDoc['tags']['openIdConnectUrl'][0]);
                }
            }

            if (isset($parsedDoc['tags']['bearerFormat'])) {
                $secScheme['bearerFormat'] = trim((string) $parsedDoc['tags']['bearerFormat'][0]);
            }
            $components[$type][$name] = $secScheme;
        } elseif ($type === 'pathItems') {
            $components[$type][$name] = [
                'description' => $firstDescLine,
            ];
        } elseif ($type === 'callbacks') {
            $components[$type][$name] = [
                '{$request.query.callbackUrl}' => [
                    'post' => [
                        'requestBody' => [
                            'content' => ['application/json' => ['schema' => $schema]],
                        ],
                        'responses' => [
                            '200' => ['description' => 'ok'],
                        ],
                    ],
                ],
            ];
        } elseif ($type === 'links') {
            $opId = isset($parsedDoc['tags']['operationId']) ? trim((string) $parsedDoc['tags']['operationId'][0]) : 'linkOperation';
            $components[$type][$name] = [
                'operationId' => $opId,
                'description' => $firstDescLine,
            ];
        } else {
            $components[$type][$name] = $schema;
        }
    }

    return $components;
}

/**
 * Infers OpenAPI Schema properties from parsed mock Example Objects.
 *
 * @param array<string, array<string, mixed>> $mocks Parsed mock Example Objects.
 * @param array<string, array<string, mixed>> $existingSchemas Existing component schemas.
 * @return array<string, array<string, mixed>> Updated component schemas map.
 */
function infer_schemas_from_mocks(array $mocks, array $existingSchemas = []): array
{
    $schemas = $existingSchemas;
    $phpTypeToSchemaType = [
        'integer' => ['type' => 'integer'],
        'double' => ['type' => 'number'],
        'boolean' => ['type' => 'boolean'],
    ];

    foreach ($mocks as $name => $example) {
        if (isset($example['dataValue']) && is_array($example['dataValue'])) {
            $schemaName = ucfirst((string) $name) . 'Model';
            if (!isset($schemas[$schemaName])) {
                $properties = [];
                foreach ($example['dataValue'] as $key => $val) {
                    $type = gettype($val);
                    if (isset($phpTypeToSchemaType[$type])) {
                        $properties[$key] = $phpTypeToSchemaType[$type];
                    } elseif ($type === 'array') {
                        $properties[$key] = ['type' => 'array', 'items' => ['type' => 'string']];
                    } else {
                        $properties[$key] = ['type' => 'string'];
                    }
                }
                $schemas[$schemaName] = [
                    'type' => 'object',
                    'properties' => $properties,
                ];
            }
        }
    }

    return $schemas;
}

/**
 * Validates a Components Object.
 *
 * @param mixed $components The Components Object to validate.
 * @return void
 * @throws \RuntimeException If validation fails.
 */
function validateComponentsObject(mixed $components): void
{
    if (!is_array($components)) {
        throw new \RuntimeException('Field "components" must be an object');
    }
    $validKeys = [
        'schemas' => '\Cdd\Schemas\validateSchemaOrReferenceObject',
        'responses' => '\Cdd\Responses\validateResponseOrReferenceObject',
        'parameters' => '\Cdd\Parameters\validateParameterOrReferenceObject',
        'examples' => '\Cdd\Mocks\validateExampleOrReferenceObject',
        'requestBodies' => '\Cdd\RequestBodies\validateRequestBodyOrReferenceObject',
        'headers' => '\Cdd\Responses\validateHeaderOrReferenceObject',
        'securitySchemes' => '\Cdd\Security\validateSecuritySchemeOrReferenceObject',
        'links' => '\Cdd\Responses\validateLinkOrReferenceObject',
        'callbacks' => '\Cdd\Operations\validateCallbackOrReferenceObject',
        'pathItems' => '\Cdd\Paths\validatePathItemObject',
        'mediaTypes' => '\Cdd\Encoding\validateMediaTypeOrReferenceObject',
    ];
    foreach ($validKeys as $key => $validator) {
        if (isset($components[$key])) {
            if (!is_array($components[$key])) {
                throw new \RuntimeException("Components '{$key}' must be a map");
            }
            foreach ($components[$key] as $name => $item) {
                if (!preg_match('/^[a-zA-Z0-9\.\-_]+$/', (string) $name)) {
                    throw new \RuntimeException("Components '{$key}' map keys must match ^[a-zA-Z0-9\.\-_]+\$");
                }
                if (is_callable($validator)) {
                    $validator($item);
                }
            }
        }
    }
}
